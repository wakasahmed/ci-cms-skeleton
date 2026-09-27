<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Frontend.php';

/** Public Moyasar preparation, return and webhook endpoints. */
class Payments extends Frontend
{
    public function prepare()
    {
        if ($this->input->method() !== 'post' || !$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);

            return;
        }

        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');
        $token = trim((string) $this->input->post('booking_token'));
        $bookingId = trim((string) $this->input->post('booking_id'));
        $this->load->model('Booking_payment_model');
        $result = $this->Booking_payment_model->prepare($token, $bookingId);
        if (empty($result['success'])) {
            $this->output->set_status_header($result['error'] === 'expired' ? 422 : 503);
        }

        $this->output->set_output($this->json($result));
    }

    /** Save Moyasar's payment ID before a possible 3-D Secure redirect. */
    public function record()
    {
        if ($this->input->method() !== 'post' || !$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);

            return;
        }

        $attempt = trim((string) $this->input->post('attempt_reference'));
        $paymentId = trim((string) $this->input->post('payment_id'));
        $this->load->model('Booking_payment_model');
        $saved = $this->Booking_payment_model->recordProviderPayment($attempt, $paymentId);
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');
        if (!$saved) {
            $this->output->set_status_header(422);
        }
        $this->output->set_output($this->json(array('success' => (bool) $saved)));
    }

    public function paymentReturn()
    {
        $paymentId = trim((string) $this->input->get('id'));
        $this->load->model('Booking_payment_model');
        $result = $this->Booking_payment_model->verifyAndComplete($paymentId);
        if (!empty($result['success']) && !empty($result['notify'])) {
            $this->sendCompletionNotifications($result);
        }
        if (!empty($result['success']) && !empty($result['notify_refund'])) {
            $this->sendRefundNotification((int) $result['booking_id']);
        }

        $this->output->set_header('Cache-Control: no-store');
        $this->load->view('frontend/payment_result', array(
            'paymentResult' => $result,
        ));
    }

    public function webhook()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);

            return;
        }

        $payload = json_decode((string) $this->input->raw_input_stream, true);
        if (!$this->validWebhook($payload)) {
            log_message('error', 'Rejected an invalid Moyasar webhook request.');
            $this->output->set_status_header(401)->set_output('Unauthorized');

            return;
        }

        $eventType = (string) $payload['type'];
        $paymentId = isset($payload['data']['id']) ? (string) $payload['data']['id'] : '';
        $supported = array(
            'payment_paid',
            'payment_failed',
            'payment_faild',
            'payment_authorized',
            'payment_captured',
            'payment_refunded',
            'payment_voided',
            'payment_verified',
        );
        if (!in_array($eventType, $supported, true) || $paymentId === '') {
            $this->output->set_status_header(204);

            return;
        }

        $this->load->model('Booking_payment_model');
        $result = $this->Booking_payment_model->verifyAndComplete($paymentId);
        if (!empty($result['success']) && !empty($result['notify'])) {
            $this->sendCompletionNotifications($result);
        }
        if (!empty($result['success']) && !empty($result['notify_refund'])) {
            $this->sendRefundNotification((int) $result['booking_id']);
        }

        // A verified non-paid state is still a successfully handled webhook.
        $this->output->set_status_header(200)->set_output('OK');
    }

    private function sendCompletionNotifications(array $result)
    {
        $bookingId = (int) $result['booking_id'];
        $this->sendBookingAdminNotification($bookingId, 'completed');
        $this->sendBookingCustomerConfirmation($bookingId);
        $this->sendBookingWhatsappConfirmation($bookingId);
        $this->sendBookingTourGuideAssignment($bookingId);
        $this->Booking_payment_model->markNotificationsSent((int) $result['payment_id']);
    }

    private function sendRefundNotification($bookingId)
    {
        $this->load->library('Booking_email_service');
        if (!$this->booking_email_service->sendCustomerRefund((int) $bookingId)) {
            log_message('error', 'Moyasar webhook refund email failed for booking ID ' . (int) $bookingId . '.');
        }
    }

    private function validWebhook($payload)
    {
        if (!is_array($payload)
            || MOYASAR_WEBHOOK_SECRET === ''
            || !isset($payload['secret_token'], $payload['type'], $payload['data'])
            || !is_string($payload['secret_token'])
            || !is_array($payload['data'])
            || !hash_equals(MOYASAR_WEBHOOK_SECRET, $payload['secret_token'])
        ) {
            return false;
        }

        if (isset($payload['live']) && (bool) $payload['live'] === MOYASAR_SANDBOX) {
            return false;
        }

        return true;
    }

    private function json(array $value)
    {
        return json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }
}
