<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_model extends CI_Model
{
    /** Trusted provider data set only by completeVerifiedPayment(). */
    private $verifiedPaymentContext = null;

    private function reference($value, $type)
    {
        $decoded = is_string($value) && strlen($value) < 2048
            ? $this->encryption->decrypt($value)
            : false;
        return is_string($decoded) && preg_match('/^' . $type . ':([1-9][0-9]*)$/D', $decoded, $match)
            ? (int) $match[1]
            : 0;
    }

    private function assigned($rows, $column, $id)
    {
        foreach ($rows as $row) {
            if ((int) $row[$column] === $id) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Customer identity for discount usage limits: the booking email, trimmed
     * and lowercased. Centralized here so every usage count (apply, review,
     * final payment) agrees on what "the same customer" means.
     */
    private function normalizeDiscountEmail($email)
    {
        return mb_strtolower(trim((string) $email));
    }

    /** Discount codes are 3-40 uppercase ASCII letters/digits, matching the admin rules. */
    private function normalizeDiscountCode($code)
    {
        if (!is_string($code) || strlen($code) > 200) {
            return false;
        }
        $normalized = strtoupper(trim($code));
        return preg_match('/^[A-Z0-9]{3,40}$/D', $normalized) ? $normalized : false;
    }

    private function fetchDiscountCode($code, $lock)
    {
        $sql = 'SELECT d.*, r.ref_status, r.ref_name, r.ref_name_ar FROM discount_codes d '
            . 'INNER JOIN referrals r ON r.ref_id = d.discount_ref_id WHERE d.discount_code = ?'
            . ($lock ? ' FOR UPDATE' : '');
        $query = $this->db->query($sql, array($code));
        return $query ? $query->row_array() : null;
    }

    /** Successful redemptions: Completed bookings, plus any legacy Consumed rows. */
    private function countDiscountUses($discountId, $lock = false)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM tour_bookings WHERE book_promo_id = ? "
            . "AND book_status IN ('Completed','Consumed')" . ($lock ? ' FOR UPDATE' : '');
        $query = $this->db->query($sql, array($discountId));
        $row = $query ? $query->row_array() : null;
        return $row ? (int) $row['cnt'] : 0;
    }

    /** Enforces DISCOUNT_CODE_USE_BY_ONE_CUSTOMER: same code, same normalized email. */
    private function countDiscountUsesByCustomer($discountId, $normalizedEmail, $lock = false)
    {
        $sql = "SELECT COUNT(*) AS cnt FROM tour_bookings WHERE book_promo_id = ? "
            . "AND book_status IN ('Completed','Consumed') AND LOWER(TRIM(book_email)) = ?"
            . ($lock ? ' FOR UPDATE' : '');
        $query = $this->db->query($sql, array($discountId, $normalizedEmail));
        $row = $query ? $query->row_array() : null;
        return $row ? (int) $row['cnt'] : 0;
    }

    /**
     * Binary eligibility only (status, expiry, referral, structural limits,
     * global/per-customer usage) — never recomputes the discounted amount, so
     * an admin edit to the code's rate cannot change an already-reviewed price.
     * When $lock is true, the caller already holds (or is taking) a row lock
     * on discount_codes and the usage counts read the latest committed data.
     */
    private function discountEligibility($discount, $normalizedEmail, $lock)
    {
        if (!$discount) {
            return array('valid' => false, 'error' => 'invalid');
        }
        if ($discount['discount_status'] !== 'Enable' || $discount['ref_status'] !== 'Enable') {
            return array('valid' => false, 'error' => 'unavailable');
        }
        $today = date('Y-m-d');
        if (empty($discount['discount_expiry'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', (string) $discount['discount_expiry'])
            || $discount['discount_expiry'] < $today) {
            return array('valid' => false, 'error' => 'expired');
        }
        if (!in_array($discount['discount_type'], array('Fixed Amount', 'Percentage'), true)
            || !in_array($discount['discount_ref_commission_type'], array('Fixed Amount', 'Percentage'), true)) {
            return array('valid' => false, 'error' => 'unavailable');
        }
        $value = (float) $discount['discount_value'];
        $commissionValue = (float) $discount['discount_ref_commission'];
        $valueRange = $discount['discount_type'] === 'Percentage'
            ? array(DISCOUNT_VALUE_MIN_PERCENTAGE, DISCOUNT_VALUE_MAX_PERCENTAGE)
            : array(DISCOUNT_VALUE_MIN_FIXED, DISCOUNT_VALUE_MAX_FIXED);
        $commissionRange = $discount['discount_ref_commission_type'] === 'Percentage'
            ? array(DISCOUNT_REF_COMMISSION_MIN_PERCENTAGE, DISCOUNT_REF_COMMISSION_MAX_PERCENTAGE)
            : array(DISCOUNT_REF_COMMISSION_MIN_FIXED, DISCOUNT_REF_COMMISSION_MAX_FIXED);
        if ($value < $valueRange[0] || $value > $valueRange[1]
            || $commissionValue < $commissionRange[0] || $commissionValue > $commissionRange[1]) {
            return array('valid' => false, 'error' => 'unavailable');
        }
        $maxUses = $discount['discount_no_of_uses'];
        $hasLimit = $maxUses !== null && $maxUses !== '' && (string) $maxUses !== '0';
        if ($hasLimit) {
            if (!ctype_digit((string) $maxUses)) {
                return array('valid' => false, 'error' => 'unavailable');
            }
            if ($this->countDiscountUses((int) $discount['discount_id'], $lock) >= (int) $maxUses) {
                return array('valid' => false, 'error' => 'exhausted');
            }
        }
        if ($normalizedEmail !== ''
            && $this->countDiscountUsesByCustomer((int) $discount['discount_id'], $normalizedEmail, $lock) >= DISCOUNT_CODE_USE_BY_ONE_CUSTOMER) {
            return array('valid' => false, 'error' => 'customer_limit');
        }
        return array('valid' => true);
    }

    /**
     * Deterministic whole-currency-unit money math (no fractional/cents
     * storage) so percentage/fixed discounts and referral commissions round
     * the same way everywhere they are computed (preview, review, payment).
     */
    private function calculateDiscount($originalTotal, $discountType, $discountValue, $commissionType, $commissionValue)
    {
        $original = (int) round((float) $originalTotal);
        $discount = $discountType === 'Percentage'
            ? (int) round($original * $discountValue / 100)
            : (int) round($discountValue);
        $discount = max(0, min($discount, $original));
        $final = $original - $discount;
        $commission = $commissionType === 'Percentage'
            ? (int) round($final * $commissionValue / 100)
            : (int) round($commissionValue);
        $commission = max(0, $commission);
        return array(
            'original_total' => $original,
            'discount_amount' => $discount,
            'final_total' => $final,
            'commission_base' => $final,
            'commission_amount' => $commission,
        );
    }

    /**
     * Full evaluate + price: used by the discount preview/apply endpoint and
     * by Review acceptance, where the code's current live terms are what the
     * customer is agreeing to. Not used to re-check an already-accepted
     * booking — see revalidateAppliedDiscount().
     */
    public function evaluateDiscountCode($rawCode, $originalTotal, $email, $lock = false)
    {
        $code = $this->normalizeDiscountCode($rawCode);
        if ($code === false) {
            return array('valid' => false, 'error' => 'invalid');
        }
        $discount = $this->fetchDiscountCode($code, $lock);
        $eligibility = $this->discountEligibility($discount, $this->normalizeDiscountEmail($email), $lock);
        if (!$eligibility['valid']) {
            return $eligibility;
        }
        // The booking snapshot columns store whole currency units, so the code's
        // percentage/fixed terms are rounded once here and that same rounded
        // value is used both for the discount math and for the stored snapshot.
        $discountValue = (int) round((float) $discount['discount_value']);
        $refCommissionValue = (int) round((float) $discount['discount_ref_commission']);
        $pricing = $this->calculateDiscount(
            $originalTotal,
            $discount['discount_type'],
            $discountValue,
            $discount['discount_ref_commission_type'],
            $refCommissionValue
        );
        return array(
            'valid' => true,
            'discount_id' => (int) $discount['discount_id'],
            'code' => $code,
            'name' => $discount['discount_name'],
            'name_ar' => $discount['discount_name_ar'],
            'type' => $discount['discount_type'],
            'value' => $discountValue,
            'ref_id' => (int) $discount['discount_ref_id'],
            'ref_name' => $discount['ref_name'],
            'ref_commission_type' => $discount['discount_ref_commission_type'],
            'ref_commission_value' => $refCommissionValue,
        ) + $pricing;
    }

    /**
     * Structural re-check only, used inside the final payment transaction.
     * It never recomputes the discount amount or commission — those stay
     * frozen at whatever Review acceptance saved — it only confirms the code,
     * referral and usage limits are still in good standing right now.
     */
    private function revalidateAppliedDiscount($discountId, $code, $email, $lock)
    {
        $normalizedCode = $this->normalizeDiscountCode($code);
        if ($normalizedCode === false) {
            return array('valid' => false, 'error' => 'invalid');
        }
        $discount = $this->fetchDiscountCode($normalizedCode, $lock);
        if (!$discount || (int) $discount['discount_id'] !== (int) $discountId) {
            return array('valid' => false, 'error' => 'invalid');
        }
        return $this->discountEligibility($discount, $this->normalizeDiscountEmail($email), $lock);
    }

    /** True once the discount snapshot columns exist (see db/discount-code-booking-migration.sql). */
    public function discountFieldsAvailable()
    {
        return $this->db->field_exists('book_original_total', 'tour_bookings')
            && $this->db->field_exists('book_discount_amount', 'tour_bookings')
            && $this->db->field_exists('book_ref_id', 'tour_bookings');
    }

    /**
     * Same booking-token and booking_id ownership check saveBooking() uses,
     * exposed for the discount preview/apply endpoint, which never writes.
     */
    public function findBookingByToken($token, $bookingIdRef)
    {
        $this->load->library('encryption');
        $existing = $this->db->where('book_submission_token', $token)->get('tour_bookings')->row_array();
        if (!$existing) {
            return null;
        }
        $bookingId = $this->reference($bookingIdRef, 'booking');
        if ($bookingId && $bookingId !== (int) $existing['book_id']) {
            return null;
        }
        return $existing;
    }

    /** No code applied: the zeroed snapshot shared by save() and remove(). */
    private function clearedDiscountSnapshot($originalTotal)
    {
        return array(
            'book_promo_id' => 0,
            'book_promo_code' => '',
            'book_original_total' => $originalTotal,
            'book_discount_amount' => 0,
            'book_discount_type' => null,
            'book_discount_value' => null,
            'book_discount_name' => null,
            'book_discount_name_ar' => null,
            'book_ref_id' => null,
            'book_ref_name' => null,
            'book_ref_commission' => 0,
            'book_ref_commission_type' => null,
            'book_ref_commission_value' => null,
            'book_ref_commission_base' => null,
        );
    }

    /**
     * Apply or replace the promo code on a Pending booking. Deliberately
     * separate from save(): it never submits the booking, never requires
     * terms, never advances the wizard and never marks Review complete —
     * it only updates the discount snapshot on the row that is already
     * there. Also doubles as the Review-lock recovery path: mutating the
     * code always clears an existing book_terms_accepted_at, so an
     * already-accepted Review must be accepted again with the new terms.
     */
    public function applyDiscountCode($token, $bookingIdRef, $rawCode)
    {
        return $this->mutateDiscountCode($token, $bookingIdRef, $rawCode);
    }

    /** Clears the applied promo code and restores the undiscounted total. */
    public function removeDiscountCode($token, $bookingIdRef)
    {
        return $this->mutateDiscountCode($token, $bookingIdRef, '');
    }

    private function mutateDiscountCode($token, $bookingIdRef, $rawCode)
    {
        if (!$this->discountFieldsAvailable()) {
            return array('success' => false, 'error' => 'unavailable');
        }
        $existing = $this->findBookingByToken($token, $bookingIdRef);
        if (!$existing || $existing['book_status'] !== 'Pending') {
            return array('success' => false, 'error' => 'expired');
        }
        $removing = trim((string) $rawCode) === '';
        $now = date('Y-m-d H:i:s');
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();
        $locked = $this->db->query('SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE', array($existing['book_id']));
        $current = $locked ? $locked->row_array() : null;
        if (!$current || $current['book_status'] !== 'Pending') {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            return array('success' => false, 'error' => 'expired');
        }
        $originalTotal = (int) $current['book_original_total'];
        $priced = $originalTotal > 0;
        if ($removing) {
            $data = $this->clearedDiscountSnapshot($originalTotal);
        } else {
            $evaluation = $this->evaluateDiscountCode($rawCode, $priced ? $originalTotal : 0, $current['book_email']);
            if (!$evaluation['valid']) {
                $this->db->trans_rollback();
                $this->db->db_debug = $debug;
                return array('success' => false, 'error' => $evaluation['error']);
            }
            $data = array(
                'book_promo_id' => $evaluation['discount_id'],
                'book_promo_code' => $evaluation['code'],
                'book_original_total' => $originalTotal,
                'book_discount_amount' => $priced ? $evaluation['discount_amount'] : 0,
                'book_discount_type' => $evaluation['type'],
                'book_discount_value' => $evaluation['value'],
                'book_discount_name' => $evaluation['name'],
                'book_discount_name_ar' => $evaluation['name_ar'],
                'book_ref_id' => $evaluation['ref_id'],
                'book_ref_name' => $evaluation['ref_name'],
                'book_ref_commission' => $priced ? $evaluation['commission_amount'] : 0,
                'book_ref_commission_type' => $evaluation['ref_commission_type'],
                'book_ref_commission_value' => $evaluation['ref_commission_value'],
                'book_ref_commission_base' => $priced ? $evaluation['commission_base'] : null,
            );
        }
        // Tax is charged after the discount, at the rate saved with the booking's price.
        $this->load->library('tour_pricing');
        $taxPercent = (float) $current['book_tax_percent'];
        if ($priced) {
            $net = (int) round($originalTotal - $data['book_discount_amount']);
            $data['book_tax_amount'] = $this->tour_pricing->percentAmount($net, $taxPercent);
            $data['book_fee'] = $net + $data['book_tax_amount'];
        } else {
            $data['book_fee'] = (int) $current['book_fee'];
        }
        $customerAmounts = $this->tour_pricing->customerAmounts(
            $originalTotal,
            $data['book_discount_amount'],
            $taxPercent
        );
        $data['book_updated'] = $now;
        $reviewWasLocked = !empty($current['book_terms_accepted_at']);
        if ($reviewWasLocked) {
            $data['book_terms_accepted_at'] = null;
        }
        $saved = $this->db->where('book_id', $current['book_id'])->update('tour_bookings', $data);
        if (!$saved || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            return array('success' => false, 'error' => 'unavailable');
        }
        $committed = $this->db->trans_commit();
        $this->db->db_debug = $debug;
        if (!$committed) {
            return array('success' => false, 'error' => 'unavailable');
        }
        return array(
            'success' => true,
            'code' => $data['book_promo_code'],
            'name' => $data['book_discount_name'],
            'name_ar' => $data['book_discount_name_ar'],
            'type' => $data['book_discount_type'],
            'value' => $data['book_discount_value'],
            'original_total' => $customerAmounts['original'],
            'discount_amount' => $customerAmounts['discount'],
            'total' => $data['book_fee'],
            'incomplete' => !$priced,
            'review_reset' => $reviewWasLocked,
        );
    }

    /**
     * Encrypted snapshot of a place Google verified for the pickup field
     * (Frontend::booking_place()). The booking form posts this token rather than
     * raw place fields, so a visitor cannot store coordinates that never came from
     * a Places lookup.
     */
    public function placeToken(array $place)
    {
        $this->load->library('encryption');

        return $this->encryption->encrypt(json_encode(array(
            'place_id' => (string) $place['place_id'],
            'address' => (string) $place['address'],
            'latitude' => (float) $place['latitude'],
            'longitude' => (float) $place['longitude'],
        )));
    }

    /**
     * tour_bookings place columns for a posted pickup_place_token. An empty or
     * unreadable token (free-text pickup, or the text was edited after picking a
     * suggestion) clears the columns. Returns nothing while the columns have not
     * been added to the database yet.
     */
    private function placeColumns($token)
    {
        foreach (array('book_place_id', 'book_complete_address', 'book_latitude', 'book_longitude') as $field) {
            if (!$this->db->field_exists($field, 'tour_bookings')) {
                return array();
            }
        }

        $columns = array(
            'book_place_id' => null,
            'book_complete_address' => null,
            'book_latitude' => null,
            'book_longitude' => null,
        );
        if (!is_string($token) || $token === '' || strlen($token) > 2048) {
            return $columns;
        }

        $this->load->library('encryption');
        $decoded = $this->encryption->decrypt($token);
        $place = is_string($decoded) ? json_decode($decoded, true) : null;
        if (!is_array($place)
            || !isset($place['place_id'], $place['address'], $place['latitude'], $place['longitude'])
            || !is_string($place['place_id'])
            || !is_string($place['address'])
            || !is_numeric($place['latitude'])
            || !is_numeric($place['longitude'])) {
            return $columns;
        }

        return array(
            'book_place_id' => substr($place['place_id'], 0, 255),
            'book_complete_address' => mb_substr($place['address'], 0, 500),
            'book_latitude' => round((float) $place['latitude'], 7),
            'book_longitude' => round((float) $place['longitude'], 7),
        );
    }

    public function save($values, $locale, $currency)
    {
        $this->load->library('encryption');
        $this->load->model('Tour_model');
        if (
            !$this->db->field_exists('book_submission_token', 'tour_bookings')
            || !$this->db->field_exists('steps_completed', 'tour_bookings')
        ) {
            return array('success' => false, 'error' => 'unavailable');
        }
        $step = (int) $values['booking_step'];
        $bookingId = $this->reference($values['booking_id'], 'booking');
        $existing = $this->db->where('book_submission_token', $values['booking_token'])
            ->get('tour_bookings')->row_array();
        if ($existing && (($step !== 1 && $bookingId !== (int) $existing['book_id'])
            || ($bookingId && $bookingId !== (int) $existing['book_id']))) {
            return array('success' => false, 'error' => 'expired');
        }
        if (!$existing && $step !== 1) {
            return array('success' => false, 'error' => 'expired');
        }
        if ($existing && $existing['book_status'] !== 'Pending') {
            return $step === 6 && $existing['book_status'] === 'Completed'
                ? $this->result($existing)
                : array('success' => false, 'error' => 'expired');
        }
        // Accepted review details are immutable, including through direct POST requests. Applying or
        // removing a promo code (Booking_model::applyDiscountCode()/removeDiscountCode()) is the only
        // thing that reopens an accepted Review — it clears book_terms_accepted_at directly.
        if ($existing && !empty($existing['book_terms_accepted_at']) && $step !== 6) {
            return $step === 5
                ? $this->result($existing)
                : array('success' => false, 'error' => 'expired');
        }
        if ($step === 6) {
            if (empty($existing['book_terms_accepted_at'])
                || !is_array($this->verifiedPaymentContext)
            ) {
                return array('success' => false, 'error' => 'payment');
            }
            foreach (array('book_payment_method', 'book_paid_amount', 'book_payment_date', 'book_transaction_id', 'book_payer_email') as $field) {
                if (!$this->db->field_exists($field, 'tour_bookings')) {
                    return array('success' => false, 'error' => 'unavailable');
                }
            }
            foreach (array(
                'tour' => 'tour_id',
                'vehicle' => 'vehicle_id',
                'slot' => 'slot_id',
                'guide' => 'tour_guide_id',
                'language' => 'lang_id',
            ) as $type => $column) {
                $field = $type === 'language' ? 'language' : $type . '_id';
                $values[$field] = $this->encryption->encrypt($type . ':' . (int) $existing['book_' . $column]);
            }
            foreach (array(
                'full_name' => 'name',
                'email' => 'email',
                'mobile' => 'phone',
                'pickup_location' => 'address',
                'country' => 'country_name',
                'guests' => 'guests',
                'tour_date' => 'date',
                'notes' => 'notes',
            ) as $field => $column) {
                $values[$field] = (string) $existing['book_' . $column];
            }
        }
        $tourId = $this->reference($values['tour_id'], 'tour');
        $vehicleId = $this->reference($values['vehicle_id'], 'vehicle');
        $slotId = $this->reference($values['slot_id'], 'slot');
        $guideId = $this->reference($values['guide_id'], 'guide');
        $languageId = $this->reference($values['language'], 'language');
        $tour = $this->db->where('tour_id', $tourId)->where('tour_status', 'Enable')->get('tours')->row_array();
        if (!$tour) {
            return array('success' => false, 'error' => 'preferences');
        }
        $experience = trim($tour['tour_type']) === 'Experience';
        if ($step === 1) {
            return $this->saveDetails($values, $tour, $locale, $existing);
        }
        $vehicle = $this->assigned($this->Tour_model->get_tour_vehicles($tourId, $locale), 'vehicle_id', $vehicleId);
        $slot = $this->assigned($this->Tour_model->get_tour_slots($tourId, $locale), 'slot_id', $slotId);
        $guests = (int) $values['guests'];
        $date = $values['tour_date'];
        if (!$vehicle || !$slot || !preg_match('/^[1-9][0-9]*$/D', $values['guests'])
            || $guests > (int) $vehicle['vehicle_max_capacity'] - ($experience ? 1 : 2)
            || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            || $date <= date('Y-m-d')
            || $date > date('Y-m-t', strtotime('+11 months', strtotime(date('Y-m-01'))))) {
            return array('success' => false, 'error' => 'preferences');
        }
        $hours = (int) $slot['slot_hours'];
        if (!in_array($hours, array(2, 4, 6, 8), true) || (float) $tour['tour_price_' . $hours] <= 0) {
            return array('success' => false, 'error' => 'preferences');
        }
        $guide = null;
        $language = null;
        if (!$experience && $step >= 4) {
            $guide = $this->assigned($this->Tour_model->get_tour_guides($tourId, $locale), 'tour_guide_id', $guideId);
            $language = $guide ? $this->assigned($guide['booking_languages'], 'lang_id', $languageId) : null;
            if (!$guide || !$language) {
                return array('success' => false, 'error' => 'preferences');
            }
        }
        if (!$experience && $step >= 3 && !$language) {
            foreach ($this->Tour_model->get_tour_guides($tourId, $locale) as $assignedGuide) {
                $language = $this->assigned($assignedGuide['booking_languages'], 'lang_id', $languageId);
                if ($language) {
                    break;
                }
            }
            if (!$language) {
                return array('success' => false, 'error' => 'preferences');
            }
        }
        $tourPrice = (int) round((float) $tour['tour_price_' . $hours]);
        $guidePrice = $experience ? 0 : (int) round((float) $tour[($languageId === 1 ? 'tour_arabic_guide_price_' : 'tour_english_guide_price_') . $hours]);
        $vehiclePrice = (int) round((float) $vehicle['vehicle_price_' . $hours]);
        $meals = (int) round((float) $vehicle['vehicle_meals_' . $hours]);
        if (min($tourPrice, $guidePrice, $vehiclePrice, $meals) < 0) {
            return array('success' => false, 'error' => 'preferences');
        }
        // Tour price and meals are per person; guide and vehicle are per booking.
        // Profit is added to the subtotal, a discount comes off that, and tax is
        // charged on what remains (see Tour_pricing).
        $this->load->library('tour_pricing');
        $rates = $this->tour_pricing->rates();
        $tourTotal = $tourPrice * $guests;
        $mealsTotal = $meals * $guests;
        $subtotal = $tourTotal + $guidePrice + $vehiclePrice + $mealsTotal;
        $profitAmount = $this->tour_pricing->percentAmount($subtotal, $rates['profit']);
        $total = $subtotal + $profitAmount;
        /* Promo codes are applied/removed only through applyDiscountCode()/removeDiscountCode(),
           never through this step save. A code already on the row is carried forward and its
           discount recalculated against the freshly computed total above, so guests/vehicle/slot
           changes always reprice it. If it is no longer eligible, the code stays visible (so the
           customer can see and remove/replace it) but contributes zero discount; Review (step 5)
           refuses to accept while an applied code is ineligible. */
        $discountSnapshot = array();
        $existingPromoId = (int) $existing['book_promo_id'];
        if ($this->discountFieldsAvailable()) {
            if ($existingPromoId > 0) {
                $recheck = $this->evaluateDiscountCode($existing['book_promo_code'], $total, $values['email']);
                if ($recheck['valid']) {
                    $discountSnapshot = array(
                        'book_promo_id' => $recheck['discount_id'],
                        'book_promo_code' => $recheck['code'],
                        'book_original_total' => $recheck['original_total'],
                        'book_discount_amount' => $recheck['discount_amount'],
                        'book_discount_type' => $recheck['type'],
                        'book_discount_value' => $recheck['value'],
                        'book_discount_name' => $recheck['name'],
                        'book_discount_name_ar' => $recheck['name_ar'],
                        'book_ref_id' => $recheck['ref_id'],
                        'book_ref_name' => $recheck['ref_name'],
                        'book_ref_commission' => $recheck['commission_amount'],
                        'book_ref_commission_type' => $recheck['ref_commission_type'],
                        'book_ref_commission_value' => $recheck['ref_commission_value'],
                        'book_ref_commission_base' => $recheck['commission_base'],
                    );
                    $total = $recheck['final_total'];
                } elseif ($step === 5) {
                    return array('success' => false, 'error' => 'discount_' . $recheck['error']);
                } else {
                    $discountSnapshot = array(
                        'book_promo_id' => $existingPromoId,
                        'book_promo_code' => $existing['book_promo_code'],
                        'book_original_total' => $total,
                        'book_discount_amount' => 0,
                        'book_discount_type' => $existing['book_discount_type'],
                        'book_discount_value' => $existing['book_discount_value'],
                        'book_discount_name' => $existing['book_discount_name'],
                        'book_discount_name_ar' => $existing['book_discount_name_ar'],
                        'book_ref_id' => $existing['book_ref_id'],
                        'book_ref_name' => $existing['book_ref_name'],
                        'book_ref_commission' => 0,
                        'book_ref_commission_type' => $existing['book_ref_commission_type'],
                        'book_ref_commission_value' => $existing['book_ref_commission_value'],
                        'book_ref_commission_base' => null,
                    );
                }
            } else {
                $discountSnapshot = $this->clearedDiscountSnapshot($total);
            }
        }
        $taxAmount = $this->tour_pricing->percentAmount($total, $rates['tax']);
        $total += $taxAmount;
        $country = $this->db->where('name', strtoupper($values['country']))->get('countries')->row_array();
        $now = date('Y-m-d H:i:s');
        $reference = $existing['book_res_code'];
        $data = array(
            'book_name' => $values['full_name'],
            'book_email' => $values['email'],
            'book_address' => $values['pickup_location'],
            'book_phone' => $values['mobile'],
            'book_date' => $date,
            'book_added' => $now,
            'book_updated' => $now,
            'book_ip' => $this->input->ip_address(),
            'book_user_agent' => substr((string) $this->input->user_agent(), 0, 255),
            'book_tour_id' => $tourId,
            'book_vehicle_id' => $vehicleId,
            'book_slot_id' => $slotId,
            'book_tour_guide_id' => $guide ? $guideId : null,
            'book_lang_id' => $language ? $languageId : null,
            'book_country_id' => $country ? (int) $country['id'] : null,
            'book_country_name' => $values['country'],
            'book_tour_name' => $locale === 'ar' && !empty($tour['tour_name_ar']) ? $tour['tour_name_ar'] : $tour['tour_name'],
            'book_vehicle_name' => $vehicle['vehicle_name'],
            'book_slot_name' => $slot['slot_name'],
            'book_tour_guide_name' => $guide ? $guide['tour_guide_name'] : null,
            'book_lang_name' => $language ? $language['lang_name'] : null,
            'book_lang' => $locale === 'ar' ? 'Arabic' : 'English',
            'book_status' => $step === 6 ? 'Completed' : 'Pending',
            'book_fee' => $total,
            'book_res_code' => $reference,
            'book_submission_token' => $values['booking_token'],
            'book_guests' => $guests,
            'book_notes' => $values['notes'],
            'book_slot_hours' => $hours,
            'book_slot_start_time' => $slot['slot_start_time'],
            'book_slot_end_time' => $slot['slot_end_time'],
            'book_tour_price' => $tourPrice,
            'book_tour_total' => $tourTotal,
            'book_guide_price' => $guidePrice,
            'book_vehicle_price' => $vehiclePrice,
            'book_meals_per_guest' => $meals,
            'book_meals_total' => $mealsTotal,
            'book_profit_percent' => $rates['profit'],
            'book_profit_amount' => $profitAmount,
            'book_tax_percent' => $rates['tax'],
            'book_tax_amount' => $taxAmount,
            'book_currency' => $currency,
            'book_terms_accepted_at' => $step >= 5 ? ($existing['book_terms_accepted_at'] ?: $now) : null,
        ) + $discountSnapshot;
        // Payment (step 6) replays the stored details, so the stored place must not be overwritten.
        if ($step !== 6) {
            $data += $this->placeColumns($values['pickup_place_token']);
        }
        // The optional WhatsApp opt-in is answered on Review (step 5), next to the terms. Keep the
        // first consent time when Review is accepted again; unticking it withdraws consent.
        if ($step === 5
            && WHATSAPP_BOOKING_ENABLED
            && $this->db->field_exists('book_whatsapp_consent_at', 'tour_bookings')
        ) {
            $data['book_whatsapp_consent_at'] = isset($values['whatsapp_consent']) && $values['whatsapp_consent'] === '1'
                ? (!empty($existing['book_whatsapp_consent_at']) ? $existing['book_whatsapp_consent_at'] : $now)
                : null;
        }
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();
        $locked = $this->db->query('SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE', array($existing['book_id']));
        $current = $locked ? $locked->row_array() : null;
        if (!$current || $current['book_status'] !== 'Pending') {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            return $current && $step === 6 && $current['book_status'] === 'Completed'
                ? $this->result($current)
                : array('success' => false, 'error' => 'expired');
        }
        if ((!empty($current['book_terms_accepted_at']) && $step !== 6)
            || ($step === 6 && empty($current['book_terms_accepted_at']))) {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            return array('success' => false, 'error' => 'expired');
        }
        // Serialize final redemptions on the discount row, ahead of the guide/availability locks below.
        if ($step === 6 && (int) $current['book_promo_id'] > 0) {
            $recheck = $this->revalidateAppliedDiscount(
                (int) $current['book_promo_id'],
                $current['book_promo_code'],
                $current['book_email'],
                true
            );
            if (!$recheck['valid']) {
                $this->db->trans_rollback();
                $this->db->db_debug = $debug;
                return array('success' => false, 'error' => 'discount_' . $recheck['error']);
            }
        }
        // Lock both the old and new guides before releasing legacy holds or reserving.
        $lockGuides = array_filter(array_unique(array((int) $current['book_tour_guide_id'], $guideId)));
        sort($lockGuides);
        foreach ($lockGuides as $lockGuide) {
            $this->db->query('SELECT tour_guide_id FROM tour_guides WHERE tour_guide_id = ? FOR UPDATE', array($lockGuide));
        }
        $this->releaseBookingHolds($existing['book_id']);
        $availability = null;
        if (!$experience) {
            $guideIds = array_column($this->Tour_model->get_tour_guides($tourId, $locale), 'tour_guide_id');
            if ($step >= 4) {
                // The guide row serializes final submissions across all slots and tours.
                $this->db->query('SELECT tour_guide_id FROM tour_guides WHERE tour_guide_id = ? FOR UPDATE', array($guideId));
                $guideIds = array($guideId);
            }
            $available = $this->Tour_model->get_booking_availability($guideIds, $date, $date);
            $matches = $this->assigned($available, 'avail_slot_id', $slotId);
            if (!$matches) {
                $this->db->trans_rollback();
                $this->db->db_debug = $debug;
                return array('success' => false, 'error' => 'preferences');
            }
            if ($step >= 4) {
                $dailyQuery = $this->db->query(
                    'SELECT * FROM tour_guide_availability WHERE avail_tour_guide_id = ? AND avail_date = ? ORDER BY avail_id FOR UPDATE',
                    array($guideId, $date)
                );
                $daily = $dailyQuery ? $dailyQuery->result_array() : array();
                foreach ($daily as $row) {
                    if (in_array($row['avail_book_status'], array('Reserved', 'On-hold'), true)) {
                        $this->db->trans_rollback();
                        $this->db->db_debug = $debug;
                        return array('success' => false, 'error' => 'preferences');
                    }
                    if ((int) $row['avail_slot_id'] === $slotId && $row['avail_status'] === 'Enable'
                        && $row['avail_book_status'] === 'Available') {
                        $availability = $row;
                    }
                }
                if (!$availability) {
                    $this->db->trans_rollback();
                    $this->db->db_debug = $debug;
                    return array('success' => false, 'error' => 'preferences');
                }
            }
        }
        $data['book_avail_id'] = $availability ? $availability['avail_id'] : null;
        unset($data['book_added'], $data['book_submission_token']);
        if ($step === 2) {
            $allowed = array('book_date', 'book_slot_id', 'book_slot_name', 'book_slot_hours',
                'book_slot_start_time', 'book_slot_end_time', 'book_updated');
            $data = array_intersect_key($data, array_flip($allowed));
            // Changing the schedule invalidates the previous guide selection.
            $data['book_tour_guide_id'] = null;
            $data['book_tour_guide_name'] = null;
            $data['book_avail_id'] = null;
        }
        if ($step === 6) {
            // Payment uses the reviewed amount and snapshot, never resubmitted details.
            $total = (int) $current['book_fee'];
            $data = array(
                'book_status' => 'Completed',
                'book_updated' => $now,
                'book_avail_id' => $availability ? $availability['avail_id'] : null,
                'book_paid_amount' => $total,
                'book_payment_method' => $this->verifiedPaymentContext['payment_method'],
                'book_payment_date' => $this->verifiedPaymentContext['paid_at'],
                'book_transaction_id' => $this->verifiedPaymentContext['provider_payment_id'],
                'book_payer_email' => $this->verifiedPaymentContext['payer_email'],
            );
        }
        // Experiences skip the guide step, so review/payment are completed steps 4/5.
        // Use the locked row so editing earlier steps cannot reduce saved progress.
        $data['steps_completed'] = max(
            (int) $current['steps_completed'],
            $experience && $step >= 5 ? $step - 1 : $step
        );
        $saved = $this->db->where('book_id', $existing['book_id'])->update('tour_bookings', $data);
        if ($saved && $step === 5 && $availability) {
            // Completing Review holds the guide for the whole day (one tour per guide per day).
            // Every slot carries this booking's ID so it can be released together; the holds
            // above were already released, and manually Unavailable slots are not touched.
            $this->db->where('avail_tour_guide_id', $guideId)->where('avail_date', $date)
                ->where('avail_book_status', 'Available')
                ->update('tour_guide_availability', array(
                    'avail_book_status' => 'On-hold',
                    'avail_book_id' => $existing['book_id'],
                    'avail_updated' => $now,
                ));
        }
        if ($saved && $step === 6 && $availability) {
            // Held slots were released above, so only slots still Available become Unavailable.
            $this->db->where('avail_tour_guide_id', $guideId)->where('avail_date', $date)
                ->where('avail_id !=', $availability['avail_id'])
                ->where('avail_book_status', 'Available')
                ->update('tour_guide_availability', array(
                    'avail_book_status' => 'Unavailable',
                    'avail_book_id' => $existing['book_id'],
                    'avail_updated' => $now,
                ));
            $this->db->where('avail_id', $availability['avail_id'])->update('tour_guide_availability', array(
                'avail_book_status' => 'Reserved',
                'avail_book_id' => $existing['book_id'],
                'avail_updated' => $now,
            ));
        }
        if (!$saved || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            log_message('error', 'Booking insert failed.');
            return array('success' => false, 'error' => 'unavailable');
        }
        $committed = $this->db->trans_commit();
        $this->db->db_debug = $debug;
        if (!$committed) {
            return array('success' => false, 'error' => 'unavailable');
        }
        $resultRow = array(
            'book_id' => $existing['book_id'],
            'book_res_code' => $reference,
            'book_fee' => $total,
            'steps_completed' => $data['steps_completed'],
        );
        // Steps that write a restricted $data (step 2) or a payment-only $data (step 6) do not carry
        // the discount snapshot, so fall back to the row already locked as $current for those.
        foreach (array(
            'book_original_total', 'book_discount_amount', 'book_promo_code',
            'book_discount_name', 'book_discount_name_ar', 'book_discount_type', 'book_discount_value',
            'book_tax_percent',
        ) as $field) {
            if (array_key_exists($field, $data)) {
                $resultRow[$field] = $data[$field];
            } elseif (array_key_exists($field, $current)) {
                $resultRow[$field] = $current[$field];
            }
        }
        $result = $this->result($resultRow);
        if ($step === 6) {
            // Controller-only metadata. Frontend::saveBooking() removes these
            // values before returning the public JSON response.
            $result['notification_stage'] = 'completed';
            $result['notification_booking_id'] = (int) $existing['book_id'];
        }

        return $result;
    }

    /**
     * Complete a booking from payment data already fetched and verified with
     * Moyasar. The trusted context never comes from Frontend request fields.
     */
    public function completeVerifiedPayment($bookingId, array $payment)
    {
        $bookingId = (int) $bookingId;
        $required = array('provider_payment_id', 'payment_method', 'payer_email', 'paid_at');
        foreach ($required as $field) {
            if (!isset($payment[$field]) || !is_string($payment[$field]) || trim($payment[$field]) === '') {
                return array('success' => false, 'error' => 'payment');
            }
        }
        if ($bookingId <= 0
            || strlen($payment['provider_payment_id']) > 255
            || strlen($payment['payment_method']) > 100
            || strlen($payment['payer_email']) > 255
            || filter_var($payment['payer_email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            return array('success' => false, 'error' => 'payment');
        }

        $booking = $this->db->where('book_id', $bookingId)->get('tour_bookings')->row_array();
        if (!$booking) {
            return array('success' => false, 'error' => 'expired');
        }
        if ($booking['book_status'] === 'Completed'
            && hash_equals((string) $booking['book_transaction_id'], $payment['provider_payment_id'])
        ) {
            return $this->result($booking);
        }
        if ($booking['book_status'] !== 'Pending') {
            return array('success' => false, 'error' => 'expired');
        }

        $this->load->library('encryption');
        $values = array(
            'booking_token' => $booking['book_submission_token'],
            'booking_id' => $this->encryption->encrypt('booking:' . $bookingId),
            'booking_step' => '6',
            'tour_id' => '',
            'vehicle_id' => '',
            'slot_id' => '',
            'guide_id' => '',
            'language' => '',
            'guests' => '',
            'tour_date' => '',
            'full_name' => '',
            'email' => '',
            'country' => '',
            'mobile' => '',
            'pickup_location' => '',
            'pickup_place_token' => '',
            'notes' => '',
            'terms' => '1',
        );
        $this->verifiedPaymentContext = array(
            'provider_payment_id' => trim($payment['provider_payment_id']),
            'payment_method' => trim($payment['payment_method']),
            'payer_email' => trim($payment['payer_email']),
            'paid_at' => date('Y-m-d H:i:s', strtotime($payment['paid_at']) ?: time()),
        );

        try {
            return $this->save(
                $values,
                $booking['book_lang'] === 'Arabic' ? 'ar' : 'en',
                $booking['book_currency']
            );
        } finally {
            $this->verifiedPaymentContext = null;
        }
    }

    private function releaseBookingHolds($bookingId)
    {
        $this->db->where('avail_book_id', (int) $bookingId)->where('avail_book_status', 'On-hold')
            ->update('tour_guide_availability', array(
                'avail_book_status' => 'Available',
                'avail_book_id' => 0,
                'avail_updated' => date('Y-m-d H:i:s'),
            ));
    }

    /**
     * Cancel Pending bookings older than TOUR_BOOKING_CUTOFF_HOURS (measured from book_added)
     * and release the guide slots they hold. Processes a small random batch per run so a
     * booking that keeps failing cannot block the others.
     *
     * @return array cancelled = bookings cancelled, released = guide slots released,
     *               cancelled_ids = IDs of the cancelled bookings
     */
    public function cancelAbandonedBookings()
    {
        $now = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', time() - TOUR_BOOKING_CUTOFF_HOURS * 60 * 60);
        $candidates = $this->db->select('b.book_id, t.tour_type')
            ->from('tour_bookings b')
            ->join('tours t', 't.tour_id = b.book_tour_id', 'inner')
            ->where('b.book_status', 'Pending')
            ->where('b.book_added <=', $cutoff)
            ->order_by('b.book_id', 'RANDOM')
            ->limit(5)
            ->get()->result_array();
        $summary = array('cancelled' => 0, 'released' => 0, 'cancelled_ids' => array());
        foreach ($candidates as $candidate) {
            $this->db->trans_begin();
            // Same lock order as the booking steps: booking first, then guide.
            $booking = $this->db->query(
                'SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE',
                array($candidate['book_id'])
            )->row_array();
            // Re-check under the lock: the customer may have just paid.
            if (!$booking || $booking['book_status'] !== 'Pending' || $booking['book_added'] > $cutoff) {
                $this->db->trans_rollback();
                continue;
            }
            if ($this->db->table_exists('tour_booking_payments')) {
                // Do not cancel while a recently prepared/initiated 3-D Secure
                // attempt is still within the normal booking cutoff window.
                $activePayment = $this->db->where('booking_id', (int) $booking['book_id'])
                    ->group_start()
                        ->group_start()
                            ->where_in('payment_status', array('prepared', 'initiated'))
                            ->where('updated_at >', $cutoff)
                        ->group_end()
                        ->or_where('payment_status', 'authorized')
                    ->group_end()
                    ->count_all_results('tour_booking_payments');
                if ($activePayment > 0) {
                    $this->db->trans_rollback();
                    continue;
                }
            }
            $released = 0;
            $slot = null;
            if (trim($candidate['tour_type']) === 'Tour' && (int) $booking['book_avail_id'] > 0) {
                $slot = $this->db->select('avail_tour_guide_id, avail_date')
                    ->where('avail_id', (int) $booking['book_avail_id'])
                    ->get('tour_guide_availability')->row_array();
            }
            if ($slot) {
                $this->db->query(
                    'SELECT tour_guide_id FROM tour_guides WHERE tour_guide_id = ? FOR UPDATE',
                    array((int) $slot['avail_tour_guide_id'])
                );
                // Only slots this booking holds are released. A guide has one booking per day, and
                // a stale booking that never held its slot must not free another customer's.
                $this->db->where('avail_tour_guide_id', (int) $slot['avail_tour_guide_id'])
                    ->where('avail_date', $slot['avail_date'])
                    ->where('avail_book_id', (int) $booking['book_id'])
                    ->where('avail_book_status', 'On-hold')
                    ->update('tour_guide_availability', array(
                        'avail_book_status' => 'Available',
                        'avail_book_id' => 0,
                        'avail_updated' => $now,
                    ));
                $released = $this->db->affected_rows();
            }
            $this->db->where('book_id', (int) $booking['book_id'])->update('tour_bookings', array(
                'book_status' => 'Cancelled',
                'book_cancel_reason' => 'The customer did not complete the booking.',
                'book_updated' => $now,
                'book_avail_id' => 0,
            ));
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                $this->db->trans_rollback();
                throw new RuntimeException('Abandoned booking could not be cancelled.');
            }
            $summary['cancelled']++;
            $summary['released'] += $released;
            $summary['cancelled_ids'][] = (int) $booking['book_id'];
        }
        return $summary;
    }

    /** Completed bookings that ended and were not asked for a review yet. See finishedBookingIds(). */
    public function getBookingsForReviewRequest($limit = 5)
    {
        return $this->finishedBookingIds('book_review_noti', $limit);
    }

    /** Record that the review request was sent. Returns false when it was already recorded. */
    public function markReviewRequested($bookingId)
    {
        return $this->markNotified('book_review_noti', $bookingId);
    }

    /**
     * Completed bookings that ended, came through a referral and earned that referral a commission,
     * and whose referral was not told yet. See finishedBookingIds().
     */
    public function getBookingsForCommissionNotice($limit = 5)
    {
        return $this->finishedBookingIds(
            'book_commission_noti',
            $limit,
            array('book_ref_id >' => 0, 'book_ref_commission >' => 0)
        );
    }

    /** Record that the referral was told about the commission. Returns false when already recorded. */
    public function markCommissionNotified($bookingId)
    {
        return $this->markNotified('book_commission_noti', $bookingId);
    }

    /**
     * IDs of Completed bookings whose slot has ended and whose notification flag is still 'No', in
     * random order so a booking that keeps failing cannot block the others. An overnight slot (end
     * time not after its start time) ends on the following day, as in
     * Tour_review_model::bookingEndTimestamp().
     *
     * @param string $flagColumn book_review_noti or book_commission_noti (always a fixed internal value)
     * @param array  $extraWhere additional query builder conditions
     * @return int[]
     */
    private function finishedBookingIds($flagColumn, $limit, array $extraWhere = array())
    {
        $now = $this->db->escape(date('Y-m-d H:i:s'));
        $rows = $this->db->select('book_id')
            ->from('tour_bookings')
            ->where('book_status', 'Completed')
            ->where($flagColumn, 'No')
            ->where($extraWhere)
            ->where(
                "TIMESTAMP(book_date, COALESCE(book_slot_end_time, '23:59:59'))
                    + INTERVAL (book_slot_end_time <= book_slot_start_time) DAY < ".$now,
                null,
                false
            )
            ->order_by('book_id', 'RANDOM')
            ->limit((int) $limit)
            ->get()
            ->result_array();

        return array_map('intval', array_column($rows, 'book_id'));
    }

    /** Set a notification flag from 'No' to 'Yes'. Returns false when it was not 'No' any more. */
    private function markNotified($flagColumn, $bookingId)
    {
        $this->db->where('book_id', (int) $bookingId)
            ->where($flagColumn, 'No')
            ->update('tour_bookings', array($flagColumn => 'Yes'));

        return $this->db->affected_rows() === 1;
    }

    /** The guide currently assigned to a booking, with both language names and the booking's language. */
    public function getAssignedGuide($bookingId)
    {
        return $this->db
            ->select(
                'bookings.book_id, bookings.book_status, bookings.book_lang, '.
                'bookings.book_tour_guide_id, bookings.book_tour_guide_name, '.
                'guides.tour_guide_name, guides.tour_guide_name_ar, '.
                'guides.tour_guide_email, guides.tour_guide_noti_lang'
            )
            ->from('tour_bookings bookings')
            ->join('tour_guides guides', 'guides.tour_guide_id = bookings.book_tour_guide_id', 'left')
            ->where('bookings.book_id', (int) $bookingId)
            ->get()
            ->row_array();
    }

    /**
     * Load a booking with its current localized tour, slot, vehicle, language, guide and country,
     * for the customer emails sent by Booking_email_service.
     */
    public function getCustomerEmailRecord($bookingId)
    {
        return $this->db
            ->select(
                'bookings.*, tours.tour_type, tours.tour_name AS linked_tour_name, '.
                'tours.tour_name_ar AS linked_tour_name_ar, '.
                'vehicles.vehicle_name AS linked_vehicle_name, '.
                'vehicles.vehicle_name_ar AS linked_vehicle_name_ar, '.
                'slots.slot_name AS linked_slot_name, slots.slot_name_ar AS linked_slot_name_ar, '.
                'slots.slot_hours AS linked_slot_hours, slots.slot_start_time AS linked_slot_start_time, '.
                'slots.slot_end_time AS linked_slot_end_time, '.
                'languages.lang_name AS linked_lang_name, languages.lang_name_ar AS linked_lang_name_ar, '.
                'guides.tour_guide_name AS linked_guide_name, '.
                'guides.tour_guide_name_ar AS linked_guide_name_ar, '.
                'guides.tour_guide_email AS linked_guide_email, '.
                'guides.tour_guide_noti_lang AS linked_guide_noti_lang, '.
                'referrals.ref_email AS linked_ref_email, referrals.ref_lang AS linked_ref_lang, '.
                'referrals.ref_name AS linked_ref_name, referrals.ref_name_ar AS linked_ref_name_ar, '.
                'countries.name AS linked_country_name, countries.name_ar AS linked_country_name_ar'
            )
            ->from('tour_bookings bookings')
            ->join('tours', 'tours.tour_id = bookings.book_tour_id', 'left')
            ->join('vehicles', 'vehicles.vehicle_id = bookings.book_vehicle_id', 'left')
            ->join('tour_slots slots', 'slots.slot_id = bookings.book_slot_id', 'left')
            ->join('tour_languages languages', 'languages.lang_id = bookings.book_lang_id', 'left')
            ->join('tour_guides guides', 'guides.tour_guide_id = bookings.book_tour_guide_id', 'left')
            ->join('countries', 'countries.id = bookings.book_country_id', 'left')
            ->join('referrals', 'referrals.ref_id = bookings.book_ref_id', 'left')
            ->where('bookings.book_id', (int) $bookingId)
            ->get()
            ->row_array();
    }

    private function result($booking)
    {
        $result = array(
            'success' => true,
            'booking_id' => $this->encryption->encrypt('booking:' . (int) $booking['book_id']),
            'reference' => $booking['book_res_code'],
            'total' => $booking['book_fee'],
            'steps_completed' => (int) $booking['steps_completed'],
        );
        if (array_key_exists('book_original_total', $booking)) {
            $code = isset($booking['book_promo_code']) ? $booking['book_promo_code'] : '';
            $discountAmount = isset($booking['book_discount_amount']) ? (int) $booking['book_discount_amount'] : 0;
            $originalTotal = (int) $booking['book_original_total'];
            // Customers see tax-inclusive amounts only, never profit or tax lines.
            $this->load->library('tour_pricing');
            $customerAmounts = $this->tour_pricing->customerAmounts(
                $originalTotal,
                $discountAmount,
                isset($booking['book_tax_percent']) ? (float) $booking['book_tax_percent'] : 0
            );
            $result['original_total'] = $customerAmounts['original'];
            $result['discount_amount'] = $customerAmounts['discount'];
            $result['discount_code'] = $code;
            $result['discount_name'] = isset($booking['book_discount_name']) ? $booking['book_discount_name'] : null;
            $result['discount_name_ar'] = isset($booking['book_discount_name_ar']) ? $booking['book_discount_name_ar'] : null;
            $result['discount_type'] = isset($booking['book_discount_type']) ? $booking['book_discount_type'] : null;
            $result['discount_value'] = isset($booking['book_discount_value']) ? $booking['book_discount_value'] : null;
            // A code stays visible once applied even when it stops contributing a discount, so the
            // customer can see and remove/replace it rather than being silently charged full price.
            $result['discount_incomplete'] = $code !== '' && $originalTotal <= 0;
            $result['discount_eligible'] = $code === '' || $originalTotal <= 0 || $discountAmount > 0;
        }
        return $result;
    }

    private function saveDetails($values, $tour, $locale, $existing)
    {
        $capacity = 0;
        $experience = trim($tour['tour_type']) === 'Experience';
        foreach ($this->Tour_model->get_tour_vehicles($tour['tour_id'], $locale) as $vehicle) {
            $capacity = max($capacity, (int) $vehicle['vehicle_max_capacity'] - ($experience ? 1 : 2));
        }
        if (!preg_match('/^[1-9][0-9]*$/D', $values['guests']) || (int) $values['guests'] > $capacity) {
            return array('success' => false, 'error' => 'preferences');
        }
        $country = $this->db->where('name', strtoupper($values['country']))->get('countries')->row_array();
        $now = date('Y-m-d H:i:s');
        $data = array(
            'book_name' => $values['full_name'],
            'book_email' => $values['email'],
            'book_phone' => $values['mobile'],
            'book_address' => $values['pickup_location'],
            'book_country_id' => $country ? (int) $country['id'] : null,
            'book_country_name' => $values['country'],
            'book_guests' => (int) $values['guests'],
            'book_tour_id' => $tour['tour_id'],
            'book_tour_name' => $locale === 'ar' && !empty($tour['tour_name_ar']) ? $tour['tour_name_ar'] : $tour['tour_name'],
            'book_lang' => $locale === 'ar' ? 'Arabic' : 'English',
            'book_updated' => $now,
        ) + $this->placeColumns($values['pickup_place_token']);
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();
        $this->db->query('SELECT tour_id FROM tours WHERE tour_id = ? FOR UPDATE', array($tour['tour_id']));
        $query = $this->db->query('SELECT * FROM tour_bookings WHERE book_submission_token = ? FOR UPDATE', array($values['booking_token']));
        $current = $query ? $query->row_array() : null;
        if ($current && ($current['book_status'] !== 'Pending' || !empty($current['book_terms_accepted_at']))) {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            return array('success' => false, 'error' => 'expired');
        }
        $created = empty($current);
        if ($current) {
            $data['steps_completed'] = max((int) $current['steps_completed'], 1);
            $saved = $this->db->where('book_id', $current['book_id'])->update('tour_bookings', $data);
            $current['steps_completed'] = $data['steps_completed'];
        } else {
            $data += array(
                'steps_completed' => 1,
                'book_added' => $now,
                'book_status' => 'Pending',
                'book_fee' => 0,
                'book_promo_code' => '',
                'book_res_code' => 'ALM-' . strtoupper(bin2hex(random_bytes(8))),
                'book_submission_token' => $values['booking_token'],
                'book_ip' => $this->input->ip_address(),
                'book_user_agent' => substr((string) $this->input->user_agent(), 0, 255),
            );
            $saved = $this->db->insert('tour_bookings', $data);
            $current = array(
                'book_id' => $this->db->insert_id(),
                'book_res_code' => $data['book_res_code'],
                'book_fee' => 0,
                'steps_completed' => 1,
            );
        }
        if (!$saved || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            $this->db->db_debug = $debug;
            return array('success' => false, 'error' => 'unavailable');
        }
        $committed = $this->db->trans_commit();
        $this->db->db_debug = $debug;
        if (!$committed) {
            return array('success' => false, 'error' => 'unavailable');
        }

        $result = $this->result($current);
        if ($created) {
            // Notify only for the first committed step-1 insert. Re-saving the
            // contact step or retrying its request must not send another email.
            $result['notification_stage'] = 'step1';
            $result['notification_booking_id'] = (int) $current['book_id'];
        }

        return $result;
    }
}
