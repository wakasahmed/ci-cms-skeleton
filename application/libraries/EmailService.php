<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Central email delivery service.
 *
 * This is a library, rather than a model, because sending email is an
 * infrastructure concern and does not represent a database entity.
 */
class EmailService
{
    private $CI;
    private $emailConfig = array();
    private $lastError = '';
    private $lastLogPath = '';

    /**
     * Short tags each notification entity supports. The parse*ShortTags() methods
     * replace exactly these, and the manage/admin short tag picker lists them for
     * email and WhatsApp templates (see Short_tags and config/short_tags.php).
     */
    private static $shortTagFields = array(
        'contact' => array(
            'id',
            'first_name',
            'last_name',
            'email',
            'subject',
            'phone',
            'message',
            'ip',
            'user_agent',
            'created_at',
            'updated_at',
            'country',
            'website',
        ),
        'plan_your_visit' => array(
            'id',
            'name',
            'email',
            'phone',
            'country',
            'arrival_date',
            'departure_date',
            'guests',
            'step_completed',
            'preferred_language',
            'preferred_time',
            'interests',
            'message',
            'created_at',
            'updated_at',
            'ip',
            'user_agent',
            'website',
        ),
        'booking' => array(
            'book_id',
            'book_name',
            'book_email',
            'book_phone',
            'book_address',
            'book_res_code',
            'book_tour_name',
            'book_date',
            'book_guests',
            'book_slot_name',
            'book_slot_hours',
            'book_slot_start_time',
            'book_slot_end_time',
            'book_lang_name',
            'book_tour_guide_name',
            'book_old_tour_guide_name',
            'book_vehicle_name',
            'book_original_total',
            'book_discount_amount',
            'book_tour_total',
            'book_profit_percent',
            'book_profit_amount',
            'book_tax_percent',
            'book_tax_amount',
            'book_paid_amount',
            'book_currency',
            'book_payment_method',
            'book_payment_date',
            'book_transaction_id',
            'book_ref_name',
            'book_promo_code',
            'book_ref_commission',
            'book_refund_amount',
            'book_refunded_at',
            'book_notes',
            'book_review_url',
        ),
        'discount' => array(
            'discount_id',
            'discount_name',
            'discount_ref_id',
            'discount_code',
            'discount_type',
            'discount_value',
            'discount_ref_commission_type',
            'discount_ref_commission',
            'discount_expiry',
            'discount_no_of_uses',
            'discount_status',
            'discount_added',
            'discount_updated',
            'discount_ref_name',
        ),
    );

    /** Tags that render HTML, so they only work in emails, never in WhatsApp templates. */
    private static $htmlShortTags = array(
        'booking' => array(
            'book_review_url_btn',
        ),
    );

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('email', TRUE);
        $this->emailConfig = (array) $this->CI->config->item('email');
    }

    /**
     * Send or log an email.
     *
     * Supported options:
     * - to, cc, bcc: string or array of recipients
     * - from_email, from_name, return_path
     * - reply_to, reply_to_name
     * - subject, message, alt_message
     * - mailtype, charset, priority, headers
     * - attachments: paths or arrays containing path, disposition, name, mime
     * - bcc_limit, auto_clear
     * - config: any per-message CI_Email configuration overrides
     */
    public function send(array $options = array())
    {
        $this->lastError = '';
        $this->lastLogPath = '';

        $options = array_merge(array(
            'to' => array(),
            'cc' => array(),
            'bcc' => array(),
            'from_email' => '',
            'from_name' => '',
            'return_path' => NULL,
            'reply_to' => '',
            'reply_to_name' => '',
            'subject' => '',
            'message' => '',
            'alt_message' => '',
            'mailtype' => 'html',
            'charset' => 'UTF-8',
            'priority' => 3,
            'headers' => array(),
            'attachments' => array(),
            'bcc_limit' => '',
            'auto_clear' => TRUE,
            'config' => array()
        ), $options);

        if ($this->recipientsAreEmpty($options['to'])) {
            return $this->fail('At least one recipient is required.');
        }

        $defaults = $this->senderDefaults();
        $options['from_email'] = trim((string) ($options['from_email'] ?: $defaults['email']));
        $options['from_name'] = trim((string) ($options['from_name'] ?: $defaults['name']));
        if ($options['from_email'] === '') {
            return $this->fail('A sender email address is required.');
        }

        if (strtolower((string) EMAIL_HOST) === 'log') {
            return $this->writeLog($options);
        }

        $messageConfig = array_merge($this->emailConfig, (array) $options['config'], array(
            'mailtype' => $options['mailtype'],
            'charset' => $options['charset']
        ));

        $this->CI->load->library('email', $messageConfig);
        $this->CI->email->initialize($messageConfig);
        $this->CI->email->from($options['from_email'], $options['from_name'], $options['return_path']);
        $this->CI->email->to($options['to']);

        if (!$this->recipientsAreEmpty($options['cc'])) {
            $this->CI->email->cc($options['cc']);
        }
        if (!$this->recipientsAreEmpty($options['bcc'])) {
            $this->CI->email->bcc($options['bcc'], $options['bcc_limit']);
        }
        if (trim((string) $options['reply_to']) !== '') {
            $this->CI->email->reply_to($options['reply_to'], $options['reply_to_name']);
        }

        $this->CI->email->subject((string) $options['subject']);
        $this->CI->email->message((string) $options['message']);
        if ((string) $options['alt_message'] !== '') {
            $this->CI->email->set_alt_message((string) $options['alt_message']);
        }
        $this->CI->email->set_priority((int) $options['priority']);

        foreach ((array) $options['headers'] as $header => $value) {
            $this->CI->email->set_header($header, $value);
        }
        foreach ((array) $options['attachments'] as $attachment) {
            $this->attach($attachment);
        }

        if (!$this->CI->email->send((bool) $options['auto_clear'])) {
            $this->lastError = 'Email delivery failed. Check the application log and SMTP configuration.';
            log_message('error', $this->lastError);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Render the shared branded email view for a supported locale.
     *
     * Callers may supply body or contents for the main HTML content. The
     * branding and Email footer navigation use Website Settings and the
     * links managed at /manage/foot/index/three unless explicitly supplied.
     */
    public function renderTemplate(array $params = array(), $locale = 'English')
    {
        $locale = $this->normalizeLocale($locale);
        $settings = !empty($params['site_settings']) && is_array($params['site_settings'])
            ? $params['site_settings']
            : $this->CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));

        if (empty($settings)) {
            $this->fail('Unable to render email template: site settings are missing.');
            return FALSE;
        }

        $titleField = $locale === 'Arabic' ? 'website_title_ar' : 'website_title';
        $brandName = !empty($settings[$titleField])
            ? trim($settings[$titleField])
            : trim($settings['website_title']);
        $body = isset($params['body'])
            ? $params['body']
            : (isset($params['contents']) ? $params['contents'] : '');
        $logoFile = !empty($settings['logo']) ? trim($settings['logo']) : '';
        $copyrightField = $locale === 'Arabic' ? 'copyright_text_ar' : 'copyright_text';
        $licenseField = $locale === 'Arabic' ? 'license_number_ar' : 'license_number';
        $copyrightText = !empty($settings[$copyrightField])
            ? str_replace('[YEAR]', date('Y'), trim($settings[$copyrightField]))
            : '';
        $licenseNumber = !empty($settings[$licenseField]) ? trim($settings[$licenseField]) : '';

        $data = array_merge($params, array(
            'site_settings' => $settings,
            'locale' => $locale,
            'brand_name' => $brandName,
            'logo_url' => $logoFile === '' ? '' : $this->logoUrl($logoFile),
            'heading' => isset($params['heading']) ? $params['heading'] : '',
            'body' => $body,
            'footer' => isset($params['footer'])
                ? $params['footer']
                : $this->CI->SqlModel->getFoot('three', $locale),
            'copyright_text' => $copyrightText,
            'license_number' => $licenseNumber,
        ));

        $view = $locale === 'Arabic' ? 'email/arabic' : 'email/english';

        return $this->CI->load->view($view, $data, TRUE);
    }

    /** Short tag names an entity supports: contact, plan_your_visit, booking or discount. */
    public function shortTagFields($entity)
    {
        return isset(self::$shortTagFields[$entity])
            ? self::$shortTagFields[$entity]
            : array();
    }

    /** Short tags of an entity that render HTML and therefore only work in emails. */
    public function htmlShortTags($entity)
    {
        return isset(self::$htmlShortTags[$entity])
            ? self::$htmlShortTags[$entity]
            : array();
    }

    /** Replace the supported contact-request short tags with scalar values. */
    public function parseContactShortTags($template, array $values = array())
    {
        $fields = $this->shortTagFields('contact');
        $replacements = array();

        foreach ($fields as $field) {
            $value = isset($values[$field]) && is_scalar($values[$field])
                ? (string) $values[$field]
                : '';
            if ($field === 'message') {
                $value = nl2br($value);
            }
            $replacements['{{'.$field.'}}'] = $value;
        }

        return strtr((string) $template, $replacements);
    }

    /** Replace the supported Plan Your Trip short tags with scalar values. */
    public function parsePlanYourTripShortTags($template, array $values = array())
    {
        $fields = $this->shortTagFields('plan_your_visit');
        $replacements = array();

        foreach ($fields as $field) {
            $value = isset($values[$field]) && is_scalar($values[$field])
                ? (string) $values[$field]
                : '';
            if ($field === 'message') {
                $value = nl2br($value);
            }
            $replacements['{{'.$field.'}}'] = $value;
        }

        return strtr((string) $template, $replacements);
    }

    /** Replace the supported tour-booking short tags with scalar values. */
    public function parseBookingShortTags($template, array $values = array())
    {
        $fields = $this->shortTagFields('booking');
        $replacements = array();

        foreach ($fields as $field) {
            $replacements['{{'.$field.'}}'] = isset($values[$field]) && is_scalar($values[$field])
                ? (string) $values[$field]
                : '';
        }
        $replacements['{{book_review_url_btn}}'] = $this->reviewButton($values);

        return strtr((string) $template, $replacements);
    }

    /**
     * Build the "Leave a Review" button for {{book_review_url_btn}}.
     *
     * The button is assembled here, after sendManagedTemplate() has escaped the values,
     * so its markup is not escaped. The label comes from the book_review_button_label
     * value so the caller can localize it.
     */
    private function reviewButton(array $values)
    {
        $reviewUrl = isset($values['book_review_url']) && is_scalar($values['book_review_url'])
            ? trim((string) $values['book_review_url'])
            : '';
        if ($reviewUrl === '') {
            return '';
        }

        $label = isset($values['book_review_button_label']) && is_scalar($values['book_review_button_label'])
            ? trim((string) $values['book_review_button_label'])
            : '';
        if ($label === '') {
            $label = 'Leave a Review';
        }

        return '<a href="'.htmlspecialchars($reviewUrl, ENT_QUOTES, 'UTF-8', FALSE).'" '.
            'style="display:inline-block;padding:12px 20px;border-radius:6px;'.
            'background:#63569b;color:#fff;text-decoration:none;font-weight:700">'.
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8', FALSE).
            '</a>';
    }

    /** Replace the supported discount-code short tags with scalar values. */
    public function parseDiscountShortTags($template, array $values = array())
    {
        $fields = $this->shortTagFields('discount');
        $replacements = array();

        foreach ($fields as $field) {
            $replacements['{{'.$field.'}}'] = isset($values[$field]) && is_scalar($values[$field])
                ? (string) $values[$field]
                : '';
        }

        return strtr((string) $template, $replacements);
    }

    /**
     * Send an email built from a managed email_templates row.
     *
     * Supported options:
     * - template_id: the email_templates.id to send
     * - to: recipient address
     * - language: 'English' or 'Arabic'. The Arabic fields are used only when the template
     *   has an Arabic subject and body, otherwise the English fields are sent.
     * - values: short-tag values keyed by field name
     * - parser: parseBookingShortTags or parseDiscountShortTags, whichever owns the tags
     * - multiline_fields: value keys whose line breaks become <br> in the HTML body
     * - label: prefix for log messages
     */
    public function sendManagedTemplate(array $options)
    {
        $options = array_merge(array(
            'template_id' => 0,
            'to' => '',
            'language' => 'English',
            'values' => array(),
            'parser' => '',
            'multiline_fields' => array(),
            'label' => 'Email',
        ), $options);
        $label = (string) $options['label'];
        $templateId = (int) $options['template_id'];

        if (!in_array($options['parser'], array('parseBookingShortTags', 'parseDiscountShortTags'), TRUE)) {
            return $this->fail($label.' has no valid short tag parser.');
        }

        $template = $this->CI->SqlModel->getSingleRecord('email_templates', array('id' => $templateId));
        if (empty($template)) {
            return $this->fail($label.' email template '.$templateId.' is missing.');
        }

        $isArabic = $this->normalizeLocale($options['language']) === 'Arabic';
        $useArabicTemplate = $isArabic
            && trim((string) $template['subject_ar']) !== ''
            && trim((string) $template['contents_ar']) !== '';
        $subjectField = $useArabicTemplate ? 'subject_ar' : 'subject';
        $headingField = $useArabicTemplate ? 'heading_ar' : 'heading';
        $contentsField = $useArabicTemplate ? 'contents_ar' : 'contents';
        $subjectTemplate = trim((string) $template[$subjectField]);
        $contentsTemplate = trim((string) $template[$contentsField]);

        if ($subjectTemplate === '' || $contentsTemplate === '') {
            return $this->fail(
                $label.' was not sent because email template '.$templateId.' has incomplete '.
                ($useArabicTemplate ? 'Arabic' : 'English').' fields.'
            );
        }

        $headerValues = array();
        $htmlValues = array();
        foreach ((array) $options['values'] as $field => $value) {
            $headerValues[$field] = trim(preg_replace('/[\r\n]+/', ' ', (string) $value));
            $htmlValues[$field] = $this->escape($value);
        }
        foreach ((array) $options['multiline_fields'] as $field) {
            if (isset($options['values'][$field])) {
                $htmlValues[$field] = nl2br($this->escape($options['values'][$field]), FALSE);
            }
        }

        $parser = $options['parser'];
        $subject = $this->$parser($subjectTemplate, $headerValues);
        $heading = $this->$parser(
            isset($template[$headingField]) ? $template[$headingField] : '',
            $htmlValues
        );
        $contents = $this->$parser($contentsTemplate, $htmlValues);
        $message = $this->renderTemplate(array(
            'heading' => $heading,
            'body' => $contents,
        ), $useArabicTemplate ? 'Arabic' : 'English');

        if ($message === FALSE) {
            return $this->fail($label.' template could not be rendered.');
        }

        $sent = $this->send(array(
            'to' => $options['to'],
            'subject' => $subject,
            'message' => $message,
            'alt_message' => $this->plainText($heading, $contents),
        ));

        if (!$sent) {
            log_message('error', $label.' email failed: '.$this->lastError);
        }

        return $sent;
    }

    /**
     * Currency label from Website Settings in the recipient's language. Falls back to
     * $fallback (for example the currency stored on a booking) when the setting is empty.
     */
    public function currencyUnit($locale, $fallback = '')
    {
        $settings = $this->CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $field = $this->normalizeLocale($locale) === 'Arabic' ? 'currency_unit_ar' : 'currency_unit';
        $unit = is_array($settings) && isset($settings[$field])
            ? trim((string) $settings[$field])
            : '';

        if ($unit === '') {
            $unit = trim((string) $fallback);
        }

        return $unit !== '' ? $unit : 'SAR';
    }
    /**
     * Format a date for an email in the recipient's language. Arabic uses the frontend
     * Arabic month names ("21 سبتمبر 2026"); English uses EMAIL_DATE_FORMAT. Digits stay
     * Western, as on the Arabic frontend. Values that cannot be parsed are returned as-is.
     */
    public function formatDate($value, $locale = 'English')
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === FALSE) {
            return (string) $value;
        }

        if ($this->normalizeLocale($locale) !== 'Arabic') {
            return date(EMAIL_DATE_FORMAT, $timestamp);
        }

        $catalog = $this->arabicFrontendCatalog();
        $monthKey = 'month.'.(int) date('n', $timestamp);
        $month = isset($catalog[$monthKey]) && is_string($catalog[$monthKey])
            ? $catalog[$monthKey]
            : date('F', $timestamp);
        $template = isset($catalog['format.date']) && is_string($catalog['format.date'])
            ? $catalog['format.date']
            : '{day} {month} {year}';

        return strtr($template, array(
            '{day}' => date('j', $timestamp),
            '{month}' => $month,
            '{year}' => date('Y', $timestamp),
        ));
    }

    /**
     * Format a time for an email in the recipient's language. Arabic keeps EMAIL_TIME_FORMAT
     * but shows the am/pm marker as ص / م. Values that cannot be parsed are returned as-is.
     */
    public function formatTime($value, $locale = 'English')
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === FALSE) {
            return (string) $value;
        }

        if ($this->normalizeLocale($locale) !== 'Arabic') {
            return date(EMAIL_TIME_FORMAT, $timestamp);
        }

        $output = '';
        $escaped = FALSE;
        for ($index = 0, $length = strlen(EMAIL_TIME_FORMAT); $index < $length; $index++) {
            $token = EMAIL_TIME_FORMAT[$index];

            if ($escaped) {
                $output .= $token;
                $escaped = FALSE;
            } elseif ($token === '\\') {
                $escaped = TRUE;
            } elseif ($token === 'a' || $token === 'A') {
                $output .= date('a', $timestamp) === 'am' ? 'ص' : 'م';
            } else {
                $output .= date($token, $timestamp);
            }
        }

        return $output;
    }

    /** Format a date and time for an email in the recipient's language. */
    public function formatDateTime($value, $locale = 'English')
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === FALSE) {
            return (string) $value;
        }

        if ($this->normalizeLocale($locale) !== 'Arabic') {
            return date(EMAIL_DATETIME_FORMAT, $timestamp);
        }

        return $this->formatDate($value, $locale).' '.$this->formatTime($value, $locale);
    }
    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastLogPath()
    {
        return $this->lastLogPath;
    }

    /**
     * Sender identity, most specific first: the dedicated sender fields in
     * Website Settings, then the website's own name/email, then the
     * hard-coded fallback constants.
     */
    private function senderDefaults()
    {
        $settings = $this->CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));

        $email = !empty($settings['sender_email']) ? $settings['sender_email'] : $settings['email'];
        $name = !empty($settings['sender_name']) ? $settings['sender_name'] : $settings['website_title'];

        return array(
            'email' => !empty($email) ? $email : EMAIL_ADDRESS,
            'name' => !empty($name) ? $name : EMAIL_SENDER_NAME,
        );
    }

    /** Resized (height 140px, auto width) logo URL for the email header, via Imagethumb. */
    private function logoUrl($logoFile)
    {
        $path = FCPATH.'assets/frontend/images/logo/'.$logoFile;

        if (!is_file($path)) {
            return base_url('assets/frontend/images/logo/'.$logoFile);
        }

        return $this->CI->imagethumb->image($path, 0, 140);
    }

    /** Arabic frontend language file, which holds the Arabic month names and date format. */
    private function arabicFrontendCatalog()
    {
        static $catalog = NULL;

        if ($catalog === NULL) {
            $loaded = $this->CI->lang->load('frontend', 'arabic', TRUE);
            $catalog = is_array($loaded) ? $loaded : array();
        }

        return $catalog;
    }

    private function normalizeLocale($locale)
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, array('ar', 'arabic'), TRUE) ? 'Arabic' : 'English';
    }

    private function attach($attachment)
    {
        if (is_string($attachment)) {
            $this->CI->email->attach($attachment);
            return;
        }
        if (!is_array($attachment) || empty($attachment['path'])) {
            return;
        }

        $this->CI->email->attach(
            $attachment['path'],
            isset($attachment['disposition']) ? $attachment['disposition'] : 'attachment',
            isset($attachment['name']) ? $attachment['name'] : NULL,
            isset($attachment['mime']) ? $attachment['mime'] : ''
        );
    }

    private function writeLog(array $options)
    {
        $directory = FCPATH.'email_logs';
        if (!is_dir($directory) && !mkdir($directory, 0750, TRUE) && !is_dir($directory)) {
            return $this->fail('Unable to create the email log directory.');
        }

        try {
            $suffix = bin2hex(random_bytes(5));
        } catch (Exception $exception) {
            $suffix = str_replace('.', '', uniqid('', TRUE));
        }

        $subjectSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) $options['subject']));
        $subjectSlug = trim(substr($subjectSlug, 0, 50), '-') ?: 'email';
        $filename = date('Y-m-d_H-i-s').'_'.sprintf('%06d', (int) ((microtime(TRUE) * 1000000) % 1000000)).
            '_'.$subjectSlug.'_'.$suffix.'.html';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        $body = strtolower((string) $options['mailtype']) === 'html'
            ? (string) $options['message']
            : '<pre>'.$this->escape($options['message']).'</pre>';

        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'.
            '<meta name="viewport" content="width=device-width,initial-scale=1">'.
            '<title>'.$this->escape($options['subject']).'</title>'.
            '<style>body{margin:0;background:#f4f4f5;color:#18181b;font:14px/1.5 Arial,sans-serif}'.
            '.email-log{max-width:990px;margin:24px auto;background:#fff;border:1px solid #ddd;border-radius:8px;overflow:hidden}'.
            '.meta{padding:20px;background:#fafafa;border-bottom:1px solid #ddd}.meta dl{display:grid;grid-template-columns:100px 1fr;gap:6px;margin:0}'.
            '.meta dt{font-weight:700}.meta dd{margin:0;word-break:break-word}.message{padding:24px}</style></head><body>'.
            '<main class="email-log"><section class="meta"><dl>'.
            '<dt>Created</dt><dd>'.$this->escape(date('c')).'</dd>'.
            '<dt>From</dt><dd>'.$this->escape($options['from_name'].' <'.$options['from_email'].'>').'</dd>'.
            '<dt>To</dt><dd>'.$this->escape($this->recipientsToString($options['to'])).'</dd>'.
            '<dt>CC</dt><dd>'.$this->escape($this->recipientsToString($options['cc'])).'</dd>'.
            '<dt>BCC</dt><dd>'.$this->escape($this->recipientsToString($options['bcc'])).'</dd>'.
            '<dt>Reply-To</dt><dd>'.$this->escape($options['reply_to']).'</dd>'.
            '<dt>Subject</dt><dd>'.$this->escape($options['subject']).'</dd>'.
            '<dt>Priority</dt><dd>'.$this->escape($options['priority']).'</dd>'.
            '<dt>Headers</dt><dd>'.$this->escape($this->headersToString($options['headers'])).'</dd>'.
            '<dt>Attachments</dt><dd>'.$this->escape($this->attachmentsToString($options['attachments'])).'</dd>'.
            '</dl></section><section class="message">'.$body.'</section></main></body></html>';

        if (file_put_contents($path, $html, LOCK_EX) === FALSE) {
            return $this->fail('Unable to write the email log file.');
        }

        $this->lastLogPath = $path;
        return TRUE;
    }

    private function recipientsAreEmpty($recipients)
    {
        return is_array($recipients) ? count(array_filter($recipients)) === 0 : trim((string) $recipients) === '';
    }

    private function recipientsToString($recipients)
    {
        return is_array($recipients) ? implode(', ', $recipients) : (string) $recipients;
    }

    private function attachmentsToString($attachments)
    {
        $paths = array();
        foreach ((array) $attachments as $attachment) {
            $paths[] = is_array($attachment) && isset($attachment['path']) ? $attachment['path'] : (string) $attachment;
        }
        return implode(', ', array_filter($paths));
    }

    private function headersToString($headers)
    {
        $values = array();
        foreach ((array) $headers as $header => $value) {
            $values[] = $header.': '.$value;
        }
        return implode('; ', $values);
    }

    /** Plain-text alternative for clients that do not render the HTML part. */
    private function plainText($heading, $contents)
    {
        $contents = preg_replace('/<br\s*\/?>/i', "\n", (string) $contents);
        $contents = html_entity_decode(
            strip_tags($contents),
            ENT_QUOTES,
            'UTF-8'
        );

        return trim((string) $heading)."\n\n".trim($contents);
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function fail($message)
    {
        $this->lastError = $message;
        log_message('error', 'EmailService: '.$message);
        return FALSE;
    }
}
