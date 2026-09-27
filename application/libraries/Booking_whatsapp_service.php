<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WhatsApp notifications for frontend bookings.
 *
 * Only customers who ticked the WhatsApp consent on the booking Review step
 * (tour_bookings.book_whatsapp_consent_at) are messaged. A WhatsApp failure is
 * logged and never affects the booking or its emails.
 */
class Booking_whatsapp_service
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Send the booking confirmation on WhatsApp. Returns true when Meta accepted it. */
    public function sendCustomerConfirmation($bookingId)
    {
        $result = $this->customerConfirmationResult($bookingId);
        if (empty($result['success'])) {
            log_message(
                'error',
                'WhatsApp booking confirmation not sent for booking ID ' . (int) $bookingId . ': ' . $result['error']
            );
        }

        return !empty($result['success']);
    }

    /**
     * Send the booking confirmation and return a Whatsapp_gateway-style result, including
     * why nothing was sent. For now this sends Meta's sample order confirmation template
     * (WHATSAPP_BOOKING_TEST_TEMPLATE) for the Meta app review recording; its numbered
     * placeholders take the customer's name, the booking reference and the tour date.
     */
    public function customerConfirmationResult($bookingId)
    {
        try {
            if (!WHATSAPP_BOOKING_ENABLED) {
                return $this->failure('Booking WhatsApp messages are switched off (WHATSAPP_BOOKING_ENABLED).');
            }
            if (!WHATSAPP_ENABLED) {
                return $this->failure('WhatsApp is not configured.');
            }
            if (WHATSAPP_BOOKING_TEST_TEMPLATE === '') {
                return $this->failure('WHATSAPP_BOOKING_TEST_TEMPLATE is empty, so booking messages are switched off.');
            }
            if (!$this->CI->db->field_exists('book_whatsapp_consent_at', 'tour_bookings')) {
                return $this->failure('The tour_bookings.book_whatsapp_consent_at column is missing. Run database/booking-whatsapp-consent.sql.');
            }

            $booking = $this->CI->SqlModel->getSingleRecord('tour_bookings', array('book_id' => (int) $bookingId));
            if (empty($booking)) {
                return $this->failure('Booking ' . (int) $bookingId . ' was not found.');
            }
            if ($booking['book_status'] !== 'Completed') {
                return $this->failure('Booking ' . (int) $bookingId . ' is ' . $booking['book_status'] . ', not Completed.');
            }
            if (empty($booking['book_whatsapp_consent_at'])) {
                return $this->failure('The customer did not tick the WhatsApp consent on the Review step.');
            }
            if (trim((string) $booking['book_phone']) === '') {
                return $this->failure('The booking has no mobile number.');
            }

            $this->CI->load->library('Whatsapp_gateway');

            return $this->CI->whatsapp_gateway->sendTemplate(
                $booking['book_phone'],
                WHATSAPP_BOOKING_TEST_TEMPLATE,
                'en_US',
                array(
                    array(
                        'type' => 'body',
                        'parameters' => array(
                            $this->textParameter($booking['book_name']),
                            $this->textParameter($booking['book_res_code']),
                            $this->textParameter(date('M d, Y', strtotime($booking['book_date']))),
                        ),
                    ),
                )
            );
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }
    }

    /** Meta rejects parameter values containing line breaks or tabs. */
    private function textParameter($value)
    {
        return array(
            'type' => 'text',
            'text' => trim(preg_replace('/\s+/', ' ', (string) $value)),
        );
    }

    private function failure($message)
    {
        return array(
            'success' => false,
            'status_code' => 0,
            'data' => array(),
            'error' => $message,
        );
    }
}
