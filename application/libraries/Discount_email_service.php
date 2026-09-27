<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Emails sent to a referral about their discount code, built from the managed email
 * templates and delivered through EmailService.
 *
 * The recipient is the referral's own email (referrals.ref_email) and the language is the
 * referral's own language (referrals.ref_lang), looked up through discount_codes.discount_ref_id.
 */
class Discount_email_service
{
    /** New Discount Code Added template. */
    const TEMPLATE_CODE_ADDED = 11;

    /** Discount Code Updated template. */
    const TEMPLATE_CODE_UPDATED = 12;

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('EmailService');
    }

    /** Tell the referral their new discount code is ready (template 11). */
    public function sendCodeAdded($discountId)
    {
        return $this->sendCodeEmail($discountId, self::TEMPLATE_CODE_ADDED, 'added');
    }

    /** Tell the referral their discount code was updated (template 12). */
    public function sendCodeUpdated($discountId)
    {
        return $this->sendCodeEmail($discountId, self::TEMPLATE_CODE_UPDATED, 'updated');
    }

    private function sendCodeEmail($discountId, $templateId, $event)
    {
        $discountId = (int) $discountId;
        $label = 'Discount code '.$event.' (discount ID '.$discountId.')';

        if ($discountId <= 0) {
            return false;
        }

        $discount = $this->CI->SqlModel->getSingleRecord(
            'discount_codes',
            array('discount_id' => $discountId)
        );
        if (empty($discount)) {
            log_message('error', $label.' email could not load the discount code.');

            return false;
        }

        $referral = $this->CI->SqlModel->getSingleRecord(
            'referrals',
            array('ref_id' => (int) $discount['discount_ref_id'])
        );
        if (empty($referral)) {
            log_message('error', $label.' email could not load referral ID '.(int) $discount['discount_ref_id'].'.');

            return false;
        }
        if (filter_var($referral['ref_email'], FILTER_VALIDATE_EMAIL) === false) {
            log_message('error', $label.' email was not sent because the referral has an invalid email.');

            return false;
        }

        // The referral is emailed in the language set on their own profile.
        $isArabic = trim((string) $referral['ref_lang']) === 'Arabic';
        $locale = $isArabic ? 'ar' : 'en';

        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => $templateId,
            'to' => $referral['ref_email'],
            'language' => $isArabic ? 'Arabic' : 'English',
            'values' => $this->shortTagValues($discount, $referral, $locale),
            'parser' => 'parseDiscountShortTags',
            'label' => $label,
        ));
    }

    /** Build localized values for EmailService::parseDiscountShortTags(). */
    private function shortTagValues(array $discount, array $referral, $locale)
    {
        $emailService = $this->CI->emailservice;
        $isArabic = $locale === 'ar';
        $noOfUses = (int) $discount['discount_no_of_uses'];

        return array(
            'discount_id' => (int) $discount['discount_id'],
            'discount_name' => $this->localized(
                $discount['discount_name'],
                $discount['discount_name_ar'],
                $isArabic
            ),
            'discount_ref_id' => (int) $discount['discount_ref_id'],
            'discount_code' => $discount['discount_code'],
            'discount_type' => $this->typeLabel($discount['discount_type'], $locale),
            'discount_value' => (int) $discount['discount_value'],
            'discount_ref_commission_type' => $this->typeLabel(
                $discount['discount_ref_commission_type'],
                $locale
            ),
            'discount_ref_commission' => $discount['discount_ref_commission'] === null
                ? ''
                : (int) $discount['discount_ref_commission'],
            'discount_expiry' => $discount['discount_expiry'] === null
                ? ''
                : $emailService->formatDate($discount['discount_expiry'], $locale),
            // Blank or 0 means unlimited uses, as the discount code form explains.
            'discount_no_of_uses' => $noOfUses > 0
                ? $noOfUses
                : ($isArabic ? 'غير محدود' : 'Unlimited'),
            'discount_status' => $discount['discount_status'],
            'discount_added' => $emailService->formatDateTime($discount['discount_added'], $locale),
            'discount_updated' => $emailService->formatDateTime($discount['discount_updated'], $locale),
            'discount_ref_name' => $this->localized(
                $referral['ref_name'],
                $referral['ref_name_ar'],
                $isArabic
            ),
        );
    }

    /**
     * Fixed Amount shows the currency label from Website Settings in the recipient's
     * language; Percentage shows %.
     */
    private function typeLabel($type, $locale)
    {
        return $type === 'Percentage'
            ? '%'
            : $this->CI->emailservice->currencyUnit($locale);
    }

    /** The Arabic text when the email is Arabic and it is filled in, otherwise the English text. */
    private function localized($english, $arabic, $isArabic)
    {
        $arabic = trim((string) $arabic);

        return $isArabic && $arabic !== ''
            ? $arabic
            : trim((string) $english);
    }
}
