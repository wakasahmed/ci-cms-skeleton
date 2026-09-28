<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Small presentation helpers shared by the public views.
 *
 * Tailwind scans this file (see assets/frontend/css/src/tailwind.css), so the
 * class names below must stay complete, literal strings.
 */

if (!function_exists('frontend_button_class')) {
    /**
     * Classes for the reference design's pill button.
     *
     * $variant is one of: primary, outline, light, outline-light.
     * $extra carries the size and spacing classes (for example "h-13 px-8"),
     * written in the calling view so Tailwind finds them there.
     */
    function frontend_button_class($variant = 'primary', $extra = '')
    {
        $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full'
            .' text-[0.95rem] font-semibold'
            .' transition-[background-color,border-color,color,box-shadow,transform] duration-200'
            .' ease-[var(--ease-out-soft)] cursor-pointer select-none active:translate-y-px'
            .' focus-visible:outline-2 focus-visible:outline-offset-3'
            .' disabled:pointer-events-none disabled:opacity-45 disabled:active:translate-y-0'
            .' [&_svg]:size-4 [&_svg]:shrink-0';

        $variants = array(
            'primary' => 'bg-primary-cta text-primary-foreground shadow-[var(--shadow-card)]'
                .' hover:bg-primary-strong hover:shadow-[var(--shadow-soft)] active:bg-primary-strong'
                .' focus-visible:outline-primary',
            'outline' => 'border border-border-strong bg-background text-foreground'
                .' hover:border-primary hover:bg-petal hover:text-primary focus-visible:outline-primary',
            'light' => 'bg-background text-primary shadow-[var(--shadow-card)] hover:bg-petal'
                .' focus-visible:outline-background',
            'outline-light' => 'border border-background/40 text-background hover:bg-background/12'
                .' focus-visible:outline-background',
        );

        $classes = $base.' '.(isset($variants[$variant]) ? $variants[$variant] : $variants['primary']);

        return trim($classes.' '.$extra);
    }
}

if (!function_exists('frontend_phone_href')) {
    /** "tel:" link target for a displayed phone number ("+48 512 129 654" → "tel:+48512129654"). */
    function frontend_phone_href($phone)
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);

        return $digits !== '' ? 'tel:'.$digits : '';
    }
}

if (!function_exists('frontend_opening_hours')) {
    /**
     * Website Settings opening hours ("Days | Hours" per line) as rows of
     * array('days' => ..., 'hours' => ..., 'closed' => bool).
     */
    function frontend_opening_hours($value)
    {
        $rows = array();

        foreach (preg_split('/\r\n|\r|\n/', (string) $value) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }

            $rows[] = array(
                'days' => $parts[0],
                'hours' => $parts[1],
                'closed' => strcasecmp($parts[1], 'Closed') === 0,
            );
        }

        return $rows;
    }
}

if (!function_exists('frontend_lines')) {
    /** Non-empty, trimmed lines of a multi-line setting such as the address. */
    function frontend_lines($value)
    {
        $lines = array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value));

        return array_values(array_filter($lines, 'strlen'));
    }
}
