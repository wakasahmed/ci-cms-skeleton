<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Online appointment requests (/book, PROJECT_PLAN.md decision D1): the
 * wizard's catalogue (services, artists, offers), and the request itself —
 * a one-use session token, server-side validation of every choice,
 * reCAPTCHA Enterprise, the saved appointment with a snapshot of its
 * services, and two emails: the salon notification (Website Settings >
 * notification emails) and the client's "request received" email (email
 * template 2). Staff confirm each request in Manage > Appointments.
 */
class Booking_request
{
    const RECAPTCHA_ACTION = 'BOOKING';
    const RECAPTCHA_MIN_SCORE = 0.5;
    const MAX_SERVICES = 10;
    const CLIENT_TEMPLATE_ID = 2;

    /** Session key of the last saved request, shown on /book/confirmed. */
    const CONFIRMED_SESSION_KEY = 'booking_confirmed_id';

    private $CI;
    private $catalogue = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Service_model', 'Artist_model', 'Offer_model'));
        $this->CI->load->library(array('booking_schedule', 'EmailService'));
    }

    /**
     * What can be requested: 'groups' (service categories with their
     * services), 'artists' and 'offers', keyed for js/booking.js.
     */
    public function catalogue()
    {
        if ($this->catalogue !== NULL) {
            return $this->catalogue;
        }

        $groups = array();
        foreach ($this->CI->Service_model->get_menu() as $category) {
            $services = array();
            foreach ($category['services'] as $service) {
                $services[] = array(
                    'id' => (int) $service['service_id'],
                    'slug' => $service['service_slug'],
                    'name' => $service['service_name'],
                    'summary' => (string) $service['service_summary'],
                    'price' => $service['service_price_from'] !== NULL ? (float) $service['service_price_from'] : NULL,
                    'priceLabel' => frontend_service_price($service),
                    'minutes' => (int) $service['service_duration_minutes'],
                    'durationLabel' => (string) $service['service_duration_label'],
                    'image' => upload_thumb('services', $service['service_card_image'], 160, 160, 'images/no_image.jpg'),
                );
            }

            $groups[] = array(
                'slug' => $category['category_slug'],
                'name' => $category['category_name'],
                'description' => (string) $category['category_description'],
                'services' => $services,
            );
        }

        $artists = array();
        foreach ($this->CI->Artist_model->get_all() as $artist) {
            $artists[] = array(
                'id' => (int) $artist['artist_id'],
                'slug' => $artist['artist_slug'],
                'name' => $artist['artist_name'],
                'role' => (string) $artist['artist_role'],
                'specialty' => implode(' · ', frontend_lines($artist['artist_specialties'])),
                'image' => upload_thumb('artists', $artist['artist_image'], 160, 160, 'images/no_image.jpg'),
                'placeholder' => (int) $artist['artist_is_placeholder'] === 1,
                'workingDays' => array_values(array_filter(explode(',', (string) $artist['artist_working_days']))),
            );
        }

        $offerRows = $this->CI->Offer_model->get_public(FALSE);
        $offerServices = $this->CI->Offer_model->get_service_slugs(array_column($offerRows, 'offer_id'));
        $offers = array();
        foreach ($offerRows as $offer) {
            $offers[] = array(
                'id' => (int) $offer['offer_id'],
                'slug' => $offer['offer_slug'],
                'title' => $offer['offer_title'],
                'price' => $offer['offer_price'] !== NULL ? (float) $offer['offer_price'] : NULL,
                'services' => isset($offerServices[(int) $offer['offer_id']]) ? $offerServices[(int) $offer['offer_id']] : array(),
            );
        }

        $this->catalogue = array(
            'groups' => $groups,
            'artists' => $artists,
            'offers' => $offers,
        );

        return $this->catalogue;
    }

    /** The session-bound token the wizard posts back. */
    public function token()
    {
        $token = (string) $this->CI->session->userdata('booking_form_token');
        if (strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->CI->session->set_userdata('booking_form_token', $token);
        }

        return $token;
    }

    /**
     * Handle the wizard's POST. Returns array('status' => 'success' |
     * 'invalid' | 'expired' | 'recaptcha' | 'error', 'errors' => field =>
     * message, 'step' => the wizard step to return to).
     */
    public function submit()
    {
        if (!$this->consumeToken()) {
            return $this->result('expired');
        }

        $choice = $this->choice();
        if (!empty($choice['errors'])) {
            return $this->result('invalid', $choice['errors'], $choice['step']);
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
            return $this->result('recaptcha');
        }

        $appointmentId = $this->save($choice);
        if (!$appointmentId) {
            return $this->result('error');
        }

        $appointment = $this->get($appointmentId);
        $this->notifySalon($appointment);
        $this->acknowledge($appointment);
        $this->CI->session->set_userdata(self::CONFIRMED_SESSION_KEY, $appointmentId);

        return $this->result('success');
    }

    /** The request saved in this session, with its services, or NULL. */
    public function confirmed()
    {
        $appointmentId = (int) $this->CI->session->userdata(self::CONFIRMED_SESSION_KEY);

        return $appointmentId > 0 ? $this->get($appointmentId) : NULL;
    }

    /** One appointment with its 'services' rows, or NULL. */
    private function get($appointmentId)
    {
        $appointment = $this->CI->db
            ->where('appointment_id', (int) $appointmentId)
            ->get('appointments')
            ->row_array();
        if ($appointment === NULL) {
            return NULL;
        }

        $appointment['services'] = $this->CI->db
            ->where('appointment_id', (int) $appointmentId)
            ->order_by('id', 'ASC')
            ->get('appointment_services')
            ->result_array();

        return $appointment;
    }

    /**
     * The posted choices checked against the catalogue and the schedule.
     * Returns the resolved services, artist, offer, slot and details, with
     * 'errors' (field => message) and the earliest 'step' that has one.
     */
    private function choice()
    {
        $catalogue = $this->catalogue();
        $errors = array();
        $step = NULL;
        $fail = function ($field, $message, $fieldStep) use (&$errors, &$step) {
            $errors[$field] = $message;
            $step = $step === NULL ? $fieldStep : min($step, $fieldStep);
        };

        // Services (step 0).
        $bySlug = array();
        foreach ($catalogue['groups'] as $group) {
            foreach ($group['services'] as $service) {
                $bySlug[$service['slug']] = $service;
            }
        }
        $posted = $this->CI->input->post('services');
        $services = array();
        foreach (is_array($posted) ? $posted : array() as $slug) {
            if (is_string($slug) && isset($bySlug[$slug]) && !isset($services[$slug])) {
                $services[$slug] = $bySlug[$slug];
            }
        }
        if (empty($services) || count($services) > self::MAX_SERVICES) {
            $fail('services', 'Choose at least one service.', 0);
        }

        // Artist (step 1): "any" or an enabled artist.
        $artistSlug = $this->text('artist');
        $artist = NULL;
        if ($artistSlug !== '' && $artistSlug !== 'any') {
            foreach ($catalogue['artists'] as $candidate) {
                if ($candidate['slug'] === $artistSlug) {
                    $artist = $candidate;
                }
            }
            if ($artist === NULL) {
                $fail('artist', 'That artist is no longer available. Please choose again.', 1);
            }
        }

        // Offer: optional, only a public offer.
        $offer = NULL;
        $offerSlug = $this->text('offer');
        foreach ($catalogue['offers'] as $candidate) {
            if ($offerSlug !== '' && $candidate['slug'] === $offerSlug) {
                $offer = $candidate;
            }
        }

        // Date and time (step 2).
        $minutes = array_sum(array_column($services, 'minutes'));
        $date = $this->text('date');
        $time = $this->text('time');
        if (!$this->CI->booking_schedule->isAvailable(
            $date,
            $time,
            $minutes,
            $artist !== NULL ? $artist['workingDays'] : NULL
        )) {
            $fail('datetime', 'That time is no longer available. Please choose another.', 2);
        }

        // Details (step 3).
        $details = array(
            'name' => $this->text('name'),
            'phone' => $this->text('phone'),
            'email' => $this->text('email'),
            'contact' => $this->text('contact') === 'email' ? 'Email' : 'Phone',
            'notes' => $this->text('notes'),
        );
        if ($details['name'] === '' || mb_strlen($details['name']) > 150) {
            $fail('name', 'Please tell us your name.', 3);
        }
        if ($details['phone'] === ''
            || mb_strlen($details['phone']) > 40
            || !preg_match('/^\+?[0-9 ()\-]+$/', $details['phone'])
            || preg_match_all('/[0-9]/', $details['phone']) < 6
        ) {
            $fail('phone', 'We need a phone number to confirm.', 3);
        }
        if (!$this->validEmail($details['email'])) {
            $fail('email', 'That email doesn’t look quite right.', 3);
        }
        if (mb_strlen($details['notes']) > 2000) {
            $fail('notes', 'Please keep notes under 2,000 characters.', 3);
        }

        return array(
            'errors' => $errors,
            'step' => $step,
            'services' => array_values($services),
            'artist' => $artist,
            'offer' => $offer,
            'date' => $date,
            'time' => $time,
            'minutes' => $minutes,
            'details' => $details,
        );
    }

    /** Save the appointment and its services together. Returns its ID or FALSE. */
    private function save(array $choice)
    {
        $prices = array_filter(array_column($choice['services'], 'price'), 'is_numeric');
        $now = date('Y-m-d H:i:s');

        $this->CI->db->trans_start();
        $this->CI->db->insert('appointments', array(
            'appointment_reference' => $this->newReference(),
            'appointment_status' => 'New',
            'appointment_date' => $choice['date'],
            'appointment_time' => $choice['time'].':00',
            'appointment_duration_minutes' => $choice['minutes'] > 0 ? $choice['minutes'] : NULL,
            'appointment_total_price' => !empty($prices) ? array_sum($prices) : NULL,
            'appointment_artist_id' => $choice['artist'] !== NULL ? $choice['artist']['id'] : NULL,
            'appointment_artist_name' => $choice['artist'] !== NULL ? $choice['artist']['name'] : NULL,
            'appointment_offer_id' => $choice['offer'] !== NULL ? $choice['offer']['id'] : NULL,
            'appointment_offer_title' => $choice['offer'] !== NULL ? $choice['offer']['title'] : NULL,
            'customer_name' => $choice['details']['name'],
            'customer_email' => $choice['details']['email'],
            'customer_phone' => $choice['details']['phone'],
            'customer_contact_preference' => $choice['details']['contact'],
            'customer_notes' => $choice['details']['notes'] !== '' ? $choice['details']['notes'] : NULL,
            'ip' => $this->CI->input->ip_address(),
            'user_agent' => $this->userAgent(),
            'appointment_added' => $now,
            'appointment_updated' => $now,
        ));
        $appointmentId = (int) $this->CI->db->insert_id();

        foreach ($choice['services'] as $service) {
            $this->CI->db->insert('appointment_services', array(
                'appointment_id' => $appointmentId,
                'service_id' => $service['id'],
                'service_name' => $service['name'],
                'service_price' => $service['price'],
                'service_duration_minutes' => $service['minutes'] > 0 ? $service['minutes'] : NULL,
            ));
        }
        $this->CI->db->trans_complete();

        if ($this->CI->db->trans_status() === FALSE || $appointmentId < 1) {
            log_message('error', 'Booking_request could not save an appointment request.');
            return FALSE;
        }

        return $appointmentId;
    }

    /** An unused reference such as BLM-4102 (longer once the short ones run low). */
    private function newReference()
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $reference = $attempt < 10
                ? 'BLM-'.random_int(1000, 9999)
                : 'BLM-'.random_int(100000, 999999);
            $taken = $this->CI->db
                ->where('appointment_reference', $reference)
                ->count_all_results('appointments');
            if ($taken === 0) {
                return $reference;
            }
        }

        return 'BLM-'.strtoupper(bin2hex(random_bytes(4)));
    }

    /** Email every valid address in Website Settings > notification emails. */
    private function notifySalon(array $appointment)
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
            log_message('error', 'Appointment notification was not sent: no valid notification emails are configured.');
            return false;
        }

        $values = $this->emailValues($appointment);
        $rows = array(
            'Reference' => $values['reference'],
            'Services' => $values['services'],
            'Artist' => $values['artist'],
            'Offer' => $values['offer'],
            'Date' => $values['date'],
            'Time' => $values['time'],
            'Time needed' => $values['duration'],
            'Estimated total' => $values['estimated_total'],
            'Name' => $values['customer_name'],
            'Phone' => $values['customer_phone'],
            'Email' => $values['customer_email'],
            'Contact by' => $values['contact_preference'],
        );

        $table = '';
        $text = array('A new appointment request was sent from the website.', '');
        foreach ($rows as $label => $value) {
            $table .= '<tr>'
                .'<td style="padding:4px 16px 4px 0;vertical-align:top"><strong>'.$this->escape($label).':</strong></td>'
                .'<td style="padding:4px 0;vertical-align:top">'.$this->escape($value).'</td>'
                .'</tr>';
            $text[] = $label.': '.$value;
        }
        $notes = (string) $appointment['customer_notes'];
        if ($notes !== '') {
            $text[] = '';
            $text[] = 'Notes:';
            $text[] = $notes;
        }

        $subject = 'New appointment request '.$values['reference'].': '.$values['date'].' '.$values['time'];
        $message = $this->CI->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => 'New appointment request',
            'body' => '<p>A new appointment request was sent from the website. Please confirm it with the client'
                .' and update its status in Manage &gt; Appointments.</p>'
                .'<table cellpadding="0" cellspacing="0" style="margin:16px 0">'.$table.'</table>'
                .($notes !== '' ? '<h3>Notes</h3><p>'.nl2br($this->escape($notes), false).'</p>' : ''),
        ));
        if ($message === false) {
            log_message('error', 'Appointment notification could not be rendered for '.$values['reference'].'.');
            return false;
        }

        $sent = $this->CI->emailservice->send(array(
            'to' => array_values($recipients),
            'reply_to' => $appointment['customer_email'],
            'reply_to_name' => $appointment['customer_name'],
            'subject' => $subject,
            'message' => $message,
            'alt_message' => implode("\n", $text),
        ));
        if (!$sent) {
            log_message(
                'error',
                'Appointment notification failed for '.$values['reference'].': '
                .$this->CI->emailservice->getLastError()
            );
        }

        return $sent;
    }

    /** The client's "request received" email, from email template 2. */
    private function acknowledge(array $appointment)
    {
        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => self::CLIENT_TEMPLATE_ID,
            'to' => $appointment['customer_email'],
            'values' => $this->emailValues($appointment),
            'parser' => 'parseAppointmentShortTags',
            // parseAppointmentShortTags() already turns the notes' line breaks into <br>.
            'multiline_fields' => array(),
            'label' => 'Appointment request acknowledgement',
        ));
    }

    /** Short tag values for the appointment emails (EmailService 'appointment' entity). */
    private function emailValues(array $appointment)
    {
        $nameParts = preg_split('/\s+/u', trim((string) $appointment['customer_name']), 2);

        return array(
            'reference' => $appointment['appointment_reference'],
            'first_name' => $nameParts[0],
            'customer_name' => $appointment['customer_name'],
            'customer_email' => $appointment['customer_email'],
            'customer_phone' => (string) $appointment['customer_phone'],
            'contact_preference' => strtolower($appointment['customer_contact_preference']),
            'services' => implode(', ', array_column($appointment['services'], 'service_name')),
            'artist' => $appointment['appointment_artist_name'] !== NULL
                ? $appointment['appointment_artist_name']
                : 'Next available artist',
            'offer' => (string) $appointment['appointment_offer_title'],
            'date' => $this->CI->emailservice->formatDate($appointment['appointment_date']),
            'time' => $this->CI->emailservice->formatTime($appointment['appointment_time']),
            'duration' => self::duration((int) $appointment['appointment_duration_minutes']),
            'estimated_total' => $appointment['appointment_total_price'] !== NULL
                ? frontend_price($appointment['appointment_total_price'])
                : '',
            'notes' => (string) $appointment['customer_notes'],
            'created_at' => $this->CI->emailservice->formatDateTime($appointment['appointment_added']),
        );
    }

    /** "45 min", "1 hr", "1 hr 45 min" ('' for no duration). */
    public static function duration($minutes)
    {
        $minutes = (int) $minutes;
        if ($minutes < 1) {
            return '';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $hours === 0
            ? $rest.' min'
            : $hours.' hr'.($rest > 0 ? ' '.$rest.' min' : '');
    }

    /** Check and rotate the one-use token. */
    private function consumeToken()
    {
        $expected = (string) $this->CI->session->userdata('booking_form_token');
        $submitted = (string) $this->CI->input->post('form_token');
        $this->CI->session->unset_userdata('booking_form_token');

        return strlen($expected) === 64
            && strlen($submitted) === 64
            && hash_equals($expected, $submitted);
    }

    private function result($status, array $errors = array(), $step = NULL)
    {
        return array(
            'status' => $status,
            'errors' => $errors,
            'step' => $step,
        );
    }

    private function text($field)
    {
        $value = $this->CI->input->post($field);

        return is_string($value) ? trim($value) : '';
    }

    /** A valid address with a dot-separated domain. */
    private function validEmail($email)
    {
        if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $domain = substr($email, strrpos($email, '@') + 1);

        return strpos($domain, '.') > 0 && substr($domain, -1) !== '.';
    }

    private function userAgent()
    {
        $agent = trim((string) $this->CI->input->user_agent());

        return $agent !== '' ? mb_substr($agent, 0, 500) : NULL;
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
