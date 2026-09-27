<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only figures for the manage dashboard.
 *
 * Money follows the same rules as ReportsModel: whole currency units, revenue
 * is what was paid minus what was refunded, and only rows with a payment date
 * or refund date count towards the period they happened in.
 */
class Dashboard_model extends CI_Model
{
    const UPCOMING_DAYS = 7;
    const AVAILABILITY_HORIZON_DAYS = 14;
    const PROMO_EXPIRY_DAYS = 7;
    const STALE_PENDING_GRACE_MINUTES = 15;
    const TOP_TOURS_LIMIT = 5;
    const UPCOMING_LIMIT = 8;
    const RECENT_LIMIT = 6;
    const ATTENTION_PREVIEW_LIMIT = 3;
    const PLAN_COMPLETED_STEP = 4;

    /**
     * Statuses a booking can hold a payment in. A paid booking is Completed, and
     * stays Refunded (keeping its paid amount) after a refund. Every revenue and
     * "paid" figure applies this guard so a Cancelled booking can never count.
     */
    const PAID_STATUS_SQL = "IN ('Completed', 'Refunded')";

    /**
     * Everything the dashboard view needs for the last $days days (today included).
     */
    public function build($days)
    {
        $days = max(1, (int) $days);
        $today = date('Y-m-d');
        $period = $this->period($today, $days);

        return array(
            'period' => $period,
            'kpi' => $this->kpi($period, $today),
            'series' => $this->series($period),
            'status' => $this->statusBreakdown($period),
            'topTours' => $this->topTours($period),
            'upcoming' => $this->upcoming($today),
            'recent' => $this->recentBookings(),
            'attention' => $this->attention($today),
        );
    }

    /**
     * Current and previous period boundaries. The previous period is the same
     * length immediately before the current one, used for the trend badges.
     */
    private function period($today, $days)
    {
        $from = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));
        $previousTo = date('Y-m-d', strtotime($from . ' -1 day'));
        $previousFrom = date('Y-m-d', strtotime($previousTo . ' -' . ($days - 1) . ' days'));

        return array(
            'days' => $days,
            'from' => $from,
            'to' => $today,
            'start' => $from . ' 00:00:00',
            'end' => $today . ' 23:59:59',
            'previous_start' => $previousFrom . ' 00:00:00',
            'previous_end' => $previousTo . ' 23:59:59',
        );
    }

    private function kpi(array $period, $today)
    {
        $current = $this->periodTotals($period['start'], $period['end']);
        $previous = $this->periodTotals($period['previous_start'], $period['previous_end']);

        $upcomingTotals = $this->upcomingTotals($today);

        return array(
            'net_revenue' => $current['net_revenue'],
            'collected' => $current['collected'],
            'refunded' => $current['refunded'],
            'refund_count' => $current['refund_count'],
            'paid_count' => $current['paid_count'],
            'average_value' => $current['average_value'],
            'started' => $current['started'],
            'started_paid' => $current['started_paid'],
            'abandoned_count' => $current['abandoned_count'],
            'abandoned_value' => $current['abandoned_value'],
            'conversion' => $current['started'] > 0
                ? (int) round(100 * $current['started_paid'] / $current['started'])
                : 0,
            'contact_requests' => $current['contact_requests'],
            'trip_plans' => $current['trip_plans'],
            'upcoming_count' => $upcomingTotals['bookings'],
            'upcoming_guests' => $upcomingTotals['guests'],
            'trend' => array(
                'net_revenue' => $this->change($current['net_revenue'], $previous['net_revenue']),
                'started' => $this->change($current['started'], $previous['started']),
                'abandoned' => $this->change($current['abandoned_count'], $previous['abandoned_count']),
                'average_value' => $this->change($current['average_value'], $previous['average_value']),
                'contact_requests' => $this->change($current['contact_requests'], $previous['contact_requests']),
                'trip_plans' => $this->change($current['trip_plans'], $previous['trip_plans']),
            ),
        );
    }

    /**
     * Payment, refund and enquiry totals for one date-time window.
     */
    private function periodTotals($start, $end)
    {
        $paid = $this->db->query(
            'SELECT COALESCE(SUM(book_paid_amount), 0) AS collected, COUNT(*) AS paid_count
            FROM tour_bookings
            WHERE book_paid_amount > 0 AND book_status ' . self::PAID_STATUS_SQL . '
                AND book_payment_date BETWEEN ? AND ?',
            array($start, $end)
        )->row_array();

        $refunds = $this->db->query(
            'SELECT COALESCE(SUM(book_refund_amount), 0) AS refunded, COUNT(*) AS refund_count
            FROM tour_bookings
            WHERE book_refund_amount > 0 AND book_status ' . self::PAID_STATUS_SQL . '
                AND book_refunded_at BETWEEN ? AND ?',
            array($start, $end)
        )->row_array();

        $started = $this->db->query(
            "SELECT COUNT(*) AS started,
                COALESCE(SUM(book_paid_amount > 0 AND book_status " . self::PAID_STATUS_SQL . "), 0) AS started_paid,
                COALESCE(SUM(book_status = 'Cancelled'), 0) AS abandoned_count,
                COALESCE(SUM(CASE WHEN book_status = 'Cancelled' THEN book_fee ELSE 0 END), 0) AS abandoned_value
            FROM tour_bookings
            WHERE book_added BETWEEN ? AND ?",
            array($start, $end)
        )->row_array();

        $contacts = $this->db->query(
            'SELECT COUNT(*) AS total FROM contact_requests WHERE created_at BETWEEN ? AND ?',
            array($start, $end)
        )->row_array();

        $plans = $this->db->query(
            'SELECT COUNT(*) AS total
            FROM plan_your_visit
            WHERE step_completed >= ? AND created_at BETWEEN ? AND ?',
            array(self::PLAN_COMPLETED_STEP, $start, $end)
        )->row_array();

        $collected = (int) $paid['collected'];
        $refunded = (int) $refunds['refunded'];
        $paidCount = (int) $paid['paid_count'];

        return array(
            'collected' => $collected,
            'refunded' => $refunded,
            'net_revenue' => $collected - $refunded,
            'paid_count' => $paidCount,
            'refund_count' => (int) $refunds['refund_count'],
            'average_value' => $paidCount > 0 ? (int) round($collected / $paidCount) : 0,
            'started' => (int) $started['started'],
            'started_paid' => (int) $started['started_paid'],
            'abandoned_count' => (int) $started['abandoned_count'],
            'abandoned_value' => (int) $started['abandoned_value'],
            'contact_requests' => (int) $contacts['total'],
            'trip_plans' => (int) $plans['total'],
        );
    }

    /**
     * Percentage change against the previous period. NULL means there is no
     * previous value to compare with (shown as "New" when there is a current one).
     */
    private function change($current, $previous)
    {
        if ($previous <= 0) {
            return $current > 0 ? null : 0;
        }

        return (int) round(100 * ($current - $previous) / $previous);
    }

    private function upcomingTotals($today)
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS bookings, COALESCE(SUM(book_guests), 0) AS guests
            FROM tour_bookings
            WHERE book_status = 'Completed' AND book_date BETWEEN ? AND ?",
            array($today, $this->upcomingEnd($today))
        )->row_array();

        return array(
            'bookings' => (int) $row['bookings'],
            'guests' => (int) $row['guests'],
        );
    }

    private function upcomingEnd($today)
    {
        return date('Y-m-d', strtotime($today . ' +' . self::UPCOMING_DAYS . ' days'));
    }

    /**
     * Zero-filled daily series for the performance chart. Bookings are grouped
     * by the day they were started so "started" and "paid" describe the same
     * bookings; revenue is grouped by payment and refund date.
     */
    private function series(array $period)
    {
        $started = $this->dailyMap(
            "SELECT DATE(book_added) AS day, COUNT(*) AS started,
                COALESCE(SUM(book_paid_amount > 0 AND book_status " . self::PAID_STATUS_SQL . "), 0) AS paid
            FROM tour_bookings
            WHERE book_added BETWEEN ? AND ?
            GROUP BY DATE(book_added)",
            $period,
            array('started', 'paid')
        );

        $collected = $this->dailyMap(
            'SELECT DATE(book_payment_date) AS day, COALESCE(SUM(book_paid_amount), 0) AS collected
            FROM tour_bookings
            WHERE book_paid_amount > 0 AND book_status ' . self::PAID_STATUS_SQL . '
                AND book_payment_date BETWEEN ? AND ?
            GROUP BY DATE(book_payment_date)',
            $period,
            array('collected')
        );

        $refunded = $this->dailyMap(
            'SELECT DATE(book_refunded_at) AS day, COALESCE(SUM(book_refund_amount), 0) AS refunded
            FROM tour_bookings
            WHERE book_refund_amount > 0 AND book_status ' . self::PAID_STATUS_SQL . '
                AND book_refunded_at BETWEEN ? AND ?
            GROUP BY DATE(book_refunded_at)',
            $period,
            array('refunded')
        );

        $series = array(
            'labels' => array(),
            'revenue' => array(),
            'started' => array(),
            'paid' => array(),
        );

        $cursor = strtotime($period['from']);
        $last = strtotime($period['to']);
        while ($cursor <= $last) {
            $day = date('Y-m-d', $cursor);
            $series['labels'][] = $day;
            $series['revenue'][] = (isset($collected[$day]['collected']) ? $collected[$day]['collected'] : 0)
                - (isset($refunded[$day]['refunded']) ? $refunded[$day]['refunded'] : 0);
            $series['started'][] = isset($started[$day]['started']) ? $started[$day]['started'] : 0;
            $series['paid'][] = isset($started[$day]['paid']) ? $started[$day]['paid'] : 0;
            $cursor = strtotime('+1 day', $cursor);
        }

        return $series;
    }

    /**
     * Runs a grouped query bound to the period and returns rows keyed by day.
     */
    private function dailyMap($sql, array $period, array $columns)
    {
        $map = array();
        $query = $this->db->query($sql, array($period['start'], $period['end']));

        foreach ($query->result_array() as $row) {
            foreach ($columns as $column) {
                $map[$row['day']][$column] = (int) $row[$column];
            }
        }

        return $map;
    }

    /**
     * Bookings started in the period that have reached an outcome. Pending
     * bookings are still in progress (or about to be cancelled by the cleanup
     * job), so they are left out.
     */
    private function statusBreakdown(array $period)
    {
        $row = $this->db->query(
            "SELECT COALESCE(SUM(book_status = 'Completed'), 0) AS completed,
                COALESCE(SUM(book_status = 'Cancelled'), 0) AS cancelled,
                COALESCE(SUM(book_status = 'Refunded'), 0) AS refunded
            FROM tour_bookings
            WHERE book_added BETWEEN ? AND ?",
            array($period['start'], $period['end'])
        )->row_array();

        return array(
            'Completed' => (int) $row['completed'],
            'Cancelled' => (int) $row['cancelled'],
            'Refunded' => (int) $row['refunded'],
        );
    }

    /**
     * Tours and experiences ranked by net revenue of the bookings paid in the period.
     */
    private function topTours(array $period)
    {
        $query = $this->db->query(
            'SELECT b.book_tour_id AS tour_id,
                MAX(COALESCE(t.tour_name, b.book_tour_name)) AS name,
                COUNT(*) AS bookings,
                SUM(CAST(b.book_paid_amount AS SIGNED) - CAST(b.book_refund_amount AS SIGNED)) AS revenue
            FROM tour_bookings b
            LEFT JOIN tours t ON t.tour_id = b.book_tour_id
            WHERE b.book_paid_amount > 0 AND b.book_status ' . self::PAID_STATUS_SQL . '
                AND b.book_payment_date BETWEEN ? AND ?
            GROUP BY b.book_tour_id
            ORDER BY revenue DESC, bookings DESC
            LIMIT ' . (int) self::TOP_TOURS_LIMIT,
            array($period['start'], $period['end'])
        );

        $rows = array();
        foreach ($query->result_array() as $row) {
            $rows[] = array(
                'tour_id' => (int) $row['tour_id'],
                'name' => (string) $row['name'],
                'bookings' => (int) $row['bookings'],
                'revenue' => (int) $row['revenue'],
            );
        }

        return $rows;
    }

    /**
     * Paid bookings whose tour date falls in the next few days, soonest first.
     */
    private function upcoming($today)
    {
        $query = $this->db->query(
            "SELECT b.book_id, b.book_res_code, b.book_name, b.book_date, b.book_guests,
                b.book_slot_start_time, b.book_tour_guide_id,
                COALESCE(t.tour_name, b.book_tour_name) AS tour_name,
                t.tour_type,
                COALESCE(s.slot_name, b.book_slot_name) AS slot_name,
                COALESCE(g.tour_guide_name, b.book_tour_guide_name) AS guide_name
            FROM tour_bookings b
            LEFT JOIN tours t ON t.tour_id = b.book_tour_id
            LEFT JOIN tour_slots s ON s.slot_id = b.book_slot_id
            LEFT JOIN tour_guides g ON g.tour_guide_id = b.book_tour_guide_id
            WHERE b.book_status = 'Completed' AND b.book_date BETWEEN ? AND ?
            ORDER BY b.book_date ASC, b.book_slot_start_time ASC, b.book_id ASC
            LIMIT " . (int) self::UPCOMING_LIMIT,
            array($today, $this->upcomingEnd($today))
        );

        return $query->result_array();
    }

    private function recentBookings()
    {
        $query = $this->db->query(
            'SELECT b.book_id, b.book_res_code, b.book_name, b.book_date, b.book_status,
                b.book_fee, b.book_added, b.steps_completed,
                COALESCE(t.tour_name, b.book_tour_name) AS tour_name
            FROM tour_bookings b
            LEFT JOIN tours t ON t.tour_id = b.book_tour_id
            ORDER BY b.book_id DESC
            LIMIT ' . (int) self::RECENT_LIMIT
        );

        return $query->result_array();
    }

    /**
     * Items an administrator should follow up on. Each entry is a plain count
     * (and sometimes a few sample rows); the view decides how to present it.
     */
    private function attention($today)
    {
        $horizonEnd = date('Y-m-d', strtotime($today . ' +' . self::AVAILABILITY_HORIZON_DAYS . ' days'));
        $promoEnd = date('Y-m-d', strtotime($today . ' +' . self::PROMO_EXPIRY_DAYS . ' days'));
        // Pending bookings are cancelled by the booking_cron job after
        // TOUR_BOOKING_CUTOFF_HOURS; anything older than that plus a grace period
        // means the job is not running.
        $staleBefore = date(
            'Y-m-d H:i:s',
            time() - (TOUR_BOOKING_CUTOFF_HOURS * 3600) - (self::STALE_PENDING_GRACE_MINUTES * 60)
        );

        $unassigned = $this->db->query(
            "SELECT COUNT(*) AS total
            FROM tour_bookings b
            INNER JOIN tours t ON t.tour_id = b.book_tour_id
            WHERE b.book_status = 'Completed' AND t.tour_type = 'Tour'
                AND (b.book_tour_guide_id IS NULL OR b.book_tour_guide_id = 0)
                AND b.book_date >= ?",
            array($today)
        )->row_array();

        $unassignedRows = $this->db->query(
            "SELECT b.book_id, b.book_name, b.book_date, COALESCE(t.tour_name, b.book_tour_name) AS tour_name
            FROM tour_bookings b
            INNER JOIN tours t ON t.tour_id = b.book_tour_id
            WHERE b.book_status = 'Completed' AND t.tour_type = 'Tour'
                AND (b.book_tour_guide_id IS NULL OR b.book_tour_guide_id = 0)
                AND b.book_date >= ?
            ORDER BY b.book_date ASC, b.book_id ASC
            LIMIT " . (int) self::ATTENTION_PREVIEW_LIMIT,
            array($today)
        )->result_array();

        $holds = $this->db->query(
            "SELECT COUNT(DISTINCT avail_book_id) AS total
            FROM tour_guide_availability
            WHERE avail_book_status = 'On-hold' AND avail_book_id > 0"
        )->row_array();

        $stale = $this->db->query(
            "SELECT COUNT(*) AS total FROM tour_bookings WHERE book_status = 'Pending' AND book_added <= ?",
            array($staleBefore)
        )->row_array();

        $commission = $this->db->query(
            "SELECT COUNT(*) AS bookings, COALESCE(SUM(book_ref_commission), 0) AS amount
            FROM tour_bookings
            WHERE book_status = 'Completed' AND book_ref_commission > 0 AND book_ref_commission_received = 'No'"
        )->row_array();

        $promos = $this->db->query(
            "SELECT COUNT(*) AS total
            FROM discount_codes
            WHERE discount_status = 'Enable' AND discount_expiry BETWEEN ? AND ?",
            array($today, $promoEnd)
        )->row_array();

        $guidesWithoutAvailability = $this->db->query(
            "SELECT COUNT(*) AS total
            FROM tour_guides g
            WHERE g.tour_guide_status = 'Enable'
                AND NOT EXISTS (
                    SELECT 1
                    FROM tour_guide_availability a
                    WHERE a.avail_tour_guide_id = g.tour_guide_id
                        AND a.avail_date BETWEEN ? AND ?
                        AND a.avail_status = 'Enable'
                        AND a.avail_book_status = 'Available'
                )",
            array($today, $horizonEnd)
        )->row_array();

        return array(
            'unassigned_count' => (int) $unassigned['total'],
            'unassigned_rows' => $unassignedRows,
            'holds' => (int) $holds['total'],
            'stale_pending' => (int) $stale['total'],
            'commission_bookings' => (int) $commission['bookings'],
            'commission_amount' => (int) $commission['amount'],
            'promos_expiring' => (int) $promos['total'],
            'guides_without_availability' => (int) $guidesWithoutAvailability['total'],
            'availability_days' => self::AVAILABILITY_HORIZON_DAYS,
            'promo_days' => self::PROMO_EXPIRY_DAYS,
        );
    }
}
