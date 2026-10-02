<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Appointment reports: a read-only view of the appointments in a date range,
 * filtered by status, artist and service, with totals, breakdowns by artist
 * and service, a printable layout and a CSV export. See Report_model.
 */
class Reports extends CI_Controller
{
    public $moduleName = 'Reports';
    public $moduleDesc = 'Appointments by date range, status, artist and service, with booked hours and estimated value.';
    public $controller = 'reports';
    public $listView = 'reports';
    public $user_data = array();

    /** Date format of the shared .daterange control ("01-Oct-2026 to 31-Oct-2026"). */
    const RANGE_FORMAT = 'd-M-Y';

    /** Longest range, so a mistyped year cannot build a huge report. */
    const MAX_RANGE_DAYS = 731;

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

        $this->load->model('Report_model');
        $this->load->helper(array('admin_input', 'admin_listing'));
    }

    public function index()
    {
        $filters = $this->filters();
        $options = $this->Report_model->filterOptions();

        $data = array(
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data,
            'reportsActive' => 1,
            'filters' => $filters,
            'report' => $this->Report_model->build($filters),
            'artists' => $options['artists'],
            'services' => $options['services'],
            'statuses' => Report_model::STATUSES,
            'presets' => $this->presets(),
            'query' => $this->query($filters),
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$this->listView);
        $this->load->view('admin/footer');
    }

    /** One CSV line per booked service, for the same filters as the page. */
    public function export()
    {
        $filters = $this->filters();
        $rows = $this->Report_model->rows($filters);
        $filename = 'appointments_'.$filters['from'].'_to_'.$filters['to'].'.csv';

        $this->output
            ->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="'.$filename.'"')
            ->set_header('Cache-Control: no-store');

        $handle = fopen('php://temp', 'r+');
        // The BOM lets Excel read the file as UTF-8 (names, "zł").
        fwrite($handle, "\xEF\xBB\xBF");
        $this->csvLine($handle, array(
            'Reference',
            'Date',
            'Start',
            'Status',
            'Client',
            'Email',
            'Phone',
            'Service',
            'Artist',
            'Duration (min)',
            'Price (zł)',
        ));

        foreach ($rows as $row) {
            $this->csvLine($handle, array_map(array($this, 'csvCell'), array(
                $row['appointment_reference'],
                $row['appointment_date'],
                substr((string) $row['service_start_time'], 0, 5),
                $row['appointment_status'],
                $row['customer_name'],
                $row['customer_email'],
                $row['customer_phone'],
                $row['service_name'],
                $row['artist_name'],
                (int) $row['service_duration_minutes'],
                $row['service_price'] !== NULL ? number_format((float) $row['service_price'], 2, '.', '') : '',
            )));
        }

        rewind($handle);
        $this->output->set_output(stream_get_contents($handle));
        fclose($handle);
    }

    /**
     * Filters from the query string, each checked against what is allowed:
     * from/to (Y-m-d, default this month), status ('' = all), artist and
     * service (0 = all).
     */
    private function filters()
    {
        list($from, $to) = $this->parseRange((string) $this->input->get('range'));

        $status = (string) $this->input->get('status');
        $artist = (int) $this->input->get('artist');
        $service = (int) $this->input->get('service');

        return array(
            'from' => $from,
            'to' => $to,
            'status' => in_array($status, Report_model::STATUSES, TRUE) ? $status : '',
            'artist' => $artist > 0 ? $artist : 0,
            'service' => $service > 0 ? $service : 0,
        );
    }

    /** array(from, to) as Y-m-d from "d-M-Y to d-M-Y" (or one date); this month otherwise. */
    private function parseRange($range)
    {
        $parts = array_map('trim', explode(' to ', $range, 2));
        $dates = array();

        foreach ($parts as $part) {
            $date = DateTime::createFromFormat('!'.self::RANGE_FORMAT, $part);
            if ($date === FALSE || $date->format(self::RANGE_FORMAT) !== $part) {
                $dates = array();
                break;
            }
            $dates[] = $date->format('Y-m-d');
        }

        if (empty($dates)) {
            return array(date('Y-m-01'), date('Y-m-t'));
        }

        $from = $dates[0];
        $to = isset($dates[1]) ? $dates[1] : $dates[0];
        if ($to < $from) {
            list($from, $to) = array($to, $from);
        }

        $longest = date('Y-m-d', strtotime($from.' +'.(self::MAX_RANGE_DAYS - 1).' days'));

        return array($from, min($to, $longest));
    }

    /** Quick ranges shown above the report: label => "d-M-Y to d-M-Y". */
    private function presets()
    {
        $range = function ($from, $to) {
            return date(self::RANGE_FORMAT, strtotime($from)).' to '.date(self::RANGE_FORMAT, strtotime($to));
        };

        return array(
            'This month' => $range(date('Y-m-01'), date('Y-m-t')),
            'Last month' => $range(date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))),
            'Next 30 days' => $range(date('Y-m-d'), date('Y-m-d', strtotime('+29 days'))),
            'This year' => $range(date('Y-01-01'), date('Y-12-31')),
        );
    }

    /** Query-string values for the current filters (used by the export and print links). */
    private function query(array $filters)
    {
        $query = array(
            'range' => date(self::RANGE_FORMAT, strtotime($filters['from'])).' to '.date(self::RANGE_FORMAT, strtotime($filters['to'])),
        );

        foreach (array('status', 'artist', 'service') as $key) {
            if (!empty($filters[$key])) {
                $query[$key] = $filters[$key];
            }
        }

        return $query;
    }

    /** One RFC 4180 line (PHP 8.4 wants the escape argument stated; '' means none). */
    private function csvLine($handle, array $fields)
    {
        fputcsv($handle, $fields, ',', '"', '');
    }

    /**
     * Stops a spreadsheet from reading a cell as a formula (CSV injection). A
     * plain number such as a phone "+48 512 129 654" cannot run, so it is kept.
     */
    private function csvCell($value)
    {
        $value = (string) $value;

        if (preg_match('/^[+\-]?[0-9][0-9 ().\-]*$/', $value) === 1) {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
