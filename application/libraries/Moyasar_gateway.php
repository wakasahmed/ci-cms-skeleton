<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Server-side Moyasar API client.
 *
 * Payment creation stays in Moyasar's browser form. This client only performs
 * privileged fetch/refund operations and never accepts or logs card data.
 */
class Moyasar_gateway
{
    private $lastError = '';

    public function configured()
    {
        return MOYASAR_ENABLED;
    }

    public function lastError()
    {
        return $this->lastError;
    }

    public function fetchPayment($paymentId)
    {
        if (!$this->validPaymentId($paymentId)) {
            return $this->failure('Invalid Moyasar payment ID.');
        }

        return $this->request('GET', '/payments/' . rawurlencode($paymentId));
    }

    public function refundPayment($paymentId, $amountMinor)
    {
        if (!$this->validPaymentId($paymentId)) {
            return $this->failure('Invalid Moyasar payment ID.');
        }
        if (!is_int($amountMinor) || $amountMinor <= 0) {
            return $this->failure('Invalid refund amount.');
        }

        return $this->request(
            'POST',
            '/payments/' . rawurlencode($paymentId) . '/refund',
            array('amount' => $amountMinor)
        );
    }

    /** Remove fields that must not be persisted if Moyasar adds them to a response. */
    public function sanitizedResponse(array $response)
    {
        $blocked = array(
            'cvc',
            'cvv',
            'number',
            'token',
            'authentication_value',
        );

        $sanitize = function ($value, $key = '') use (&$sanitize, $blocked) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                if (strtolower((string) $key) === 'number' && is_string($value)) {
                    $digits = preg_replace('/\D+/', '', $value);

                    return strlen($digits) >= 4 ? '**** ' . substr($digits, -4) : null;
                }

                return null;
            }
            if (!is_array($value)) {
                return $value;
            }

            $clean = array();
            foreach ($value as $childKey => $childValue) {
                $clean[$childKey] = $sanitize($childValue, $childKey);
            }

            return $clean;
        };

        return $sanitize($response);
    }

    private function validPaymentId($paymentId)
    {
        return is_string($paymentId)
            && preg_match('/^[a-zA-Z0-9_-]{8,80}$/D', $paymentId) === 1;
    }

    private function request($method, $path, array $body = array())
    {
        $this->lastError = '';
        if (!$this->configured()) {
            return $this->failure('Moyasar is not configured.');
        }
        if (!function_exists('curl_init')) {
            return $this->failure('The PHP cURL extension is unavailable.');
        }

        $url = rtrim(MOYASAR_API_URL, '/') . $path;
        $handle = curl_init($url);
        $headers = array('Accept: application/json');
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(10, MOYASAR_TIMEOUT_SECONDS),
            CURLOPT_TIMEOUT => MOYASAR_TIMEOUT_SECONDS,
            CURLOPT_USERPWD => MOYASAR_SECRET_KEY . ':',
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        );

        if ($method !== 'GET') {
            $payload = json_encode($body);
            if ($payload === false) {
                curl_close($handle);

                return $this->failure('Unable to encode the Moyasar request.');
            }
            $options[CURLOPT_POSTFIELDS] = $payload;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($handle, $options);
        $raw = curl_exec($handle);
        $curlError = curl_error($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($raw === false) {
            log_message('error', 'Moyasar API request failed: ' . $curlError);

            return $this->failure('Unable to contact Moyasar.', $statusCode);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            log_message('error', 'Moyasar API returned invalid JSON (HTTP ' . $statusCode . ').');

            return $this->failure('Moyasar returned an invalid response.', $statusCode);
        }
        if ($statusCode < 200 || $statusCode >= 300) {
            $message = isset($decoded['message']) && is_string($decoded['message'])
                ? substr($decoded['message'], 0, 500)
                : 'Moyasar rejected the request.';
            log_message('error', 'Moyasar API HTTP ' . $statusCode . ': ' . $message);

            return $this->failure($message, $statusCode, $this->sanitizedResponse($decoded));
        }

        return array(
            'success' => true,
            'status_code' => $statusCode,
            'data' => $decoded,
            'error' => '',
        );
    }

    private function failure($message, $statusCode = 0, array $data = array())
    {
        $this->lastError = $message;

        return array(
            'success' => false,
            'status_code' => (int) $statusCode,
            'data' => $data,
            'error' => $message,
        );
    }
}
