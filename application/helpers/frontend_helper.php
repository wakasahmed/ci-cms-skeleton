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
     * $variant is one of: primary, outline, light, outline-light, soft, ghost.
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
            'soft' => 'bg-petal text-primary hover:bg-rose-100 focus-visible:outline-primary',
            'ghost' => 'text-foreground hover:text-primary focus-visible:outline-primary',
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

if (!function_exists('frontend_url')) {
    /**
     * A link typed in the CMS: absolute (http, https, //, tel:, mailto:) links
     * are kept, anything else is treated as a path on this site.
     */
    function frontend_url($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $value) === 1 || preg_match('#^(tel|mailto):#i', $value) === 1) {
            return $value;
        }

        return base_url(ltrim($value, '/'));
    }
}

if (!function_exists('frontend_icon_class')) {
    /** A Font Awesome class list from the CMS icon picker, limited to safe characters. */
    function frontend_icon_class($value)
    {
        return trim(preg_replace('/[^a-z0-9 -]/', '', strtolower((string) $value)));
    }
}

if (!function_exists('frontend_price')) {
    /** A złoty amount for display: "80 zł", "80.50 zł". Returns '' for an empty amount. */
    function frontend_price($amount)
    {
        if ($amount === NULL || $amount === '' || !is_numeric($amount)) {
            return '';
        }

        $amount = (float) $amount;
        $formatted = floor($amount) == $amount
            ? number_format($amount, 0, '.', ' ')
            : number_format($amount, 2, '.', ' ');

        return $formatted.' zł';
    }
}

if (!function_exists('frontend_service_price')) {
    /** A service's "from" price with its suffix ("from 20 zł / nail"), or '' when it has none. */
    function frontend_service_price(array $service)
    {
        $price = frontend_price(isset($service['service_price_from']) ? $service['service_price_from'] : NULL);
        if ($price === '') {
            return '';
        }

        $suffix = isset($service['service_price_suffix']) ? trim((string) $service['service_price_suffix']) : '';

        return 'from '.$price.($suffix !== '' ? ' '.$suffix : '');
    }
}

if (!function_exists('frontend_split_lines')) {
    /**
     * "Name | Description" lines (Miscellaneous Contents lists) as arrays of
     * trimmed parts, skipping blank lines.
     */
    function frontend_split_lines($value)
    {
        $rows = array();

        foreach (frontend_lines($value) as $line) {
            $rows[] = array_map('trim', explode('|', $line));
        }

        return $rows;
    }
}

if (!function_exists('frontend_intro')) {
    /**
     * The opening of a plain-text biography for cards: whole sentences from
     * the first paragraph, as many as fit in $maxLength (at least one).
     */
    function frontend_intro($text, $maxLength = 160)
    {
        $paragraphs = preg_split('/\R\s*\R/', trim(strip_tags((string) $text)));
        $sentences = preg_split('/(?<=[.!?])\s+/', trim((string) $paragraphs[0]));
        $intro = '';

        foreach ($sentences as $sentence) {
            $candidate = trim($intro.' '.$sentence);
            if ($intro !== '' && mb_strlen($candidate, 'UTF-8') > $maxLength) {
                break;
            }
            $intro = $candidate;
        }

        return $intro;
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

if (!function_exists('frontend_html_sections')) {
    /**
     * Rich text from the CMS editor split at its <h2> headings, for pages with
     * a numbered table of contents (the legal pages). Returns
     * array('intro' => HTML before the first heading, 'sections' => list of
     * array('title', 'id', 'html')). Each id is unique within the page.
     */
    function frontend_html_sections($html)
    {
        $result = array('intro' => '', 'sections' => array());
        $html = trim((string) $html);
        if ($html === '') {
            return $result;
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(TRUE);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('root');
        if ($root === NULL) {
            $result['intro'] = $html;
            return $result;
        }

        $ids = array();
        $current = NULL;
        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE && strtolower($node->nodeName) === 'h2') {
                if ($current !== NULL) {
                    $result['sections'][] = $current;
                }

                $title = trim(preg_replace('/\s+/u', ' ', $node->textContent));
                $id = url_title($title, '-', TRUE);
                $id = $id !== '' ? $id : 'section';
                $base = $id;
                for ($suffix = 2; isset($ids[$id]); $suffix++) {
                    $id = $base.'-'.$suffix;
                }
                $ids[$id] = TRUE;

                $current = array('title' => $title, 'id' => $id, 'html' => '');
                continue;
            }

            $markup = $document->saveHTML($node);
            if ($current === NULL) {
                $result['intro'] .= $markup;
            } else {
                $current['html'] .= $markup;
            }
        }

        if ($current !== NULL) {
            $result['sections'][] = $current;
        }
        $result['intro'] = trim($result['intro']);

        return $result;
    }
}

if (!function_exists('frontend_parse_opening_hours')) {
    /**
     * Website Settings > Opening Hours ("Days | Hours" lines such as
     * "Monday – Friday | 9:00 AM – 5:00 PM", "Sat | 10.00-14.00" or
     * "Sunday | Closed") as day code (mon…sun) => array(open minute, close
     * minute), in week order. Closed days are absent; lines that cannot be
     * read are skipped. Used by the booking schedule and the structured data.
     */
    function frontend_parse_opening_hours($text)
    {
        $codes = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
        $hours = array();

        foreach (preg_split('/\R/', (string) $text) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if (count($parts) !== 2) {
                continue;
            }

            $days = frontend_opening_days($parts[0], $codes);
            $times = frontend_opening_times($parts[1]);
            foreach ($days as $code) {
                if ($times === NULL) {
                    unset($hours[$code]);
                } else {
                    $hours[$code] = $times;
                }
            }
        }

        $ordered = array();
        foreach ($codes as $code) {
            if (isset($hours[$code])) {
                $ordered[$code] = $hours[$code];
            }
        }

        return $ordered;
    }
}

if (!function_exists('frontend_opening_days')) {
    /** Day codes named in "Monday – Friday", "Saturday" or "Mon, Wed" (wrapping ranges allowed). */
    function frontend_opening_days($text, array $codes)
    {
        $days = array();

        foreach (preg_split('/\s*,\s*/', mb_strtolower($text, 'UTF-8')) as $piece) {
            $ends = preg_split('/\s*(?:–|—|-|to)\s*/u', $piece);
            $from = array_search(substr(trim($ends[0]), 0, 3), $codes, TRUE);
            if ($from === FALSE) {
                continue;
            }

            $to = count($ends) > 1 ? array_search(substr(trim($ends[1]), 0, 3), $codes, TRUE) : $from;
            if ($to === FALSE) {
                $to = $from;
            }

            for ($index = $from; ; $index = ($index + 1) % 7) {
                $days[] = $codes[$index];
                if ($index === $to) {
                    break;
                }
            }
        }

        return array_unique($days);
    }
}

if (!function_exists('frontend_opening_times')) {
    /** array(open minute, close minute) from "9:00 AM – 5:00 PM" or "09:00-17:00"; NULL when closed or unreadable. */
    function frontend_opening_times($text)
    {
        preg_match_all('/(\d{1,2})(?:[:.](\d{2}))?\s*(am|pm)?/i', (string) $text, $matches, PREG_SET_ORDER);
        if (count($matches) < 2) {
            return NULL;
        }

        $minutes = array();
        foreach (array_slice($matches, 0, 2) as $match) {
            $hour = (int) $match[1];
            $period = isset($match[3]) ? strtolower($match[3]) : '';
            if ($period === 'pm' && $hour < 12) {
                $hour += 12;
            } elseif ($period === 'am' && $hour === 12) {
                $hour = 0;
            }
            $minutes[] = $hour * 60 + (isset($match[2]) && $match[2] !== '' ? (int) $match[2] : 0);
        }

        return $minutes[1] > $minutes[0] ? $minutes : NULL;
    }
}
