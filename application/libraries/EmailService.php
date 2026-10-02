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
     * email templates (see Short_tags and config/short_tags.php).
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
        ),
        'appointment' => array(
            'reference',
            'first_name',
            'customer_name',
            'customer_email',
            'customer_phone',
            'contact_preference',
            'services',
            'schedule',
            'artist',
            'offer',
            'date',
            'time',
            'duration',
            'estimated_total',
            'notes',
            'created_at',
        ),
        'customer' => array(
            'first_name',
            'customer_name',
            'customer_email',
            'link',
            'expires',
        ),
    );

    /** Tags that render HTML rather than plain text. */
    private static $htmlShortTags = array();

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
     * Render the shared branded email view.
     *
     * Callers may supply body or contents for the main HTML content. The
     * branding and Email footer navigation use Website Settings and the
     * links managed at /manage/foot/index/three unless explicitly supplied.
     */
    public function renderTemplate(array $params = array())
    {
        $settings = !empty($params['site_settings']) && is_array($params['site_settings'])
            ? $params['site_settings']
            : $this->CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));

        if (empty($settings)) {
            $this->fail('Unable to render email template: site settings are missing.');
            return FALSE;
        }

        $brandName = trim((string) $settings['website_title']);
        $body = isset($params['body'])
            ? $params['body']
            : (isset($params['contents']) ? $params['contents'] : '');
        $logoFile = !empty($settings['logo']) ? trim($settings['logo']) : '';
        $copyrightText = !empty($settings['copyright_text'])
            ? str_replace('[YEAR]', date('Y'), trim($settings['copyright_text']))
            : '';

        $data = array_merge($params, array(
            'site_settings' => $settings,
            'brand_name' => $brandName,
            'logo_url' => $logoFile === '' ? '' : $this->logoUrl($logoFile),
            'heading' => isset($params['heading']) ? $params['heading'] : '',
            'body' => $body,
            'footer' => isset($params['footer'])
                ? $params['footer']
                : $this->CI->SqlModel->getFoot('three', TRUE),
            'copyright_text' => $copyrightText,
        ));

        return $this->CI->load->view('email/english', $data, TRUE);
    }

    /** Short tag names an entity supports (contact, appointment, customer). */
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
        return $this->parseShortTags('contact', $template, $values, array('message'));
    }

    /** Replace the supported appointment-request short tags with scalar values. */
    public function parseAppointmentShortTags($template, array $values = array())
    {
        return $this->parseShortTags('appointment', $template, $values, array('schedule', 'notes'));
    }

    /** Replace the supported customer-account short tags with scalar values. */
    public function parseCustomerShortTags($template, array $values = array())
    {
        return $this->parseShortTags('customer', $template, $values, array());
    }

    /** Replace an entity's short tags; $multiline values keep their line breaks as <br>. */
    private function parseShortTags($entity, $template, array $values, array $multiline)
    {
        $replacements = array();

        foreach ($this->shortTagFields($entity) as $field) {
            $value = isset($values[$field]) && is_scalar($values[$field])
                ? (string) $values[$field]
                : '';
            if (in_array($field, $multiline, TRUE)) {
                $value = nl2br($value);
            }
            $replacements['{{'.$field.'}}'] = $value;
        }

        return strtr((string) $template, $replacements);
    }

    /**
     * Send an email built from a managed email_templates row.
     *
     * Supported options:
     * - template_id: the email_templates.id to send
     * - to: recipient address
     * - values: short-tag values keyed by field name
     * - parser: the parse*ShortTags() method that owns the template's tags
     *   (parseContactShortTags, parseAppointmentShortTags or parseCustomerShortTags)
     * - multiline_fields: value keys whose line breaks become <br> in the HTML body
     * - label: prefix for log messages
     */
    public function sendManagedTemplate(array $options)
    {
        $options = array_merge(array(
            'template_id' => 0,
            'to' => '',
            'values' => array(),
            'parser' => '',
            'multiline_fields' => array(),
            'label' => 'Email',
        ), $options);
        $label = (string) $options['label'];
        $templateId = (int) $options['template_id'];

        if (!in_array($options['parser'], array('parseContactShortTags', 'parseAppointmentShortTags', 'parseCustomerShortTags'), TRUE)) {
            return $this->fail($label.' has no valid short tag parser.');
        }

        $template = $this->CI->SqlModel->getSingleRecord('email_templates', array('id' => $templateId));
        if (empty($template)) {
            return $this->fail($label.' email template '.$templateId.' is missing.');
        }

        $subjectTemplate = trim((string) $template['subject']);
        $contentsTemplate = trim((string) $template['contents']);

        if ($subjectTemplate === '' || $contentsTemplate === '') {
            return $this->fail(
                $label.' was not sent because email template '.$templateId.' has incomplete fields.'
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
            isset($template['heading']) ? $template['heading'] : '',
            $htmlValues
        );
        $contents = $this->$parser($contentsTemplate, $htmlValues);
        $message = $this->renderTemplate(array(
            'heading' => $heading,
            'body' => $contents,
        ));

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

    /** Format a date for an email. Values that cannot be parsed are returned as-is. */
    public function formatDate($value)
    {
        $timestamp = strtotime((string) $value);

        return $timestamp === FALSE
            ? (string) $value
            : date(EMAIL_DATE_FORMAT, $timestamp);
    }

    /** Format a time for an email. Values that cannot be parsed are returned as-is. */
    public function formatTime($value)
    {
        $timestamp = strtotime((string) $value);

        return $timestamp === FALSE
            ? (string) $value
            : date(EMAIL_TIME_FORMAT, $timestamp);
    }

    /** Format a date and time for an email. Values that cannot be parsed are returned as-is. */
    public function formatDateTime($value)
    {
        $timestamp = strtotime((string) $value);

        return $timestamp === FALSE
            ? (string) $value
            : date(EMAIL_DATETIME_FORMAT, $timestamp);
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
