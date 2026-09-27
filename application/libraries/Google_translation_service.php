<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Google\ApiCore\ApiException;
use Google\Auth\HttpHandler\HttpClientCache;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use GuzzleHttp\Client;

class Google_translation_service
{
    private $autoload_available = false;

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

    public function translate($text, $source_language, $target_language)
    {
        $result = $this->translate_batch(array($text), $source_language, $target_language, 'text/plain');
        if (empty($result['success'])) return $result;

        return $this->result(true, 200, 'Translation completed.', array(
            'translated_text' => $result['translated_texts'][0],
            'source_language' => $source_language,
            'target_language' => $target_language
        ));
    }

    public function translate_batch(array $contents, $source_language, $target_language, $mime_type = 'text/plain')
    {
        if (!$this->autoload_available) {
            return $this->result(false, 503, 'The Google API library is not installed.');
        }

        $contents = array_values($contents);
        if (empty($contents) || count($contents) > 100) {
            return $this->result(false, 422, 'Translation batches must contain between 1 and 100 items.');
        }
        if (!in_array($mime_type, array('text/plain', 'text/html'), true)) {
            return $this->result(false, 422, 'The translation content type is not supported.');
        }
        $total_length = 0;
        foreach ($contents as $content) {
            if (!is_string($content) || trim($content) === '') {
                return $this->result(false, 422, 'Translation batch items cannot be empty.');
            }
            $length = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
            if ($length > 30000) {
                return $this->result(false, 422, 'A translation batch item exceeds the 30,000 character limit.');
            }
            $total_length += $length;
        }
        if ($total_length > 30000) {
            return $this->result(false, 422, 'The translation batch exceeds the 30,000 character limit.');
        }

        $protect_as_html = $mime_type === 'text/html';
        if ($mime_type === 'text/plain') {
            foreach ($contents as $content) {
                if (preg_match('/\{\{[a-zA-Z0-9_.-]+\}\}/', $content)) {
                    $protect_as_html = true;
                    break;
                }
            }
        }

        $request_mime_type = $protect_as_html ? 'text/html' : $mime_type;
        $placeholder_values = array();
        foreach ($contents as $index => $content) {
            $masked = $this->mask_double_curly_placeholders(
                $content,
                $protect_as_html,
                $mime_type === 'text/plain' && $protect_as_html
            );
            $contents[$index] = $masked['text'];
            $placeholder_values[$index] = $masked['values'];
        }

        if (!is_readable(GOOGLE_TRANSLATION_CREDENTIALS_FILE)) {
            log_message('error', 'The Google Translation credentials file is not readable.');
            return $this->result(false, 503, 'The Google Translation credentials are not configured.');
        }

        $client = null;

        try {
            if (!is_readable(GOOGLE_API_CA_BUNDLE)) {
                return $this->result(false, 503, 'The Google API CA certificate bundle is not installed.');
            }

            $http_client = new Client(array('verify' => GOOGLE_API_CA_BUNDLE));
            HttpClientCache::setHttpClient($http_client);
            $http_handler = HttpHandlerFactory::build($http_client);

            $client = new TranslationServiceClient(array(
                'transport' => 'rest',
                'credentials' => GOOGLE_TRANSLATION_CREDENTIALS_FILE,
                'transportConfig' => array(
                    'rest' => array('httpHandler' => array($http_handler, 'async'))
                )
            ));

            $request = (new TranslateTextRequest())
                ->setParent(TranslationServiceClient::locationName(
                    GOOGLE_TRANSLATION_PROJECT_ID,
                    GOOGLE_TRANSLATION_LOCATION
                ))
                ->setContents($contents)
                ->setMimeType($request_mime_type)
                ->setSourceLanguageCode($source_language)
                ->setTargetLanguageCode($target_language);

            $response = $client->translateText($request);
            $translations = $response->getTranslations();

            if (count($translations) !== count($contents)) {
                return $this->result(false, 502, 'Google Translation returned no translation.');
            }

            $translated_texts = array();
            foreach ($translations as $index => $translation) {
                $translated_text = $this->restore_double_curly_placeholders(
                    $translation->getTranslatedText(),
                    $placeholder_values[$index],
                    $protect_as_html,
                    $mime_type === 'text/plain' && $protect_as_html
                );

                if ($translated_text === false) {
                    return $this->result(
                        false,
                        502,
                        'A protected placeholder was changed during translation.'
                    );
                }

                $translated_texts[] = $translated_text;
            }

            return $this->result(true, 200, 'Translation completed.', array(
                'translated_texts' => $translated_texts,
                'source_language' => $source_language,
                'target_language' => $target_language,
                'mime_type' => $mime_type
            ));
        } catch (ApiException $exception) {
            log_message('error', 'Google Translation API request failed with code '.(int) $exception->getCode().'.');

            if (strpos($exception->getMessage(), 'SERVICE_DISABLED') !== false) {
                return $this->result(false, 503, 'Enable the Cloud Translation API for this Google Cloud project.');
            }
            if ((int) $exception->getCode() === 7) {
                return $this->result(false, 403, 'The service account does not have permission to use Google Translation.');
            }

            return $this->result(false, 502, 'Unable to translate the text with Google Translation.');
        } catch (Throwable $exception) {
            log_message('error', 'Google Translation request failed with exception type '.get_class($exception).'.');
            return $this->result(false, 502, 'Unable to translate the text with Google Translation.');
        } finally {
            if ($client !== null) {
                $client->close();
            }
        }
    }

    private function mask_double_curly_placeholders(
        $text,
        $protect_as_html,
        $escape_plain_text
    )
    {
        $values = array();
        $masked_text = preg_replace_callback(
            '/\{\{[a-zA-Z0-9_.-]+\}\}/',
            function ($match) use (&$values, $text) {
                $token_index = count($values);
                $token = $this->placeholder_token($token_index);

                while (strpos($text, $token) !== false || isset($values[$token])) {
                    $token_index++;
                    $token = $this->placeholder_token($token_index);
                }

                $values[$token] = $match[0];

                return $token;
            },
            $text
        );

        if ($escape_plain_text) {
            $masked_text = htmlspecialchars(
                $masked_text,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        }

        if ($protect_as_html) {
            foreach ($values as $token => $original) {
                $masked_text = str_replace(
                    $token,
                    '<span translate="no" class="notranslate" data-alam-placeholder="'
                        .$token.'"></span>',
                    $masked_text
                );
            }
        }

        return array(
            'text' => $masked_text,
            'values' => $values
        );
    }

    private function restore_double_curly_placeholders(
        $text,
        array $values,
        $protected_as_html,
        $decode_plain_html
    )
    {
        if ($protected_as_html) {
            $restored = array();
            $text = preg_replace_callback(
                '/<span\b[^>]*\bdata-alam-placeholder=(["\'])([A-Z]+)\1[^>]*>.*?<\/span>/isu',
                function ($match) use ($values, &$restored) {
                    $token = $match[2];
                    if (!isset($values[$token])) {
                        return $match[0];
                    }

                    $restored[$token] = true;

                    return $values[$token];
                },
                $text
            );

            foreach ($values as $token => $original) {
                if (!isset($restored[$token])) {
                    return false;
                }
            }

            if ($decode_plain_html) {
                $text = html_entity_decode(
                    strip_tags($text),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
            }

            return $text;
        }

        foreach ($values as $token => $original) {
            if (strpos($text, $token) === false) {
                return false;
            }

            $text = str_replace($token, $original, $text);
        }

        return $text;
    }

    private function placeholder_token($index)
    {
        $letters = '';
        $index = max(0, (int) $index);

        do {
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return 'ALAMPH'.$letters.'TOKEN';
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
