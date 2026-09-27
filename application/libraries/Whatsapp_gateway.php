<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Server-side WhatsApp Cloud API client.
 *
 * Messages sent outside a customer's 24-hour service window must use an
 * approved template (sendTemplate). Free-form text (sendText) is only
 * delivered while that window is open.
 */
class Whatsapp_gateway
{
    private $lastError = '';

    public function configured()
    {
        return WHATSAPP_ENABLED;
    }

    public function lastError()
    {
        return $this->lastError;
    }

    /**
     * Convert a stored phone number to the digits-only international format
     * the Cloud API expects (e.g. "+966 50 123 4567" becomes "966501234567").
     * Returns an empty string when the number cannot be used.
     */
    public function normalizePhone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^[1-9]\d{7,14}$/D', $digits) === 1
            ? $digits
            : '';
    }

    public function sendText($to, $text, $previewUrl = false)
    {
        $text = trim((string) $text);
        if ($text === '' || mb_strlen($text, 'UTF-8') > 4096) {
            return $this->failure('The message text must be between 1 and 4096 characters.');
        }

        return $this->sendMessage($to, 'text', array(
            'preview_url' => (bool) $previewUrl,
            'body' => $text,
        ));
    }

    /**
     * Send an approved message template.
     *
     * $components follows the Cloud API structure, for example:
     * array(
     *     array(
     *         'type' => 'body',
     *         'parameters' => array(
     *             array('type' => 'text', 'text' => 'Ahmed'),
     *         ),
     *     ),
     * )
     */
    public function sendTemplate($to, $templateName, $languageCode = 'en', array $components = array())
    {
        if (preg_match('/^[a-z0-9_]{1,512}$/D', (string) $templateName) !== 1) {
            return $this->failure('Invalid WhatsApp template name.');
        }
        if (preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/D', (string) $languageCode) !== 1) {
            return $this->failure('Invalid WhatsApp template language code.');
        }

        $template = array(
            'name' => $templateName,
            'language' => array('code' => $languageCode),
        );
        if (!empty($components)) {
            $template['components'] = $components;
        }

        return $this->sendMessage($to, 'template', $template);
    }

    /** Mark an incoming message as read (shows blue ticks to the sender). */
    public function markAsRead($messageId)
    {
        if (!$this->validMessageId($messageId)) {
            return $this->failure('Invalid WhatsApp message ID.');
        }

        return $this->request(
            'POST',
            '/' . rawurlencode(WHATSAPP_PHONE_NUMBER_ID) . '/messages',
            array(
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            )
        );
    }

    /** List the business account's message templates and their approval status. */
    public function templates($limit = 50)
    {
        if (WHATSAPP_BUSINESS_ACCOUNT_ID === '') {
            return $this->failure('The WhatsApp Business Account ID is not configured.');
        }

        $query = http_build_query(array(
            'fields' => 'name,language,status,category',
            'limit' => max(1, min(100, (int) $limit)),
        ));

        return $this->request(
            'GET',
            '/' . rawurlencode(WHATSAPP_BUSINESS_ACCOUNT_ID) . '/message_templates?' . $query
        );
    }

    /**
     * Fetch every language version of one template by its exact name.
     * Meta's name filter also matches partial names, so results are narrowed here.
     */
    public function templatesByName($name)
    {
        if (!$this->validTemplateName($name)) {
            return $this->failure('Invalid WhatsApp template name.');
        }
        if (WHATSAPP_BUSINESS_ACCOUNT_ID === '') {
            return $this->failure('The WhatsApp Business Account ID is not configured.');
        }

        $query = http_build_query(array(
            'name' => $name,
            'fields' => 'id,name,language,status,category,components',
            'limit' => 100,
        ));
        $result = $this->request(
            'GET',
            '/' . rawurlencode(WHATSAPP_BUSINESS_ACCOUNT_ID) . '/message_templates?' . $query
        );
        if (empty($result['success'])) {
            return $result;
        }

        $templates = array();
        $rows = isset($result['data']['data']) && is_array($result['data']['data'])
            ? $result['data']['data']
            : array();
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['name']) && $row['name'] === $name) {
                $templates[] = $row;
            }
        }
        $result['data'] = $templates;

        return $result;
    }

    /**
     * Submit a new template language version for review.
     *
     * $examples maps each named parameter used in $body to an example value.
     */
    public function createTemplate($name, $languageCode, $category, $body, array $examples = array())
    {
        if (!$this->validTemplateName($name)) {
            return $this->failure('Invalid WhatsApp template name.');
        }
        if (!in_array($category, array('UTILITY', 'MARKETING'), true)) {
            return $this->failure('Invalid WhatsApp template category.');
        }
        if (WHATSAPP_BUSINESS_ACCOUNT_ID === '') {
            return $this->failure('The WhatsApp Business Account ID is not configured.');
        }

        return $this->request(
            'POST',
            '/' . rawurlencode(WHATSAPP_BUSINESS_ACCOUNT_ID) . '/message_templates',
            array(
                'name' => $name,
                'language' => $languageCode,
                'category' => $category,
                'parameter_format' => 'named',
                'components' => $this->templateComponents($body, $examples),
            )
        );
    }

    /** Replace the body of an existing template language version; Meta reviews it again. */
    public function editTemplate($templateId, $body, array $examples = array())
    {
        if (preg_match('/^\d{1,30}$/D', (string) $templateId) !== 1) {
            return $this->failure('Invalid WhatsApp template ID.');
        }

        return $this->request(
            'POST',
            '/' . $templateId,
            array(
                'components' => $this->templateComponents($body, $examples),
            )
        );
    }

    /** Delete every language version of a template. */
    public function deleteTemplate($name)
    {
        if (!$this->validTemplateName($name)) {
            return $this->failure('Invalid WhatsApp template name.');
        }
        if (WHATSAPP_BUSINESS_ACCOUNT_ID === '') {
            return $this->failure('The WhatsApp Business Account ID is not configured.');
        }

        return $this->request(
            'DELETE',
            '/' . rawurlencode(WHATSAPP_BUSINESS_ACCOUNT_ID) . '/message_templates?'
                . http_build_query(array('name' => $name))
        );
    }

    /**
     * Check Meta's X-Hub-Signature-256 header against the raw request body.
     * Without an App Secret the signature cannot be checked, so the request is rejected.
     */
    public function validSignature($rawBody, $signatureHeader)
    {
        if (WHATSAPP_APP_SECRET === '' || !is_string($signatureHeader)) {
            return false;
        }
        if (strpos($signatureHeader, 'sha256=') !== 0) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', (string) $rawBody, WHATSAPP_APP_SECRET);

        return hash_equals($expected, $signatureHeader);
    }

    private function sendMessage($to, $type, array $content)
    {
        $recipient = $this->normalizePhone($to);
        if ($recipient === '') {
            return $this->failure('Invalid WhatsApp recipient phone number.');
        }

        return $this->request(
            'POST',
            '/' . rawurlencode(WHATSAPP_PHONE_NUMBER_ID) . '/messages',
            array(
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => $type,
                $type => $content,
            )
        );
    }

    private function templateComponents($body, array $examples)
    {
        $component = array(
            'type' => 'BODY',
            'text' => (string) $body,
        );

        if (!empty($examples)) {
            $params = array();
            foreach ($examples as $paramName => $example) {
                $params[] = array(
                    'param_name' => (string) $paramName,
                    'example' => (string) $example,
                );
            }
            $component['example'] = array('body_text_named_params' => $params);
        }

        return array($component);
    }

    private function validTemplateName($name)
    {
        return is_string($name)
            && preg_match('/^[a-z0-9_]{1,512}$/D', $name) === 1;
    }

    private function validMessageId($messageId)
    {
        return is_string($messageId)
            && preg_match('/^[A-Za-z0-9._=:-]{8,255}$/D', $messageId) === 1;
    }

    private function request($method, $path, array $body = array())
    {
        $this->lastError = '';
        if (!$this->configured()) {
            return $this->failure('WhatsApp is not configured.');
        }
        if (!function_exists('curl_init')) {
            return $this->failure('The PHP cURL extension is unavailable.');
        }

        $url = rtrim(WHATSAPP_GRAPH_API_URL, '/')
            . '/' . WHATSAPP_GRAPH_API_VERSION
            . $path;
        $handle = curl_init($url);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(10, WHATSAPP_TIMEOUT_SECONDS),
            CURLOPT_TIMEOUT => WHATSAPP_TIMEOUT_SECONDS,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Bearer ' . WHATSAPP_ACCESS_TOKEN,
            ),
        );

        if ($method === 'POST') {
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                curl_close($handle);

                return $this->failure('Unable to encode the WhatsApp request.');
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
            log_message('error', 'WhatsApp API request failed: ' . $curlError);

            return $this->failure('Unable to contact the WhatsApp API.', $statusCode);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            log_message('error', 'WhatsApp API returned invalid JSON (HTTP ' . $statusCode . ').');

            return $this->failure('The WhatsApp API returned an invalid response.', $statusCode);
        }
        if ($statusCode < 200 || $statusCode >= 300) {
            $message = isset($decoded['error']['message']) && is_string($decoded['error']['message'])
                ? substr($decoded['error']['message'], 0, 500)
                : 'The WhatsApp API rejected the request.';
            if (isset($decoded['error']['code'])) {
                $message .= ' (code ' . (int) $decoded['error']['code'] . ')';
            }
            log_message('error', 'WhatsApp API HTTP ' . $statusCode . ': ' . $message);

            return $this->failure($message, $statusCode, $decoded);
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
