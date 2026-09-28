<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Real-time availability for online booking (PROJECT_PLAN.md decision D1,
 * changed in Phase 8).
 *
 * A visit is a run of back-to-back segments, one per chosen service, in the
 * order chosen. Each segment needs an artist who offers that service (every
 * artist, when a service has none assigned), works that weekday (Manage >
 * Artists > working days) and has nothing else booked for that time. With a
 * chosen artist every segment goes to them; with "any artist" a segment
 * keeps the previous segment's artist when possible, otherwise the first
 * free artist in team order.
 *
 * Every appointment except a Cancelled one holds its artists' time.
 * Booking_schedule supplies the salon's hours, lead time and window.
 */
class Booking_availability
{
    /** Length used for a service without a duration. */
    const DEFAULT_SERVICE_MINUTES = 30;

    private $CI;

    /** artist id => array('id', 'name', 'workingDays', 'serviceIds', 'order'). */
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
            ->select('a.artist_id, a.artist_name, a.artist_working_days, x.service_id')
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
                    'workingDays' => array_values(array_filter(explode(',', (string) $row['artist_working_days']))),
                    'serviceIds' => array(),
                );
            }
            if ($row['service_id'] !== NULL) {
                $this->artists[$id]['serviceIds'][] = (int) $row['service_id'];
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
                    && (empty($artist['workingDays']) || in_array($dayCode, $artist['workingDays'], TRUE))
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
     * appointment's artist, time and total length.
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

        return $busy;
    }
}
