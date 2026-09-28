<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Online booking (/book, PROJECT_PLAN.md decision D1 as changed in Phase 8):
 * the wizard's catalogue (services, artists, offers), the free times
 * (Booking_availability), and the booking itself — a one-use session token,
 * server-side validation of every choice, reCAPTCHA Enterprise, a final
 * availability check under a database lock, the appointment saved as
 * Confirmed with each service's artist and start time, and two emails: the
 * salon notification (Website Settings > notification emails) and the
 * client's confirmation (email template 3).
 */
class Booking_request
{
    const RECAPTCHA_ACTION = 'BOOKING';
    const RECAPTCHA_MIN_SCORE = 0.5;
    const MAX_SERVICES = 10;

    /**
     * Client emails for a status: Confirmed is sent when a booking is made
     * (and if staff confirm an older request); both are sent when staff
     * change a status in Manage > Appointments.
     */
    const STATUS_TEMPLATE_IDS = array(
        'Confirmed' => 3,
        'Cancelled' => 4,
    );

    /** MySQL named lock held while a booking's time is checked and saved. */
    const BOOKING_LOCK = 'blossom_booking';

    /** Session key of the last saved booking, shown on /book/confirmed. */
    const CONFIRMED_SESSION_KEY = 'booking_confirmed_id';

    private $CI;
    private $catalogue = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('frontend');
        $this->CI->load->model(array('Service_model', 'Artist_model', 'Offer_model'));
        $this->CI->load->library(array('booking_schedule', 'booking_availability', 'EmailService'));
    }

    /**
     * What can be booked: 'groups' (service categories with their services),
     * 'artists' (with the services each offers) and 'offers', keyed for
     * js/booking.js.
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

        $allServices = array();
        foreach ($groups as $group) {
            $allServices = array_merge($allServices, $group['services']);
        }

        $artists = array();
        foreach ($this->CI->Artist_model->get_all() as $artist) {
            $offered = array();
            foreach ($allServices as $service) {
                if (in_array((int) $artist['artist_id'], $this->CI->booking_availability->artistsForAll(array($service)), TRUE)) {
                    $offered[] = $service['slug'];
                }
            }
            $artists[] = array(
                'id' => (int) $artist['artist_id'],
                'slug' => $artist['artist_slug'],
                'name' => $artist['artist_name'],
                'role' => (string) $artist['artist_role'],
                'specialty' => implode(' · ', frontend_lines($artist['artist_specialties'])),
                'image' => upload_thumb('artists', $artist['artist_image'], 160, 160, 'images/no_image.jpg'),
                'placeholder' => (int) $artist['artist_is_placeholder'] === 1,
                'workingDays' => array_values(array_filter(explode(',', (string) $artist['artist_working_days']))),
                'services' => $offered,
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

    /**
     * Free start times for the posted services (GET services[]) and artist
     * (GET artist, a slug or "any"): Y-m-d => list of "HH:MM", or NULL when
     * the choice itself is invalid.
     */
    public function availability()
    {
        $services = $this->resolveServices($this->CI->input->get('services'));
        if (empty($services) || count($services) > self::MAX_SERVICES) {
            return NULL;
        }

        $artistSlug = $this->CI->input->get('artist');
        $artist = $this->resolveArtist(is_string($artistSlug) ? trim($artistSlug) : 'any');
        if ($artist === FALSE || ($artist !== NULL && !$this->artistOffersAll($artist, $services))) {
            return NULL;
        }

        return $this->CI->booking_availability->days($services, $artist !== NULL ? $artist['id'] : NULL);
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
        if ($appointmentId === 'taken') {
            return $this->result('invalid', array(
                'datetime' => 'Sorry, that time was just booked. Please choose another.',
            ), 2);
        }
        if (!$appointmentId) {
            return $this->result('error');
        }

        $appointment = $this->get($appointmentId);
        $this->notifySalon($appointment);
        $this->acknowledge($appointment);
        $this->CI->session->set_userdata(self::CONFIRMED_SESSION_KEY, $appointmentId);

        return $this->result('success');
    }

    /**
     * Email the client that their appointment is confirmed or cancelled
     * (email templates 3 and 4). Returns TRUE when sent, FALSE when it
     * failed, and NULL when $status has no email.
     */
    public function sendStatusEmail($appointmentId, $status)
    {
        if (!isset(self::STATUS_TEMPLATE_IDS[$status])) {
            return NULL;
        }

        $appointment = $this->get($appointmentId);
        if ($appointment === NULL) {
            return FALSE;
        }

        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => self::STATUS_TEMPLATE_IDS[$status],
            'to' => $appointment['customer_email'],
            'values' => $this->emailValues($appointment),
            'parser' => 'parseAppointmentShortTags',
            // parseAppointmentShortTags() already turns line breaks into <br>.
            'multiline_fields' => array(),
            'label' => 'Appointment '.strtolower($status).' email',
        ));
    }

    /** The booking saved in this session, with its services, or NULL. */
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

        // Services (step 0), in the order chosen: they are booked back to back.
        $services = $this->resolveServices($this->CI->input->post('services'));
        if (empty($services) || count($services) > self::MAX_SERVICES) {
            $fail('services', 'Choose at least one service.', 0);
        }

        // Artist (step 1): "any" or an enabled artist who offers every chosen service.
        $artist = $this->resolveArtist($this->text('artist'));
        if ($artist === FALSE) {
            $artist = NULL;
            $fail('artist', 'That artist is no longer available. Please choose again.', 1);
        } elseif ($artist !== NULL && !empty($services) && !$this->artistOffersAll($artist, $services)) {
            $fail('artist', $artist['name'].' does not offer every service you chose. Please choose again.', 1);
        }

        // Offer: optional, only a public offer.
        $offer = NULL;
        $offerSlug = $this->text('offer');
        foreach ($catalogue['offers'] as $candidate) {
            if ($offerSlug !== '' && $candidate['slug'] === $offerSlug) {
                $offer = $candidate;
            }
        }

        // Date and time (step 2): checked against the diaries in save(), under the booking lock.
        $date = $this->text('date');
        $time = $this->text('time');
        if ($this->CI->booking_schedule->dayCode($date) === NULL || Booking_schedule::minute($time) === NULL) {
            $fail('datetime', 'Please choose a day and a time.', 2);
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
            'services' => $services,
            'artist' => $artist,
            'offer' => $offer,
            'date' => $date,
            'time' => $time,
            'details' => $details,
        );
    }

    /** Catalogue services for posted slugs, in the posted order, without repeats. */
    private function resolveServices($posted)
    {
        $bySlug = array();
        foreach ($this->catalogue()['groups'] as $group) {
            foreach ($group['services'] as $service) {
                $bySlug[$service['slug']] = $service;
            }
        }

        $services = array();
        foreach (is_array($posted) ? $posted : array() as $slug) {
            if (is_string($slug) && isset($bySlug[$slug]) && !isset($services[$slug])) {
                $services[$slug] = $bySlug[$slug];
            }
        }

        return array_values($services);
    }

    /** NULL for "any" (or empty), the catalogue artist for a known slug, FALSE otherwise. */
    private function resolveArtist($slug)
    {
        if ($slug === '' || $slug === 'any') {
            return NULL;
        }
        foreach ($this->catalogue()['artists'] as $artist) {
            if ($artist['slug'] === $slug) {
                return $artist;
            }
        }

        return FALSE;
    }

    private function artistOffersAll(array $artist, array $services)
    {
        return in_array($artist['id'], $this->CI->booking_availability->artistsForAll($services), TRUE);
    }

    /**
     * Book the visit: under the booking lock, check that the time is still
     * free, then save the appointment (Confirmed) and its services with their
     * artists and start times. Returns the appointment ID, 'taken' when the
     * time is no longer free, or FALSE on failure.
     */
    private function save(array $choice)
    {
        $locked = (int) $this->CI->db
            ->query('SELECT GET_LOCK(?, 10) AS locked', array(self::BOOKING_LOCK))
            ->row()
            ->locked;
        if ($locked !== 1) {
            log_message('error', 'Booking_request could not get the booking lock.');
            return FALSE;
        }

        try {
            $segments = $this->CI->booking_availability->schedule(
                $choice['services'],
                $choice['artist'] !== NULL ? $choice['artist']['id'] : NULL,
                $choice['date'],
                $choice['time']
            );
            if ($segments === NULL) {
                return 'taken';
            }

            return $this->insert($choice, $segments);
        } finally {
            $this->CI->db->query('SELECT RELEASE_LOCK(?)', array(self::BOOKING_LOCK));
        }
    }

    /** Save the appointment and its service segments together. Returns its ID or FALSE. */
    private function insert(array $choice, array $segments)
    {
        $prices = array_filter(array_column($choice['services'], 'price'), 'is_numeric');
        $now = date('Y-m-d H:i:s');
        $artistNames = array();
        $artistIds = array();
        foreach ($segments as $segment) {
            $artistIds[$segment['artist']['id']] = TRUE;
            $artistNames[$segment['artist']['id']] = $segment['artist']['name'];
        }
        $first = reset($segments);
        $last = end($segments);
        $oneArtist = count($artistIds) === 1;

        $this->CI->db->trans_start();
        $this->CI->db->insert('appointments', array(
            'appointment_reference' => $this->newReference(),
            'appointment_status' => 'Confirmed',
            'appointment_date' => $choice['date'],
            'appointment_time' => Booking_schedule::clock($first['start']).':00',
            'appointment_duration_minutes' => $last['end'] - $first['start'],
            'appointment_total_price' => !empty($prices) ? array_sum($prices) : NULL,
            // Set when one artist does the whole visit; otherwise each service row names its artist.
            'appointment_artist_id' => $oneArtist ? $first['artist']['id'] : NULL,
            'appointment_artist_name' => implode(', ', $artistNames),
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

        foreach ($segments as $segment) {
            $service = $segment['service'];
            $this->CI->db->insert('appointment_services', array(
                'appointment_id' => $appointmentId,
                'service_id' => $service['id'],
                'service_name' => $service['name'],
                'service_price' => $service['price'],
                'service_duration_minutes' => $segment['end'] - $segment['start'],
                'service_artist_id' => $segment['artist']['id'],
                'service_artist_name' => $segment['artist']['name'],
                'service_start_time' => Booking_schedule::clock($segment['start']).':00',
            ));
        }
        $this->CI->db->trans_complete();

        if ($this->CI->db->trans_status() === FALSE || $appointmentId < 1) {
            log_message('error', 'Booking_request could not save a booking.');
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
            'Schedule' => str_replace("\n", '; ', $values['schedule']),
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
        $text = array('A new appointment was booked on the website. It is confirmed and in Manage > Appointments.', '');
        foreach (array_filter($rows, 'strlen') as $label => $value) {
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

        $subject = 'New booking '.$values['reference'].': '.$values['date'].' '.$values['time'];
        $message = $this->CI->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => 'New booking',
            'body' => '<p>A new appointment was booked on the website. It is confirmed and the client has been'
                .' emailed; you can see it, or cancel it, in Manage &gt; Appointments.</p>'
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

    /** The client's booking confirmation (email template 3). */
    private function acknowledge(array $appointment)
    {
        return $this->sendStatusEmail($appointment['appointment_id'], 'Confirmed');
    }

    /** Short tag values for the appointment emails (EmailService 'appointment' entity). */
    private function emailValues(array $appointment)
    {
        $nameParts = preg_split('/\s+/u', trim((string) $appointment['customer_name']), 2);

        // One line per service: "10:00 am Gel Manicure with Ewa Mazur" (bookings made since Phase 8).
        $schedule = array();
        foreach ($appointment['services'] as $service) {
            if ($service['service_start_time'] !== NULL) {
                $schedule[] = $this->CI->emailservice->formatTime($service['service_start_time'])
                    .' '.$service['service_name']
                    .($service['service_artist_name'] !== NULL ? ' with '.$service['service_artist_name'] : '');
            }
        }

        return array(
            'reference' => $appointment['appointment_reference'],
            'first_name' => $nameParts[0],
            'customer_name' => $appointment['customer_name'],
            'customer_email' => $appointment['customer_email'],
            'customer_phone' => (string) $appointment['customer_phone'],
            'contact_preference' => strtolower($appointment['customer_contact_preference']),
            'services' => implode(', ', array_column($appointment['services'], 'service_name')),
            'schedule' => implode("\n", $schedule),
            'artist' => $appointment['appointment_artist_name'] !== NULL && $appointment['appointment_artist_name'] !== ''
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
