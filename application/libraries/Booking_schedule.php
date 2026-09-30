<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The salon-wide booking rules, from Website Settings > Opening Hours
 * ("Days | Hours" lines such as "Monday – Friday | 9:00 AM – 5:00 PM" or
 * "Sunday | Closed"): which days can be booked, and which start times a
 * visit of a given length may use on each. Whether an artist is free at
 * one of those times is decided by Booking_availability.
 */
class Booking_schedule
{
    /** How many days ahead, starting today, can be booked. */
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

    /** array(open minute, close minute) for a day code (mon…sun), or NULL when closed. */
    public function hoursFor($dayCode)
    {
        return isset($this->hours[$dayCode]) ? $this->hours[$dayCode] : NULL;
    }

    /** The bookable window as Y-m-d dates, today first. */
    public function dates()
    {
        $dates = array();
        $day = new DateTime('today');
        for ($index = 0; $index < self::DAYS_AHEAD; $index++) {
            $dates[] = $day->format('Y-m-d');
            $day->modify('+1 day');
        }

        return $dates;
    }

    /** The day code (mon…sun) of a Y-m-d date, or NULL for an invalid date. */
    public function dayCode($date)
    {
        $day = DateTime::createFromFormat('!Y-m-d', (string) $date);
        if ($day === FALSE || $day->format('Y-m-d') !== $date) {
            return NULL;
        }

        return self::DAY_CODES[(int) $day->format('N')];
    }

    /**
     * Start minutes (from midnight) the salon allows for a visit of
     * $minutes on $date: inside the window and the opening hours, on the
     * slot grid, with enough time before closing, and at least
     * LEAD_MINUTES from now today. Empty when the day cannot be booked.
     */
    public function startMinutes($date, $minutes)
    {
        $dates = $this->dates();
        $code = $this->dayCode($date);
        if ($code === NULL || !in_array($date, $dates, TRUE) || !isset($this->hours[$code])) {
            return array();
        }

        list($open, $close) = $this->hours[$code];
        $last = $close - max((int) $minutes, self::MIN_VISIT_MINUTES);
        $earliest = $date === $dates[0]
            ? (int) date('G') * 60 + (int) date('i') + self::LEAD_MINUTES
            : 0;

        $starts = array();
        for ($start = $open; $start <= $last; $start += self::SLOT_STEP) {
            if ($start >= $earliest) {
                $starts[] = $start;
            }
        }

        return $starts;
    }

    /** "HH:MM" for a minute of the day. */
    public static function clock($minute)
    {
        return sprintf('%02d:%02d', intdiv((int) $minute, 60), (int) $minute % 60);
    }

    /** Minute of the day for "HH:MM" (or "HH:MM:SS"), or NULL. */
    public static function minute($clock)
    {
        if (!preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(?::[0-5][0-9])?$/', (string) $clock, $parts)) {
            return NULL;
        }

        return (int) $parts[1] * 60 + (int) $parts[2];
    }
}
