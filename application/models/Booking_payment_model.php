<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Payment-attempt and refund persistence for the booking flow. */
class Booking_payment_model extends CI_Model
{
    const PROVIDER = 'moyasar';

    public function tablesAvailable()
    {
        return $this->db->table_exists('tour_booking_payments')
            && $this->db->table_exists('tour_booking_refunds');
    }

    public function prepare($bookingToken, $bookingIdReference)
    {
        if (!MOYASAR_ENABLED || !$this->tablesAvailable()) {
            return array('success' => false, 'error' => 'unavailable');
        }

        $this->load->model('Booking_model');
        $booking = $this->Booking_model->findBookingByToken($bookingToken, $bookingIdReference);
        if (!$booking
            || $booking['book_status'] !== 'Pending'
            || empty($booking['book_terms_accepted_at'])
            || (int) $booking['book_fee'] <= 0
        ) {
            return array('success' => false, 'error' => 'expired');
        }

        $currency = strtoupper(trim((string) $booking['book_currency']));
        if (!preg_match('/^[A-Z]{3}$/D', $currency)) {
            return array('success' => false, 'error' => 'unavailable');
        }

        $amountMinor = $this->toMinorUnits((int) $booking['book_fee']);
        $attempt = $this->db->where('booking_id', (int) $booking['book_id'])
            ->where('payment_status', 'prepared')
            ->where('provider_payment_id IS NULL', null, false)
            ->where('expected_amount_minor', $amountMinor)
            ->where('currency', $currency)
            ->order_by('payment_id', 'DESC')
            ->limit(1)
            ->get('tour_booking_payments')
            ->row_array();

        if (!$attempt) {
            $now = date('Y-m-d H:i:s');
            $attemptReference = bin2hex(random_bytes(16));
            $saved = $this->db->insert('tour_booking_payments', array(
                'booking_id' => (int) $booking['book_id'],
                'attempt_reference' => $attemptReference,
                'provider' => self::PROVIDER,
                'payment_status' => 'prepared',
                'expected_amount_minor' => $amountMinor,
                'currency' => $currency,
                'created_at' => $now,
                'updated_at' => $now,
            ));
            if (!$saved) {
                return array('success' => false, 'error' => 'unavailable');
            }
            $attempt = array('attempt_reference' => $attemptReference);
        }

        $locale = $booking['book_lang'] === 'Arabic' ? 'ar' : 'en';

        return array(
            'success' => true,
            'attempt_reference' => $attempt['attempt_reference'],
            'amount' => $amountMinor,
            'currency' => $currency,
            'description' => 'Alam booking ' . $booking['book_res_code'],
            'publishable_api_key' => MOYASAR_PUBLISHABLE_KEY,
            'callback_url' => base_url($locale . '/payments/return'),
            'record_url' => base_url($locale . '/payments/record'),
            'methods' => MOYASAR_PAYMENT_METHODS,
            'supported_networks' => MOYASAR_CARD_NETWORKS,
            'metadata' => array(
                'booking_reference' => (string) $booking['book_res_code'],
                'payment_attempt' => (string) $attempt['attempt_reference'],
            ),
        );
    }

    public function recordProviderPayment($attemptReference, $providerPaymentId)
    {
        if (!$this->validAttemptReference($attemptReference)
            || !$this->validProviderPaymentId($providerPaymentId)
            || !$this->tablesAvailable()
        ) {
            return false;
        }

        $this->db->trans_begin();
        $attempt = $this->db->query(
            'SELECT * FROM tour_booking_payments WHERE attempt_reference = ? FOR UPDATE',
            array($attemptReference)
        )->row_array();
        $ok = $attempt
            && ($attempt['provider_payment_id'] === null
                || hash_equals((string) $attempt['provider_payment_id'], $providerPaymentId));
        if ($ok) {
            $conflict = $this->db->where('provider', self::PROVIDER)
                ->where('provider_payment_id', $providerPaymentId)
                ->where('payment_id !=', (int) $attempt['payment_id'])
                ->get('tour_booking_payments')
                ->row_array();
            $ok = !$conflict && $this->db->where('payment_id', (int) $attempt['payment_id'])
                ->update('tour_booking_payments', array(
                    'provider_payment_id' => $providerPaymentId,
                    'payment_status' => $attempt['payment_status'] === 'prepared'
                        ? 'initiated'
                        : $attempt['payment_status'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ));
        }

        if ($ok && $this->db->trans_status()) {
            $ok = $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
            $ok = false;
        }

        return (bool) $ok;
    }

    /**
     * Fetch and independently verify a payment, then idempotently complete its
     * booking. Used by both the browser return and webhook paths.
     */
    public function verifyAndComplete($providerPaymentId)
    {
        if (!$this->validProviderPaymentId($providerPaymentId) || !$this->tablesAvailable()) {
            return array('success' => false, 'error' => 'invalid_payment');
        }

        $this->load->library('Moyasar_gateway');
        $response = $this->moyasar_gateway->fetchPayment($providerPaymentId);
        if (!$response['success']) {
            return array('success' => false, 'error' => 'verification_failed');
        }

        $payment = $response['data'];
        $metadata = isset($payment['metadata']) && is_array($payment['metadata'])
            ? $payment['metadata']
            : array();
        $attemptReference = isset($metadata['payment_attempt'])
            ? (string) $metadata['payment_attempt']
            : '';
        if (!$this->validAttemptReference($attemptReference)) {
            $this->logVerificationFailure($providerPaymentId, 'Missing or invalid payment attempt metadata.');

            return array('success' => false, 'error' => 'association_failed');
        }

        if (!$this->recordProviderPayment($attemptReference, $providerPaymentId)) {
            return array('success' => false, 'error' => 'association_failed');
        }

        $attempt = $this->db->select('p.*, b.book_res_code, b.book_status, b.book_email')
            ->from('tour_booking_payments p')
            ->join('tour_bookings b', 'b.book_id = p.booking_id', 'inner')
            ->where('p.attempt_reference', $attemptReference)
            ->get()
            ->row_array();
        if (!$attempt) {
            return array('success' => false, 'error' => 'association_failed');
        }

        $status = isset($payment['status']) ? strtolower((string) $payment['status']) : '';
        $bookingReference = isset($metadata['booking_reference'])
            ? (string) $metadata['booking_reference']
            : '';
        $valid = isset($payment['id'], $payment['amount'], $payment['currency'])
            && hash_equals($providerPaymentId, (string) $payment['id'])
            && hash_equals((string) $attempt['book_res_code'], $bookingReference)
            && (int) $payment['amount'] === (int) $attempt['expected_amount_minor']
            && strtoupper((string) $payment['currency']) === strtoupper((string) $attempt['currency']);

        $this->storeProviderState($attempt, $payment, $valid ? null : 'Payment verification mismatch.');
        if (!$valid) {
            $this->logVerificationFailure($providerPaymentId, 'Amount, currency or booking association mismatch.');

            return array('success' => false, 'error' => 'mismatch');
        }
        if ($status === 'refunded') {
            $synced = $this->syncProviderRefundState($attempt, $payment);

            return array(
                'success' => $synced['success'],
                'error' => $synced['success'] ? '' : 'reconciliation',
                'booking_id' => (int) $attempt['booking_id'],
                'booking_reference' => $attempt['book_res_code'],
                'payment_id' => (int) $attempt['payment_id'],
                'notify' => false,
                'notify_refund' => $synced['new_refund'],
            );
        }
        if (!in_array($status, array('paid', 'captured'), true)) {
            return array(
                'success' => false,
                'error' => in_array($status, array('failed', 'voided', 'refunded'), true)
                    ? 'payment_failed'
                    : 'payment_pending',
                'status' => $status,
                'booking_reference' => $attempt['book_res_code'],
            );
        }

        $source = isset($payment['source']) && is_array($payment['source'])
            ? $payment['source']
            : array();
        $this->load->model('Booking_model');
        $completed = $this->Booking_model->completeVerifiedPayment(
            (int) $attempt['booking_id'],
            array(
                'provider_payment_id' => $providerPaymentId,
                'payment_method' => $this->paymentMethod($source),
                'payer_email' => $attempt['book_email'],
                'paid_at' => $this->databaseDate(
                    isset($payment['updated_at']) ? $payment['updated_at'] : null
                ),
            )
        );
        if (empty($completed['success'])) {
            return array('success' => false, 'error' => isset($completed['error']) ? $completed['error'] : 'unavailable');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('payment_id', (int) $attempt['payment_id'])
            ->update('tour_booking_payments', array(
                'payment_status' => $status,
                'paid_at' => $this->databaseDate(
                    isset($payment['updated_at']) ? $payment['updated_at'] : $now
                ),
                'updated_at' => $now,
            ));

        return array(
            'success' => true,
            'booking_id' => (int) $attempt['booking_id'],
            'booking_reference' => $attempt['book_res_code'],
            'payment_id' => (int) $attempt['payment_id'],
            'notify' => !empty($completed['notification_stage']),
        );
    }

    public function markNotificationsSent($paymentId)
    {
        return $this->db->where('payment_id', (int) $paymentId)
            ->where('notifications_sent_at IS NULL', null, false)
            ->update('tour_booking_payments', array(
                'notifications_sent_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));
    }

    public function paymentForBooking($bookingId)
    {
        if (!$this->tablesAvailable()) {
            return null;
        }

        return $this->db->where('booking_id', (int) $bookingId)
            ->where('provider', self::PROVIDER)
            ->where_in('payment_status', array('paid', 'captured', 'refunded'))
            ->order_by('payment_id', 'DESC')
            ->limit(1)
            ->get('tour_booking_payments')
            ->row_array();
    }

    public function refundsForBooking($bookingId)
    {
        if (!$this->tablesAvailable()) {
            return array();
        }

        return $this->db->where('booking_id', (int) $bookingId)
            ->order_by('refund_id', 'DESC')
            ->get('tour_booking_refunds')
            ->result_array();
    }

    public function refundMoyasarBooking($bookingId, $amount, $reason, $requestToken, $adminId)
    {
        $bookingId = (int) $bookingId;
        $adminId = (int) $adminId;
        if (!$this->tablesAvailable()
            || !is_string($amount)
            || preg_match('/^[0-9]+$/D', $amount) !== 1
            || (int) $amount <= 0
            || !is_string($reason)
            || strlen($reason) > 10000
            || !$this->validAttemptReference($requestToken)
        ) {
            return array('success' => false, 'error' => 'invalid');
        }

        $existingRequest = $this->db->where('request_token', $requestToken)
            ->get('tour_booking_refunds')
            ->row_array();
        if ($existingRequest) {
            return array(
                'success' => $existingRequest['refund_status'] === 'succeeded',
                'error' => $existingRequest['refund_status'] === 'succeeded' ? '' : 'duplicate',
                'full' => false,
            );
        }

        $this->db->trans_begin();
        $booking = $this->db->query(
            'SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE',
            array($bookingId)
        )->row_array();
        $payment = $this->db->query(
            "SELECT * FROM tour_booking_payments WHERE booking_id = ? AND provider = ? "
            . "AND payment_status IN ('paid','captured','refunded') ORDER BY payment_id DESC LIMIT 1 FOR UPDATE",
            array($bookingId, self::PROVIDER)
        )->row_array();
        $pending = $this->db->where('booking_id', $bookingId)
            ->where_in('refund_status', array('pending', 'unknown'))
            ->count_all_results('tour_booking_refunds');
        $remaining = $booking
            ? (int) $booking['book_paid_amount'] - (int) $booking['book_refund_amount']
            : 0;
        $ok = $booking
            && $payment
            && $booking['book_status'] === 'Completed'
            && $pending === 0
            && (int) $amount <= $remaining;
        $now = date('Y-m-d H:i:s');
        if ($ok) {
            $ok = $this->db->insert('tour_booking_refunds', array(
                'payment_id' => (int) $payment['payment_id'],
                'booking_id' => $bookingId,
                'request_token' => $requestToken,
                'provider' => self::PROVIDER,
                'refund_status' => 'pending',
                'amount_minor' => $this->toMinorUnits((int) $amount),
                'currency' => strtoupper((string) $booking['book_currency']),
                'reason' => $reason !== '' ? $reason : null,
                'requested_by' => $adminId > 0 ? $adminId : null,
                'requested_at' => $now,
                'updated_at' => $now,
            ));
        }
        if ($ok && $this->db->trans_status()) {
            $ok = $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
            $ok = false;
        }
        if (!$ok) {
            return array('success' => false, 'error' => 'invalid');
        }

        $this->load->library('Moyasar_gateway');
        $response = $this->moyasar_gateway->refundPayment(
            $payment['provider_payment_id'],
            $this->toMinorUnits((int) $amount)
        );
        if (!$response['success']) {
            // A timeout can occur after Moyasar accepted the refund. Fetch the
            // payment once before declaring failure so a safe retry cannot
            // accidentally refund the customer twice.
            $reconciled = $this->moyasar_gateway->fetchPayment($payment['provider_payment_id']);
            $minimumRefunded = $this->toMinorUnits(
                (int) $booking['book_refund_amount'] + (int) $amount
            );
            if (!$reconciled['success']
                || !isset($reconciled['data']['refunded'])
                || (int) $reconciled['data']['refunded'] < $minimumRefunded
            ) {
                $statusCode = isset($response['status_code']) ? (int) $response['status_code'] : 0;
                $uncertain = $statusCode === 0 || $statusCode >= 500;
                $this->finishFailedRefund(
                    $requestToken,
                    $response,
                    $uncertain ? 'unknown' : 'failed'
                );

                return array('success' => false, 'error' => 'provider');
            }
            $response = $reconciled;
        }

        $providerPayment = $response['data'];
        $factor = 10 ** MOYASAR_CURRENCY_EXPONENT;
        $refundedMinor = isset($providerPayment['refunded'])
            ? (int) $providerPayment['refunded']
            : -1;
        $validResponse = isset($providerPayment['id'], $providerPayment['currency'])
            && hash_equals((string) $payment['provider_payment_id'], (string) $providerPayment['id'])
            && strtoupper((string) $providerPayment['currency']) === strtoupper((string) $booking['book_currency'])
            && $refundedMinor >= $this->toMinorUnits((int) $booking['book_refund_amount'] + (int) $amount)
            && $refundedMinor <= $this->toMinorUnits((int) $booking['book_paid_amount'])
            && $refundedMinor % $factor === 0;
        if (!$validResponse) {
            $this->finishFailedRefund(
                $requestToken,
                array('data' => $providerPayment, 'error' => 'Moyasar refund response did not reconcile.'),
                'unknown'
            );

            return array('success' => false, 'error' => 'reconciliation');
        }

        $refundedMajor = (int) ($refundedMinor / $factor);
        $full = $refundedMajor >= (int) $booking['book_paid_amount'];
        $clean = $this->moyasar_gateway->sanitizedResponse($providerPayment);
        $encoded = json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->db->trans_begin();
        $current = $this->db->query(
            'SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE',
            array($bookingId)
        )->row_array();
        $refund = $this->db->query(
            'SELECT * FROM tour_booking_refunds WHERE request_token = ? FOR UPDATE',
            array($requestToken)
        )->row_array();
        $ok = $current && $refund && $refund['refund_status'] === 'pending';
        if ($ok) {
            $completedAt = date('Y-m-d H:i:s');
            $ok = $this->db->where('refund_id', (int) $refund['refund_id'])
                ->update('tour_booking_refunds', array(
                    'refund_status' => 'succeeded',
                    'provider_response' => $encoded === false ? null : $encoded,
                    'completed_at' => $completedAt,
                    'updated_at' => $completedAt,
                ));
            // Any successful refund — full or partial — marks the booking Refunded and
            // releases its guide/vehicle slot, matching the pre-existing offline refund flow.
            $ok = $ok && $this->db->where('book_id', $bookingId)
                ->update('tour_bookings', array(
                    'book_status' => 'Refunded',
                    'book_refund_amount' => $refundedMajor,
                    'book_refund_reply' => $reason !== '' ? $reason : null,
                    'book_refunded_at' => $completedAt,
                    'book_updated' => $completedAt,
                    'book_avail_id' => 0,
                ));
            $ok = $ok && $this->db->where('payment_id', (int) $payment['payment_id'])
                ->update('tour_booking_payments', array(
                    'payment_status' => isset($providerPayment['status'])
                        ? substr(strtolower((string) $providerPayment['status']), 0, 32)
                        : 'refunded',
                    'provider_response' => $encoded === false ? null : $encoded,
                    'last_verified_at' => $completedAt,
                    'updated_at' => $completedAt,
                ));
            if ($ok) {
                $this->db->where('avail_book_id', $bookingId)
                    ->update('tour_guide_availability', array(
                        'avail_book_id' => 0,
                        'avail_book_status' => 'Available',
                        'avail_updated' => $completedAt,
                    ));
            }
        }
        if ($ok && $this->db->trans_status()) {
            $ok = $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
            $ok = false;
        }

        return array('success' => (bool) $ok, 'error' => $ok ? '' : 'database', 'full' => $full);
    }

    /** Reconcile refunds made in Moyasar's dashboard and delivered by webhook. */
    private function syncProviderRefundState(array $attempt, array $payment)
    {
        $factor = 10 ** MOYASAR_CURRENCY_EXPONENT;
        $refundedMinor = isset($payment['refunded']) ? (int) $payment['refunded'] : -1;
        if ($refundedMinor < 0
            || $refundedMinor > (int) $attempt['expected_amount_minor']
            || $refundedMinor % $factor !== 0
        ) {
            return array('success' => false, 'new_refund' => false);
        }

        $refundedMajor = (int) ($refundedMinor / $factor);
        $this->db->trans_begin();
        $booking = $this->db->query(
            'SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE',
            array((int) $attempt['booking_id'])
        )->row_array();
        if (!$booking || $refundedMajor < (int) $booking['book_refund_amount']) {
            $this->db->trans_rollback();

            return array('success' => false, 'new_refund' => false);
        }

        $difference = $refundedMajor - (int) $booking['book_refund_amount'];
        $now = date('Y-m-d H:i:s');
        $ok = true;
        if ($difference > 0) {
            $clean = $this->moyasar_gateway->sanitizedResponse($payment);
            $encoded = json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $unresolved = $this->db->where('payment_id', (int) $attempt['payment_id'])
                ->where_in('refund_status', array('pending', 'unknown'))
                ->where('amount_minor', $this->toMinorUnits($difference))
                ->order_by('refund_id', 'ASC')
                ->limit(1)
                ->get('tour_booking_refunds')
                ->row_array();
            if ($unresolved) {
                $ok = $this->db->where('refund_id', (int) $unresolved['refund_id'])
                    ->update('tour_booking_refunds', array(
                        'refund_status' => 'succeeded',
                        'provider_response' => $encoded === false ? null : $encoded,
                        'error_message' => null,
                        'completed_at' => $now,
                        'updated_at' => $now,
                    ));
            } else {
                $ok = $this->db->insert('tour_booking_refunds', array(
                    'payment_id' => (int) $attempt['payment_id'],
                    'booking_id' => (int) $attempt['booking_id'],
                    'request_token' => bin2hex(random_bytes(16)),
                    'provider' => self::PROVIDER,
                    'refund_status' => 'succeeded',
                    'amount_minor' => $this->toMinorUnits($difference),
                    'currency' => strtoupper((string) $attempt['currency']),
                    'reason' => 'Reconciled from Moyasar webhook.',
                    'provider_response' => $encoded === false ? null : $encoded,
                    'requested_at' => $now,
                    'completed_at' => $now,
                    'updated_at' => $now,
                ));
            }
            // Any successful refund — full or partial — marks the booking Refunded and
            // releases its guide/vehicle slot, matching the pre-existing offline refund flow.
            $ok = $ok && $this->db->where('book_id', (int) $attempt['booking_id'])
                ->update('tour_bookings', array(
                    'book_status' => 'Refunded',
                    'book_refund_amount' => $refundedMajor,
                    'book_refund_reply' => 'Refund reconciled from Moyasar.',
                    'book_refunded_at' => $now,
                    'book_updated' => $now,
                    'book_avail_id' => 0,
                ));
            if ($ok) {
                $this->db->where('avail_book_id', (int) $attempt['booking_id'])
                    ->update('tour_guide_availability', array(
                        'avail_book_id' => 0,
                        'avail_book_status' => 'Available',
                        'avail_updated' => $now,
                    ));
            }
        }

        if ($ok && $this->db->trans_status()) {
            return array(
                'success' => (bool) $this->db->trans_commit(),
                'new_refund' => $difference > 0,
            );
        }

        $this->db->trans_rollback();

        return array('success' => false, 'new_refund' => false);
    }

    private function finishFailedRefund($requestToken, array $response, $status = 'failed')
    {
        $data = isset($response['data']) && is_array($response['data'])
            ? $this->moyasar_gateway->sanitizedResponse($response['data'])
            : array();
        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $error = isset($response['error']) ? substr((string) $response['error'], 0, 1000) : 'Refund failed.';
        $this->db->where('request_token', $requestToken)
            ->where('refund_status', 'pending')
            ->update('tour_booking_refunds', array(
                'refund_status' => in_array($status, array('failed', 'unknown'), true)
                    ? $status
                    : 'failed',
                'provider_response' => $encoded === false ? null : $encoded,
                'error_message' => $error,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        log_message('error', 'Moyasar refund failed for request ' . $requestToken . ': ' . $error);
    }

    private function storeProviderState(array $attempt, array $payment, $error)
    {
        $clean = $this->moyasar_gateway->sanitizedResponse($payment);
        $source = isset($clean['source']) && is_array($clean['source'])
            ? $clean['source']
            : array();
        $encoded = json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->db->where('payment_id', (int) $attempt['payment_id'])
            ->update('tour_booking_payments', array(
                'payment_status' => isset($payment['status'])
                    ? substr(strtolower((string) $payment['status']), 0, 32)
                    : 'unknown',
                'payment_method' => isset($source['type']) ? substr((string) $source['type'], 0, 32) : null,
                'payment_network' => isset($source['company']) ? substr((string) $source['company'], 0, 32) : null,
                'masked_number' => isset($source['number']) ? substr((string) $source['number'], 0, 32) : null,
                'provider_created_at' => $this->databaseDate(
                    isset($payment['created_at']) ? $payment['created_at'] : null
                ),
                'last_verified_at' => date('Y-m-d H:i:s'),
                'provider_response' => $encoded === false ? null : $encoded,
                'error_message' => $error,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
    }

    private function paymentMethod(array $source)
    {
        $type = isset($source['type']) ? strtolower(trim((string) $source['type'])) : 'payment';
        $network = isset($source['company']) ? strtolower(trim((string) $source['company'])) : '';
        $labels = array(
            'creditcard' => 'Card',
            'applepay' => 'Apple Pay',
            'samsungpay' => 'Samsung Pay',
            'stcpay' => 'STC Pay',
        );
        $label = isset($labels[$type]) ? $labels[$type] : ucfirst($type);
        if ($network !== '') {
            $label .= ' (' . strtoupper($network) . ')';
        }

        return substr('Moyasar ' . $label, 0, 100);
    }

    private function databaseDate($value)
    {
        $timestamp = is_string($value) ? strtotime($value) : false;

        return $timestamp === false ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $timestamp);
    }

    private function validAttemptReference($reference)
    {
        return is_string($reference) && preg_match('/^[a-f0-9]{32}$/D', $reference) === 1;
    }

    private function validProviderPaymentId($paymentId)
    {
        return is_string($paymentId)
            && preg_match('/^[a-zA-Z0-9_-]{8,80}$/D', $paymentId) === 1;
    }

    private function toMinorUnits($amount)
    {
        return (int) $amount * (10 ** MOYASAR_CURRENCY_EXPONENT);
    }

    private function logVerificationFailure($paymentId, $message)
    {
        log_message(
            'error',
            'Moyasar payment verification failed for payment ' . substr((string) $paymentId, 0, 80)
            . ': ' . $message
        );
    }
}
