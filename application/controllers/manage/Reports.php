<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Manage/admin reports.
 *
 * Filters, sorting and paging travel as normal GET parameters
 * (/manage/reports/bookings?status=Completed&sort=date&order=DESC). The old
 * segment based URLs are still accepted and redirected to the new format.
 * Every request value is validated against an allowlist before it reaches
 * the model.
 */
class Reports extends CI_Controller
{
    public $tblName = 'tour_bookings';
    public $colPrefix = 'book_';
    public $pKey = 'book_id';
    public $moduleName = 'Reports';
    public $controller = 'reports';
    public $per_page = 10;
    public $tStatus = 'book_status';
    public $user_data = array();

    private $bookingStatuses = array('Pending', 'Completed', 'Cancelled', 'Refunded');

    /**
     * Presentation config per report. `filters` lists every filter the report
     * accepts, `primary` the ones shown before "More filters".
     */
    private $reports = array(
        'bookings' => array(
            'title' => 'Bookings Report',
            'view' => 'reportByTour',
            'orientation' => 'landscape',
            'description' => 'Every booking, including partial pending bookings and experiences without a guide.',
            'filters' => array('q', 'status', 'tour_id', 'date', 'guide_id', 'slot_id', 'lang_id', 'country_id', 'created'),
            'primary' => array('q', 'status', 'tour_id', 'date'),
        ),
        'payments' => array(
            'title' => 'Payments Report',
            'view' => 'reportByPayments',
            'orientation' => 'landscape',
            'description' => 'Amounts paid, refunded and collected per booking, based on the recorded payment details.',
            'filters' => array('q', 'status', 'pay_state', 'date', 'tour_id', 'pay_method', 'comm', 'paid', 'guide_id', 'country_id'),
            'primary' => array('q', 'status', 'pay_state', 'date'),
        ),
        'referrals' => array(
            'title' => 'Discounts & Referrals Report',
            'view' => 'reportByReferrals',
            'orientation' => 'landscape',
            'description' => 'Bookings that used a promo code, received a discount or carry a referral commission, from the booking snapshot.',
            'filters' => array('q', 'status', 'ref_id', 'promo', 'discount', 'ref_paid', 'tour_id', 'date', 'created'),
            'primary' => array('q', 'status', 'ref_id', 'promo'),
        ),
        'evaluation' => array(
            'title' => 'Tour Performance',
            'view' => 'reportByEvaluation',
            'orientation' => 'landscape',
            'description' => 'Booking outcomes and revenue per tour or experience. Tours without matching bookings are not listed.',
            'filters' => array('status', 'tour_id', 'date'),
            'primary' => array('status', 'tour_id', 'date'),
        ),
        'gevaluation' => array(
            'title' => 'Guide Performance',
            'view' => 'reportByEvaluationGuide',
            'orientation' => 'landscape',
            'description' => 'Booking outcomes per guide. Bookings without a guide are grouped as Unassigned.',
            'filters' => array('status', 'guide_id', 'tour_id', 'date'),
            'primary' => array('status', 'guide_id', 'tour_id', 'date'),
        ),
        'timeslots' => array(
            'title' => 'Tours & Time Slots',
            'view' => 'reportByTimeSlots',
            'orientation' => 'landscape',
            'description' => 'Booking outcomes per tour and time slot, using the slot saved on each booking.',
            'filters' => array('status', 'tour_id', 'slot_id', 'date'),
            'primary' => array('status', 'tour_id', 'slot_id', 'date'),
        ),
    );

    /**
     * Filter control metadata. `options` names a list loaded by
     * ReportsModel::filterOptions(); `search` enables the Select2 search box.
     */
    private $filterFields = array(
        'q' => array('label' => 'Keywords', 'type' => 'search'),
        'status' => array('label' => 'Status', 'type' => 'select', 'options' => 'statuses'),
        'tour_id' => array('label' => 'Tour / experience', 'type' => 'select', 'options' => 'tours', 'search' => TRUE),
        'guide_id' => array('label' => 'Guide', 'type' => 'select', 'options' => 'guides', 'search' => TRUE),
        'slot_id' => array('label' => 'Slot', 'type' => 'select', 'options' => 'slots'),
        'lang_id' => array('label' => 'Language', 'type' => 'select', 'options' => 'languages'),
        'country_id' => array('label' => 'Country', 'type' => 'select', 'options' => 'countries', 'search' => TRUE),
        'date' => array('label' => 'Tour date', 'type' => 'daterange'),
        'created' => array('label' => 'Booked on', 'type' => 'daterange'),
        'paid' => array('label' => 'Payment date', 'type' => 'daterange'),
        'pay_state' => array('label' => 'Payment state', 'type' => 'select', 'options' => 'payment_states'),
        'pay_method' => array('label' => 'Payment method', 'type' => 'select', 'options' => 'payment_methods'),
        'comm' => array('label' => 'Commission received', 'type' => 'select', 'options' => 'yes_no'),
        'ref_id' => array('label' => 'Referral', 'type' => 'select', 'options' => 'referrals', 'search' => TRUE),
        'promo' => array('label' => 'Promo code', 'type' => 'select', 'options' => 'promo_codes', 'search' => TRUE),
        'discount' => array('label' => 'Has discount', 'type' => 'select', 'options' => 'yes_no'),
        'ref_paid' => array('label' => 'Referral commission', 'type' => 'select', 'options' => 'paid_unpaid'),
    );

    /**
     * Old sort names from the segment based URLs mapped to the current keys.
     */
    private $legacySorts = array(
        'book_id' => 'id',
        'book_name' => 'customer',
        'book_tour_name' => 'tour',
        'book_date' => 'date',
        'tour_guide_name' => 'guide',
        'book_status' => 'status',
        'book_fee' => 'total',
        'book_ref_commission' => 'commission',
        'book_ref_commission_received' => 'paid',
        'ref_name' => 'referral',
        'discount_name' => 'promotion',
        'consumed' => 'total_bookings',
    );

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        );
        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
        }

        $this->load->helper('report');
        $this->load->model('ReportsModel');
    }

    public function bookings()
    {
        $this->renderReport('bookings');
    }

    public function payments()
    {
        $this->renderReport('payments');
    }

    public function referrals()
    {
        $this->renderReport('referrals');
    }

    public function evaluation()
    {
        $this->renderReport('evaluation');
    }

    public function gevaluation()
    {
        $this->renderReport('gevaluation');
    }

    public function timeslots()
    {
        $this->renderReport('timeslots');
    }

    /**
     * Updates the "commission received" flag of a payment row.
     */
    public function dopay()
    {
        $this->updateCommission('payments', 'book_ref_commission_received', TRUE);
    }

    /**
     * Updates the "referral commission paid" flag of a discount/referral row.
     */
    public function dorefpay()
    {
        $this->updateCommission('referrals', 'book_ref_commission_received');
    }

    private function renderReport($report)
    {
        $config = $this->reports[$report];
        $this->redirectLegacyUrl($report);

        $isPrint = $this->isPrintRequest();
        $filters = $this->readFilters($config['filters'], $this->input->get());
        list($sort, $order) = $this->readSort($report);
        $perPage = $this->resolvePerPage();
        $filterParams = $this->filterParams($filters);
        $sortParams = $this->sortParams($report, $sort, $order);

        $total = $this->ReportsModel->countRows($report, $filters);
        $page = 1;
        $offset = 0;
        if (!$isPrint && $perPage > 0 && $total > 0) {
            $page = min(max(1, (int) $this->input->get('page')), (int) ceil($total / $perPage));
            $offset = ($page - 1) * $perPage;
        }

        $records = $total > 0
            ? $this->ReportsModel->getRows($report, $filters, $sort, $order, $isPrint ? 0 : $perPage, $offset)
            : array();

        $currency = $this->businessCurrency();
        $summary = $this->ReportsModel->getSummary($report, $filters);
        $options = $this->loadOptions($config['filters']);
        $chips = $this->activeChips($report, $filters, $options, $sortParams);

        $data = array(
            'page_title' => PROJECT_TITLE . ' | ' . $config['title'],
            'userdata' => $this->user_data,
            'reportsActive' => 1,
            'reportType' => $report,
            'useReports' => TRUE,
            'report' => $report,
            'report_config' => $config,
            'report_ctx' => array(
                'report' => $report,
                'sort' => $sort,
                'order' => $order,
                'query' => $filterParams,
                'print' => $isPrint,
            ),
            'is_print' => $isPrint,
            'company_name' => PROJECT_TITLE,
            'generated_at' => date(ADMIN_DATETIME_FORMAT),
            'filters' => $filters,
            'filter_controls' => $this->filterControls($config, $filters, $options),
            'filter_chips' => $chips,
            'has_active_filters' => !empty($chips),
            'clear_url' => report_url($report),
            'print_url' => report_url($report, array_merge($filterParams, $sortParams, array('print' => 1, 'autoprint' => 1))),
            'back_url' => report_url($report, array_merge($filterParams, $sortParams)),
            'form_hidden' => $sortParams,
            'records' => $records,
            'total_rows' => $total,
            'has_any_records' => $total > 0 ? TRUE : $this->ReportsModel->hasRecords($report),
            'summary' => $summary,
            'summary_cards' => report_summary_cards($report, $summary, $currency),
            'multiple_currencies' => $summary['currencies'] > 1,
            'currency' => $currency,
            'per_page' => $perPage,
            'page_numb' => $offset,
            'paginate' => $this->buildPagination($report, $filterParams, $sortParams, $total, $perPage),
            'commission_endpoint' => base_url('manage/reports/' . ($report === 'payments' ? 'dopay' : 'dorefpay')),
            'report_url' => report_url($report),
            'useSweetAlert' => TRUE,
        );

        if ($isPrint) {
            $this->load->view('admin/reportPrintHeader', $data);
            $this->load->view('admin/' . $config['view']);
            $this->load->view('admin/reportPrintFooter');
            return;
        }

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/' . $config['view']);
        $this->load->view('admin/footer');
    }

    private function isPrintRequest()
    {
        return in_array((string) $this->input->get('print'), array('1', 'yes'), TRUE);
    }

    /**
     * Validated filter values for the given filter keys. Anything invalid is
     * dropped, so the report falls back to "no filter" for that field.
     */
    private function readFilters(array $keys, $source)
    {
        $source = is_array($source) ? $source : array();
        $filters = array();

        foreach ($keys as $key) {
            $raw = (isset($source[$key]) && is_string($source[$key])) ? trim($source[$key]) : '';
            if ($raw === '') {
                continue;
            }

            switch ($key) {
                case 'q':
                    // Control characters become spaces; invalid UTF-8 is dropped.
                    $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $raw);
                    if ($value === NULL) {
                        break;
                    }
                    $value = trim(preg_replace('/\s+/u', ' ', $value));
                    $value = function_exists('mb_substr') ? mb_substr($value, 0, 100, 'UTF-8') : substr($value, 0, 100);
                    if ($value !== '') {
                        $filters['q'] = $value;
                    }
                    break;
                case 'status':
                    if (in_array($raw, $this->bookingStatuses, TRUE)) {
                        $filters['status'] = $raw;
                    }
                    break;
                case 'tour_id':
                case 'slot_id':
                case 'lang_id':
                case 'country_id':
                case 'ref_id':
                    if ($this->isPositiveId($raw)) {
                        $filters[$key] = (int) $raw;
                    }
                    break;
                case 'guide_id':
                    if ($raw === 'none') {
                        $filters['guide_id'] = 'none';
                    } elseif ($this->isPositiveId($raw)) {
                        $filters['guide_id'] = (int) $raw;
                    }
                    break;
                case 'date':
                case 'created':
                case 'paid':
                    $range = $this->parseDateRange($raw);
                    if ($range !== NULL) {
                        $filters[$key . '_from'] = $range[0];
                        $filters[$key . '_to'] = $range[1];
                    }
                    break;
                case 'pay_state':
                    if (in_array($raw, ReportsModel::PAYMENT_STATES, TRUE)) {
                        $filters['pay_state'] = $raw;
                    }
                    break;
                case 'pay_method':
                case 'promo':
                    if (strlen($raw) <= 255) {
                        $filters[$key] = $raw;
                    }
                    break;
                case 'comm':
                case 'discount':
                case 'ref_paid':
                    if (in_array($raw, array('Yes', 'No'), TRUE)) {
                        $filters[$key] = $raw;
                    }
                    break;
            }
        }

        return $filters;
    }

    private function isPositiveId($value)
    {
        return ctype_digit($value) && strlen($value) <= 10 && (int) $value > 0;
    }

    /**
     * "01-Sep-2026 to 30-Sep-2026", a single date, or ISO dates. Returns
     * array(from, to) as Y-m-d, or NULL when invalid.
     */
    private function parseDateRange($raw)
    {
        return report_parse_date_range($raw);
    }

    private function formatRange($from, $to)
    {
        return report_format_date_range($from, $to);
    }

    /**
     * Sort key and direction, restricted to the report's own allowlist.
     */
    private function readSort($report)
    {
        $sort = $this->input->get('sort');
        $order = strtoupper((string) $this->input->get('order'));

        if (!is_string($sort) || !in_array($sort, $this->ReportsModel->sortKeys($report), TRUE)) {
            $sort = $this->ReportsModel->defaultSort($report);
            $order = $this->ReportsModel->defaultOrder($report);
        }

        return array($sort, $order === 'DESC' ? 'DESC' : ($order === 'ASC' ? 'ASC' : $this->ReportsModel->defaultOrder($report)));
    }

    /**
     * Sort parameters for generated URLs, omitted while the default is active.
     */
    private function sortParams($report, $sort, $order)
    {
        if ($sort === $this->ReportsModel->defaultSort($report) && $order === $this->ReportsModel->defaultOrder($report)) {
            return array();
        }

        return array('sort' => $sort, 'order' => $order);
    }

    /**
     * Rows per page: 0 (All) or 10-100 in steps of 10. A valid request value is
     * remembered in the session like the other admin listings.
     */
    private function resolvePerPage()
    {
        $allowed = array_merge(array(0), range(10, 100, 10));
        $requested = $this->input->get('per_page');

        if (is_string($requested) && ctype_digit($requested) && in_array((int) $requested, $allowed, TRUE)) {
            $this->session->set_userdata('per_page', (int) $requested);
        }

        $stored = $this->session->userdata('per_page');
        if ($stored !== NULL && is_numeric($stored) && in_array((int) $stored, $allowed, TRUE)) {
            return (int) $stored;
        }

        return (int) $this->per_page;
    }

    /**
     * URL parameters describing the active filters (used by every link).
     */
    private function filterParams(array $filters)
    {
        $params = array();
        $keys = array('q', 'status', 'tour_id', 'guide_id', 'slot_id', 'lang_id', 'country_id', 'ref_id', 'pay_state', 'pay_method', 'comm', 'promo', 'discount', 'ref_paid');

        foreach ($keys as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== 0) {
                $params[$key] = $filters[$key];
            }
        }
        foreach (array('date', 'created', 'paid') as $key) {
            if (!empty($filters[$key . '_from'])) {
                $params[$key] = $this->formatRange($filters[$key . '_from'], $filters[$key . '_to']);
            }
        }

        return $params;
    }

    private function buildPagination($report, array $filterParams, array $sortParams, $total, $perPage)
    {
        if ($perPage <= 0 || $total <= $perPage) {
            return '';
        }

        $config = admin_pagination_config(
            str_replace('&', '&amp;', report_url($report, array_merge($filterParams, $sortParams))),
            $total,
            $perPage,
            0
        );
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['use_page_numbers'] = TRUE;
        $config['reuse_query_string'] = FALSE;

        $this->pagination->initialize($config);

        return $this->pagination->create_links();
    }

    private function businessCurrency()
    {
        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));

        return (!empty($settings['currency_unit'])) ? trim($settings['currency_unit']) : '';
    }

    /**
     * Option lists for the filters this report shows.
     */
    private function loadOptions(array $filterKeys)
    {
        $needed = array();
        foreach ($filterKeys as $key) {
            if (!empty($this->filterFields[$key]['options'])) {
                $needed[] = $this->filterFields[$key]['options'];
            }
        }

        $options = $this->ReportsModel->filterOptions($needed);
        $options['statuses'] = $this->bookingStatuses;

        return $options;
    }

    /**
     * Control definitions for the shared filter partial.
     */
    private function filterControls(array $config, array $filters, array $options)
    {
        $controls = array('primary' => array(), 'more' => array());

        foreach ($config['filters'] as $key) {
            $field = $this->filterFields[$key];
            $control = array(
                'key' => $key,
                'label' => $field['label'],
                'type' => $field['type'],
                'search' => !empty($field['search']),
                'value' => $this->controlValue($key, $filters),
                'choices' => array(),
                'groups' => array(),
                'extra' => array(),
            );

            if ($field['type'] === 'select') {
                $this->fillChoices($control, $field['options'], $options);
            }

            $controls[in_array($key, $config['primary'], TRUE) ? 'primary' : 'more'][] = $control;
        }

        return $controls;
    }

    private function controlValue($key, array $filters)
    {
        if (in_array($key, array('date', 'created', 'paid'), TRUE)) {
            return !empty($filters[$key . '_from'])
                ? $this->formatRange($filters[$key . '_from'], $filters[$key . '_to'])
                : '';
        }

        return isset($filters[$key]) ? (string) $filters[$key] : '';
    }

    private function fillChoices(array &$control, $optionName, array $options)
    {
        $list = isset($options[$optionName]) ? $options[$optionName] : array();

        switch ($optionName) {
            case 'statuses':
                foreach ($list as $status) {
                    $control['choices'][] = array($status, $status);
                }
                break;
            case 'payment_states':
                $control['choices'] = array(
                    array('unpaid', 'Unpaid'),
                    array('partial', 'Partially paid'),
                    array('paid', 'Paid'),
                    array('refunded', 'Refunded'),
                );
                break;
            case 'yes_no':
                $control['choices'] = array(array('Yes', 'Yes'), array('No', 'No'));
                break;
            case 'paid_unpaid':
                $control['choices'] = array(array('Yes', 'Paid'), array('No', 'Unpaid'));
                break;
            case 'tours':
                foreach ($list as $tour) {
                    $group = trim((string) $tour['type']) === 'Experience' ? 'Experiences' : 'Tours';
                    $control['groups'][$group][] = array((string) $tour['id'], $tour['name']);
                }
                ksort($control['groups']);
                $control['groups'] = array_reverse($control['groups'], TRUE);
                break;
            case 'payment_methods':
            case 'promo_codes':
                foreach ($list as $value) {
                    $control['choices'][] = array($value, $value);
                }
                break;
            default:
                foreach ($list as $row) {
                    $control['choices'][] = array((string) $row['id'], $row['name']);
                }
        }

        if ($optionName === 'guides') {
            $control['extra'][] = array('none', 'Unassigned (no guide)');
        }
    }

    /**
     * Human-readable active filters with the URL that removes each one.
     */
    private function activeChips($report, array $filters, array $options, array $sortParams)
    {
        $config = $this->reports[$report];
        $params = $this->filterParams($filters);
        $chips = array();

        foreach ($config['filters'] as $key) {
            $value = $this->controlValue($key, $filters);
            if ($value === '') {
                continue;
            }

            $field = $this->filterFields[$key];
            $text = $value;
            if ($field['type'] === 'select') {
                $control = array('choices' => array(), 'groups' => array(), 'extra' => array());
                $this->fillChoices($control, $field['options'], $options);
                $text = $this->choiceLabel($control, $value);
            }

            $remaining = $params;
            unset($remaining[$key]);

            $chips[] = array(
                'key' => $key,
                'label' => $field['label'],
                'text' => $text,
                'remove_url' => report_url($report, array_merge($remaining, $sortParams)),
            );
        }

        return $chips;
    }

    private function choiceLabel(array $control, $value)
    {
        $lists = array_merge(array($control['choices'], $control['extra']), array_values($control['groups']));
        foreach ($lists as $list) {
            foreach ($list as $choice) {
                if ((string) $choice[0] === (string) $value) {
                    return $choice[1];
                }
            }
        }

        return '#' . $value;
    }

    /**
     * Redirects old /reports/<name>/<sort>/<order>/<status>/... URLs to the
     * query string format.
     */
    private function redirectLegacyUrl($report)
    {
        $segments = $this->uri->segment_array();
        if (!isset($segments[4])) {
            return;
        }

        $legacy = array(
            4 => 'sort',
            5 => 'order',
            6 => 'status',
            7 => 'q',
            8 => 'tour_id',
            9 => 'guide_id',
            10 => 'slot_id',
            11 => 'lang_id',
            12 => 'country_id',
            13 => 'date',
        );
        $renamed = array(
            'payments' => array('lang_id' => 'comm'),
            'referrals' => array('slot_id' => 'ref_id', 'lang_id' => 'ref_paid'),
        );

        $params = array();
        foreach ($legacy as $position => $key) {
            $value = isset($segments[$position]) ? urldecode($segments[$position]) : '-';
            if ($value === '' || $value === '-') {
                continue;
            }
            if (isset($renamed[$report][$key])) {
                $key = $renamed[$report][$key];
            }
            $params[$key] = $value;
        }

        if (isset($params['status']) && $params['status'] === 'Consumed') {
            $params['status'] = 'Completed';
        }
        if (isset($params['sort'])) {
            $sort = isset($this->legacySorts[$params['sort']]) ? $this->legacySorts[$params['sort']] : $params['sort'];
            if (in_array($sort, $this->ReportsModel->sortKeys($report), TRUE)) {
                $params['sort'] = $sort;
            } else {
                unset($params['sort'], $params['order']);
            }
        } else {
            unset($params['order']);
        }

        redirect(report_url($report, $params), 'location', 302);
    }

    /**
     * Shared AJAX handler for the commission paid controls. Always answers
     * with JSON: success, message, and on success the refreshed summary.
     */
    private function updateCommission($report, $column, $enforceTourEnd = FALSE)
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(405, array('success' => FALSE, 'message' => 'Method not allowed.'));
            return;
        }

        $bookId = $this->input->post('book_id');
        $value = $this->input->post('value');
        if (!is_string($bookId) || !$this->isPositiveId($bookId) || !in_array($value, array('Yes', 'No'), TRUE)) {
            $this->jsonResponse(422, array('success' => FALSE, 'message' => 'The request was not valid.'));
            return;
        }

        $result = $this->ReportsModel->updateCommissionReceived(
            (int) $bookId,
            $column,
            $value,
            $enforceTourEnd
        );
        $messages = array(
            'not_found' => array(404, 'That booking no longer exists.'),
            'not_completed' => array(422, 'Only completed bookings can be marked.'),
            'tour_ended' => array(422, 'Commission received can no longer be changed after the tour end time.'),
            'failed' => array(500, 'The change could not be saved. Please try again.'),
        );
        if ($result !== 'ok') {
            $this->jsonResponse($messages[$result][0], array('success' => FALSE, 'message' => $messages[$result][1]));
            return;
        }

        // Recompute the summary with the same filters the page is showing.
        $queryString = substr((string) $this->input->post('filters'), 0, 4000);
        parse_str(ltrim($queryString, '?'), $submitted);
        $filters = $this->readFilters($this->reports[$report]['filters'], $submitted);
        $summary = $this->ReportsModel->getSummary($report, $filters);

        $values = array();
        foreach (report_summary_cards($report, $summary, $this->businessCurrency()) as $key => $card) {
            $values[$key] = $card[1];
        }

        $this->jsonResponse(200, array(
            'success' => TRUE,
            'message' => 'Saved.',
            'book_id' => (int) $bookId,
            'value' => $value,
            'summary' => $values,
        ));
    }

    private function jsonResponse($status, array $payload)
    {
        if ($this->config->item('csrf_protection')) {
            $payload['csrf_name'] = $this->security->get_csrf_token_name();
            $payload['csrf_hash'] = $this->security->get_csrf_hash();
        }

        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
