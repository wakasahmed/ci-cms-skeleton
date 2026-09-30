<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Admin input helpers
|--------------------------------------------------------------------------
|
| Small, shared normalisers for values posted by manage/admin forms.
| Validation decisions stay in the controllers.
|
*/

if (!function_exists('admin_clean_text')) {
    /**
     * Trims a posted value and limits it to $maxLength characters.
     */
    function admin_clean_text($value, $maxLength = NULL)
    {
        $value = trim((string) $value);

        if ($maxLength === NULL) {
            return $value;
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, (int) $maxLength, 'UTF-8')
            : substr($value, 0, (int) $maxLength);
    }
}

if (!function_exists('admin_clean_lines')) {
    /**
     * Normalises a "one item per line" textarea: trims each line, drops empty
     * lines, and limits the number of lines and each line's length.
     * Returns the cleaned text joined with "\n".
     */
    function admin_clean_lines($value, $maxLines = 30, $maxLineLength = 255)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
        $clean = array();

        foreach ($lines as $line) {
            $line = admin_clean_text($line, $maxLineLength);

            if ($line !== '') {
                $clean[] = $line;
            }

            if (count($clean) >= $maxLines) {
                break;
            }
        }

        return implode("\n", $clean);
    }
}

if (!function_exists('admin_price_value')) {
    /**
     * Parses a price such as "80", "80.5" or "80,50".
     *
     * Returns a normalised "80.50" string, NULL for an empty value, or FALSE
     * when the value is not a valid non-negative price up to 999999.99.
     */
    function admin_price_value($value)
    {
        $value = str_replace(array(' ', ','), array('', '.'), trim((string) $value));

        if ($value === '') {
            return NULL;
        }

        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $value)) {
            return FALSE;
        }

        return number_format((float) $value, 2, '.', '');
    }
}

if (!function_exists('admin_format_price')) {
    /**
     * Formats a stored price for display, e.g. "80 zł" or "12.50 zł".
     */
    function admin_format_price($value, $suffix = '')
    {
        if ($value === NULL || $value === '') {
            return '';
        }

        $amount = (float) $value;
        $formatted = floor($amount) == $amount
            ? number_format($amount, 0, '.', ' ')
            : number_format($amount, 2, '.', ' ');
        $suffix = trim((string) $suffix);

        return $formatted.' zł'.($suffix !== '' ? ' '.$suffix : '');
    }
}

if (!function_exists('admin_date_value')) {
    /**
     * Parses a date from the shared .datepicker control (m/d/Y, or typed
     * without leading zeros as n/j/Y) or ISO
     * (Y-m-d). Returns 'Y-m-d', NULL for an empty value, or FALSE when the
     * value is not a real calendar date.
     */
    function admin_date_value($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return NULL;
        }

        foreach (array('!m/d/Y', '!n/j/Y', '!Y-m-d') as $format) {
            $date = DateTime::createFromFormat($format, $value);

            if ($date !== FALSE && $date->format(ltrim($format, '!')) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return FALSE;
    }
}

if (!function_exists('admin_datepicker_value')) {
    /**
     * Formats a stored Y-m-d date for the shared .datepicker control.
     */
    function admin_datepicker_value($value)
    {
        $date = DateTime::createFromFormat('!Y-m-d', (string) $value);

        return $date !== FALSE ? $date->format('m/d/Y') : (string) $value;
    }
}

if (!function_exists('admin_time_value')) {
    /**
     * Parses a time from the shared .timepicker control ("09:30 AM", or
     * typed as "9:30 am" or 24-hour "09:30"). Returns 'H:i:s', NULL for an
     * empty value, or FALSE when the value is not a time.
     */
    function admin_time_value($value)
    {
        $value = strtoupper(preg_replace('/\s+/', ' ', trim((string) $value)));

        if ($value === '') {
            return NULL;
        }

        if (preg_match('/^(0?[1-9]|1[0-2]):([0-5][0-9]) ?(AM|PM)$/', $value, $match) === 1) {
            $hour = (int) $match[1] % 12 + ($match[3] === 'PM' ? 12 : 0);

            return sprintf('%02d:%02d:00', $hour, (int) $match[2]);
        }

        if (preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(?::[0-5][0-9])?$/', $value, $match) === 1) {
            return sprintf('%02d:%02d:00', (int) $match[1], (int) $match[2]);
        }

        return FALSE;
    }
}

if (!function_exists('admin_timepicker_value')) {
    /**
     * Formats a stored H:i:s time for the shared .timepicker control.
     */
    function admin_timepicker_value($value)
    {
        $time = DateTime::createFromFormat('!H:i:s', (string) $value);

        return $time !== FALSE ? $time->format('h:i A') : (string) $value;
    }
}

if (!function_exists('admin_ids')) {
    /**
     * Converts a posted list of IDs into unique positive integers.
     */
    function admin_ids($values)
    {
        $ids = array_map('intval', (array) $values);

        return array_values(array_unique(array_filter($ids, function ($id) {
            return $id > 0;
        })));
    }
}
