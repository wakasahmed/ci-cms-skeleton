<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The public Contact form (/contact): subjects and success message from
 * Manage > Form Settings, a one-use session token, server-side validation,
 * reCAPTCHA Enterprise, the saved Contact Request, and two emails — the
 * salon notification (Website Settings > notification emails) and the
 * acknowledgement to the visitor (email template 1).
 *
 * Global CSRF protection is off, so the one-use token is what ties a POST to
 * a form this site rendered.
 */
class Contact_form
{
    const RECAPTCHA_ACTION = 'CONTACT';
    const RECAPTCHA_MIN_SCORE = 0.5;
    const MESSAGE_MIN_WORDS = 3;
    const ACKNOWLEDGEMENT_TEMPLATE_ID = 1;

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Contact_model');
        $this->CI->load->library('form_token');
    }

    /** Subjects (value => label) and the success message. */
    public function settings()
    {
        $settings = $this->CI->Contact_model->get_form_settings();
        $subjects = array();

        if (is_array($settings)) {
            foreach (preg_split('/\R/u', (string) $settings['subject_values']) as $subject) {
                $subject = trim($subject);
                if ($subject !== '') {
                    $subjects[$subject] = $subject;
                }
            }
        }

        $success = is_array($settings) ? trim((string) $settings['success_message']) : '';

        return array(
            'subjects' => $subjects,
            'success_message' => $success !== ''
                ? $success
                : 'Thank you. We will reply during opening hours.',
        );
    }

    /** The session-bound token the form posts back. */
    public function token()
    {
        return $this->CI->form_token->get('contact');
    }

    /**
     * Handle a POST. Returns array('status' => 'success' | 'invalid' |
     * 'expired' | 'recaptcha' | 'error', 'errors' => field => message,
     * 'values' => the submitted values to show again).
     */
    public function submit()
    {
        $values = $this->values();

        if (!$this->CI->form_token->consume('contact')) {
            return $this->result('expired', $values);
        }

        $settings = $this->settings();
        $errors = $this->validate($values, $settings['subjects']);
        if (!empty($errors)) {
            return $this->result('invalid', $values, $errors);
        }

        $this->CI->load->library('google_recaptcha');
        $verified = $this->CI->google_recaptcha->verify(
            $this->CI->input->post('recaptcha_token'),
            self::RECAPTCHA_ACTION,
            self::RECAPTCHA_MIN_SCORE,
            $this->CI->input->ip_address(),
            (string) $this->userAgent()
        );
        if (!$verified) {
            return $this->result('recaptcha', $values);
        }

        // The form asks for one name; the first word is stored as the first name.
        $nameParts = preg_split('/\s+/u', $values['name'], 2);
        $now = date('Y-m-d H:i:s');
        $request = array(
            'first_name' => $nameParts[0],
            'last_name' => isset($nameParts[1]) ? $nameParts[1] : '',
            'email' => $values['email'],
            'subject' => $values['subject'],
            'phone' => $values['phone'] !== '' ? $values['phone'] : NULL,
            'message' => $values['message'],
            'ip' => $this->CI->input->ip_address(),
            'user_agent' => $this->userAgent(),
            'created_at' => $now,
            'updated_at' => $now,
        );

        $requestId = $this->CI->Contact_model->create_request($request);
        if (!$requestId) {
            log_message('error', 'Contact_model::create_request failed to save a contact request.');
            return $this->result('error', $values);
        }

        $request['id'] = (int) $requestId;
        $this->notifySalon($request);
        $this->acknowledge($request);

        return $this->result('success', array());
    }

    /** Untrusted POST values as trimmed strings. */
    private function values()
    {
        $values = array();
        foreach (array('name', 'email', 'phone', 'subject', 'message') as $field) {
            $value = $this->CI->input->post($field);
            $values[$field] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }

    /** Server-side validation: field => message, empty when valid. */
    private function validate(array $values, array $subjects)
    {
        $errors = array();

        if ($values['name'] === '' || mb_strlen($values['name']) > 255) {
            $errors['name'] = 'Please tell us your name.';
        }
        if (!$this->validEmail($values['email'])) {
            $errors['email'] = 'That email doesn’t look quite right.';
        }
        if ($values['phone'] !== ''
            && (mb_strlen($values['phone']) > 50
                || !preg_match('/^\+?[0-9 ()\-]+$/', $values['phone'])
                || preg_match_all('/[0-9]/', $values['phone']) < 6)
        ) {
            $errors['phone'] = 'Please enter a phone number we can call, or leave it empty.';
        }
        if (!isset($subjects[$values['subject']])) {
            $errors['subject'] = 'Please choose a subject.';
        }
        if ($values['message'] === '' || mb_strlen($values['message']) > 5000) {
            $errors['message'] = 'Please write your message (up to 5,000 characters).';
        } elseif ($this->wordCount($values['message']) < self::MESSAGE_MIN_WORDS) {
            $errors['message'] = 'Please tell us a little more.';
        }

        return $errors;
    }

    /** A valid address with a dot-separated domain. */
    private function validEmail($email)
    {
        if ($email === '' || mb_strlen($email) > 100 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $domain = substr($email, strrpos($email, '@') + 1);

        return strpos($domain, '.') > 0 && substr($domain, -1) !== '.';
    }

    private function wordCount($message)
    {
        $matched = preg_match_all("/\\p{L}[\\p{L}\\p{M}\\p{N}'’-]*/u", (string) $message);

        return $matched === false ? 0 : $matched;
    }

    private function userAgent()
    {
        $agent = trim((string) $this->CI->input->user_agent());

        return $agent !== '' ? mb_substr($agent, 0, 500) : NULL;
    }

    private function result($status, array $values, array $errors = array())
    {
        return array(
            'status' => $status,
            'errors' => $errors,
            'values' => $values,
        );
    }

    /** Email every valid address in Website Settings > notification emails. */
    private function notifySalon(array $request)
    {
        $settings = $this->CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $addresses = isset($settings['notification_emails']) ? (string) $settings['notification_emails'] : '';
        $recipients = array();
        foreach (preg_split('/\R/', $addresses) as $line) {
            $email = trim($line);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $recipients[$email] = $email;
            }
        }
        if (empty($recipients)) {
            log_message('error', 'Contact notification was not sent: no valid notification emails are configured.');
            return false;
        }

        $this->CI->load->library('EmailService');
        $name = trim($request['first_name'].' '.$request['last_name']);
        $rows = array(
            'Request ID' => (int) $request['id'],
            'Name' => $name,
            'Email' => $request['email'],
            'Phone' => $request['phone'] !== NULL ? $request['phone'] : 'Not provided',
            'Subject' => $request['subject'],
            'Received' => $this->CI->emailservice->formatDateTime($request['created_at']),
        );

        $table = '';
        $text = array('A new message was sent from the website contact form.', '');
        foreach ($rows as $label => $value) {
            $table .= '<tr>'
                .'<td style="padding:4px 16px 4px 0;vertical-align:top"><strong>'.$this->escape($label).':</strong></td>'
                .'<td style="padding:4px 0;vertical-align:top">'.$this->escape($value).'</td>'
                .'</tr>';
            $text[] = $label.': '.$value;
        }
        $text[] = '';
        $text[] = 'Message:';
        $text[] = $request['message'];

        $subject = 'New contact message: '.$request['subject'];
        $message = $this->CI->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => $subject,
            'body' => '<p>A new message was sent from the website contact form.</p>'
                .'<table cellpadding="0" cellspacing="0" style="margin:16px 0">'.$table.'</table>'
                .'<h3>Message</h3>'
                .'<p>'.nl2br($this->escape($request['message']), false).'</p>',
        ));
        if ($message === false) {
            log_message('error', 'Contact notification could not be rendered for request '.(int) $request['id'].'.');
            return false;
        }

        $sent = $this->CI->emailservice->send(array(
            'to' => array_values($recipients),
            'reply_to' => $request['email'],
            'reply_to_name' => $name,
            'subject' => $subject,
            'message' => $message,
            'alt_message' => implode("\n", $text),
        ));
        if (!$sent) {
            log_message(
                'error',
                'Contact notification failed for request '.(int) $request['id'].': '
                .$this->CI->emailservice->getLastError()
            );
        }

        return $sent;
    }

    /** The acknowledgement to the visitor, from email template 1. */
    private function acknowledge(array $request)
    {
        $this->CI->load->library('EmailService');

        $values = array();
        foreach ($this->CI->emailservice->shortTagFields('contact') as $field) {
            $values[$field] = isset($request[$field]) && $request[$field] !== NULL ? (string) $request[$field] : '';
        }
        $values['created_at'] = $this->CI->emailservice->formatDateTime($request['created_at']);
        $values['updated_at'] = $this->CI->emailservice->formatDateTime($request['updated_at']);

        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => self::ACKNOWLEDGEMENT_TEMPLATE_ID,
            'to' => $request['email'],
            'values' => $values,
            'parser' => 'parseContactShortTags',
            // parseContactShortTags() already turns the message's line breaks into <br>.
            'multiline_fields' => array(),
            'label' => 'Contact acknowledgement',
        ));
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
