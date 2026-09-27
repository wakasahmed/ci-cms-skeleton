<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profit and tax math shared by the "starting from" card prices
 * (Tour_model), the booking total (Booking_model) and the booking emails.
 * assets/frontend/js/booking.js mirrors percentAmount() for the live
 * estimate, so keep the rounding identical in both places.
 *
 * Money is stored in whole currency units, so every percentage amount is
 * rounded once, here.
 */
class Tour_pricing
{
    private $CI;
    private $rates = null;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Current Website Settings percentages: ['profit' => float, 'tax' => float]. */
    public function rates()
    {
        if ($this->rates !== null) {
            return $this->rates;
        }

        $this->rates = array('profit' => 0.0, 'tax' => 0.0);
        // Both columns come from db/tour-profit-tax-migration.sql.
        if (
            !$this->CI->db->field_exists('profit', 'site_settings')
            || !$this->CI->db->field_exists('tax', 'site_settings')
        ) {
            return $this->rates;
        }

        $settings = $this->CI->db->select('profit, tax')
            ->where('id', 1)
            ->get('site_settings')
            ->row_array();
        if (!empty($settings)) {
            $this->rates = array(
                'profit' => max(0.0, (float) $settings['profit']),
                'tax' => max(0.0, (float) $settings['tax']),
            );
        }

        return $this->rates;
    }

    /** A percentage of a whole-unit amount, rounded to whole units. */
    public function percentAmount($amount, $percent)
    {
        return max(0, (int) round((int) $amount * (float) $percent / 100));
    }

    /**
     * Customer price for a pre-profit subtotal with no discount: the current
     * profit is added first, then tax on that amount. Used for "starting
     * from" prices.
     */
    public function grossPrice($subtotal)
    {
        $rates = $this->rates();
        $original = (int) $subtotal + $this->percentAmount($subtotal, $rates['profit']);

        return $original + $this->percentAmount($original, $rates['tax']);
    }

    /**
     * Tax-inclusive amounts shown to customers, who never see profit or tax
     * as separate lines. $original is the pre-tax total (profit included) and
     * $discount the pre-tax discount, as stored on the booking; the displayed
     * discount is the difference between the two tax-inclusive totals so the
     * shown lines always add up.
     */
    public function customerAmounts($original, $discount, $taxPercent)
    {
        $original = (int) $original;
        $net = max(0, $original - (int) $discount);
        $displayOriginal = $original + $this->percentAmount($original, $taxPercent);
        $total = $net + $this->percentAmount($net, $taxPercent);

        return array(
            'original' => $displayOriginal,
            'discount' => max(0, $displayOriginal - $total),
            'total' => $total,
        );
    }
}
