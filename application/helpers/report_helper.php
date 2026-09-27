<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Presentation helpers shared by the manage/admin report views.
 */

if (!function_exists('report_e')) {
    function report_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('report_dash')) {
    /**
     * Escaped text, or an em dash when the value is empty / not applicable.
     */
    function report_dash($value)
    {
        $value = trim((string) $value);

        return $value === '' ? '&mdash;' : report_e($value);
    }
}

if (!function_exists('report_number')) {
    function report_number($value)
    {
        return number_format((float) $value, 0);
    }
}

if (!function_exists('report_money')) {
    /**
     * Whole currency units (the booking schema stores integers) prefixed with
     * the currency code, e.g. "SAR 1,700".
     */
    function report_money($amount, $currency = '')
    {
        $currency = trim((string) $currency);

        return report_e(($currency !== '' ? $currency . ' ' : '') . number_format((float) $amount, 0));
    }
}

if (!function_exists('report_date')) {
    function report_date($date)
    {
        $timestamp = $date ? strtotime($date) : FALSE;

        return $timestamp ? report_e(date(ADMIN_DATE_FORMAT, $timestamp)) : '&mdash;';
    }
}

if (!function_exists('report_time_range')) {
    function report_time_range($start, $end)
    {
        if (!$start || !$end) {
            return '';
        }

        return date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end));
    }
}

if (!function_exists('report_query_string')) {
    function report_query_string(array $params)
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('report_url')) {
    function report_url($report, array $params = array())
    {
        $url = base_url('manage/reports/' . $report);

        return empty($params) ? $url : $url . '?' . report_query_string($params);
    }
}

if (!function_exists('report_parse_date')) {
    /**
     * Single date as "01-Sep-2026", "1-Sep-2026" or ISO. Returns Y-m-d, or
     * NULL when invalid or outside 2000-2100.
     */
    function report_parse_date($value)
    {
        foreach (array('d-M-Y', 'j-M-Y', 'Y-m-d') as $format) {
            $date = DateTime::createFromFormat('!' . $format, $value);
            $errors = DateTime::getLastErrors();
            $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
            if ($date instanceof DateTime && !$hasErrors) {
                $year = (int) $date->format('Y');

                return ($year >= 2000 && $year <= 2100) ? $date->format('Y-m-d') : NULL;
            }
        }

        return NULL;
    }
}

if (!function_exists('report_parse_date_range')) {
    /**
     * "01-Sep-2026 to 30-Sep-2026", a single date, or ISO dates. Returns
     * array(from, to) as Y-m-d, or NULL when invalid.
     */
    function report_parse_date_range($raw)
    {
        $parts = preg_split('/\s+to\s+/i', $raw);
        if ($parts === FALSE || count($parts) > 2) {
            return NULL;
        }

        $dates = array();
        foreach ($parts as $part) {
            $date = report_parse_date(trim($part));
            if ($date === NULL) {
                return NULL;
            }
            $dates[] = $date;
        }

        if (count($dates) === 1) {
            $dates[1] = $dates[0];
        }
        if ($dates[0] > $dates[1]) {
            $dates = array($dates[1], $dates[0]);
        }

        return $dates;
    }
}

if (!function_exists('report_format_date_range')) {
    function report_format_date_range($from, $to)
    {
        $fromText = date('d-M-Y', strtotime($from));
        $toText = date('d-M-Y', strtotime($to));

        return $fromText === $toText ? $fromText : $fromText . ' to ' . $toText;
    }
}

if (!function_exists('report_status_badge')) {
    function report_status_badge($status)
    {
        $classes = array(
            'Completed' => 'status-enabled',
            'Cancelled' => 'status-disabled',
            'Refunded' => 'status-published',
            'Pending' => 'status-warning',
        );
        $class = isset($classes[$status]) ? $classes[$status] : 'status-warning';

        return '<span class="status-badge report-status ' . $class . '">' . report_e($status) . '</span>';
    }
}

if (!function_exists('report_payment_state_badge')) {
    function report_payment_state_badge($state)
    {
        $labels = array(
            'unpaid' => array('Unpaid', 'status-warning'),
            'partial' => array('Partially paid', 'status-warning'),
            'paid' => array('Paid', 'status-enabled'),
            'refunded' => array('Refunded', 'status-published'),
        );
        $badge = isset($labels[$state]) ? $labels[$state] : array(ucfirst((string) $state), 'status-disabled');

        return '<span class="status-badge report-status ' . $badge[1] . '">' . report_e($badge[0]) . '</span>';
    }
}

if (!function_exists('report_th')) {
    /**
     * Table heading cell. Sortable headings link to the same report with the
     * active filters preserved; in print mode (or for a blank $column) the
     * heading is plain text.
     *
     * $ctx keys: report, sort, order, query (active URL params), print.
     */
    function report_th($label, $column, array $ctx, $class = '')
    {
        $classAttr = $class !== '' ? ' class="' . report_e($class) . '"' : '';

        if ($column === '' || !empty($ctx['print'])) {
            return '<th scope="col"' . $classAttr . '>' . report_e($label) . '</th>';
        }

        $isActive = $ctx['sort'] === $column;
        $nextOrder = ($isActive && $ctx['order'] === 'ASC') ? 'DESC' : 'ASC';
        $params = array_merge($ctx['query'], array('sort' => $column, 'order' => $nextOrder));

        $ariaSort = '';
        $icon = 'bi-arrow-down-up';
        if ($isActive) {
            $ariaSort = ' aria-sort="' . ($ctx['order'] === 'ASC' ? 'ascending' : 'descending') . '"';
            $icon = $ctx['order'] === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down';
        }

        return '<th scope="col"' . $classAttr . $ariaSort . '>'
            . '<a class="pages-sort-link" href="' . report_e(report_url($ctx['report'], $params)) . '">'
            . report_e($label)
            . ' <i class="bi ' . $icon . ' pages-sort-icon" aria-hidden="true"></i></a></th>';
    }
}

if (!function_exists('report_progress')) {
    /**
     * Booking-wizard progress. A Pending booking that has not reached the last
     * step is a partial booking.
     */
    function report_progress($steps, $totalSteps, $status)
    {
        $steps = max(0, (int) $steps);
        $totalSteps = max(1, (int) $totalSteps);
        $isComplete = $steps >= $totalSteps;
        $label = $isComplete ? 'Complete' : ($status === 'Pending' ? 'Partial' : '');
        $text = ($label !== '' ? $label . ' &middot; ' : '') . $steps . ' of ' . $totalSteps . ($label === '' ? ' steps' : '');

        return '<div class="report-progress">'
            . '<span class="report-progress-label">' . $text . '</span>'
            . '<progress class="report-progress-bar" max="' . $totalSteps . '" value="' . min($steps, $totalSteps) . '" aria-label="Booking progress: ' . $steps . ' of ' . $totalSteps . ' steps"></progress>'
            . '</div>';
    }
}

if (!function_exists('report_booking_link')) {
    /**
     * Link to the booking detail page (plain text in print mode). $label is
     * escaped here.
     */
    function report_booking_link($bookId, $label, $isPrint = FALSE)
    {
        $label = report_e($label);
        if ($isPrint) {
            return $label;
        }

        return '<a class="pages-name-link" href="' . report_e(base_url('manage/bookings/control/view/' . (int) $bookId)) . '">' . $label . '</a>';
    }
}

if (!function_exists('report_reference')) {
    function report_reference($bookId, $reference)
    {
        $reference = trim((string) $reference);

        return $reference !== '' ? $reference : '#' . (int) $bookId;
    }
}

if (!function_exists('report_commission_control')) {
    /**
     * Yes / No radio group that saves a commission-paid flag through
     * reports.js. Only Completed bookings are editable; other bookings, and
     * print mode, show the stored value as text. A completed booking can still
     * render as a disabled control when a report-specific cutoff has passed.
     */
    function report_commission_control($bookId, $reference, $value, $isEditable, $isPrint, $kind, $isDisabled = FALSE)
    {
        $value = $value === 'Yes' ? 'Yes' : 'No';
        if ($isPrint || !$isEditable) {
            return report_e($value);
        }

        $name = 'commission-' . $kind . '-' . (int) $bookId;
        $legend = ($kind === 'referral' ? 'Referral commission paid' : 'Commission received') . ' for booking ' . $reference;
        $html = '<fieldset class="report-commission" data-report-commission data-book-id="' . (int) $bookId . '" data-current="' . $value . '"'
            . ($isDisabled ? ' disabled aria-disabled="true"' : '') . '>'
            . '<legend class="visually-hidden">' . report_e($legend) . '</legend>';

        foreach (array('Yes', 'No') as $option) {
            $id = $name . '-' . strtolower($option);
            $html .= '<span class="form-check form-check-inline">'
                . '<input class="form-check-input" type="radio" name="' . report_e($name) . '" id="' . report_e($id) . '" value="' . $option . '"' . ($value === $option ? ' checked' : '') . ($isDisabled ? ' disabled' : '') . '>'
                . '<label class="form-check-label" for="' . report_e($id) . '">' . $option . '</label>'
                . '</span>';
        }

        return $html . '</fieldset>';
    }
}

if (!function_exists('report_summary_cards')) {
    /**
     * Summary cards for a report as array(key => array(label, value, tone)).
     * Values are already formatted and escaped, so the same map can be sent
     * back to the browser after a commission update.
     */
    function report_summary_cards($report, array $summary, $currency)
    {
        $count = function ($key) use ($summary) {
            return report_number($summary[$key]);
        };
        $money = function ($key) use ($summary, $currency) {
            return report_money($summary[$key], $currency);
        };

        switch ($report) {
            case 'bookings':
                return array(
                    'total' => array('Bookings', $count('total'), ''),
                    'completed' => array('Completed', $count('completed'), 'success'),
                    'pending' => array('Pending', $count('pending'), 'warning'),
                    'cancelled' => array('Cancelled', $count('cancelled'), ''),
                    'refunded' => array('Refunded', $count('refunded'), ''),
                    'guests' => array('Guests', $count('guests'), ''),
                    'final_total' => array('Final total', $money('final_total'), ''),
                );
            case 'payments':
                return array(
                    'final_total' => array('Gross booking total', $money('final_total'), ''),
                    'paid_total' => array('Total paid', $money('paid_total'), 'success'),
                    'refunded_total' => array('Total refunded', $money('refunded_total'), ''),
                    'net_collected' => array('Net collected', $money('net_collected'), 'primary'),
                    'outstanding' => array('Outstanding (completed bookings)', $money('outstanding'), 'warning'),
                    'commission_total' => array('Commission', $money('commission_total'), ''),
                    'commission_received' => array('Commission received', $money('commission_received'), 'success'),
                    'commission_remaining' => array('Commission remaining', $money('commission_remaining'), 'warning'),
                );
            case 'referrals':
                return array(
                    'promo_bookings' => array('Promo-code bookings', $count('promo_bookings'), ''),
                    'original_total' => array('Original booking value', $money('original_total'), ''),
                    'discount_total' => array('Discounts granted', $money('discount_total'), 'warning'),
                    'final_total' => array('Final booking value', $money('final_total'), 'primary'),
                    'ref_commission' => array('Referral commission', $money('ref_commission'), ''),
                    'ref_paid' => array('Paid commission', $money('ref_paid'), 'success'),
                    'ref_unpaid' => array('Unpaid commission', $money('ref_unpaid'), 'warning'),
                );
            default:
                return array(
                    'total' => array('Bookings', $count('total'), ''),
                    'completed' => array('Completed', $count('completed'), 'success'),
                    'pending' => array('Pending', $count('pending'), 'warning'),
                    'cancelled' => array('Cancelled', $count('cancelled'), ''),
                    'refunded' => array('Refunded', $count('refunded'), ''),
                    'completion_rate' => array('Completion rate', report_e($summary['completion_rate']) . '%', ''),
                    'guests' => array('Guests', $count('guests'), ''),
                    'net_collected' => array('Net collected', $money('net_collected'), 'primary'),
                );
        }
    }
}
