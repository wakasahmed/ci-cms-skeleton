<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only figures for the manage dashboard.
 *
 * Built around appointment requests from the website booking form, contact
 * messages and the salon catalogue. A request counts towards the period in
 * which it was received (appointment_added), not the appointment date.
 */
class Dashboard_model extends CI_Model
{
    const UPCOMING_DAYS = 7;
    const UPCOMING_LIMIT = 8;
    const RECENT_LIMIT = 6;
    const TOP_SERVICES_LIMIT = 5;
    const OFFER_ENDING_DAYS = 7;

    /** Hours a New request may wait before the dashboard flags it. */
    const REPLY_WITHIN_HOURS = 24;

    /** Appointment statuses, in display order. */
    const STATUSES = array('New', 'Confirmed', 'Completed', 'Cancelled');

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
            'topServices' => $this->topServices($period),
            'upcoming' => $this->upcoming($today),
            'recent' => $this->recent(),
            'attention' => $this->attention($today),
            'catalogue' => $this->catalogue($today),
        );
    }

    /**
     * Current and previous period boundaries. The previous period is the same
     * length immediately before the current one, used for the trend badges.
     */
    private function period($today, $days)
    {
        $from = date('Y-m-d', strtotime($today.' -'.($days - 1).' days'));

        return array(
            'days' => $days,
            'from' => $from,
            'to' => $today,
            'previous_from' => date('Y-m-d', strtotime($from.' -'.$days.' days')),
            'previous_to' => date('Y-m-d', strtotime($from.' -1 day')),
        );
    }

    private function kpi(array $period, $today)
    {
        $requests = $this->countBetween('appointments', 'appointment_added', $period['from'], $period['to']);
        $previousRequests = $this->countBetween('appointments', 'appointment_added', $period['previous_from'], $period['previous_to']);
        $messages = $this->countBetween('contact_requests', 'created_at', $period['from'], $period['to']);
        $previousMessages = $this->countBetween('contact_requests', 'created_at', $period['previous_from'], $period['previous_to']);

        return array(
            'awaiting' => (int) $this->db
                ->where('appointment_status', 'New')
                ->count_all_results('appointments'),
            'requests' => $requests,
            'upcoming' => (int) $this->db
                ->where('appointment_status', 'Confirmed')
                ->where('appointment_date >=', $today)
                ->where('appointment_date <=', $this->upcomingEnd($today))
                ->count_all_results('appointments'),
            'messages' => $messages,
            'trend' => array(
                'requests' => $this->change($requests, $previousRequests),
                'messages' => $this->change($messages, $previousMessages),
            ),
        );
    }

    /**
     * Rows in $table whose $column (a DATETIME) falls on a day in the range.
     */
    private function countBetween($table, $column, $from, $to)
    {
        return (int) $this->db
            ->where($column.' >=', $from.' 00:00:00')
            ->where($column.' <=', $to.' 23:59:59')
            ->count_all_results($table);
    }

    /**
     * Percentage change, or NULL when there is nothing to compare with.
     */
    private function change($current, $previous)
    {
        if ((int) $previous === 0) {
            return (int) $current === 0 ? 0 : NULL;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function upcomingEnd($today)
    {
        return date('Y-m-d', strtotime($today.' +'.self::UPCOMING_DAYS.' days'));
    }

    /**
     * Requests received per day across the period, including empty days.
     */
    private function series(array $period)
    {
        $rows = $this->db
            ->select('DATE(appointment_added) AS day, COUNT(*) AS total', FALSE)
            ->where('appointment_added >=', $period['from'].' 00:00:00')
            ->where('appointment_added <=', $period['to'].' 23:59:59')
            ->group_by('DATE(appointment_added)', FALSE)
            ->get('appointments')
            ->result_array();
        $totals = array_column($rows, 'total', 'day');
        $labels = array();
        $requests = array();

        for ($day = $period['from']; $day <= $period['to']; $day = date('Y-m-d', strtotime($day.' +1 day'))) {
            $labels[] = $day;
            $requests[] = isset($totals[$day]) ? (int) $totals[$day] : 0;
        }

        return array(
            'labels' => $labels,
            'requests' => $requests,
        );
    }

    /**
     * Requests received in the period, by current status.
     */
    private function statusBreakdown(array $period)
    {
        $rows = $this->db
            ->select('appointment_status AS status, COUNT(*) AS total')
            ->where('appointment_added >=', $period['from'].' 00:00:00')
            ->where('appointment_added <=', $period['to'].' 23:59:59')
            ->group_by('appointment_status')
            ->get('appointments')
            ->result_array();
        $totals = array_column($rows, 'total', 'status');
        $breakdown = array();

        foreach (self::STATUSES as $status) {
            $breakdown[$status] = isset($totals[$status]) ? (int) $totals[$status] : 0;
        }

        return $breakdown;
    }

    /**
     * Most requested services in the period, excluding cancelled requests.
     * Grouped by the name stored on the request, so retired services still count.
     */
    private function topServices(array $period)
    {
        return $this->db
            ->select('s.service_name AS name, COUNT(*) AS requests')
            ->from('appointment_services s')
            ->join('appointments a', 'a.appointment_id = s.appointment_id')
            ->where('a.appointment_added >=', $period['from'].' 00:00:00')
            ->where('a.appointment_added <=', $period['to'].' 23:59:59')
            ->where('a.appointment_status !=', 'Cancelled')
            ->group_by('s.service_name')
            ->order_by('requests', 'DESC')
            ->order_by('s.service_name', 'ASC')
            ->limit(self::TOP_SERVICES_LIMIT)
            ->get()
            ->result_array();
    }

    /**
     * New and confirmed appointments from today onwards, soonest first.
     */
    private function upcoming($today)
    {
        $rows = $this->db
            ->where_in('appointment_status', array('New', 'Confirmed'))
            ->where('appointment_date >=', $today)
            ->order_by('appointment_date', 'ASC')
            ->order_by('appointment_time', 'ASC')
            ->limit(self::UPCOMING_LIMIT)
            ->get('appointments')
            ->result_array();

        return $this->withServiceNames($rows);
    }

    private function recent()
    {
        $rows = $this->db
            ->order_by('appointment_added', 'DESC')
            ->order_by('appointment_id', 'DESC')
            ->limit(self::RECENT_LIMIT)
            ->get('appointments')
            ->result_array();

        return $this->withServiceNames($rows);
    }

    /**
     * Adds a comma-separated 'services' summary to each appointment row.
     */
    private function withServiceNames(array $rows)
    {
        $ids = array_map('intval', array_column($rows, 'appointment_id'));

        if (empty($ids)) {
            return $rows;
        }

        $services = $this->db
            ->select('appointment_id, service_name')
            ->where_in('appointment_id', $ids)
            ->order_by('id', 'ASC')
            ->get('appointment_services')
            ->result_array();
        $names = array();

        foreach ($services as $service) {
            $names[(int) $service['appointment_id']][] = $service['service_name'];
        }

        foreach ($rows as $index => $row) {
            $id = (int) $row['appointment_id'];
            $rows[$index]['services'] = isset($names[$id]) ? implode(', ', $names[$id]) : '';
        }

        return $rows;
    }

    private function attention($today)
    {
        $replyCutoff = date('Y-m-d H:i:s', strtotime('-'.self::REPLY_WITHIN_HOURS.' hours'));
        $offerEnd = date('Y-m-d', strtotime($today.' +'.self::OFFER_ENDING_DAYS.' days'));

        return array(
            'waiting' => (int) $this->db
                ->where('appointment_status', 'New')
                ->where('appointment_added <', $replyCutoff)
                ->count_all_results('appointments'),
            'new_past' => (int) $this->db
                ->where('appointment_status', 'New')
                ->where('appointment_date <', $today)
                ->count_all_results('appointments'),
            'confirmed_past' => (int) $this->db
                ->where('appointment_status', 'Confirmed')
                ->where('appointment_date <', $today)
                ->count_all_results('appointments'),
            'offers_ending' => (int) $this->db
                ->where('offer_status', 'Enable')
                ->where('offer_valid_to >=', $today)
                ->where('offer_valid_to <=', $offerEnd)
                ->count_all_results('offers'),
            'placeholder_artists' => (int) $this->db
                ->where('artist_status', 'Enable')
                ->where('artist_is_placeholder', 1)
                ->count_all_results('artists'),
        );
    }

    /**
     * Counts for the catalogue and content section.
     */
    private function catalogue($today)
    {
        return array(
            'services' => (int) $this->db->where('service_status', 'Enable')->count_all_results('services'),
            'artists' => (int) $this->db->where('artist_status', 'Enable')->count_all_results('artists'),
            'gallery' => (int) $this->db->where('image_status', 'Enable')->count_all_results('gallery_images'),
            'offers' => (int) $this->db
                ->where('offer_status', 'Enable')
                ->group_start()
                    ->where('offer_valid_from IS NULL', NULL, FALSE)
                    ->or_where('offer_valid_from <=', $today)
                ->group_end()
                ->group_start()
                    ->where('offer_valid_to IS NULL', NULL, FALSE)
                    ->or_where('offer_valid_to >=', $today)
                ->group_end()
                ->count_all_results('offers'),
            'pages' => (int) $this->db->count_all_results('pages'),
            'sliders' => (int) $this->db->count_all_results('sliders'),
            'blogs' => (int) $this->db->count_all_results('blogs'),
            'reviews' => (int) $this->db->count_all_results('customer_reviews'),
            'faqs' => (int) $this->db->count_all_results('faqs'),
            'admins' => (int) $this->db->count_all_results('admin_users'),
        );
    }
}
