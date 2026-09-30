<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Real-time availability for online booking (PROJECT_PLAN.md decision D1,
 * changed in Phase 8).
 *
 * A visit is a run of back-to-back segments, one per chosen service, in the
 * order chosen. Each segment needs an artist who offers that service (every
 * artist, when a service has none assigned), works at that time (Manage >
 * Artists > working hours: the weekday, and the day's start and end when
 * set) and has nothing else booked for that time. With a chosen artist every
 * segment goes to them; with "any artist" a segment keeps the previous
 * segment's artist when possible, otherwise the first free artist in team
 * order.
 *
 * Every appointment except a Cancelled one holds its artists' time, and so
 * does the artists' time off. Booking_schedule supplies the salon's hours,
 * lead time and window.
 */
class Booking_availability
{
    /** Length used for a service without a duration. */
    const DEFAULT_SERVICE_MINUTES = 30;

    private $CI;

    /**
     * artist id => array('id', 'name', 'hours', 'serviceIds'). 'hours' maps a
     * day code to array(start minute, end minute), either one NULL for "the
     * salon's opening or closing time"; an empty 'hours' means every day.
     */
    private $artists = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('booking_schedule');
    }

    /** Bookable artists with the services they take, in team order. */
    public function artists()
    {
        if ($this->artists !== NULL) {
            return $this->artists;
        }

        $rows = $this->CI->db
            ->select('a.artist_id, a.artist_name, x.service_id')
            ->from('artists a')
            ->join('artist_services x', 'x.artist_id = a.artist_id', 'left')
            ->where('a.artist_status', 'Enable')
            ->order_by('a.artist_order', 'ASC')
            ->order_by('a.artist_id', 'ASC')
            ->get()
            ->result_array();

        $this->artists = array();
        foreach ($rows as $row) {
            $id = (int) $row['artist_id'];
            if (!isset($this->artists[$id])) {
                $this->artists[$id] = array(
                    'id' => $id,
                    'name' => $row['artist_name'],
                    'hours' => array(),
                    'serviceIds' => array(),
                );
            }
            if ($row['service_id'] !== NULL) {
                $this->artists[$id]['serviceIds'][] = (int) $row['service_id'];
            }
        }

        if (!empty($this->artists)) {
            $hours = $this->CI->db
                ->select('artist_id, hours_day, hours_start, hours_end')
                ->where_in('artist_id', array_keys($this->artists))
                ->get('artist_hours')
                ->result_array();

            foreach ($hours as $row) {
                $this->artists[(int) $row['artist_id']]['hours'][$row['hours_day']] = array(
                    $row['hours_start'] !== NULL ? Booking_schedule::minute($row['hours_start']) : NULL,
                    $row['hours_end'] !== NULL ? Booking_schedule::minute($row['hours_end']) : NULL,
                );
            }
        }

        return $this->artists;
    }

    /** IDs of the artists who offer every one of $services (catalogue service rows). */
    public function artistsForAll(array $services)
    {
        $ids = array();
        foreach ($this->artists() as $artist) {
            $all = TRUE;
            foreach ($services as $service) {
                if (!$this->offers($artist, $service)) {
                    $all = FALSE;
                }
            }
            if ($all) {
                $ids[] = $artist['id'];
            }
        }

        return $ids;
    }

    /**
     * Free start times for $services with $artistId (NULL for any artist):
     * Y-m-d => list of "HH:MM", one entry per day in the booking window.
     */
    public function days(array $services, $artistId = NULL)
    {
        $dates = $this->CI->booking_schedule->dates();
        $busy = $this->busy(reset($dates), end($dates));
        $minutes = $this->totalMinutes($services);

        $days = array();
        foreach ($dates as $date) {
            $days[$date] = array();
            foreach ($this->CI->booking_schedule->startMinutes($date, $minutes) as $start) {
                if ($this->plan($services, $artistId, $date, $start, $busy) !== NULL) {
                    $days[$date][] = Booking_schedule::clock($start);
                }
            }
        }

        return $days;
    }

    /**
     * The segments of a visit starting on $date at $time ("HH:MM"), each
     * array('service', 'artist', 'start', 'end') with minutes of the day,
     * or NULL when the salon rules or the artists' diaries do not allow it.
     * Call inside Booking_request's booking lock before saving.
     */
    public function schedule(array $services, $artistId, $date, $time)
    {
        $start = Booking_schedule::minute($time);
        if ($start === NULL
            || !in_array($start, $this->CI->booking_schedule->startMinutes($date, $this->totalMinutes($services)), TRUE)
        ) {
            return NULL;
        }

        return $this->plan($services, $artistId, $date, $start, $this->busy($date, $date));
    }

    /** Sum of the services' lengths. */
    public function totalMinutes(array $services)
    {
        $total = 0;
        foreach ($services as $service) {
            $total += $this->serviceMinutes($service);
        }

        return $total;
    }

    /** Assign an artist to each segment, or NULL when one cannot be staffed. */
    private function plan(array $services, $artistId, $date, $start, array $busy)
    {
        $artists = $this->artists();
        $dayCode = $this->CI->booking_schedule->dayCode($date);
        if ($artistId !== NULL && !isset($artists[$artistId])) {
            return NULL;
        }

        $segments = array();
        $previous = NULL;
        $cursor = $start;
        foreach ($services as $service) {
            $end = $cursor + $this->serviceMinutes($service);
            $candidates = $artistId !== NULL
                ? array($artists[$artistId])
                : $this->preferring($artists, $previous);

            $chosen = NULL;
            foreach ($candidates as $artist) {
                if ($this->offers($artist, $service)
                    && $this->works($artist, $dayCode, $cursor, $end)
                    && $this->isFree($busy, $artist['id'], $date, $cursor, $end)
                ) {
                    $chosen = $artist;
                    break;
                }
            }
            if ($chosen === NULL) {
                return NULL;
            }

            $segments[] = array(
                'service' => $service,
                'artist' => $chosen,
                'start' => $cursor,
                'end' => $end,
            );
            $previous = $chosen['id'];
            $cursor = $end;
        }

        return $segments;
    }

    /** Artists in team order, with $firstId (the previous segment's artist) first. */
    private function preferring(array $artists, $firstId)
    {
        if ($firstId === NULL || !isset($artists[$firstId])) {
            return array_values($artists);
        }

        $ordered = array($artists[$firstId]);
        foreach ($artists as $id => $artist) {
            if ($id !== $firstId) {
                $ordered[] = $artist;
            }
        }

        return $ordered;
    }

    /** A service with no artists assigned can be done by anyone. */
    private function offers(array $artist, array $service)
    {
        $assigned = FALSE;
        foreach ($this->artists() as $candidate) {
            if (in_array((int) $service['id'], $candidate['serviceIds'], TRUE)) {
                $assigned = TRUE;
                break;
            }
        }

        return !$assigned || in_array((int) $service['id'], $artist['serviceIds'], TRUE);
    }

    /**
     * Whether $artist works from $start to $end (minutes) on $dayCode. The
     * salon's own hours are applied by Booking_schedule; this only narrows
     * them to the artist's day.
     */
    private function works(array $artist, $dayCode, $start, $end)
    {
        if (empty($artist['hours'])) {
            return TRUE;
        }
        if (!isset($artist['hours'][$dayCode])) {
            return FALSE;
        }

        list($from, $until) = $artist['hours'][$dayCode];

        return ($from === NULL || $start >= $from) && ($until === NULL || $end <= $until);
    }

    private function serviceMinutes(array $service)
    {
        return (int) $service['minutes'] > 0 ? (int) $service['minutes'] : self::DEFAULT_SERVICE_MINUTES;
    }

    private function isFree(array $busy, $artistId, $date, $start, $end)
    {
        if (empty($busy[$artistId][$date])) {
            return TRUE;
        }
        foreach ($busy[$artistId][$date] as $interval) {
            if ($start < $interval[1] && $end > $interval[0]) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Booked time from $from to $to (Y-m-d): artist id => date => list of
     * array(start minute, end minute). Uses each service row's own artist
     * and start time; requests saved before Phase 8 fall back to the
     * appointment's artist, time and total length. Time off is added on top:
     * the whole day, or the same hours on each day of its range.
     */
    private function busy($from, $to)
    {
        $rows = $this->CI->db
            ->select('a.appointment_id, a.appointment_date, a.appointment_time, a.appointment_duration_minutes,'
                .' a.appointment_artist_id, s.service_artist_id, s.service_start_time, s.service_duration_minutes', FALSE)
            ->from('appointments a')
            ->join('appointment_services s', 's.appointment_id = a.appointment_id', 'left')
            ->where('a.appointment_status !=', 'Cancelled')
            ->where('a.appointment_date >=', $from)
            ->where('a.appointment_date <=', $to)
            ->get()
            ->result_array();

        $busy = array();
        $wholeVisits = array();
        foreach ($rows as $row) {
            $date = $row['appointment_date'];
            if ($row['service_artist_id'] !== NULL && $row['service_start_time'] !== NULL) {
                $start = Booking_schedule::minute($row['service_start_time']);
                $length = (int) $row['service_duration_minutes'] > 0
                    ? (int) $row['service_duration_minutes']
                    : self::DEFAULT_SERVICE_MINUTES;
                $busy[(int) $row['service_artist_id']][$date][] = array($start, $start + $length);
            } elseif ($row['appointment_artist_id'] !== NULL && !isset($wholeVisits[$row['appointment_id']])) {
                $wholeVisits[$row['appointment_id']] = TRUE;
                $start = Booking_schedule::minute($row['appointment_time']);
                $length = max((int) $row['appointment_duration_minutes'], self::DEFAULT_SERVICE_MINUTES);
                $busy[(int) $row['appointment_artist_id']][$date][] = array($start, $start + $length);
            }
        }

        return $this->addTimeOff($busy, $from, $to);
    }

    private function addTimeOff(array $busy, $from, $to)
    {
        $rows = $this->CI->db
            ->select('artist_id, time_off_start_date, time_off_end_date, time_off_start_time, time_off_end_time')
            ->where('time_off_start_date <=', $to)
            ->where('time_off_end_date >=', $from)
            ->get('artist_time_off')
            ->result_array();

        foreach ($rows as $row) {
            $interval = $row['time_off_start_time'] !== NULL && $row['time_off_end_time'] !== NULL
                ? array(
                    Booking_schedule::minute($row['time_off_start_time']),
                    Booking_schedule::minute($row['time_off_end_time']),
                )
                : array(0, 24 * 60);

            $day = new DateTime(max($row['time_off_start_date'], $from));
            $last = min($row['time_off_end_date'], $to);
            while ($day->format('Y-m-d') <= $last) {
                $busy[(int) $row['artist_id']][$day->format('Y-m-d')][] = $interval;
                $day->modify('+1 day');
            }
        }

        return $busy;
    }
}
