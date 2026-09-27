<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_review_model extends CI_Model
{
    const TABLE = 'tour_reviews';

    public function booking($bookingId, $locale = 'en')
    {
        $booking = $this->db
            ->select(
                'bookings.*, tours.tour_type, tours.tour_image, tours.tour_image_ar, '
                . 'guides.tour_guide_name AS linked_guide_name, '
                . 'guides.tour_guide_name_ar AS linked_guide_name_ar, '
                . 'slots.slot_name AS linked_slot_name, '
                . 'slots.slot_name_ar AS linked_slot_name_ar'
            )
            ->from('tour_bookings bookings')
            ->join('tours', 'tours.tour_id = bookings.book_tour_id', 'left')
            ->join(
                'tour_guides guides',
                'guides.tour_guide_id = bookings.book_tour_guide_id',
                'left'
            )
            ->join('tour_slots slots', 'slots.slot_id = bookings.book_slot_id', 'left')
            ->where('bookings.book_id', (int) $bookingId)
            ->get()
            ->row_array();

        if (!$booking) {
            return array();
        }

        $booking['review_guide_name'] = $this->localizedRelatedName(
            $booking,
            'linked_guide_name',
            'book_tour_guide_name',
            $locale
        );
        $booking['review_slot_name'] = $this->localizedRelatedName(
            $booking,
            'linked_slot_name',
            'book_slot_name',
            $locale
        );

        return $booking;
    }

    private function localizedRelatedName(array $booking, $linkedField, $fallbackField, $locale)
    {
        $english = isset($booking[$linkedField])
            ? trim((string) $booking[$linkedField])
            : '';
        $arabicField = $linkedField . '_ar';
        $arabic = isset($booking[$arabicField])
            ? trim((string) $booking[$arabicField])
            : '';

        if ($locale === 'ar' && $arabic !== '') {
            return $arabic;
        }
        if ($english !== '') {
            return $english;
        }

        return isset($booking[$fallbackField])
            ? trim((string) $booking[$fallbackField])
            : '';
    }

    public function reviewForBooking($bookingId)
    {
        if (!$this->db->table_exists(self::TABLE)) {
            return array();
        }

        return $this->db
            ->where('tour_review_booking_id', (int) $bookingId)
            ->get(self::TABLE)
            ->row_array();
    }

    public function isEligible(array $booking)
    {
        if (empty($booking) || $booking['book_status'] !== 'Completed') {
            return false;
        }

        $end = $this->bookingEndTimestamp($booking);

        return $end !== false && $end < time();
    }

    public function bookingEndTimestamp(array $booking)
    {
        $date = isset($booking['book_date']) ? trim((string) $booking['book_date']) : '';
        $endTime = isset($booking['book_slot_end_time'])
            ? trim((string) $booking['book_slot_end_time'])
            : '';
        $startTime = isset($booking['book_slot_start_time'])
            ? trim((string) $booking['book_slot_start_time'])
            : '';

        if ($date === '') {
            return false;
        }

        $end = strtotime($date . ' ' . ($endTime !== '' ? $endTime : '23:59:59'));
        if ($end === false) {
            return false;
        }

        if ($startTime !== '' && $endTime !== '' && $endTime <= $startTime) {
            $end = strtotime('+1 day', $end);
        }

        return $end;
    }

    public function token(array $booking)
    {
        $secret = (string) $this->config->item('encryption_key');
        $payload = 'tour-review|' . (int) $booking['book_id'] . '|'
            . (string) $booking['book_res_code'];

        return hash_hmac('sha256', $payload, $secret);
    }

    public function validToken(array $booking, $token)
    {
        return is_string($token)
            && preg_match('/^[a-f0-9]{64}$/D', $token)
            && hash_equals($this->token($booking), strtolower($token));
    }

    public function reviewUrl(array $booking, $locale = 'en')
    {
        $locale = $locale === 'ar' ? 'ar' : 'en';

        return base_url(
            $locale . '/review/' . (int) $booking['book_id'] . '/' . $this->token($booking)
        );
    }

    public function save(array $booking, array $values)
    {
        if (!$this->db->table_exists(self::TABLE)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $data = array(
            'tour_review_booking_id' => (int) $booking['book_id'],
            'tour_review_tour_id' => (int) $booking['book_tour_id'],
            'tour_review_guide_id' => !empty($booking['book_tour_guide_id'])
                ? (int) $booking['book_tour_guide_id']
                : null,
            'tour_review_customer_name' => (string) $booking['book_name'],
            'tour_review_customer_email' => (string) $booking['book_email'],
            'tour_review_tour_name' => (string) $booking['book_tour_name'],
            'tour_review_tour_type' => trim((string) $booking['tour_type']) === 'Tour'
                ? 'Tour'
                : 'Experience',
            'tour_review_guide_name' => !empty($booking['book_tour_guide_name'])
                ? (string) $booking['book_tour_guide_name']
                : null,
            'tour_review_overall_rating' => (int) $values['overall_rating'],
            'tour_review_activity_rating' => (int) $values['activity_rating'],
            'tour_review_guide_rating' => trim((string) $booking['tour_type']) === 'Tour'
                ? (int) $values['guide_rating']
                : null,
            'tour_review_vehicle_rating' => (int) $values['vehicle_rating'],
            'tour_review_driver_rating' => (int) $values['driver_rating'],
            'tour_review_service_rating' => (int) $values['service_rating'],
            'tour_review_comments' => (string) $values['comments'],
            'tour_review_public_consent' => !empty($values['public_consent']) ? 1 : 0,
            'tour_review_locale' => $values['locale'] === 'ar' ? 'ar' : 'en',
            'tour_review_status' => 'New',
            'tour_review_ip' => substr((string) $this->input->ip_address(), 0, 45),
            'tour_review_user_agent' => substr((string) $this->input->user_agent(), 0, 255),
            'tour_review_added' => $now,
            'tour_review_updated' => $now,
        );

        return $this->db->insert(self::TABLE, $data);
    }
}
