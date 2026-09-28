<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * When an appointment can be requested online, from Website Settings >
 * Opening Hours ("Days | Hours" lines such as "Monday – Friday | 9:00 AM –
 * 5:00 PM" or "Sunday | Closed").
 *
 * The booking wizard (js/booking.js) builds its day and time buttons from
 * config(); Booking_request re-checks the chosen slot with isAvailable().
 * These are request slots, not a live diary: the salon confirms every
 * request (PROJECT_PLAN.md decision D1).
 */
class Booking_schedule
{
    /** How many days ahead, starting today, can be requested. */
    const DAYS_AHEAD = 21;

    /** Start times are offered every this many minutes. */
    const SLOT_STEP = 30;

    /** A visit must start at least this many minutes from now. */
    const LEAD_MINUTES = 60;

    /**
     * The last start leaves at least this long before closing, or the
     * whole visit when it is longer.
     */
    const MIN_VISIT_MINUTES = 60;

    /** Day codes in PHP's date('N') order (1 = Monday). */
    const DAY_CODES = array(1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun');

    /** day code => array(open minute, close minute); closed days are absent. */
    private $hours = array();

    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->helper('frontend');
        $settings = $CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $this->hours = frontend_parse_opening_hours(isset($settings['opening_hours']) ? $settings['opening_hours'] : '');
    }

    /** TRUE when at least one day has opening hours. */
    public function hasHours()
    {
        return !empty($this->hours);
    }

    /** Settings for js/booking.js. */
    public function config()
    {
        return array(
            'hours' => $this->hours,
            'today' => date('Y-m-d'),
            'nowMinutes' => (int) date('G') * 60 + (int) date('i'),
            'daysAhead' => self::DAYS_AHEAD,
            'slotStep' => self::SLOT_STEP,
            'leadMinutes' => self::LEAD_MINUTES,
            'minVisitMinutes' => self::MIN_VISIT_MINUTES,
        );
    }

    /**
     * TRUE when a visit of $minutes can be requested on $date (Y-m-d) at
     * $time (H:i), with $workingDays (day codes) limiting the days for a
     * chosen artist (NULL for any artist).
     */
    public function isAvailable($date, $time, $minutes, $workingDays = NULL)
    {
        $day = DateTime::createFromFormat('!Y-m-d', (string) $date);
        if ($day === FALSE || $day->format('Y-m-d') !== $date) {
            return FALSE;
        }

        $today = new DateTime('today');
        $offset = (int) $today->diff($day)->format('%r%a');
        if ($offset < 0 || $offset >= self::DAYS_AHEAD) {
            return FALSE;
        }

        $code = self::DAY_CODES[(int) $day->format('N')];
        if (!isset($this->hours[$code])) {
            return FALSE;
        }
        if (is_array($workingDays) && !in_array($code, $workingDays, TRUE)) {
            return FALSE;
        }

        if (!preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', (string) $time, $parts)) {
            return FALSE;
        }
        $start = (int) $parts[1] * 60 + (int) $parts[2];
        list($open, $close) = $this->hours[$code];

        if ($start < $open || ($start - $open) % self::SLOT_STEP !== 0) {
            return FALSE;
        }
        if ($start > $close - max((int) $minutes, self::MIN_VISIT_MINUTES)) {
            return FALSE;
        }
        if ($offset === 0 && $start < (int) date('G') * 60 + (int) date('i') + self::LEAD_MINUTES) {
            return FALSE;
        }

        return TRUE;
    }
}
