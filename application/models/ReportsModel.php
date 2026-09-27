<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data access for the manage/admin reports.
 *
 * Every report is built on the tour_bookings table. Historical facts (names,
 * prices, discounts, referral commissions, slot times) are read from the
 * snapshot columns saved on each booking. Current tables (tours, tour_guides,
 * referrals, ...) are only LEFT JOINed as a fallback, so a booking never
 * disappears from a report because a related record is missing or was edited.
 *
 * The same filter set is applied by row, count and summary queries through
 * applyFilters(), so they can never disagree.
 */
class ReportsModel extends SqlModel
{
    const PAYMENT_STATES = array('unpaid', 'partial', 'paid', 'refunded');

    /**
     * SQL classification of a booking's payment state. Refunds win over
     * everything else; the remaining states compare the paid amount with the
     * final total.
     */
    const PAYMENT_STATE_SQL = "CASE
        WHEN b.book_status = 'Refunded' OR b.book_refund_amount > 0 THEN 'refunded'
        WHEN b.book_paid_amount <= 0 THEN 'unpaid'
        WHEN b.book_paid_amount < b.book_fee THEN 'partial'
        ELSE 'paid'
    END";

    /**
     * Report definitions: how rows are produced, which bookings are in scope,
     * which grouping is used and the sort keys accepted from the request.
     * Sort values are trusted SQL expressions; request values only ever pick a
     * key from this list.
     */
    private $definitions = array(
        'bookings' => array(
            'kind' => 'rows',
            'scope' => '',
            'default_sort' => 'id',
            'default_order' => 'DESC',
            'sorts' => array(
                'id' => 'b.book_id',
                'reference' => 'b.book_res_code',
                'customer' => 'b.book_name',
                'tour' => 'tour_name',
                'date' => 'b.book_date',
                'guide' => 'guide_name',
                'language' => 'lang_name',
                'guests' => 'b.book_guests',
                'country' => 'country_name',
                'progress' => 'b.steps_completed',
                'status' => 'b.book_status',
                'total' => 'b.book_fee',
            ),
        ),
        'payments' => array(
            'kind' => 'rows',
            'scope' => '',
            'default_sort' => 'id',
            'default_order' => 'DESC',
            'sorts' => array(
                'id' => 'b.book_id',
                'reference' => 'b.book_res_code',
                'customer' => 'b.book_name',
                'tour' => 'tour_name',
                'date' => 'b.book_date',
                'status' => 'b.book_status',
                'total' => 'b.book_fee',
                'paid' => 'b.book_paid_amount',
                'refunded' => 'b.book_refund_amount',
                'net' => 'net_collected',
                'method' => 'b.book_payment_method',
                'payment_date' => 'b.book_payment_date',
                'transaction' => 'b.book_transaction_id',
                'commission' => 'b.book_ref_commission',
                'commission_received' => 'b.book_ref_commission_received',
            ),
        ),
        'referrals' => array(
            'kind' => 'rows',
            'scope' => 'referrals',
            'default_sort' => 'id',
            'default_order' => 'DESC',
            'sorts' => array(
                'id' => 'b.book_id',
                'reference' => 'b.book_res_code',
                'customer' => 'b.book_name',
                'referral' => 'ref_name',
                'promo' => 'b.book_promo_code',
                'promotion' => 'discount_name',
                'tour' => 'tour_name',
                'date' => 'b.book_date',
                'original' => 'b.book_original_total',
                'discount' => 'b.book_discount_amount',
                'total' => 'b.book_fee',
                'commission' => 'b.book_ref_commission',
                'paid' => 'b.book_ref_commission_received',
                'status' => 'b.book_status',
            ),
        ),
        'evaluation' => array(
            'kind' => 'group',
            'scope' => '',
            'group_by' => array('b.book_tour_id'),
            'default_sort' => 'tour_name',
            'default_order' => 'ASC',
            'sorts' => array(
                'tour_name' => 'tour_name',
                'total_bookings' => 'total_bookings',
                'completed' => 'completed',
                'pending' => 'pending',
                'cancelled' => 'cancelled',
                'refunded' => 'refunded',
                'completion_rate' => 'completion_rate',
                'guests' => 'guests',
                'final_total' => 'final_total',
                'paid_amount' => 'paid_amount',
                'refunded_amount' => 'refunded_amount',
                'net_collected' => 'net_collected',
            ),
        ),
        'gevaluation' => array(
            'kind' => 'group',
            'scope' => '',
            'group_by' => array('b.book_tour_guide_id'),
            'default_sort' => 'guide_name',
            'default_order' => 'ASC',
            'sorts' => array(
                'guide_name' => 'guide_name',
                'total_bookings' => 'total_bookings',
                'completed' => 'completed',
                'pending' => 'pending',
                'cancelled' => 'cancelled',
                'refunded' => 'refunded',
                'completion_rate' => 'completion_rate',
                'guests' => 'guests',
                'paid_amount' => 'paid_amount',
                'net_collected' => 'net_collected',
                'profile_rating' => 'profile_rating',
            ),
        ),
        'timeslots' => array(
            'kind' => 'group',
            'scope' => '',
            'group_by' => array(
                'b.book_tour_id',
                'b.book_slot_name',
                'b.book_slot_start_time',
                'b.book_slot_end_time',
                'b.book_slot_hours',
            ),
            'default_sort' => 'tour_name',
            'default_order' => 'ASC',
            'sorts' => array(
                'tour_name' => 'tour_name',
                'slot_name' => 'b.book_slot_name',
                'slot_time' => 'b.book_slot_start_time',
                'total_bookings' => 'total_bookings',
                'completed' => 'completed',
                'pending' => 'pending',
                'cancelled' => 'cancelled',
                'refunded' => 'refunded',
                'guests' => 'guests',
                'completion_rate' => 'completion_rate',
                'net_collected' => 'net_collected',
            ),
        ),
    );

    /**
     * Snapshot columns that may be updated from the report screens.
     */
    private $commissionColumns = array(
        'book_ref_commission_received',
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function isReport($report)
    {
        return isset($this->definitions[$report]);
    }

    public function sortKeys($report)
    {
        return $this->isReport($report)
            ? array_keys($this->definitions[$report]['sorts'])
            : array();
    }

    public function defaultSort($report)
    {
        return $this->definitions[$report]['default_sort'];
    }

    public function defaultOrder($report)
    {
        return $this->definitions[$report]['default_order'];
    }

    /**
     * Number of result rows (or groups for grouped reports) for a filter set.
     */
    public function countRows($report, array $filters)
    {
        $definition = $this->definitions[$report];

        if ($definition['kind'] === 'group') {
            $this->db->select('1', FALSE);
            $this->bookingBase($filters, $definition['scope']);
            $this->db->group_by($definition['group_by']);
            $subQuery = $this->db->get_compiled_select();

            $row = $this->db
                ->query('SELECT COUNT(*) AS total FROM (' . $subQuery . ') AS report_groups')
                ->row_array();

            return (int) $row['total'];
        }

        $this->bookingBase($filters, $definition['scope']);

        return (int) $this->db->count_all_results();
    }

    /**
     * Result rows. A $limit of 0 returns every row (print mode).
     */
    public function getRows($report, array $filters, $sort, $order, $limit = 0, $offset = 0)
    {
        $definition = $this->definitions[$report];

        switch ($report) {
            case 'bookings':
                $this->db->select($this->bookingRowColumns(), FALSE);
                $this->bookingBase($filters, $definition['scope'], TRUE);
                break;
            case 'payments':
                $this->db->select($this->paymentRowColumns(), FALSE);
                $this->bookingBase($filters, $definition['scope'], TRUE);
                break;
            case 'referrals':
                $this->db->select($this->referralRowColumns(), FALSE);
                $this->bookingBase($filters, $definition['scope'], TRUE);
                $this->db->join('referrals r', 'r.ref_id = b.book_ref_id', 'left');
                $this->db->join('discount_codes d', 'd.discount_id = b.book_promo_id', 'left');
                break;
            case 'evaluation':
                $this->db->select($this->tourPerformanceColumns(), FALSE);
                $this->bookingBase($filters, $definition['scope']);
                $this->db->join('tours t', 't.tour_id = b.book_tour_id', 'left');
                $this->db->group_by($definition['group_by']);
                break;
            case 'gevaluation':
                $this->db->select($this->guidePerformanceColumns(), FALSE);
                $this->bookingBase($filters, $definition['scope']);
                $this->db->join('tour_guides g', 'g.tour_guide_id = b.book_tour_guide_id', 'left');
                $this->db->group_by($definition['group_by']);
                break;
            case 'timeslots':
                $this->db->select($this->slotPerformanceColumns(), FALSE);
                $this->bookingBase($filters, $definition['scope']);
                $this->db->join('tours t', 't.tour_id = b.book_tour_id', 'left');
                $this->db->group_by($definition['group_by']);
                break;
            default:
                return array();
        }

        $this->applyOrder($definition, $sort, $order);

        if ((int) $limit > 0) {
            $this->db->limit((int) $limit, max(0, (int) $offset));
        }

        return $this->db->get()->result_array();
    }

    /**
     * Totals across the complete filtered result set (not just one page).
     * One query returns the superset of every figure the report cards need.
     */
    public function getSummary($report, array $filters)
    {
        $definition = $this->definitions[$report];

        $this->db->select(
            "COUNT(*) AS total,
            COALESCE(SUM(b.book_status = 'Completed'), 0) AS completed,
            COALESCE(SUM(b.book_status = 'Pending'), 0) AS pending,
            COALESCE(SUM(b.book_status = 'Cancelled'), 0) AS cancelled,
            COALESCE(SUM(b.book_status = 'Refunded'), 0) AS refunded,
            COALESCE(SUM(b.book_guests), 0) AS guests,
            COALESCE(SUM(b.book_fee), 0) AS final_total,
            COALESCE(SUM(b.book_original_total), 0) AS original_total,
            COALESCE(SUM(b.book_discount_amount), 0) AS discount_total,
            COALESCE(SUM(b.book_paid_amount), 0) AS paid_total,
            COALESCE(SUM(b.book_refund_amount), 0) AS refunded_total,
            COALESCE(SUM(b.book_paid_amount), 0) - COALESCE(SUM(b.book_refund_amount), 0) AS net_collected,
            COALESCE(SUM(CASE
                WHEN b.book_status = 'Completed' AND b.book_paid_amount < b.book_fee
                THEN CAST(b.book_fee AS SIGNED) - CAST(b.book_paid_amount AS SIGNED)
                ELSE 0
            END), 0) AS outstanding,
            COALESCE(SUM(b.book_ref_commission), 0) AS commission_total,
            COALESCE(SUM(CASE WHEN b.book_ref_commission_received = 'Yes' THEN b.book_ref_commission ELSE 0 END), 0) AS commission_received,
            COALESCE(SUM(b.book_promo_code <> '' OR b.book_promo_id > 0), 0) AS promo_bookings,
            COALESCE(SUM(b.book_ref_commission), 0) AS ref_commission,
            COALESCE(SUM(CASE WHEN b.book_ref_commission_received = 'Yes' THEN b.book_ref_commission ELSE 0 END), 0) AS ref_paid,
            COUNT(DISTINCT NULLIF(b.book_currency, '')) AS currencies",
            FALSE
        );
        $this->bookingBase($filters, $definition['scope']);
        $row = $this->db->get()->row_array();

        $summary = array();
        foreach ($row as $key => $value) {
            $summary[$key] = (int) $value;
        }
        $summary['commission_remaining'] = $summary['commission_total'] - $summary['commission_received'];
        $summary['ref_unpaid'] = $summary['ref_commission'] - $summary['ref_paid'];
        $summary['completion_rate'] = $summary['total'] > 0
            ? round(100 * $summary['completed'] / $summary['total'], 1)
            : 0;

        return $summary;
    }

    /**
     * True when the report has any booking at all, ignoring filters. Lets the
     * view tell "nothing exists yet" apart from "nothing matches".
     */
    public function hasRecords($report)
    {
        $this->bookingBase(array(), $this->definitions[$report]['scope']);

        return $this->db->count_all_results() > 0;
    }

    /**
     * Option lists for the filter controls. Only the lists a report needs are
     * loaded. Current tables provide the lists; bookings never depend on them.
     */
    public function filterOptions(array $names)
    {
        $options = array();

        foreach ($names as $name) {
            switch ($name) {
                case 'tours':
                    $options['tours'] = $this->db
                        ->select('tour_id AS id, tour_name AS name, tour_type AS type')
                        ->order_by('tour_order', 'ASC')
                        ->order_by('tour_name', 'ASC')
                        ->get('tours')
                        ->result_array();
                    break;
                case 'guides':
                    $options['guides'] = $this->db
                        ->select('tour_guide_id AS id, tour_guide_name AS name')
                        ->order_by('tour_guide_name', 'ASC')
                        ->get('tour_guides')
                        ->result_array();
                    break;
                case 'slots':
                    $options['slots'] = $this->db
                        ->select('slot_id AS id, slot_title AS name')
                        ->order_by('slot_order', 'ASC')
                        ->get('tour_slots')
                        ->result_array();
                    break;
                case 'languages':
                    $options['languages'] = $this->db
                        ->select('lang_id AS id, lang_name AS name')
                        ->order_by('lang_order', 'ASC')
                        ->get('tour_languages')
                        ->result_array();
                    break;
                case 'countries':
                    // Only countries that actually appear on a booking.
                    $options['countries'] = $this->db
                        ->query(
                            'SELECT c.id, c.name FROM countries c
                            WHERE c.id IN (SELECT DISTINCT book_country_id FROM tour_bookings WHERE book_country_id IS NOT NULL)
                            ORDER BY c.name ASC'
                        )
                        ->result_array();
                    break;
                case 'payment_methods':
                    $options['payment_methods'] = $this->distinctBookingValues('book_payment_method');
                    break;
                case 'promo_codes':
                    $options['promo_codes'] = $this->distinctBookingValues('book_promo_code');
                    break;
                case 'referrals':
                    // Snapshot IDs and names, so removed referrals stay filterable.
                    $options['referrals'] = $this->db
                        ->query(
                            "SELECT b.book_ref_id AS id, COALESCE(MAX(r.ref_name), MAX(b.book_ref_name)) AS name
                            FROM tour_bookings b
                            LEFT JOIN referrals r ON r.ref_id = b.book_ref_id
                            WHERE b.book_ref_id IS NOT NULL AND b.book_ref_id > 0
                            GROUP BY b.book_ref_id
                            ORDER BY name ASC"
                        )
                        ->result_array();
                    break;
            }
        }

        return $options;
    }

    /**
     * Updates a commission-paid flag. Only Completed bookings may be updated.
     * Payment reports can additionally lock the flag once the saved tour end
     * date and time have passed.
     */
    public function updateCommissionReceived($bookId, $column, $value, $enforceTourEnd = FALSE)
    {
        if (!in_array($column, $this->commissionColumns, TRUE) || !in_array($value, array('Yes', 'No'), TRUE)) {
            return 'failed';
        }

        $booking = $this->db
            ->select('book_id, book_status, book_date, book_slot_start_time, book_slot_end_time')
            ->where('book_id', (int) $bookId)
            ->get('tour_bookings')
            ->row_array();

        if (empty($booking)) {
            return 'not_found';
        }

        if ($booking['book_status'] !== 'Completed') {
            return 'not_completed';
        }

        if ($enforceTourEnd && $this->tourEndHasPassed($booking)) {
            return 'tour_ended';
        }

        $updated = $this->db
            ->where('book_id', (int) $bookId)
            ->update('tour_bookings', array(
                $column => $value,
                'book_updated' => date('Y-m-d H:i:s'),
            ));

        return $updated ? 'ok' : 'failed';
    }

    private function tourEndHasPassed(array $booking)
    {
        if (empty($booking['book_date']) || empty($booking['book_slot_end_time'])) {
            return FALSE;
        }

        $tourEnd = strtotime($booking['book_date'] . ' ' . $booking['book_slot_end_time']);
        if ($tourEnd === FALSE) {
            return FALSE;
        }

        if (
            !empty($booking['book_slot_start_time'])
            && $booking['book_slot_end_time'] <= $booking['book_slot_start_time']
        ) {
            $tourEnd = strtotime('+1 day', $tourEnd);
        }

        return $tourEnd < time();
    }

    /**
     * FROM clause, optional row-level joins and the shared filters.
     */
    private function bookingBase(array $filters, $scope = '', $withJoins = FALSE)
    {
        $this->db->from('tour_bookings b');

        if ($withJoins) {
            $this->db->join('tours t', 't.tour_id = b.book_tour_id', 'left');
            $this->db->join('tour_guides g', 'g.tour_guide_id = b.book_tour_guide_id', 'left');
            $this->db->join('tour_languages l', 'l.lang_id = b.book_lang_id', 'left');
            $this->db->join('countries c', 'c.id = b.book_country_id', 'left');
        }

        if ($scope === 'referrals') {
            $this->db->where(
                "(b.book_promo_id > 0 OR b.book_promo_code <> '' OR b.book_ref_id IS NOT NULL OR b.book_discount_amount > 0 OR b.book_ref_commission > 0)",
                NULL,
                FALSE
            );
        }

        $this->applyFilters($filters);
    }

    /**
     * Applies every supported, already validated filter. Empty values are
     * ignored. All values are bound by the query builder.
     */
    private function applyFilters(array $filters)
    {
        if (!empty($filters['q'])) {
            $this->applyKeywords($filters['q']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('b.book_status', $filters['status']);
        }

        $idColumns = array(
            'tour_id' => 'b.book_tour_id',
            'slot_id' => 'b.book_slot_id',
            'lang_id' => 'b.book_lang_id',
            'country_id' => 'b.book_country_id',
            'ref_id' => 'b.book_ref_id',
        );
        foreach ($idColumns as $key => $column) {
            if (!empty($filters[$key])) {
                $this->db->where($column, (int) $filters[$key]);
            }
        }

        if (isset($filters['guide_id']) && $filters['guide_id'] === 'none') {
            $this->db->where('(b.book_tour_guide_id IS NULL OR b.book_tour_guide_id = 0)', NULL, FALSE);
        } elseif (!empty($filters['guide_id'])) {
            $this->db->where('b.book_tour_guide_id', (int) $filters['guide_id']);
        }

        $this->applyDateRange('b.book_date', $filters, 'date', FALSE);
        $this->applyDateRange('b.book_added', $filters, 'created', TRUE);
        $this->applyDateRange('b.book_payment_date', $filters, 'paid', TRUE);

        if (!empty($filters['pay_state']) && in_array($filters['pay_state'], self::PAYMENT_STATES, TRUE)) {
            $this->db->where(self::PAYMENT_STATE_SQL . ' = ' . $this->db->escape($filters['pay_state']), NULL, FALSE);
        }

        if (!empty($filters['pay_method'])) {
            $this->db->where('b.book_payment_method', $filters['pay_method']);
        }

        if (!empty($filters['comm'])) {
            $this->db->where('b.book_ref_commission_received', $filters['comm']);
        }

        if (!empty($filters['promo'])) {
            $this->db->where('b.book_promo_code', $filters['promo']);
        }

        if (!empty($filters['discount'])) {
            $this->db->where('b.book_discount_amount ' . ($filters['discount'] === 'Yes' ? '>' : '='), 0);
        }

        if (!empty($filters['ref_paid'])) {
            $this->db->where('b.book_ref_commission_received', $filters['ref_paid']);
        }
    }

    /**
     * Every keyword must match at least one searchable column. Short terms
     * (one or two characters) are searched like any other term.
     */
    private function applyKeywords($keywords)
    {
        $columns = array(
            'b.book_name',
            'b.book_email',
            'b.book_phone',
            'b.book_res_code',
            'b.book_tour_name',
            'b.book_tour_guide_name',
            'b.book_slot_name',
            'b.book_promo_code',
            'b.book_ref_name',
            'b.book_payer_email',
            'b.book_transaction_id',
        );

        $terms = preg_split('/\s+/', trim((string) $keywords), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terms as $term) {
            $this->db->group_start();
            foreach ($columns as $column) {
                $this->db->or_like($column, $term, 'both');
            }
            if (ctype_digit($term) && strlen($term) <= 10) {
                $this->db->or_where('b.book_id', (int) $term);
            }
            $this->db->group_end();
        }
    }

    /**
     * Inclusive date range. Datetime columns cover the whole last day.
     */
    private function applyDateRange($column, array $filters, $prefix, $isDateTime)
    {
        $from = isset($filters[$prefix . '_from']) ? $filters[$prefix . '_from'] : '';
        $to = isset($filters[$prefix . '_to']) ? $filters[$prefix . '_to'] : '';

        if ($from === '' || $to === '') {
            return;
        }

        $this->db->where($column . ' >=', $isDateTime ? $from . ' 00:00:00' : $from);
        $this->db->where($column . ' <=', $isDateTime ? $to . ' 23:59:59' : $to);
    }

    /**
     * ORDER BY from the report's own allowlist, plus a stable tie-breaker.
     */
    private function applyOrder(array $definition, $sort, $order)
    {
        $expression = isset($definition['sorts'][$sort])
            ? $definition['sorts'][$sort]
            : $definition['sorts'][$definition['default_sort']];
        $direction = strtoupper((string) $order) === 'DESC' ? 'DESC' : 'ASC';

        $this->db->order_by($expression, $direction, FALSE);

        if ($definition['kind'] === 'rows') {
            $this->db->order_by('b.book_id', 'DESC', FALSE);
        } else {
            $this->db->order_by('total_bookings', 'DESC', FALSE);
        }
    }

    private function distinctBookingValues($column)
    {
        if (!in_array($column, array('book_payment_method', 'book_promo_code'), TRUE)) {
            return array();
        }

        $rows = $this->db
            ->distinct()
            ->select($column)
            ->where($column . ' IS NOT NULL', NULL, FALSE)
            ->where($column . " <> ''", NULL, FALSE)
            ->order_by($column, 'ASC')
            ->get('tour_bookings')
            ->result_array();

        return array_column($rows, $column);
    }

    private function bookingRowColumns()
    {
        return "b.book_id,
            b.book_res_code,
            b.book_name,
            b.book_date,
            b.book_status,
            b.book_guests,
            b.book_fee,
            b.book_currency,
            b.steps_completed,
            b.book_slot_name,
            b.book_slot_start_time,
            b.book_slot_end_time,
            COALESCE(NULLIF(b.book_tour_name, ''), t.tour_name) AS tour_name,
            CASE WHEN TRIM(t.tour_type) = 'Experience' THEN 5 ELSE 6 END AS total_steps,
            COALESCE(NULLIF(b.book_tour_guide_name, ''), g.tour_guide_name) AS guide_name,
            COALESCE(NULLIF(b.book_lang_name, ''), l.lang_name) AS lang_name,
            COALESCE(NULLIF(b.book_country_name, ''), c.name) AS country_name";
    }

    private function paymentRowColumns()
    {
        return "b.book_id,
            b.book_res_code,
            b.book_name,
            b.book_date,
            b.book_slot_start_time,
            b.book_slot_end_time,
            b.book_status,
            b.book_fee,
            b.book_paid_amount,
            b.book_refund_amount,
            b.book_payment_method,
            b.book_payment_date,
            b.book_transaction_id,
            b.book_currency,
            b.book_ref_commission,
            b.book_ref_commission_received,
            COALESCE(NULLIF(b.book_tour_name, ''), t.tour_name) AS tour_name,
            CAST(b.book_paid_amount AS SIGNED) - CAST(b.book_refund_amount AS SIGNED) AS net_collected,
            " . self::PAYMENT_STATE_SQL . " AS payment_state";
    }

    private function referralRowColumns()
    {
        return "b.book_id,
            b.book_res_code,
            b.book_name,
            b.book_date,
            b.book_status,
            b.book_currency,
            b.book_promo_code,
            b.book_original_total,
            b.book_discount_amount,
            b.book_discount_type,
            b.book_discount_value,
            b.book_fee,
            b.book_ref_commission,
            b.book_ref_commission_received,
            b.book_slot_name,
            COALESCE(NULLIF(b.book_tour_name, ''), t.tour_name) AS tour_name,
            COALESCE(NULLIF(b.book_ref_name, ''), r.ref_name) AS ref_name,
            COALESCE(NULLIF(b.book_discount_name, ''), d.discount_name) AS discount_name";
    }

    private function statusCountColumns()
    {
        return "COUNT(*) AS total_bookings,
            SUM(b.book_status = 'Completed') AS completed,
            SUM(b.book_status = 'Pending') AS pending,
            SUM(b.book_status = 'Cancelled') AS cancelled,
            SUM(b.book_status = 'Refunded') AS refunded,
            ROUND(100 * SUM(b.book_status = 'Completed') / COUNT(*), 1) AS completion_rate,
            COALESCE(SUM(b.book_guests), 0) AS guests,
            SUM(b.book_fee) AS final_total,
            SUM(b.book_paid_amount) AS paid_amount,
            SUM(b.book_refund_amount) AS refunded_amount,
            SUM(b.book_paid_amount) - SUM(b.book_refund_amount) AS net_collected";
    }

    private function tourPerformanceColumns()
    {
        return "b.book_tour_id AS group_id,
            COALESCE(MAX(t.tour_name), MAX(b.book_tour_name)) AS tour_name,
            MAX(t.tour_type) AS tour_type,
            " . $this->statusCountColumns();
    }

    private function guidePerformanceColumns()
    {
        return "b.book_tour_guide_id AS group_id,
            COALESCE(MAX(g.tour_guide_name), MAX(b.book_tour_guide_name)) AS guide_name,
            MAX(g.tour_guide_rating) AS profile_rating,
            " . $this->statusCountColumns();
    }

    private function slotPerformanceColumns()
    {
        return "b.book_tour_id AS group_id,
            COALESCE(MAX(t.tour_name), MAX(b.book_tour_name)) AS tour_name,
            b.book_slot_name AS slot_name,
            b.book_slot_start_time AS slot_start,
            b.book_slot_end_time AS slot_end,
            b.book_slot_hours AS slot_hours,
            " . $this->statusCountColumns();
    }
}
