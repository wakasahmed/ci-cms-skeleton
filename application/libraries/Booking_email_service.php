<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Booking emails built from the managed email templates: customer emails plus the
 * tour guide assignment email.
 *
 * Shared by the frontend booking flow (confirmation and guide assignment), the
 * abandoned-booking cron (cancellation) and the manage refund and guide change
 * actions so all of them render short tags, localisation and delivery the same way.
 * The customer emails always follow the booking's own book_lang, never the language
 * of the request. The email sent to the tour guide follows the guide's own
 * notification language (tour_guides.tour_guide_noti_lang) instead.
 */
class Booking_email_service
{
    /** Tour and Experience confirmation templates. */
    const TEMPLATE_TOUR_CONFIRMATION = 3;
    const TEMPLATE_EXPERIENCE_CONFIRMATION = 4;

    /** Tour and Experience cancellation templates. */
    const TEMPLATE_TOUR_CANCELLATION = 5;
    const TEMPLATE_EXPERIENCE_CANCELLATION = 6;

    /** Tour and Experience refund templates. */
    const TEMPLATE_TOUR_REFUND = 7;
    const TEMPLATE_EXPERIENCE_REFUND = 8;

    /** Tour Guide Change template. */
    const TEMPLATE_GUIDE_CHANGE = 9;

    /** Tour Assigned to the Guide template, sent to the tour guide. */
    const TEMPLATE_GUIDE_ASSIGNMENT = 10;

    /** Tour Unassigned to the Guide template, sent to the guide who was replaced. */
    const TEMPLATE_GUIDE_UNASSIGNMENT = 15;

    /** Referral Earned Commission template, sent to the referral. */
    const TEMPLATE_COMMISSION_EARNED = 13;

    /** Customer Review / Rating template, asks the customer to rate a finished booking. */
    const TEMPLATE_REVIEW_REQUEST = 14;

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Booking_model');
        $this->CI->load->model('Tour_review_model');
        $this->CI->load->library('EmailService');
    }

    /** Send the paid-booking confirmation using template 3 for tours or 4 for experiences. */
    public function sendCustomerConfirmation($bookingId)
    {
        return $this->sendBookingTemplate($bookingId, 'Completed', 'confirmation');
    }

    /** Send the cancellation email using template 5 for tours or 6 for experiences. */
    public function sendCustomerCancellation($bookingId)
    {
        return $this->sendBookingTemplate($bookingId, 'Cancelled', 'cancellation');
    }

    /** Send the refund email using template 7 for tours or 8 for experiences. */
    public function sendCustomerRefund($bookingId)
    {
        return $this->sendBookingTemplate($bookingId, array('Completed', 'Refunded'), 'refund');
    }

    /**
     * Ask the customer to rate a finished booking (template 14, both tour types). The email
     * carries the tokenised review page link that Tour_review_model builds.
     */
    public function sendCustomerReviewRequest($bookingId)
    {
        return $this->sendBookingTemplate($bookingId, 'Completed', 'review request');
    }

    /**
     * Tell the referral a booking made with their discount code earned them a commission
     * (template 13, both tour types). The recipient is the referral's email in referrals, in the
     * referral's own language.
     */
    public function sendReferralCommissionEarned($bookingId)
    {
        return $this->sendBookingTemplate($bookingId, 'Completed', 'commission earned');
    }

    /**
     * Tell the tour guide a new tour was assigned to them (template 10). Tours only:
     * Experiences have no guide. The recipient is the guide's email in tour_guides.
     */
    public function sendTourGuideAssignment($bookingId)
    {
        return $this->sendBookingTemplate($bookingId, 'Completed', 'guide assignment');
    }

    /**
     * Tell the guide who was replaced that they were unassigned from the tour (template 15).
     * Tours only. $previousGuide must be the assignedGuide() snapshot taken before the change:
     * the email goes to that guide's address in that guide's language, and book_tour_guide_name
     * is that guide's own name (the booking already points at the new guide).
     */
    public function sendPreviousGuideUnassignment($bookingId, array $previousGuide)
    {
        return $this->sendBookingTemplate(
            $bookingId,
            'Completed',
            'guide unassignment',
            array('book_tour_guide_name' => $previousGuide['notification_name']),
            $previousGuide
        );
    }

    /**
     * Snapshot the guide assigned to a booking before it is changed. Call this before updating the
     * guide: afterwards the booking points at the new guide.
     *
     * - guide_id, and name: the guide's name in the booking's language (book_lang), for the
     *   customer's guide change email (sendCustomerGuideChange())
     * - email, notification_language and notification_name: the guide's own address, language and
     *   name in that language, for the unassignment email (sendPreviousGuideUnassignment())
     *
     * @return array empty when the booking is missing
     */
    public function assignedGuide($bookingId)
    {
        $assigned = $this->CI->Booking_model->getAssignedGuide($bookingId);
        if (empty($assigned)) {
            return array();
        }

        $isArabic = trim((string) $assigned['book_lang']) === 'Arabic';
        $name = $isArabic ? trim((string) $assigned['tour_guide_name_ar']) : '';
        if ($name === '') {
            $name = trim((string) $assigned['tour_guide_name']);
        }
        if ($name === '') {
            $name = trim((string) $assigned['book_tour_guide_name']);
        }

        // The guide's own language decides how the guide is addressed in their email.
        $notificationLanguage = trim((string) $assigned['tour_guide_noti_lang']);
        $notificationName = $notificationLanguage === 'Arabic'
            ? trim((string) $assigned['tour_guide_name_ar'])
            : '';
        if ($notificationName === '') {
            $notificationName = trim((string) $assigned['tour_guide_name']);
        }
        if ($notificationName === '') {
            $notificationName = trim((string) $assigned['book_tour_guide_name']);
        }

        return array(
            'guide_id' => (int) $assigned['book_tour_guide_id'],
            'name' => $name,
            'email' => trim((string) $assigned['tour_guide_email']),
            'notification_language' => $notificationLanguage,
            'notification_name' => $notificationName,
        );
    }

    /**
     * Send the guide change email (template 9) after the guide was replaced.
     * The booking's current guide fills book_tour_guide_name; $previousGuideName fills
     * book_old_tour_guide_name and must come from assignedGuide() taken before the change.
     */
    public function sendCustomerGuideChange($bookingId, $previousGuideName)
    {
        return $this->sendBookingTemplate(
            $bookingId,
            'Completed',
            'guide change',
            array('book_old_tour_guide_name' => trim((string) $previousGuideName))
        );
    }

    private function sendBookingTemplate(
        $bookingId,
        $requiredStatus,
        $emailType,
        array $extraShortTagValues = array(),
        array $previousGuide = array()
    ) {
        $bookingId = (int) $bookingId;
        $label = 'Booking '.$emailType;

        if ($bookingId <= 0) {
            return false;
        }

        $booking = $this->CI->Booking_model->getCustomerEmailRecord($bookingId);
        if (empty($booking)) {
            log_message('error', $label.' could not load booking ID '.$bookingId.'.');

            return false;
        }
        $requiredStatuses = is_array($requiredStatus) ? $requiredStatus : array($requiredStatus);
        if (!in_array($booking['book_status'], $requiredStatuses, true)) {
            log_message(
                'error',
                $label.' was not sent because booking ID '.$bookingId.' is not '.
                strtolower(implode(' or ', $requiredStatuses)).'.'
            );

            return false;
        }

        $isTour = trim((string) $booking['tour_type']) === 'Tour';
        $audience = $this->audience($emailType);
        if (in_array($audience, array('guide', 'previous guide'), true) && !$isTour) {
            return false;
        }

        // The guide and the referral are emailed at their own address and in the language set on
        // their own profile, not in the customer's.
        $recipientFields = array(
            'customer' => array('book_email', 'book_lang', ''),
            'guide' => array('linked_guide_email', 'linked_guide_noti_lang', 'tour guide '),
            'referral' => array('linked_ref_email', 'linked_ref_lang', 'referral '),
            // The replaced guide is no longer on the booking, so their details come from the snapshot.
            'previous guide' => array('', '', 'previous tour guide '),
        );
        $recipient = $audience === 'previous guide'
            ? (isset($previousGuide['email']) ? $previousGuide['email'] : '')
            : $booking[$recipientFields[$audience][0]];
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            log_message(
                'error',
                $label.' was not sent because booking ID '.$bookingId.' has an invalid '.
                $recipientFields[$audience][2].'email.'
            );

            return false;
        }

        $emailLanguage = $audience === 'previous guide'
            ? $previousGuide['notification_language']
            : $booking[$recipientFields[$audience][1]];
        $isArabic = trim((string) $emailLanguage) === 'Arabic';
        $locale = $isArabic ? 'ar' : 'en';

        // These emails are only due once the booking has finished, as the review page requires.
        if (in_array($emailType, array('review request', 'commission earned'), true)
            && !$this->CI->Tour_review_model->isEligible($booking)
        ) {
            log_message(
                'error',
                $label.' was not sent because booking ID '.$bookingId.' has not finished yet.'
            );

            return false;
        }

        $shortTagValues = array_merge(
            $this->shortTagValues($booking, $locale, $isTour),
            $extraShortTagValues
        );

        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => $this->templateId($emailType, $isTour),
            'to' => $recipient,
            'language' => $isArabic ? 'Arabic' : 'English',
            'values' => $shortTagValues,
            'parser' => 'parseBookingShortTags',
            'multiline_fields' => array('book_notes'),
            'label' => $label.' (booking ID '.$bookingId.')',
        ));
    }

    /** Who the email is addressed to: the customer, the tour guide, the replaced guide or the referral. */
    private function audience($emailType)
    {
        if ($emailType === 'guide assignment') {
            return 'guide';
        }
        if ($emailType === 'guide unassignment') {
            return 'previous guide';
        }
        if ($emailType === 'commission earned') {
            return 'referral';
        }

        return 'customer';
    }

    private function templateId($emailType, $isTour)
    {
        if ($emailType === 'cancellation') {
            return $isTour
                ? self::TEMPLATE_TOUR_CANCELLATION
                : self::TEMPLATE_EXPERIENCE_CANCELLATION;
        }
        if ($emailType === 'guide change') {
            return self::TEMPLATE_GUIDE_CHANGE;
        }
        if ($emailType === 'guide assignment') {
            return self::TEMPLATE_GUIDE_ASSIGNMENT;
        }
        if ($emailType === 'guide unassignment') {
            return self::TEMPLATE_GUIDE_UNASSIGNMENT;
        }
        if ($emailType === 'review request') {
            return self::TEMPLATE_REVIEW_REQUEST;
        }
        if ($emailType === 'commission earned') {
            return self::TEMPLATE_COMMISSION_EARNED;
        }
        if ($emailType === 'refund') {
            return $isTour
                ? self::TEMPLATE_TOUR_REFUND
                : self::TEMPLATE_EXPERIENCE_REFUND;
        }

        return $isTour
            ? self::TEMPLATE_TOUR_CONFIRMATION
            : self::TEMPLATE_EXPERIENCE_CONFIRMATION;
    }

    /** Build localized values for EmailService::parseBookingShortTags(). */
    private function shortTagValues(array $booking, $locale, $isTour)
    {
        $localized = function ($englishField, $arabicField, $fallbackField) use ($booking, $locale) {
            $field = $locale === 'ar' && trim((string) $booking[$arabicField]) !== ''
                ? $arabicField
                : $englishField;
            $value = trim((string) $booking[$field]);

            return $value !== '' ? $value : trim((string) $booking[$fallbackField]);
        };
        $currency = $this->CI->emailservice->currencyUnit($locale, $booking['book_currency']);
        $originalTotal = (int) $booking['book_original_total'];
        if ($originalTotal <= 0) {
            $originalTotal = (int) $booking['book_fee']
                - (int) $booking['book_tax_amount']
                + (int) $booking['book_discount_amount'];
        }
        // Customers see tax-inclusive amounts only, never profit or tax lines.
        $this->CI->load->library('tour_pricing');
        $customerAmounts = $this->CI->tour_pricing->customerAmounts(
            $originalTotal,
            $booking['book_discount_amount'],
            $booking['book_tax_percent']
        );

        return array(
            'book_id' => (int) $booking['book_id'],
            'book_name' => $booking['book_name'],
            'book_email' => $booking['book_email'],
            'book_phone' => $booking['book_phone'],
            'book_address' => $booking['book_address'],
            'book_res_code' => $booking['book_res_code'],
            'book_tour_name' => $localized(
                'linked_tour_name',
                'linked_tour_name_ar',
                'book_tour_name'
            ),
            'book_date' => $this->formatDate($booking['book_date'], $locale),
            'book_guests' => (int) $booking['book_guests'],
            'book_slot_name' => $localized(
                'linked_slot_name',
                'linked_slot_name_ar',
                'book_slot_name'
            ),
            'book_slot_hours' => !empty($booking['linked_slot_hours'])
                ? (int) $booking['linked_slot_hours']
                : (int) $booking['book_slot_hours'],
            'book_slot_start_time' => $this->formatTime(
                !empty($booking['linked_slot_start_time'])
                    ? $booking['linked_slot_start_time']
                    : $booking['book_slot_start_time'],
                $locale
            ),
            'book_slot_end_time' => $this->formatTime(
                !empty($booking['linked_slot_end_time'])
                    ? $booking['linked_slot_end_time']
                    : $booking['book_slot_end_time'],
                $locale
            ),
            'book_lang_name' => $localized(
                'linked_lang_name',
                'linked_lang_name_ar',
                'book_lang_name'
            ),
            'book_tour_guide_name' => $isTour
                ? $localized('linked_guide_name', 'linked_guide_name_ar', 'book_tour_guide_name')
                : '',
            'book_vehicle_name' => $localized(
                'linked_vehicle_name',
                'linked_vehicle_name_ar',
                'book_vehicle_name'
            ),
            'book_original_total' => number_format($customerAmounts['original']),
            'book_discount_amount' => number_format($customerAmounts['discount']),
            // Bookings made while the tour price was a flat amount have no tour total.
            'book_tour_total' => number_format(
                $booking['book_tour_total'] !== null
                    ? (int) $booking['book_tour_total']
                    : (int) $booking['book_tour_price']
            ),
            'book_profit_percent' => $this->formatPercent($booking['book_profit_percent']),
            'book_profit_amount' => number_format((int) $booking['book_profit_amount']),
            'book_tax_percent' => $this->formatPercent($booking['book_tax_percent']),
            'book_tax_amount' => number_format((int) $booking['book_tax_amount']),
            'book_paid_amount' => number_format((int) $booking['book_paid_amount']),
            'book_currency' => $currency,
            'book_payment_method' => $booking['book_payment_method'],
            'book_payment_date' => $this->formatDateTime($booking['book_payment_date'], $locale),
            'book_transaction_id' => $booking['book_transaction_id'],
            'book_refund_amount' => number_format((int) $booking['book_refund_amount']),
            'book_refunded_at' => $this->formatDateTime($booking['book_refunded_at'], $locale),
            'book_ref_name' => $localized('linked_ref_name', 'linked_ref_name_ar', 'book_ref_name'),
            'book_promo_code' => $booking['book_promo_code'],
            'book_ref_commission' => number_format((int) $booking['book_ref_commission']),
            'book_notes' => $this->valueOrFallback(
                $booking['book_notes'],
                $locale === 'ar' ? 'لا توجد ملاحظات إضافية.' : 'No additional notes.'
            ),
            'book_review_url' => $this->CI->Tour_review_model->reviewUrl($booking, $locale),
            // Used by {{book_review_url_btn}}; not a short tag of its own.
            'book_review_button_label' => $locale === 'ar' ? 'اترك تقييمًا' : 'Leave a Review',
        );
    }

    /** A saved percentage without trailing zeros and without the % sign: 15.00 -> 15, 12.50 -> 12.5. */
    private function formatPercent($value)
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function valueOrFallback($value, $fallback = 'Not provided')
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    /** Dates and times follow the booking's language: Arabic month names and ص / م for Arabic. */
    private function formatDate($value, $locale)
    {
        return strtotime((string) $value) === false
            ? $this->valueOrFallback($value)
            : $this->CI->emailservice->formatDate($value, $locale);
    }

    private function formatTime($value, $locale)
    {
        return strtotime((string) $value) === false
            ? $this->valueOrFallback($value)
            : $this->CI->emailservice->formatTime($value, $locale);
    }

    private function formatDateTime($value, $locale)
    {
        return $this->CI->emailservice->formatDateTime($value, $locale);
    }
}
