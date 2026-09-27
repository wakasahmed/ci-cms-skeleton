<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpClientCache;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;

/**
 * Google Places API (New) client for the booking form's pickup-location
 * autocomplete. Suggestions and selected places are limited to Madinah
 * (GOOGLE_PLACES_MADINAH_BOUNDS) and returned in the requested language.
 */
class Google_places_service
{
    private $autoload_available = false;
    private $http_client = null;

    public function __construct()
    {
        $autoload_file = APPPATH.'third_party/google_api/autoload.php';

        if (is_readable($autoload_file)) {
            require_once $autoload_file;
            $this->autoload_available = true;
        } else {
            log_message('error', 'Google API autoloader was not found at '.$autoload_file);
        }
    }

    /**
     * Place suggestions for what the visitor has typed so far.
     *
     * @param string $input         Typed text.
     * @param string $language      'en' or 'ar'; names and addresses come back in this language.
     * @param string $session_token Optional client-generated token that groups a typing
     *                              session with its final place lookup for billing.
     */
    public function autocomplete($input, $language, $session_token = '')
    {
        $input = trim((string) $input);
        $length = function_exists('mb_strlen') ? mb_strlen($input, 'UTF-8') : strlen($input);

        if ($length < GOOGLE_PLACES_MIN_INPUT_LENGTH || $length > GOOGLE_PLACES_MAX_INPUT_LENGTH) {
            return $this->result(false, 422, 'The search text has an unsupported length.');
        }
        if (!$this->valid_session_token($session_token)) {
            return $this->result(false, 422, 'The session token is not valid.');
        }

        $bounds = GOOGLE_PLACES_MADINAH_BOUNDS;
        $body = array(
            'input' => $input,
            'languageCode' => $this->language_code($language),
            'regionCode' => GOOGLE_PLACES_REGION_CODE,
            'includedRegionCodes' => array(GOOGLE_PLACES_REGION_CODE),
            'locationRestriction' => array(
                'rectangle' => array(
                    'low' => array(
                        'latitude' => $bounds['south'],
                        'longitude' => $bounds['west']
                    ),
                    'high' => array(
                        'latitude' => $bounds['north'],
                        'longitude' => $bounds['east']
                    )
                )
            )
        );
        if ($session_token !== '') {
            $body['sessionToken'] = $session_token;
        }

        $response = $this->request(
            'POST',
            GOOGLE_PLACES_AUTOCOMPLETE_URL,
            array('json' => $body)
        );
        if (empty($response['success'])) {
            return $response;
        }

        $suggestions = array();
        $items = isset($response['body']['suggestions']) && is_array($response['body']['suggestions'])
            ? $response['body']['suggestions']
            : array();

        foreach ($items as $item) {
            if (empty($item['placePrediction']['placeId'])) {
                continue;
            }

            $prediction = $item['placePrediction'];
            $text = isset($prediction['text']['text']) ? (string) $prediction['text']['text'] : '';
            if ($text === '') {
                continue;
            }

            $suggestions[] = array(
                'place_id' => (string) $prediction['placeId'],
                'text' => $text,
                'main_text' => isset($prediction['structuredFormat']['mainText']['text'])
                    ? (string) $prediction['structuredFormat']['mainText']['text']
                    : $text,
                'secondary_text' => isset($prediction['structuredFormat']['secondaryText']['text'])
                    ? (string) $prediction['structuredFormat']['secondaryText']['text']
                    : ''
            );
        }

        return $this->result(true, 200, 'Suggestions loaded.', array(
            'suggestions' => $suggestions
        ));
    }

    /**
     * The verified place_id, complete address and coordinates for a chosen suggestion.
     * A place outside Madinah is rejected even if the place ID was supplied directly.
     */
    public function place_details($place_id, $language, $session_token = '')
    {
        if (!$this->valid_place_id($place_id)) {
            return $this->result(false, 422, 'The place ID is not valid.');
        }
        if (!$this->valid_session_token($session_token)) {
            return $this->result(false, 422, 'The session token is not valid.');
        }

        $query = array('languageCode' => $this->language_code($language));
        if ($session_token !== '') {
            $query['sessionToken'] = $session_token;
        }

        $response = $this->request(
            'GET',
            GOOGLE_PLACES_DETAILS_URL.$place_id,
            array(
                'query' => $query,
                'headers' => array('X-Goog-FieldMask' => 'id,formattedAddress,location')
            )
        );
        if (empty($response['success'])) {
            return $response;
        }

        $place = $response['body'];
        if (empty($place['id'])
            || empty($place['formattedAddress'])
            || !isset($place['location']['latitude'], $place['location']['longitude'])
        ) {
            return $this->result(false, 502, 'Google Places returned an incomplete place.');
        }

        $latitude = (float) $place['location']['latitude'];
        $longitude = (float) $place['location']['longitude'];
        if (!$this->within_madinah($latitude, $longitude)) {
            return $this->result(false, 422, 'The place is outside the Madinah service area.');
        }

        return $this->result(true, 200, 'Place loaded.', array(
            'place_id' => (string) $place['id'],
            'address' => (string) $place['formattedAddress'],
            'latitude' => $latitude,
            'longitude' => $longitude
        ));
    }

    public function valid_place_id($place_id)
    {
        return is_string($place_id) && preg_match('/^[A-Za-z0-9_-]{10,255}$/D', $place_id) === 1;
    }

    public function within_madinah($latitude, $longitude)
    {
        $bounds = GOOGLE_PLACES_MADINAH_BOUNDS;

        return is_numeric($latitude)
            && is_numeric($longitude)
            && $latitude >= $bounds['south']
            && $latitude <= $bounds['north']
            && $longitude >= $bounds['west']
            && $longitude <= $bounds['east'];
    }

    private function request($method, $url, array $options)
    {
        if (!$this->autoload_available) {
            return $this->result(false, 503, 'The Google API library is not installed.');
        }
        if (!is_readable(RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE)) {
            log_message('error', 'The Google Places credentials file is not readable.');
            return $this->result(false, 503, 'The Google Places credentials are not configured.');
        }
        if (!is_readable(GOOGLE_API_CA_BUNDLE)) {
            return $this->result(false, 503, 'The Google API CA certificate bundle is not installed.');
        }

        try {
            $access_token = $this->access_token();
            if ($access_token === '') {
                return $this->result(false, 503, 'Unable to authenticate with Google Places.');
            }

            $options['headers'] = array_merge(
                isset($options['headers']) ? $options['headers'] : array(),
                array('Authorization' => 'Bearer '.$access_token)
            );

            $response = $this->http_client()->request($method, $url, $options);
            $status = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($status < 200 || $status >= 300) {
                $google_status = isset($body['error']['status']) ? $body['error']['status'] : 'UNKNOWN';
                log_message('error', 'Google Places API request failed with HTTP '.$status.' ('.$google_status.').');

                if ($status === 401) {
                    $this->forget_access_token();
                }

                if ($status === 403 || $google_status === 'PERMISSION_DENIED') {
                    return $this->result(false, 503, 'The service account cannot use the Google Places API.');
                }

                return $this->result(false, 502, 'Unable to load Google Places results.');
            }

            return $this->result(true, 200, 'OK', array(
                'body' => is_array($body) ? $body : array()
            ));
        } catch (Throwable $exception) {
            log_message('error', 'Google Places request failed with exception type '.get_class($exception).'.');
            return $this->result(false, 502, 'Unable to load Google Places results.');
        }
    }

    /**
     * OAuth token for the service account, cached on disk until shortly before it expires.
     *
     * The expiry is stored beside the token and checked here on purpose: the Google
     * library's FileSystemCacheItemPool discards item expiry, so a token cached through
     * it is served forever and Google rejects it with ACCESS_TOKEN_EXPIRED.
     */
    private function access_token()
    {
        $cached = $this->read_cached_access_token();
        if ($cached !== '') {
            return $cached;
        }

        $credentials = new ServiceAccountCredentials(
            GOOGLE_PLACES_OAUTH_SCOPE,
            json_decode(file_get_contents(RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE), true)
        );

        $token = $credentials->fetchAuthToken(
            HttpHandlerFactory::build($this->http_client())
        );
        if (empty($token['access_token'])) {
            return '';
        }

        $lifetime = isset($token['expires_in']) ? (int) $token['expires_in'] : 3600;
        $this->write_cached_access_token(
            (string) $token['access_token'],
            time() + max($lifetime - GOOGLE_PLACES_TOKEN_EXPIRY_MARGIN_SECONDS, 0)
        );

        return (string) $token['access_token'];
    }

    private function access_token_cache_file()
    {
        return APPPATH.'cache/google_places/access_token.json';
    }

    /** The cached token, or an empty string when there is none or it is about to expire. */
    private function read_cached_access_token()
    {
        $file = $this->access_token_cache_file();
        if (!is_readable($file)) {
            return '';
        }

        $cached = json_decode((string) file_get_contents($file), true);
        if (!is_array($cached)
            || empty($cached['access_token'])
            || !isset($cached['expires_at'])
            || (int) $cached['expires_at'] <= time()
        ) {
            return '';
        }

        return (string) $cached['access_token'];
    }

    private function write_cached_access_token($access_token, $expires_at)
    {
        $file = $this->access_token_cache_file();
        $directory = dirname($file);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return;
        }

        @file_put_contents($file, json_encode(array(
            'access_token' => $access_token,
            'expires_at' => (int) $expires_at
        )), LOCK_EX);
    }

    /** Drops the cached token so the next request fetches a fresh one. */
    private function forget_access_token()
    {
        $file = $this->access_token_cache_file();
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function http_client()
    {
        if ($this->http_client === null) {
            $this->http_client = new Client(array(
                'verify' => GOOGLE_API_CA_BUNDLE,
                'timeout' => GOOGLE_PLACES_TIMEOUT_SECONDS,
                'connect_timeout' => 3,
                'http_errors' => false
            ));
            HttpClientCache::setHttpClient($this->http_client);
        }

        return $this->http_client;
    }

    private function language_code($language)
    {
        return $language === 'ar' ? 'ar' : 'en';
    }

    private function valid_session_token($session_token)
    {
        return $session_token === ''
            || (is_string($session_token) && preg_match('/^[A-Za-z0-9_-]{16,64}$/D', $session_token) === 1);
    }

    private function result($success, $status_code, $message, array $data = array())
    {
        return array_merge(array(
            'success' => (bool) $success,
            'status_code' => (int) $status_code,
            'message' => $message
        ), $data);
    }
}
