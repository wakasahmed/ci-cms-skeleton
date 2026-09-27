<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Public WhatsApp Cloud API webhook and an admin/CLI test sender. */
class Whatsapp extends CI_Controller
{
    /**
     * Meta calls this URL with GET once to verify the subscription, then with
     * signed POST requests for incoming messages and delivery status updates.
     */
    public function webhook()
    {
        $method = $this->input->method();
        if ($method === 'get') {
            $this->verifySubscription();

            return;
        }
        if ($method !== 'post') {
            show_error('Method not allowed', 405);

            return;
        }

        $rawBody = (string) $this->input->raw_input_stream;
        $this->load->library('Whatsapp_gateway');
        $signature = $this->input->get_request_header('X-Hub-Signature-256', true);
        if (!$this->whatsapp_gateway->validSignature($rawBody, $signature)) {
            log_message('error', 'Rejected an invalid WhatsApp webhook request.');
            $this->output->set_status_header(401)->set_output('Unauthorized');

            return;
        }

        $payload = json_decode($rawBody, true);
        if (is_array($payload) && isset($payload['object']) && $payload['object'] === 'whatsapp_business_account') {
            $this->handleEvents($payload);
        }

        // Meta retries any non-200 response, so acknowledge every signed request.
        $this->output->set_status_header(200)->set_output('OK');
    }

    /**
     * Send a test message.
     *
     * CLI:     php index.php whatsapp test 966501234567 [template|text|templates] ["Message text"]
     *          php index.php whatsapp test 966501234567 approved <template ID> [en|ar]
     *          php index.php whatsapp test 966501234567 order
     *          php index.php whatsapp test - booking <booking ID>
     * Browser: /whatsapp/test?to=966501234567&mode=template (signed-in administrators only)
     *          /whatsapp/test?to=966501234567&mode=approved&template=3&lang=ar
     *          /whatsapp/test?to=966501234567&mode=order
     *          /whatsapp/test?mode=booking&booking=123
     *
     * "template" sends Meta's pre-approved hello_world template, which works outside
     * the 24-hour window. "approved" sends one of the managed WhatsApp templates
     * (manage/whatsapp-templates) that Meta has approved, filled with each short
     * tag's example value. "order" sends Meta's approved sample
     * jaspers_market_order_confirmation_v1, which only exists on Meta's test WhatsApp
     * Business Account. "booking" runs the real booking confirmation sender for one
     * booking (to its own mobile number) and reports why it was not sent, if it was not.
     * "text" only arrives if the recipient messaged the business
     * number within the last 24 hours. "templates" lists the account's templates.
     */
    public function test($to = '', $mode = '', $text = '', $language = '')
    {
        if (!is_cli()) {
            $admin = $this->SqlModel->authAdmin(
                $this->session->userdata('admin_auth'),
                $this->session->userdata('admin_id')
            );
            if (!$admin) {
                redirect(base_url('manage/login'));

                return;
            }

            $to = (string) $this->input->get('to', true);
            $mode = (string) $this->input->get('mode', true);
            $text = (string) $this->input->get('text', true);
            $language = (string) $this->input->get('lang', true);
            if ($mode === 'approved') {
                $text = (string) $this->input->get('template', true);
            } elseif ($mode === 'booking') {
                $text = (string) $this->input->get('booking', true);
            }
        }

        $mode = $mode === '' ? 'template' : $mode;
        $this->load->library('Whatsapp_gateway');

        switch ($mode) {
            case 'template':
                $result = $this->whatsapp_gateway->sendTemplate($to, 'hello_world', 'en_US');
                break;
            case 'text':
                $result = $this->whatsapp_gateway->sendText(
                    $to,
                    $text !== '' ? $text : 'Test message from ' . base_url()
                );
                break;
            case 'templates':
                $result = $this->whatsapp_gateway->templates();
                break;
            case 'order':
                // Meta's sample template uses numbered placeholders: name, order number, delivery date.
                $result = $this->whatsapp_gateway->sendTemplate(
                    $to,
                    'jaspers_market_order_confirmation_v1',
                    'en_US',
                    array(
                        array(
                            'type' => 'body',
                            'parameters' => array(
                                array('type' => 'text', 'text' => 'Ahmed Khan'),
                                array('type' => 'text', 'text' => 'ALM-10234'),
                                array('type' => 'text', 'text' => date('j M Y', strtotime('+3 days'))),
                            ),
                        ),
                    )
                );
                break;
            case 'booking':
                // In this mode the third argument is the booking ID; "to" is not used.
                $this->load->library('Booking_whatsapp_service');
                $result = $this->booking_whatsapp_service->customerConfirmationResult((int) $text);
                break;
            case 'approved':
                // In this mode the third argument is the managed template ID.
                $this->load->library('Whatsapp_template_service');
                $result = $this->whatsapp_template_service->sendSample(
                    $to,
                    (int) $text,
                    $language === '' ? 'en' : $language
                );
                break;
            default:
                $result = array(
                    'success' => false,
                    'status_code' => 0,
                    'data' => array(),
                    'error' => 'Unknown mode. Use template, order, booking, approved, text or templates.',
                );
        }

        $output = json_encode(
            $result,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . PHP_EOL;

        if (is_cli()) {
            echo $output;
            if (empty($result['success'])) {
                exit(1);
            }

            return;
        }

        $this->output
            ->set_header('Cache-Control: no-store')
            ->set_content_type('application/json')
            ->set_status_header(empty($result['success']) ? 422 : 200)
            ->set_output($output);
    }

    private function verifySubscription()
    {
        // PHP turns the "hub.mode" style query keys into "hub_mode".
        $mode = (string) $this->input->get('hub_mode');
        $token = (string) $this->input->get('hub_verify_token');
        $challenge = (string) $this->input->get('hub_challenge');

        if ($mode !== 'subscribe'
            || WHATSAPP_WEBHOOK_VERIFY_TOKEN === ''
            || !hash_equals(WHATSAPP_WEBHOOK_VERIFY_TOKEN, $token)
            || preg_match('/^[A-Za-z0-9_-]{1,255}$/D', $challenge) !== 1
        ) {
            $this->output->set_status_header(403)->set_output('Forbidden');

            return;
        }

        $this->output
            ->set_content_type('text/plain')
            ->set_status_header(200)
            ->set_output($challenge);
    }

    /**
     * Record incoming messages and delivery status updates. Notification sending
     * does not depend on these events yet, so they are only logged for now.
     */
    private function handleEvents(array $payload)
    {
        $entries = isset($payload['entry']) && is_array($payload['entry'])
            ? $payload['entry']
            : array();

        foreach ($entries as $entry) {
            $changes = isset($entry['changes']) && is_array($entry['changes'])
                ? $entry['changes']
                : array();

            foreach ($changes as $change) {
                if (!isset($change['field'], $change['value']) || !is_array($change['value'])) {
                    continue;
                }
                if ($change['field'] === 'message_template_status_update') {
                    $this->handleTemplateStatus($change['value']);
                    continue;
                }
                if ($change['field'] !== 'messages') {
                    continue;
                }

                $value = $change['value'];
                if (!empty($value['statuses']) && is_array($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->logEvent(array(
                            'event' => 'status',
                            'message_id' => $this->field($status, 'id'),
                            'status' => $this->field($status, 'status'),
                            'recipient' => $this->field($status, 'recipient_id'),
                            'error' => isset($status['errors'][0]['code'])
                                ? (int) $status['errors'][0]['code']
                                : null,
                        ));
                    }
                }

                if (!empty($value['messages']) && is_array($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        // Message content is not logged to keep customer data out of log files.
                        $this->logEvent(array(
                            'event' => 'message',
                            'message_id' => $this->field($message, 'id'),
                            'from' => $this->field($message, 'from'),
                            'type' => $this->field($message, 'type'),
                        ));
                    }
                }
            }
        }
    }

    /** Record Meta's review decision on a managed WhatsApp template. */
    private function handleTemplateStatus(array $value)
    {
        $templateId = $this->field($value, 'message_template_id');
        $event = $this->field($value, 'event');
        $this->load->library('Whatsapp_template_service');
        $matched = $this->whatsapp_template_service->applyStatusEvent(
            $templateId,
            $event,
            $this->field($value, 'reason')
        );

        $this->logEvent(array(
            'event' => 'template_status',
            'template_id' => $templateId,
            'template_name' => $this->field($value, 'message_template_name'),
            'language' => $this->field($value, 'message_template_language'),
            'status' => $event,
            'matched' => $matched,
        ));
    }

    private function field(array $data, $key)
    {
        return isset($data[$key]) && is_scalar($data[$key])
            ? substr((string) $data[$key], 0, 255)
            : '';
    }

    private function logEvent(array $event)
    {
        if (!WHATSAPP_WEBHOOK_LOG) {
            return;
        }

        $line = date('Y-m-d H:i:s') . ' ' . json_encode($event, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents(APPPATH . 'logs/whatsapp_webhook.log', $line, FILE_APPEND | LOCK_EX);
    }
}
