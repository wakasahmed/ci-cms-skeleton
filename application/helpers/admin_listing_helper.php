<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Admin listing helpers
|--------------------------------------------------------------------------
|
| Markup shared by manage/admin record listings.
|
*/

if (!function_exists('admin_sort_heading')) {
    /**
     * Renders a sortable <th> with a sort link, icon and aria-sort.
     *
     * $nextOrder is the order a click would apply (listings receive the
     * toggled order as $order), so the current order is its opposite.
     *
     * @param string $label     Visible column label.
     * @param string $column    Allowlisted sort column.
     * @param string $sortby    Column the listing is currently sorted by.
     * @param string $nextOrder 'ASC' or 'DESC'.
     * @param string $url       Link that applies this sort.
     * @param string $class     Optional class for the <th>.
     */
    function admin_sort_heading($label, $column, $sortby, $nextOrder, $url, $class = '')
    {
        $isActive = ($sortby === $column);
        $icon = 'bi-arrow-down-up';
        $ariaSort = '';

        if ($isActive) {
            $icon = $nextOrder === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down';
            $ariaSort = $nextOrder === 'DESC' ? 'ascending' : 'descending';
        }

        $html = '<th scope="col"';
        $html .= $class !== '' ? ' class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'"' : '';
        $html .= $ariaSort !== '' ? ' aria-sort="'.$ariaSort.'"' : '';
        $html .= '>';
        $html .= '<a class="pages-sort-link" href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'">';
        $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $html .= ' <i class="bi '.$icon.' pages-sort-icon" aria-hidden="true"></i>';
        $html .= '</a></th>';

        return $html;
    }
}

if (!function_exists('admin_datetime_cell')) {
    /**
     * Renders the date + time markup used in listing timestamp cells.
     */
    function admin_datetime_cell($value)
    {
        $timestamp = strtotime((string) $value);

        if ($timestamp === FALSE) {
            return '<span class="text-muted">&mdash;</span>';
        }

        return '<time datetime="'.date('c', $timestamp).'">'
            .date(ADMIN_DATE_FORMAT, $timestamp)
            .'<span class="pages-cell-meta">'.date(ADMIN_TIME_FORMAT, $timestamp).'</span>'
            .'</time>';
    }
}

if (!function_exists('admin_appointment_status_badge')) {
    /**
     * Bootstrap badge class for an appointment status (Appointments, Reports).
     */
    function admin_appointment_status_badge($status)
    {
        $badges = array(
            'New' => 'text-bg-warning',
            'Confirmed' => 'text-bg-primary',
            'Completed' => 'text-bg-success',
            'Cancelled' => 'text-bg-secondary',
        );

        return isset($badges[$status]) ? $badges[$status] : 'text-bg-light';
    }
}
