<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Frontend extends CI_Controller
{
    const LANGUAGE_COOKIE = 'frontend_language';

    const LANGUAGE_COOKIE_LIFETIME = 31536000;

    /** Lead article plus one grid page — matches the lead-plus-grid layout. */
    const BLOG_PER_PAGE = 7;

    /** reCAPTCHA Enterprise action and minimum accepted risk score for Contact. */
    const CONTACT_RECAPTCHA_ACTION = 'CONTACT';
    const CONTACT_RECAPTCHA_MIN_SCORE = 0.5;
    const CONTACT_MESSAGE_MIN_WORDS = 3;

    /** reCAPTCHA Enterprise action and minimum accepted risk score for Plan Your Visit. */
    const PLAN_RECAPTCHA_ACTION = 'PLAN_YOUR_VISIT';
    const PLAN_RECAPTCHA_MIN_SCORE = 0.5;

    /**
     * reCAPTCHA Enterprise actions and minimum accepted risk score for the booking wizard,
     * keyed by booking_step. The step that creates the booking is verified here;
     * payment completion is verified independently through Moyasar's server API.
     */
    const BOOKING_RECAPTCHA_ACTIONS = array(
        '1' => 'BOOKING_START',
    );
    const BOOKING_RECAPTCHA_MIN_SCORE = 0.5;

    /** reCAPTCHA Enterprise action and minimum accepted risk score for the customer review form. */
    const REVIEW_RECAPTCHA_ACTION = 'REVIEW';
    const REVIEW_RECAPTCHA_MIN_SCORE = 0.5;

    private $pages = array(
        'index' => 'index.php',
        'about_us' => 'about-us.php',
        'tours' => 'tours.php',
        'tour_details' => 'tour-details.php',
        'experiences' => 'experiences.php',
        'tour_guides' => 'tour-guides.php',
        'plan_your_trip' => 'plan-your-trip.php',
        'faqs' => 'faqs.php',
        'contact' => 'contact.php',
        'blog' => 'blog.php',
        'blog_post' => 'blog-post.php',
        'book' => 'book.php',
        'review' => 'review.php',
        'privacy_policy' => 'privacy-policy.php',
        'cancellation_policy' => 'cancellation-policy.php',
        'page' => 'page.php',
        'error_404' => 'error-404.php',
    );

    public function __construct()
    {
        parent::__construct();

        /* $route['404_override'] also catches unmatched URLs under /manage
           (there is no application/controllers/manage/<Whatever>.php for
           them). Those belong to the admin area, not the public site, so
           they get the admin-styled 404 page instead of the public
           bilingual 404 page. */
        if ($this->uri->rsegment(2) === 'error_404'
            && strtolower(trim((string) $this->uri->segment(1))) === 'manage'
        ) {
            $this->renderManageNotFound();
        }

        $settings = $this->SqlModel->getSingleRecord(
            'site_settings',
            array('id' => 1)
        );
        $settings = is_array($settings) ? $settings : array();
        $requestedLocale = strtolower(trim((string) $this->uri->segment(1)));

        if (in_array($requestedLocale, array('en', 'ar'), true)) {
            $locale = $requestedLocale;
            $this->rememberFrontendLocale($locale);
        } elseif ($this->uri->rsegment(2) === 'error_404') {
            /* The 404 route override lands here for any unmatched URL that
               has no /en or /ar prefix (a mistyped or dead link). Render the
               404 page in the visitor's preferred locale instead of
               redirecting to the homepage, which would hide the broken
               link. */
            $locale = $this->preferredFrontendLocale($settings);
        } else {
            $locale = $this->preferredFrontendLocale($settings);
            redirect(base_url($locale));
        }

        $idiom = $locale === 'ar' ? 'arabic' : 'english';

        if (!defined('FRONTEND_LOCALE')) {
            define('FRONTEND_LOCALE', $locale);
        }

        $this->config->set_item('language', $idiom);
        $this->lang->load('frontend', $idiom);
        $this->load->library('frontend_seo');
        $this->load->library('frontend_presenter');

        $this->loadSharedFrontendData($settings);

        if ($this->isUnderConstruction($settings)) {
            $this->renderUnderConstruction();
        }
    }

    public function index()
    {
        $this->render('index', $this->buildHomeViewData());
    }

    /** Resolve published CMS slugs while preserving each core page's view. */
    public function page($locale = 'en', $slug = '')
    {
        $locale = strtolower(trim((string) $locale));
        $slug = trim(urldecode((string) $slug));
        if (!in_array($locale, array('en', 'ar'), true) || $slug === '') {
            return $this->error_404();
        }

        $this->load->model('Webpage_model');
        $page = $this->Webpage_model->get_page(
            $locale,
            'slug',
            $slug,
            true,
            true
        );
        if (!empty($page)) {
            $corePages = array(
                1 => 'index',
                2 => 'about_us',
                3 => 'tours',
                4 => 'experiences',
                5 => 'plan_your_trip',
                6 => 'faqs',
                7 => 'contact',
                8 => 'blog',
                9 => 'privacy_policy',
                10 => 'cancellation_policy',
                11 => 'tour_guides',
            );
            $pageId = (int) $page['page_id'];

            if (isset($corePages[$pageId])) {
                return $this->{$corePages[$pageId]}();
            }

            return $this->renderManagedPage($page, $locale);
        }

        // This utility page is not a record in the Web Pages CMS.
        $utilityPages = array(
            'book' => 'book',
        );
        if (isset($utilityPages[$slug])) {
            return $this->{$utilityPages[$slug]}();
        }

        return $this->error_404();
    }

    /**
     * Whether visitors should see the holding page instead of the site:
     * Website Settings > Website Under Construction is "Yes" and the visitor
     * is not a signed-in administrator, who keeps browsing the real site.
     */
    private function isUnderConstruction(array $settings)
    {
        return isset($settings['under_construction'])
            && $settings['under_construction'] === 'Yes'
            && (string) $this->session->userdata('admin_auth') !== 'allow';
    }

    /**
     * Serve the holding page for every public URL. 503 with Retry-After tells
     * search engines the outage is temporary, so pages already indexed are
     * kept rather than dropped. Runs from the constructor, before the router
     * dispatches, so it must flush and halt here to avoid a double render.
     */
    private function renderUnderConstruction()
    {
        $this->output->set_status_header(503);
        $this->output->set_header('Retry-After: 3600');
        $this->output->set_header('Cache-Control: no-store');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');
        $this->load->view('frontend/under_construction');
        $this->output->_display();
        exit;
    }

    /** The public, bilingual 404 page — also the target of $route['404_override']. */
    public function error_404()
    {
        $this->output->set_status_header(404);
        $this->render('error_404', $this->buildErrorViewData());
    }

    /* The admin-styled 404 page for unmatched /manage/* routes. Reuses the
       same branded, sidebar-less shell as the login screen (admin/header
       and admin/footer with $loginSection set) since the visitor is not
       guaranteed to be signed in. This runs from the constructor before
       the router dispatches to this class's routed method, so it must
       flush and halt here to avoid a double render. */
    private function renderManageNotFound()
    {
        $this->controller = 'errors';
        $this->SqlModel->setTitle();
        $this->output->set_status_header(404);
        $this->load->view('admin/header', array(
            'loginSection' => 1,
            'page_title' => PROJECT_TITLE . ' | Page Not Found',
        ));
        $this->load->view('admin/error404', array(
            'isLoggedIn' => (string) $this->session->userdata('admin_auth') === 'allow',
        ));
        $this->load->view('admin/footer');
        $this->output->_display();
        exit;
    }

    public function about_us()
    {
        $this->render('about_us', $this->buildAboutViewData());
    }

    public function tours()
    {
        $this->render('tours', $this->buildToursViewData());
    }

    public function experiences()
    {
        $this->render('experiences', $this->buildExperiencesViewData());
    }

    public function tour_guides()
    {
        $this->render('tour_guides', $this->buildTourGuidesViewData());
    }

    /** Shared Tour/Experience detail page, reached via TOUR_URI/EXPERIENCE_URI. */
    public function tour_experience($locale = 'en', $slug = '')
    {
        $slug = trim(urldecode((string) $slug));
        $data = $this->buildTourExperienceViewData($slug);
        if ($data === null) {
            return $this->error_404();
        }

        /* Canonical and hreflang URLs are built from the record's own managed
           slugs, not from whichever slug this request happened to use. */
        if (!defined('FRONTEND_TOUR_SLUG')) {
            define('FRONTEND_TOUR_SLUG', $data['slugs']['en']);
        }
        if (!defined('FRONTEND_TOUR_SLUG_AR')) {
            define('FRONTEND_TOUR_SLUG_AR', $data['slugs']['ar']);
        }
        if (!defined('FRONTEND_TOUR_TYPE')) {
            define('FRONTEND_TOUR_TYPE', $data['isExperience'] ? 'Experience' : 'Tour');
        }

        $this->render('tour_details', $data);
    }

    public function plan_your_trip()
    {
        if (strtoupper($this->input->method()) === 'POST') {
            $this->processPlanFinalSubmit();
            return;
        }

        $this->render('plan_your_trip', $this->buildPlanYourTripViewData());
    }

    /** AJAX Continue endpoint for wizard steps 1-3. Always responds with JSON. */
    public function save_plan_your_trip_step()
    {
        $this->output->set_content_type('application/json');

        if (strtoupper($this->input->method()) !== 'POST') {
            return $this->respondPlanJson(405, array(
                'success' => false, 'errors' => array(), 'message' => $this->frontendLine('plan.error.saveFailed'),
            ));
        }

        if (!$this->consumePlanFormToken()) {
            return $this->respondPlanJson(403, array(
                'success' => false,
                'expired' => true,
                'errors' => array(),
                'message' => $this->frontendLine('plan.error.security'),
            ));
        }

        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $step = (int) $this->input->post('step');
        if ($step < 1 || $step > 3) {
            return $this->respondPlanJson(422, array(
                'success' => false, 'errors' => array(), 'message' => $this->frontendLine('plan.error.summary'),
            ));
        }

        $this->load->model('Plan_your_visit_model');
        $this->load->model('Contact_model');

        $token = $this->planDraftToken();
        $draft = $token !== '' ? $this->Plan_your_visit_model->find_by_session_token($token) : null;
        if ($token !== '' && ($draft === null || (int) $draft['step_completed'] >= 4)) {
            $this->clearPlanDraftToken();
            $token = '';
            $draft = null;
        }

        $currentStep = $draft !== null ? (int) $draft['step_completed'] : 0;
        if ($step > $currentStep + 1) {
            return $this->respondPlanJson(409, array(
                'success' => false, 'expired' => true, 'errors' => array(),
                'message' => $this->frontendLine('plan.error.expired'),
            ));
        }

        if ($step === 1) {
            return $this->respondPlanStep1($token, $draft, $locale);
        }
        if ($step === 2) {
            return $this->respondPlanStep2($token);
        }

        return $this->respondPlanStep3($token);
    }

    public function faqs()
    {
        $this->render('faqs', $this->buildFaqsViewData());
    }

    public function contact()
    {
        if (strtoupper($this->input->method()) === 'POST') {
            $this->processContactSubmission();
            return;
        }

        $this->render('contact', $this->buildContactViewData());
    }

    public function blog()
    {
        $this->render('blog', $this->buildBlogViewData());
    }

    public function blog_category($locale = 'en', $slug = '')
    {
        $slug = trim(urldecode((string) $slug));
        if ($slug === '') {
            return $this->error_404();
        }

        $data = $this->buildBlogViewData($slug);
        if ($data === null) {
            return $this->error_404();
        }

        if (!defined('FRONTEND_BLOG_CATEGORY_SLUG')) {
            define('FRONTEND_BLOG_CATEGORY_SLUG', $slug);
        }

        $this->render('blog', $data);
    }

    public function blog_post($locale = 'en', $slug = '')
    {
        $slug = trim(urldecode((string) $slug));
        $data = $this->buildBlogPostViewData($slug);
        if ($data === null) {
            return $this->error_404();
        }

        if (!defined('FRONTEND_BLOG_POST_SLUG')) {
            define('FRONTEND_BLOG_POST_SLUG', $data['slugs']['en']);
        }
        if (!defined('FRONTEND_BLOG_POST_SLUG_AR')) {
            define('FRONTEND_BLOG_POST_SLUG_AR', $data['slugs']['ar']);
        }

        $this->render('blog_post', $data);
    }

    public function book()
    {
        if (strtoupper($this->input->method()) === 'POST') {
            $this->saveBooking();
            return;
        }
        $viewData = $this->buildBookingViewData();
        $tokens = (array) $this->session->userdata('booking_tokens');
        $token = bin2hex(random_bytes(32));
        $tokens[$token] = $this->input->get('i');
        $this->session->set_userdata('booking_tokens', array_slice($tokens, -10, null, true));
        $viewData['bookingData']['submissionToken'] = $token;
        $this->render('book', $viewData);
    }

    public function review($locale = 'en', $bookingId = 0, $token = '')
    {
        $bookingId = (int) $bookingId;
        $this->load->model('Tour_review_model');
        $booking = $this->Tour_review_model->booking($bookingId, $locale);

        if (!$booking || !$this->Tour_review_model->validToken($booking, $token)) {
            return $this->error_404();
        }

        if (!defined('FRONTEND_REVIEW_BOOKING_ID')) {
            define('FRONTEND_REVIEW_BOOKING_ID', $bookingId);
        }
        if (!defined('FRONTEND_REVIEW_TOKEN')) {
            define('FRONTEND_REVIEW_TOKEN', $token);
        }

        $review = $this->Tour_review_model->reviewForBooking($bookingId);
        $state = !empty($review)
            ? 'submitted'
            : ($this->Tour_review_model->isEligible($booking) ? 'form' : 'unavailable');
        $values = $this->reviewFormValues();
        $errors = array();

        if (strtoupper($this->input->method()) === 'POST' && $state === 'form') {
            $errors = $this->reviewValidationErrors($values, $booking);
            if (empty($errors) && !$this->verifyReviewRecaptcha()) {
                $errors['form'] = $this->frontendLine('review.error.recaptcha');
            }
            if (empty($errors)) {
                $this->db->trans_begin();
                $lockedBooking = $this->db->query(
                    'SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE',
                    array($bookingId)
                )->row_array();
                $alreadySubmitted = $this->Tour_review_model->reviewForBooking($bookingId);
                $valid = $lockedBooking
                    && $lockedBooking['book_status'] === 'Completed'
                    && $this->Tour_review_model->isEligible(array_merge($booking, $lockedBooking))
                    && empty($alreadySubmitted);
                $saved = $valid
                    ? $this->Tour_review_model->save($booking, $values)
                    : false;

                if ($saved && $this->db->trans_status()) {
                    $this->db->trans_commit();
                    $state = 'success';
                    // After the commit, and never allowed to affect the customer's confirmation.
                    $this->sendReviewAdminNotification(
                        $booking,
                        $this->Tour_review_model->reviewForBooking($bookingId)
                    );
                } else {
                    $this->db->trans_rollback();
                    $review = $this->Tour_review_model->reviewForBooking($bookingId);
                    if (!empty($review)) {
                        $state = 'submitted';
                    } else {
                        $errors['form'] = $this->frontendLine('review.error.save');
                    }
                }
            }
        }

        $this->output->set_header('Cache-Control: no-store, private');
        $this->render('review', array(
            'booking' => $booking,
            'review' => $review,
            'reviewState' => $state,
            'reviewValues' => $values,
            'reviewErrors' => $errors,
            'reviewToken' => $token,
            'recaptchaSiteKey' => RECAPTCHA_ENTERPRISE_SITE_KEY,
            'recaptchaAction' => self::REVIEW_RECAPTCHA_ACTION,
        ));
    }

    /** Verify the one-use reCAPTCHA Enterprise token before a customer review is saved. */
    private function verifyReviewRecaptcha()
    {
        $token = trim((string) $this->input->post('recaptcha_token'));
        if ($token === '') {
            return false;
        }

        $this->load->library('google_recaptcha');
        $result = $this->google_recaptcha->create_assessment(
            $token,
            self::REVIEW_RECAPTCHA_ACTION,
            $this->input->ip_address(),
            $this->safeUserAgent()
        );

        if (empty($result['success'])) {
            log_message(
                'error',
                'Review reCAPTCHA Enterprise verification failed: '
                . (isset($result['message']) ? $result['message'] : 'Unknown error.')
            );
            return false;
        }

        $score = isset($result['score']) ? (float) $result['score'] : 0.0;
        if ($score < self::REVIEW_RECAPTCHA_MIN_SCORE) {
            log_message('info', 'Review reCAPTCHA Enterprise rejected a score of ' . $score . '.');
            return false;
        }

        return true;
    }

    /** Tell the configured administrators that a customer has submitted a review. */
    private function sendReviewAdminNotification(array $booking, array $review)
    {
        if (empty($review)) {
            log_message('error', 'Review admin email was not sent because the saved review could not be loaded.');
            return false;
        }

        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        if (empty($settings)) {
            log_message('error', 'Review admin email was not sent because site settings are missing.');
            return false;
        }

        $recipients = $this->contactNotificationRecipients(
            isset($settings['notification_emails']) ? $settings['notification_emails'] : ''
        );
        if (empty($recipients)) {
            log_message('error', 'Review admin email was not sent because no valid notification emails are configured.');
            return false;
        }

        $reviewId = (int) $review['tour_review_id'];
        $isTour = $review['tour_review_tour_type'] === 'Tour';
        $name = trim(preg_replace('/[\r\n]+/', ' ', (string) $review['tour_review_customer_name']));
        $tourName = trim(preg_replace('/[\r\n]+/', ' ', (string) $review['tour_review_tour_name']));
        $subject = 'New Customer Review: ' . $tourName . ' (' . (int) $review['tour_review_overall_rating'] . '/5)';
        $intro = 'A customer has submitted a review for a completed ' . ($isTour ? 'tour' : 'experience') . '.';

        $activityLabel = $isTour ? 'Tour' : 'Experience';
        $ratings = array(
            'Overall' => $review['tour_review_overall_rating'],
            $activityLabel => $review['tour_review_activity_rating'],
        );
        if ($isTour) {
            $ratings['Guide'] = $review['tour_review_guide_rating'];
        }
        $ratings['Vehicle'] = $review['tour_review_vehicle_rating'];
        $ratings['Driver'] = $review['tour_review_driver_rating'];
        $ratings['Service'] = $review['tour_review_service_rating'];
        foreach ($ratings as $label => $rating) {
            $ratings[$label] = (int) $rating . ' / 5';
        }

        $comments = trim((string) $review['tour_review_comments']);
        $sections = array(
            'Review' => array(
                'Review ID' => $reviewId,
                'Submitted' => $this->contactEmailDateTime($review['tour_review_added']),
                'Website' => $review['tour_review_locale'] === 'ar' ? 'Arabic' : 'English',
                'Agreed to Public Display' => !empty($review['tour_review_public_consent']) ? 'Yes' : 'No',
            ),
            'Ratings' => $ratings,
            'Booking' => array(
                'Booking ID' => (int) $booking['book_id'],
                'Reference' => $this->bookingEmailValue($booking['book_res_code']),
                'Customer' => $this->bookingEmailValue($review['tour_review_customer_name']),
                'Email' => $this->bookingEmailValue($review['tour_review_customer_email']),
                'Phone' => $this->bookingEmailValue($booking['book_phone']),
                'Tour / Experience' => $this->bookingEmailValue($review['tour_review_tour_name']),
                'Tour Date' => $this->bookingEmailDate($booking['book_date']),
                'Visiting Time' => $this->bookingEmailValue($booking['book_slot_name']),
                'Vehicle' => $this->bookingEmailValue($booking['book_vehicle_name']),
            ),
        );
        if ($isTour) {
            $sections['Booking']['Tour Guide'] = $this->bookingEmailValue($review['tour_review_guide_name']);
        }

        $body = '<p>' . $this->escapeEmailValue($intro) . '</p>';
        $textLines = array($intro, '');
        foreach ($sections as $section => $details) {
            $body .= '<h3>' . $this->escapeEmailValue($section) . '</h3>'
                . '<table cellpadding="0" cellspacing="0" style="margin:12px 0 20px">';
            $textLines[] = $section;
            foreach ($details as $label => $value) {
                $body .= $this->contactNotificationRow($label, $value);
                $textLines[] = $label . ': ' . $value;
            }
            $body .= '</table>';
            $textLines[] = '';
        }

        $body .= '<h3>Comments</h3><p>'
            . ($comments !== ''
                ? nl2br($this->escapeEmailValue($comments), false)
                : 'No comments provided.')
            . '</p>';
        $textLines[] = 'Comments';
        $textLines[] = $comments !== '' ? $comments : 'No comments provided.';
        $textLines[] = '';

        $manageUrl = base_url('manage/tour-reviews/control/view/' . $reviewId);
        $body .= '<p><a href="' . $this->escapeEmailValue($manageUrl) . '">View this review in Manage</a></p>';
        $textLines[] = 'View review: ' . $manageUrl;

        $this->load->library('EmailService');
        $message = $this->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => $subject,
            'body' => $body,
        ), 'English');
        if ($message === false) {
            log_message('error', 'Review admin email template could not be rendered for review ID ' . $reviewId . '.');
            return false;
        }

        $replyTo = filter_var($review['tour_review_customer_email'], FILTER_VALIDATE_EMAIL) !== false
            ? $review['tour_review_customer_email']
            : '';
        $sent = $this->emailservice->send(array(
            'to' => $recipients,
            'reply_to' => $replyTo,
            'reply_to_name' => $name,
            'subject' => $subject,
            'message' => $message,
            'alt_message' => implode("\n", $textLines),
            'config' => array(
                'useragent' => trim((string) $settings['website_title']),
            ),
        ));

        if (!$sent) {
            log_message(
                'error',
                'Review admin email failed for review ID ' . $reviewId . ': ' . $this->emailservice->getLastError()
            );
        }

        return $sent;
    }

    private function reviewFormValues()
    {
        $values = array();
        foreach (array(
            'overall_rating',
            'activity_rating',
            'guide_rating',
            'vehicle_rating',
            'driver_rating',
            'service_rating',
        ) as $field) {
            $raw = $this->input->post($field);
            $values[$field] = is_string($raw) ? trim($raw) : '';
        }
        $comments = $this->input->post('comments');
        $values['comments'] = is_string($comments) ? trim($comments) : '';
        $values['public_consent'] = $this->input->post('public_consent') === '1';
        $values['locale'] = defined('FRONTEND_LOCALE') ? FRONTEND_LOCALE : 'en';

        return $values;
    }

    private function reviewValidationErrors(array $values, array $booking)
    {
        $errors = array();
        $requiredRatings = array(
            'overall_rating',
            'activity_rating',
            'vehicle_rating',
            'driver_rating',
            'service_rating',
        );
        if (trim((string) $booking['tour_type']) === 'Tour') {
            $requiredRatings[] = 'guide_rating';
        }

        foreach ($requiredRatings as $field) {
            if (!ctype_digit((string) $values[$field])
                || (int) $values[$field] < 1
                || (int) $values[$field] > 5) {
                $errors[$field] = $this->frontendLine('review.error.rating');
            }
        }
        if (mb_strlen($values['comments']) > 5000) {
            $errors['comments'] = $this->frontendLine('review.error.comments');
        }

        return $errors;
    }

    /** Shared by saveBooking() and booking_discount(): the token must still map to the tour it was issued for. */
    private function bookingTokenValid($token, $postedTourValue)
    {
        $tokens = (array) $this->session->userdata('booking_tokens');
        $this->load->library('encryption');
        $expectedTour = isset($tokens[$token]) ? $this->encryption->decrypt($tokens[$token]) : false;
        $postedTour = is_string($postedTourValue) && strlen($postedTourValue) < 2048
            ? $this->encryption->decrypt($postedTourValue) : false;
        return $expectedTour && $postedTour && hash_equals($expectedTour, $postedTour);
    }

    private function saveBooking()
    {
        require_once APPPATH . 'views/frontend/functions.php';
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');
        $values = array();
        foreach (array('booking_token', 'tour_id', 'vehicle_id', 'slot_id', 'guide_id', 'language',
            'guests', 'tour_date', 'full_name', 'email', 'country', 'mobile', 'pickup_location',
            'pickup_place_token', 'notes', 'terms', 'whatsapp_consent', 'booking_step', 'booking_id') as $field) {
            $value = $this->input->post($field);
            $values[$field] = is_string($value) ? trim($value) : '';
        }
        if (!$this->bookingTokenValid($values['booking_token'], $values['tour_id'])) {
            $result = array('success' => false, 'error' => 'expired');
            $this->output->set_status_header(403);
        } elseif (mb_strlen($values['full_name']) < 2 || mb_strlen($values['full_name']) > 255
            || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 255
            || mb_strlen($values['pickup_location']) < 2 || mb_strlen($values['pickup_location']) > 255
            || !in_array($values['country'], countries(), true)
            || !preg_match('/^\+[1-9][0-9]{6,14}$/D', $values['mobile'])
            || mb_strlen($values['notes']) > 5000
            || !in_array($values['booking_step'], array('1', '2', '3', '4', '5', '6'), true)
            || (in_array($values['booking_step'], array('5', '6'), true) && $values['terms'] !== '1')) {
            $result = array('success' => false, 'error' => 'details');
            $this->output->set_status_header(422);
        } elseif (isset(self::BOOKING_RECAPTCHA_ACTIONS[$values['booking_step']])
            && !$this->verifyBookingRecaptcha(self::BOOKING_RECAPTCHA_ACTIONS[$values['booking_step']])) {
            $result = array('success' => false, 'error' => 'recaptcha');
            $this->output->set_status_header(403);
        } else {
            $this->load->model('Booking_model');
            $result = $this->Booking_model->save($values, FRONTEND_LOCALE, business()['currency']);
            if (!$result['success']) {
                $this->output->set_status_header($result['error'] === 'unavailable' ? 503 : 422);
            } elseif (!empty($result['notification_stage']) && !empty($result['notification_booking_id'])) {
                $this->sendBookingAdminNotification(
                    (int) $result['notification_booking_id'],
                    $result['notification_stage']
                );
                if ($result['notification_stage'] === 'completed') {
                    $this->sendBookingCustomerConfirmation(
                        (int) $result['notification_booking_id']
                    );
                    $this->sendBookingWhatsappConfirmation(
                        (int) $result['notification_booking_id']
                    );
                    $this->sendBookingTourGuideAssignment(
                        (int) $result['notification_booking_id']
                    );
                }
            }
        }
        unset($result['notification_stage'], $result['notification_booking_id']);
        $this->output->set_output(json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    /** Verify the one-use reCAPTCHA Enterprise token for a booking step that creates or completes the booking. */
    private function verifyBookingRecaptcha($action)
    {
        $token = trim((string) $this->input->post('recaptcha_token'));
        if ($token === '') {
            return false;
        }

        $this->load->library('google_recaptcha');
        $result = $this->google_recaptcha->create_assessment(
            $token,
            $action,
            $this->input->ip_address(),
            $this->safeUserAgent()
        );

        if (empty($result['success'])) {
            log_message(
                'error',
                'Booking reCAPTCHA Enterprise verification failed: '
                . (isset($result['message']) ? $result['message'] : 'Unknown error.')
            );
            return false;
        }

        $score = isset($result['score']) ? (float) $result['score'] : 0.0;
        if ($score < self::BOOKING_RECAPTCHA_MIN_SCORE) {
            log_message('info', 'Booking reCAPTCHA Enterprise rejected a score of ' . $score . '.');
            return false;
        }

        return true;
    }

    /** Send the first-step or completed booking snapshot to the configured administrators. */
    protected function sendBookingAdminNotification($bookingId, $stage)
    {
        $bookingId = (int) $bookingId;
        if ($bookingId <= 0 || !in_array($stage, array('step1', 'completed'), true)) {
            return false;
        }

        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        if (empty($settings)) {
            log_message('error', 'Booking admin email was not sent because site settings are missing.');
            return false;
        }

        $recipients = $this->contactNotificationRecipients(
            isset($settings['notification_emails']) ? $settings['notification_emails'] : ''
        );
        if (empty($recipients)) {
            log_message('error', 'Booking admin email was not sent because no valid notification emails are configured.');
            return false;
        }

        $booking = $this->bookingAdminNotificationRecord($bookingId);
        if (empty($booking)) {
            log_message('error', 'Booking admin email could not load booking ID '.$bookingId.'.');
            return false;
        }

        $completed = $stage === 'completed';
        $isTour = trim((string) $booking['tour_type']) === 'Tour';
        $totalSteps = $isTour ? 6 : 5;
        $name = trim(preg_replace('/[\r\n]+/', ' ', (string) $booking['book_name']));
        $reference = trim((string) $booking['book_res_code']);
        $subject = $completed
            ? 'Completed Booking '.$reference.': '.$name
            : 'New Booking Started (Step 1): '.$name;
        $intro = $completed
            ? 'A customer has completed payment for a booking.'
            : 'A customer has completed step 1 of the booking form.';

        $sections = array(
            'Booking' => array(
                'Booking ID' => $bookingId,
                'Reference' => $this->bookingEmailValue($reference),
                'Status' => $this->bookingEmailValue($booking['book_status']),
                'Steps Completed' => (int) $booking['steps_completed'].' of '.$totalSteps,
                'Tour Type' => $this->bookingEmailValue($booking['tour_type']),
                'Website' => $this->bookingEmailValue($booking['book_lang']),
                'Created' => $this->contactEmailDateTime($booking['book_added']),
            ),
            'Customer' => array(
                'Name' => $this->bookingEmailValue($booking['book_name']),
                'Email' => $this->bookingEmailValue($booking['book_email']),
                'Phone' => $this->bookingEmailValue($booking['book_phone']),
                'Country' => $this->bookingEmailValue($booking['book_country_name']),
                'Guests' => (int) $booking['book_guests'],
                'Pickup / Address' => $this->bookingEmailValue($booking['book_address']),
            ),
            'Tour / Experience' => array(
                'Tour / Experience' => $this->bookingEmailValue($booking['book_tour_name']),
            ),
        );

        if ($isTour) {
            $sections['Tour Guide'] = $completed
                ? array(
                    'Guide Name' => $this->bookingEmailValue($booking['book_tour_guide_name']),
                    'Guide Title' => $this->bookingEmailValue($booking['guide_title']),
                    'Guide Email' => $this->bookingEmailValue($booking['guide_email']),
                    'Guide Phone' => $this->bookingEmailValue($booking['guide_phone']),
                    'Guide License Number' => $this->bookingEmailValue($booking['guide_license_number']),
                )
                : array('Guide' => 'Not selected yet');
        }

        if ($completed) {
            $currency = trim((string) $booking['book_currency']);
            $currency = $currency !== '' ? $currency : 'SAR';
            $discountAmount = (int) $booking['book_discount_amount'];
            $taxAmount = (int) $booking['book_tax_amount'];
            $profitAmount = (int) $booking['book_profit_amount'];
            $originalTotal = (int) $booking['book_original_total'];
            if ($originalTotal <= 0) {
                $originalTotal = (int) $booking['book_fee'] - $taxAmount + $discountAmount;
            }

            $sections['Customer']['Customer Comments'] = $this->bookingEmailValue(
                $booking['book_notes'],
                'No comments provided.'
            );
            $sections['Tour / Experience'] += array(
                'Tour Date' => $this->bookingEmailDate($booking['book_date']),
                'Visiting Time' => $this->bookingEmailValue($booking['book_slot_name']),
                'Duration' => (int) $booking['book_slot_hours'].' hours',
                'Start Time' => $this->bookingEmailTime($booking['book_slot_start_time']),
                'End Time' => $this->bookingEmailTime($booking['book_slot_end_time']),
                'Vehicle' => $this->bookingEmailValue($booking['book_vehicle_name']),
                'Preferred Language' => $this->bookingEmailValue($booking['book_lang_name']),
            );

            // book_tour_total is empty for bookings made while the tour price was a flat amount.
            if ($booking['book_tour_total'] !== null) {
                $priceBreakdown = array(
                    'Tour Price Per Guest' => $this->bookingEmailMoney($booking['book_tour_price'], $currency),
                    'Tour Total' => $this->bookingEmailMoney($booking['book_tour_total'], $currency),
                );
            } else {
                $priceBreakdown = array(
                    'Tour Price' => $this->bookingEmailMoney($booking['book_tour_price'], $currency),
                );
            }
            if ($isTour) {
                $priceBreakdown['Guide Price'] = $this->bookingEmailMoney(
                    $booking['book_guide_price'],
                    $currency
                );
            }
            $priceBreakdown += array(
                'Vehicle Price' => $this->bookingEmailMoney($booking['book_vehicle_price'], $currency),
                'Meals Per Guest' => $this->bookingEmailMoney($booking['book_meals_per_guest'], $currency),
                'Meals Total' => $this->bookingEmailMoney($booking['book_meals_total'], $currency),
                'Subtotal' => $this->bookingEmailMoney($originalTotal - $profitAmount, $currency),
                'Profit ('.$this->bookingEmailPercent($booking['book_profit_percent']).')'
                    => $this->bookingEmailMoney($profitAmount, $currency),
                'Original Total' => $this->bookingEmailMoney($originalTotal, $currency),
                'Discount Amount' => $this->bookingEmailMoney($discountAmount, $currency),
                'Tax ('.$this->bookingEmailPercent($booking['book_tax_percent']).')'
                    => $this->bookingEmailMoney($taxAmount, $currency),
                'Total Payable' => $this->bookingEmailMoney($booking['book_fee'], $currency),
                'Total Paid' => $this->bookingEmailMoney($booking['book_paid_amount'], $currency),
            );
            $sections['Price Breakdown'] = $priceBreakdown;

            if (trim((string) $booking['book_promo_code']) !== '' || $discountAmount > 0) {
                $discountValue = trim((string) $booking['book_discount_type']) === 'Percentage'
                    ? number_format((int) $booking['book_discount_value']).'%'
                    : $this->bookingEmailMoney($booking['book_discount_value'], $currency);
                $sections['Promotion / Referral'] = array(
                    'Discount Code' => $this->bookingEmailValue($booking['book_promo_code']),
                    'Discount Name' => $this->bookingEmailValue($booking['book_discount_name']),
                    'Discount Type' => $this->bookingEmailValue($booking['book_discount_type']),
                    'Discount Value' => $discountValue,
                    'Discount Amount' => $this->bookingEmailMoney($discountAmount, $currency),
                );
                if (trim((string) $booking['book_ref_name']) !== '') {
                    $sections['Promotion / Referral'] += array(
                        'Referral' => $booking['book_ref_name'],
                        'Referral Commission' => $this->bookingEmailMoney(
                            $booking['book_ref_commission'],
                            $currency
                        ),
                    );
                }
            }

            $sections['Payment'] = array(
                'Payment Method' => $this->bookingEmailValue($booking['book_payment_method']),
                'Payer Email' => $this->bookingEmailValue($booking['book_payer_email']),
                'Transaction ID' => $this->bookingEmailValue($booking['book_transaction_id']),
                'Payment Date' => $this->contactEmailDateTime($booking['book_payment_date']),
            );
        }

        $body = '<p>'.$this->escapeEmailValue($intro).'</p>';
        $textLines = array($intro, '');
        foreach ($sections as $section => $details) {
            $body .= '<h3>'.$this->escapeEmailValue($section).'</h3>'.
                '<table cellpadding="0" cellspacing="0" style="margin:12px 0 20px">';
            $textLines[] = $section;
            foreach ($details as $label => $value) {
                $body .= $this->contactNotificationRow($label, $value);
                $textLines[] = $label.': '.$value;
            }
            $body .= '</table>';
            $textLines[] = '';
        }

        $manageUrl = base_url('manage/bookings/control/view/'.$bookingId);
        $body .= '<p><a href="'.$this->escapeEmailValue($manageUrl).'">View this booking in Manage</a></p>';
        $textLines[] = 'View booking: '.$manageUrl;

        $this->load->library('EmailService');
        $message = $this->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => $subject,
            'body' => $body,
        ), 'English');
        if ($message === false) {
            log_message('error', 'Booking admin email template could not be rendered for booking ID '.$bookingId.'.');
            return false;
        }

        $replyTo = filter_var($booking['book_email'], FILTER_VALIDATE_EMAIL) !== false
            ? $booking['book_email']
            : '';
        $sent = $this->emailservice->send(array(
            'to' => $recipients,
            'reply_to' => $replyTo,
            'reply_to_name' => $name,
            'subject' => $subject,
            'message' => $message,
            'alt_message' => implode("\n", $textLines),
            'config' => array(
                'useragent' => trim((string) $settings['website_title']),
            ),
        ));

        if (!$sent) {
            log_message(
                'error',
                'Booking admin email failed for booking ID '.$bookingId.': '.$this->emailservice->getLastError()
            );
        }

        return $sent;
    }

    /** Load the committed booking snapshot plus the current type and assigned-guide contact details. */
    private function bookingAdminNotificationRecord($bookingId)
    {
        return $this->db
            ->select(
                'bookings.*, tours.tour_type, guides.tour_guide_title AS guide_title, '.
                'guides.tour_guide_email AS guide_email, guides.tour_guide_phone AS guide_phone, '.
                'guides.tour_guide_license_number AS guide_license_number'
            )
            ->from('tour_bookings bookings')
            ->join('tours', 'tours.tour_id = bookings.book_tour_id', 'left')
            ->join('tour_guides guides', 'guides.tour_guide_id = bookings.book_tour_guide_id', 'left')
            ->where('bookings.book_id', (int) $bookingId)
            ->get()
            ->row_array();
    }

    private function bookingEmailValue($value, $fallback = 'Not provided')
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    private function bookingEmailMoney($amount, $currency)
    {
        return $this->bookingEmailValue($currency, 'SAR').' '.number_format((int) $amount);
    }

    /** "15%" or "12.5%": a saved percentage without trailing zeros. */
    private function bookingEmailPercent($percent)
    {
        return rtrim(rtrim(number_format((float) $percent, 2, '.', ''), '0'), '.').'%';
    }

    private function bookingEmailDate($value)
    {
        $timestamp = strtotime((string) $value);

        return $timestamp === false ? $this->bookingEmailValue($value) : date(EMAIL_DATE_FORMAT, $timestamp);
    }

    private function bookingEmailTime($value)
    {
        $timestamp = strtotime((string) $value);

        return $timestamp === false ? $this->bookingEmailValue($value) : date(EMAIL_TIME_FORMAT, $timestamp);
    }

    /** Send the paid-booking confirmation using template 3 for tours or 4 for experiences. */
    protected function sendBookingCustomerConfirmation($bookingId)
    {
        $this->load->library('Booking_email_service');

        return $this->booking_email_service->sendCustomerConfirmation($bookingId);
    }

    /** WhatsApp the booking confirmation when the customer opted in on the Review step. */
    protected function sendBookingWhatsappConfirmation($bookingId)
    {
        $this->load->library('Booking_whatsapp_service');

        return $this->booking_whatsapp_service->sendCustomerConfirmation($bookingId);
    }

    /** Tell the tour guide about the newly completed booking (template 10, Tours only). */
    protected function sendBookingTourGuideAssignment($bookingId)
    {
        $this->load->library('Booking_email_service');

        return $this->booking_email_service->sendTourGuideAssignment($bookingId);
    }

    /**
     * Apply or remove a promo code on the Pending booking the token owns.
     * Deliberately outside saveBooking()'s step flow: it never submits the
     * booking, never requires terms, never advances the wizard and never
     * marks Review complete — see Booking_model::applyDiscountCode() /
     * removeDiscountCode(). Also the recovery path once Review has been
     * accepted: applying/removing here always reopens it.
     */
    public function booking_discount()
    {
        require_once APPPATH . 'views/frontend/functions.php';
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');
        if (strtoupper($this->input->method()) !== 'POST') {
            $this->output->set_status_header(405);
            $this->output->set_header('Allow: POST');
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'invalid')));
            return;
        }
        $postString = function ($field) {
            $value = $this->input->post($field);
            return is_string($value) ? $value : '';
        };
        $token = trim($postString('booking_token'));
        $tourId = trim($postString('tour_id'));
        $bookingIdPosted = trim($postString('booking_id'));
        $action = trim($postString('discount_action'));
        $code = $postString('discount_code');
        if (!$this->bookingTokenValid($token, $tourId)) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'expired')));
            return;
        }
        if (!in_array($action, array('apply', 'remove'), true)
            || ($action === 'apply' && (trim($code) === '' || mb_strlen($code) > 40))) {
            $this->output->set_status_header(422);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'invalid')));
            return;
        }
        $this->load->model('Booking_model');
        $result = $action === 'apply'
            ? $this->Booking_model->applyDiscountCode($token, $bookingIdPosted, $code)
            : $this->Booking_model->removeDiscountCode($token, $bookingIdPosted);
        if (!$result['success']) {
            $this->output->set_status_header('unavailable' === $result['error'] ? 503 : 422);
            $this->output->set_output(json_encode(array('success' => false, 'error' => $result['error']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
            return;
        }
        // Referral identity and commission figures stay server-side; only customer-facing pricing is returned.
        $this->output->set_output(json_encode(array(
            'success' => true,
            'action' => $action,
            'code' => $result['code'],
            'name' => FRONTEND_LOCALE === 'ar' && !empty($result['name_ar']) ? $result['name_ar'] : $result['name'],
            'type' => $result['type'],
            'value' => $result['value'],
            'original_total' => $result['original_total'],
            'discount_amount' => $result['discount_amount'],
            'total' => $result['total'],
            'incomplete' => $result['incomplete'],
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    /**
     * Pickup-location suggestions for the booking form, limited to Madinah and
     * written in the site language. Only a visitor holding a booking token issued
     * by the /book page can call it, because every request is billed by Google.
     */
    public function booking_places()
    {
        if (!$this->beginBookingPlaceRequest()) {
            return;
        }

        $this->load->library('google_places_service');
        $result = $this->google_places_service->autocomplete(
            $this->bookingPlacePost('q'),
            FRONTEND_LOCALE,
            $this->bookingPlacePost('s')
        );

        if (empty($result['success'])) {
            $this->respondBookingPlaceFailure($result);
            return;
        }

        $this->output->set_output(json_encode(array(
            'success' => true,
            'suggestions' => $result['suggestions'],
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    /**
     * Verifies the suggestion the visitor picked with Google and returns an
     * encrypted token for it. The form posts that token instead of raw place
     * fields, so Booking_model can trust what it stores.
     */
    public function booking_place()
    {
        if (!$this->beginBookingPlaceRequest()) {
            return;
        }

        $this->load->library('google_places_service');
        $result = $this->google_places_service->place_details(
            $this->bookingPlacePost('place_id'),
            FRONTEND_LOCALE,
            $this->bookingPlacePost('s')
        );

        if (empty($result['success'])) {
            $this->respondBookingPlaceFailure($result);
            return;
        }

        $this->load->model('Booking_model');
        $this->output->set_output(json_encode(array(
            'success' => true,
            'token' => $this->Booking_model->placeToken($result),
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    /**
     * Shared gate for the two place endpoints: JSON headers, POST only, a known
     * booking token and a per-session request limit. Writes the error response
     * itself and returns false when the request must stop.
     */
    private function beginBookingPlaceRequest()
    {
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');

        if (strtoupper($this->input->method()) !== 'POST') {
            $this->output->set_status_header(405);
            $this->output->set_header('Allow: POST');
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'invalid')));
            return false;
        }

        $token = $this->bookingPlacePost('booking_token');
        $tokens = (array) $this->session->userdata('booking_tokens');
        if ($token === '' || !isset($tokens[$token])) {
            $this->output->set_status_header(403);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'expired')));
            return false;
        }

        if (!$this->bookingPlaceWithinRateLimit()) {
            $this->output->set_status_header(429);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'rate_limited')));
            return false;
        }

        return true;
    }

    private function bookingPlacePost($field)
    {
        $value = $this->input->post($field);

        return is_string($value) ? trim($value) : '';
    }

    /** Fixed window kept in the visitor's session; it caps Google billing per visitor. */
    private function bookingPlaceWithinRateLimit()
    {
        $now = time();
        $window = (array) $this->session->userdata('booking_place_rate');
        $started = isset($window['started']) ? (int) $window['started'] : 0;
        $count = isset($window['count']) ? (int) $window['count'] : 0;

        if ($started <= 0 || $now - $started >= GOOGLE_PLACES_RATE_LIMIT_WINDOW_SECONDS) {
            $started = $now;
            $count = 0;
        }

        $count++;
        $this->session->set_userdata('booking_place_rate', array(
            'started' => $started,
            'count' => $count,
        ));

        return $count <= GOOGLE_PLACES_RATE_LIMIT_REQUESTS;
    }

    /** Google's own error text stays in the log; the visitor only learns to type the address. */
    private function respondBookingPlaceFailure(array $result)
    {
        $status = (int) $result['status_code'] === 422 ? 422 : 503;
        $this->output->set_status_header($status);
        $this->output->set_output(json_encode(array(
            'success' => false,
            'error' => $status === 422 ? 'invalid' : 'unavailable',
        )));
    }

    /** Public, localized picker data; database IDs never leave this endpoint. */
    public function booking_options()
    {
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');
        if (strtoupper($this->input->method()) !== 'GET') {
            $this->output->set_status_header(405);
            $this->output->set_header('Allow: GET');
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }

        $this->load->model('Tour_model');
        $options = array();
        foreach ($this->Tour_model->get_booking_tours(FRONTEND_LOCALE, 'Tour') as $row) {
            $detail = $this->Tour_model->get_by_slug($row['tour_slug'], FRONTEND_LOCALE);
            if (!$detail || trim($detail['tour_type']) !== 'Tour') {
                continue;
            }
            $tour = $this->prepareTourExperience(
                $detail,
                $row['tour_slug'],
                trim($detail['tour_type']) === 'Experience'
            );
            $options[] = array(
                'id' => $this->bookingReference('tour', $row['tour_id']),
                'title' => $tour['title'],
                'category' => $tour['category'],
                'summary' => html_entity_decode(strip_tags($tour['summary']), ENT_QUOTES, 'UTF-8'),
                'duration' => $tour['duration'],
                'image' => $tour['image'],
                'price' => (int) $tour['price'],
            );
        }
        $this->output->set_output(json_encode(array(
            'success' => true,
            'tours' => $options,
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    }

    /**
     * Homepage availability check. Answers whether the chosen Tour has a free
     * guide on the chosen date (and language, when given) with a vehicle that
     * serves it, so the visitor is only sent to /book with a workable choice.
     * Vehicles carry no per-date availability of their own: the vehicle must
     * be assigned to the Tour, enabled and able to carry guests.
     */
    public function booking_availability()
    {
        $this->output->set_content_type('application/json');
        $this->output->set_header('Cache-Control: no-store');
        if (strtoupper($this->input->method()) !== 'GET') {
            $this->output->set_status_header(405);
            $this->output->set_header('Allow: GET');
            $this->output->set_output(json_encode(array('success' => false)));
            return;
        }

        $tourId = $this->bookingReferenceId($this->input->get('i'), 'tour');
        $vehicleValue = $this->input->get('vehicle');
        $languageValue = $this->input->get('language');
        $vehicleId = $this->bookingReferenceId($vehicleValue, 'vehicle');
        $languageId = $this->bookingReferenceId($languageValue, 'language');
        $date = $this->input->get('date');

        // The date window is decided by the server clock, never the browser's.
        $minimumDate = date('Y-m-d', strtotime('tomorrow'));
        $maximumDate = date('Y-m-t', strtotime('+11 months', strtotime(date('Y-m-01'))));
        $validDate = is_string($date)
            && preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $date, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            && $date >= $minimumDate
            && $date <= $maximumDate;
        $vehicleGiven = is_string($vehicleValue) && $vehicleValue !== '';
        $languageGiven = is_string($languageValue) && $languageValue !== '';

        if ($tourId < 1
            || !$validDate
            || ($vehicleGiven && $vehicleId < 1)
            || ($languageGiven && $languageId < 1)
        ) {
            $this->output->set_status_header(422);
            $this->output->set_output(json_encode(array('success' => false, 'error' => 'invalid')));
            return;
        }

        $this->load->model('Tour_model');
        $available = $this->Tour_model->has_available_guide($tourId, $date, $languageId);
        if ($available && $vehicleGiven) {
            $available = false;
            foreach ($this->Tour_model->get_tour_vehicles($tourId, FRONTEND_LOCALE) as $vehicle) {
                if ((int) $vehicle['vehicle_id'] === $vehicleId
                    && $this->vehicleGuestCapacity($vehicle['vehicle_max_capacity'], false) >= 1
                ) {
                    $available = true;
                    break;
                }
            }
        }

        $this->output->set_output(json_encode(array('success' => true, 'available' => $available)));
    }

    /** Entity-scoped encrypted references prevent plain IDs and cross-entity substitution. */
    private function bookingReference($type, $id)
    {
        $this->load->library('encryption');

        return $this->encryption->encrypt($type . ':' . (int) $id);
    }

    /** Decode an entity-scoped booking reference back to its ID, or 0 when invalid. */
    private function bookingReferenceId($value, $type)
    {
        if (!is_string($value) || $value === '' || strlen($value) >= 2048) {
            return 0;
        }

        $this->load->library('encryption');
        $decoded = $this->encryption->decrypt($value);

        return is_string($decoded)
            && preg_match('/^' . $type . ':([1-9][0-9]*)$/D', $decoded, $match)
            ? (int) $match[1]
            : 0;
    }

    /**
     * Homepage availability search: the bookable Tours with the vehicles and
     * languages each one offers. Every value the form submits is an encrypted
     * booking reference, so the booking page accepts it without a lookup.
     */
    private function buildHeroSearchData($locale)
    {
        $this->load->model('Tour_model');

        $tours = array();
        foreach ($this->Tour_model->get_hero_search_options($locale) as $row) {
            $vehicles = array();
            foreach ($row['vehicles'] as $vehicle) {
                $capacity = $this->vehicleGuestCapacity(
                    $vehicle['vehicle_max_capacity'],
                    false
                );
                if ($capacity < 1) {
                    continue;
                }

                $vehicles[] = array(
                    'id' => $this->bookingReference('vehicle', $vehicle['vehicle_id']),
                    'name' => $vehicle['vehicle_name'],
                    'capacity' => $capacity,
                );
            }

            $languages = array();
            foreach ($row['languages'] as $language) {
                $languages[] = array(
                    'id' => $this->bookingReference('language', $language['id']),
                    'label' => $language['label'],
                );
            }

            $tours[] = array(
                'id' => $this->bookingReference('tour', $row['tour_id']),
                'title' => $row['tour_name'],
                'vehicles' => $vehicles,
                'languages' => $languages,
            );
        }

        return array(
            'tours' => $tours,
            'minimumDate' => date('Y-m-d', strtotime('tomorrow')),
            'maximumDate' => date('Y-m-t', strtotime('+11 months', strtotime(date('Y-m-01')))),
        );
    }

    private function buildBookingViewData()
    {
        $locale = FRONTEND_LOCALE;
        $this->load->model('Tour_model');
        $this->load->library('encryption');
        $requested = $this->input->get('i');
        if (!is_string($requested) || trim($requested) === '') {
            show_404();
        }
        $requestedDate = $this->input->get('date');
        $preDate = is_string($requestedDate)
            && preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $requestedDate, $dateParts)
            && checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])
            ? $requestedDate
            : '';
        $decoded = is_string($requested) && strlen($requested) < 2048
            ? $this->encryption->decrypt($requested)
            : false;
        // Existing listing/search links use public slugs; canonicalize them before rendering a form.
        if ($decoded === false && is_string($requested) && $requested !== '' && strlen($requested) <= 255) {
            $legacyTour = $this->Tour_model->get_by_slug($requested, $locale);
            if ($legacyTour) {
                $query = array('i' => $this->bookingReference('tour', $legacyTour['tour_id']));
                if ($preDate !== '') {
                    $query['date'] = $preDate;
                }
                redirect(base_url($locale . '/book?' . http_build_query($query)));
            }
        }
        $selectedId = is_string($decoded) && preg_match('/^tour:([1-9][0-9]*)$/D', $decoded, $match)
            ? (int) $match[1]
            : 0;
        if ($selectedId < 1) {
            show_404();
        }
        $data = array(
            'tours' => array(),
            'vehicles' => array(),
            'guides' => array(),
            'languages' => array(),
        );
        $references = array();
        $selectedGuideReferences = array();
        $selectedVehicleReferences = array();
        $selectedLanguageReferences = array();
        $selectedExperience = false;
        $arabic = '';
        $preTour = '';
        $rows = $this->Tour_model->get_booking_tours($locale);
        foreach ($rows as $row) {
            $id = (int) $row['tour_id'];
            $reference = $this->bookingReference('tour', $id);
            $detail = $this->Tour_model->get_by_slug($row['tour_slug'], $locale);
            if (!$detail) {
                continue;
            }
            $experience = trim($detail['tour_type']) === 'Experience';
            $tour = $this->prepareTourExperience($detail, $row['tour_slug'], $experience);
            $tour['slug'] = $reference;
            $tour['isExperience'] = $experience;
            $tour['capacity'] = 0;
            if ($id === $selectedId) {
                $preTour = $reference;
                $selectedExperience = $experience;
                $bookingHero = $tour;
                $bookingBreadcrumbItems = $this->tourBreadcrumbItems($detail, $locale, $experience);
                $bookingBreadcrumbItems[2]['href'] = base_url(
                    $locale . '/' . ($experience ? EXPERIENCE_URI : TOUR_URI) . $row['tour_slug']
                );
                $bookingBreadcrumbItems[] = array('label' => $this->frontendLine('book.eyebrow'));
            }
            foreach ($this->Tour_model->get_tour_vehicles($id, $locale) as $vehicle) {
                $item = $this->prepareTourVehicle($vehicle, $experience);
                $item['id'] = $this->bookingReference('vehicle', $vehicle['vehicle_id']);
                $item['tour'] = $reference;
                if ($id === $selectedId) {
                    $selectedVehicleReferences[(int) $vehicle['vehicle_id']] = $item['id'];
                }
                $item['label'] = $item['name'];
                $item['blurb'] = '';
                $item['prices'] = array();
                $item['meals'] = array();
                foreach (array(2, 4, 6, 8) as $hours) {
                    $item['prices'][$hours] = (int) $vehicle['vehicle_price_' . $hours];
                    $item['meals'][$hours] = (int) $vehicle['vehicle_meals_' . $hours];
                }
                $tour['capacity'] = max($tour['capacity'], $item['capacity']);
                $data['vehicles'][] = $item;
            }
            if (!$experience) {
                foreach ($this->Tour_model->get_tour_guides($id, $locale) as $guide) {
                    $item = $this->prepareTourGuide($guide);
                    $item['id'] = $this->bookingReference('guide', $guide['tour_guide_id']);
                    if ($id === $selectedId) {
                        $selectedGuideReferences[(int) $guide['tour_guide_id']] = $item['id'];
                    }
                    $item['role'] = $item['title'];
                    $item['expertise'] = array();
                    $item['tours'] = array($reference);
                    $item['active'] = true;
                    $item['languages'] = array();
                    foreach ($guide['booking_languages'] as $language) {
                        $languageId = (int) $language['lang_id'];
                        if (!isset($references[$languageId])) {
                            $references[$languageId] = $this->bookingReference('language', $languageId);
                            $data['languages'][] = array(
                                'id' => $references[$languageId],
                                'label' => $language['lang_name'],
                                'isArabic' => $languageId === 1,
                            );
                        }
                        $item['languages'][] = $references[$languageId];
                        if ($id === $selectedId) {
                            $selectedLanguageReferences[$languageId] = $references[$languageId];
                        }
                        if ($languageId === 1) {
                            $arabic = $references[$languageId];
                        }
                    }
                    $data['guides'][] = $item;
                }
            }
            $data['tours'][] = array_intersect_key($tour, array_flip(array(
                'slug',
                'title',
                'category',
                'duration',
                'capacity',
                'price',
                'image',
                'background_image',
                'isExperience',
            )));
        }
        if ($requested !== null && $requested !== '' && $preTour === '') {
            show_404();
        }
        $data['arabic'] = $arabic;
        $data['slots'] = array();
        $prices = $this->Tour_model->get_booking_prices($selectedId);
        $data['pricing'] = array('tour' => array(), 'arabicGuide' => array(), 'englishGuide' => array());
        foreach (array(2, 4, 6, 8) as $hours) {
            $data['pricing']['tour'][$hours] = (int) $prices['tour_price_' . $hours];
            $data['pricing']['arabicGuide'][$hours] = (int) $prices['tour_arabic_guide_price_' . $hours];
            $data['pricing']['englishGuide'][$hours] = (int) $prices['tour_english_guide_price_' . $hours];
        }
        // booking.js adds these the same way Booking_model::save() does.
        $this->load->library('tour_pricing');
        $rates = $this->tour_pricing->rates();
        $data['pricing']['profit'] = $rates['profit'];
        $data['pricing']['tax'] = $rates['tax'];
        $slotReferences = array();
        foreach ($this->Tour_model->get_tour_slots($selectedId, $locale) as $slot) {
            $hours = (int) $slot['slot_hours'];
            if (!in_array($hours, array(2, 4, 6, 8), true) || $data['pricing']['tour'][$hours] <= 0) {
                continue;
            }
            $times = array();
            foreach (array('slot_start_time', 'slot_end_time') as $column) {
                if (!empty($slot[$column])) {
                    $time = date('g:i A', strtotime($slot[$column]));
                    $times[] = $locale === 'ar'
                        ? str_replace(array('AM', 'PM'), array('ص', 'م'), $time)
                        : $time;
                }
            }
            $slotReferences[(int) $slot['slot_id']] = $this->bookingReference('slot', $slot['slot_id']);
            $data['slots'][] = array(
                'id' => $slotReferences[(int) $slot['slot_id']],
                'label' => $slot['slot_name'],
                'time' => implode(' – ', $times),
                'note' => (string) $slot['slot_details'],
                'icon' => trim((string) $slot['slot_icon']) !== '' ? $slot['slot_icon'] : 'clock',
                'hours' => (int) $slot['slot_hours'],
            );
        }
        $data['serverToday'] = date('Y-m-d');
        $data['minimumDate'] = date('Y-m-d', strtotime('tomorrow'));
        $data['maximumDate'] = date('Y-m-t', strtotime('+11 months', strtotime(date('Y-m-01'))));
        $data['availability'] = array();
        if (!$selectedExperience) {
            foreach ($this->Tour_model->get_booking_availability(
                array_keys($selectedGuideReferences),
                $data['minimumDate'],
                $data['maximumDate']
            ) as $availability) {
                $slotId = (int) $availability['avail_slot_id'];
                if (!isset($slotReferences[$slotId])) {
                    continue;
                }
                $data['availability'][$availability['avail_date']][$slotReferences[$slotId]][] =
                    $selectedGuideReferences[(int) $availability['avail_tour_guide_id']];
            }
        }
        if (
            $preDate < $data['minimumDate']
            || $preDate > $data['maximumDate']
            || (!$selectedExperience && !isset($data['availability'][$preDate]))
        ) {
            $preDate = '';
        }
        // Optional pre-fills from the homepage search; anything this tour does not offer is ignored.
        $requestedVehicleId = $this->bookingReferenceId($this->input->get('vehicle'), 'vehicle');
        $requestedLanguageId = $this->bookingReferenceId($this->input->get('language'), 'language');
        $data['recaptcha'] = array(
            'siteKey' => RECAPTCHA_ENTERPRISE_SITE_KEY,
            'actions' => self::BOOKING_RECAPTCHA_ACTIONS,
        );
        $data['preselect'] = array(
            'tour' => $preTour,
            'vehicle' => isset($selectedVehicleReferences[$requestedVehicleId])
                ? $selectedVehicleReferences[$requestedVehicleId]
                : '',
            'language' => isset($selectedLanguageReferences[$requestedLanguageId])
                ? $selectedLanguageReferences[$requestedLanguageId]
                : $arabic,
            'date' => $preDate,
        );

        $this->load->library('content_section_service');
        $misc = $this->content_section_service->get_miscellaneous_contents($locale);
        $helpCard = isset($misc['need_help_choosing']) && is_array($misc['need_help_choosing'])
            ? $this->prepareSupportCard($misc['need_help_choosing'], $locale)
            : null;

        if (
            is_array($bookingHero)
            && trim((string) $bookingHero['price_details']) === ''
        ) {
            $bookingHero['price_details'] = $this->defaultTourPriceDetails($misc, $selectedExperience);
        }

        return array(
            'bookingData' => $data,
            'bookingHero' => $bookingHero,
            'bookingBreadcrumbItems' => $bookingBreadcrumbItems,
            'helpCard' => $helpCard,
        );
    }

    public function privacy_policy()
    {
        $this->render('privacy_policy', $this->buildPrivacyPolicyViewData());
    }

    public function cancellation_policy()
    {
        $this->render('cancellation_policy', $this->buildCancellationPolicyViewData());
    }

    private function render($page, array $data = array())
    {
        if (!isset($this->pages[$page])) {
            show_404();
        }

        if (!defined('FRONTEND_SCRIPT')) {
            define('FRONTEND_SCRIPT', $this->pages[$page]);
        }
        $this->load->view('frontend/' . $page, $data);
    }

    /** Render a CMS page that does not have a specialized core-page layout. */
    private function renderManagedPage(array $page, $locale)
    {
        if (!defined('FRONTEND_PAGE_SLUG')) {
            define('FRONTEND_PAGE_SLUG', (string) $page['page_slug']);
        }
        if (!defined('FRONTEND_PAGE_SLUG_AR')) {
            define('FRONTEND_PAGE_SLUG_AR', (string) $page['page_slug_ar']);
        }

        $label = $this->frontend_presenter->text($page, 'page_name');
        $menuLabel = $this->frontend_presenter->menuLabel($page['page_id']);
        $config = $this->frontend_presenter->pageConfig($page, array(
            'page_title' => $label,
            'meta_description' => '',
            'active' => '',
        ), 'pages', array(
            'name' => $label,
            'description' => array(
                isset($page['banner_text']) ? $page['banner_text'] : '',
                isset($page['page_text']) ? $page['page_text'] : '',
            ),
            'images' => array(
                array('pages', isset($page['banner_background']) ? $page['banner_background'] : ''),
            ),
        ));
        $crumbs = array(
            array(
                'label' => $this->frontend_presenter->menuLabel(1),
                'href' => base_url($locale),
            ),
            array('label' => $menuLabel),
        );
        $config['breadcrumbs'] = $crumbs;
        $config = $this->frontend_presenter->managedBanner(
            $config,
            $page,
            'default',
            $crumbs
        );

        $this->render('page', array(
            'config' => $config,
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
        ));
    }

    /** Build the locale-prefixed URL for a core CMS page by its stable ID. */
    private function managedPageUrl($pageId, $locale)
    {
        $locale = $locale === 'ar' ? 'ar' : 'en';
        $pages = $this->config->item('frontend_pages');
        $page = is_array($pages) && isset($pages[(int) $pageId])
            ? $pages[(int) $pageId]
            : array();
        $field = $locale === 'ar' ? 'page_slug_ar' : 'page_slug';
        $slug = isset($page[$field]) ? trim((string) $page[$field]) : '';
        if ($slug === '' && isset($page['page_slug'])) {
            $slug = trim((string) $page['page_slug']);
        }

        return base_url($locale . ($slug !== '' ? '/' . $slug : ''));
    }

    /** Build a locale-prefixed public Blog category URL from its stable slug. */
    private function blogCategoryUrl($slug, $locale = null)
    {
        $locale = $locale !== null
            ? ($locale === 'ar' ? 'ar' : 'en')
            : (defined('FRONTEND_LOCALE') && FRONTEND_LOCALE === 'ar' ? 'ar' : 'en');

        return base_url($locale . '/' . BLOG_CATEGORY_URI . rawurlencode(trim((string) $slug)));
    }

    /**
     * Load the administrator-managed data shared by every public page once.
     * The frontend helpers read these config values without issuing queries
     * from inside the views.
     */
    private function loadSharedFrontendData(array $settings)
    {
        $this->load->model('MenuModel');
        $this->load->model('FootModel');

        $mainMenu = $this->MenuModel->getMenuData();
        $footerOne = $this->FootModel->getFooterMenuData('one');
        $footerTwo = $this->FootModel->getFooterMenuData('two');

        $this->config->set_item(
            'frontend_site_settings',
            $settings
        );
        $this->config->set_item(
            'frontend_navigation',
            isset($mainMenu['active']) && is_array($mainMenu['active'])
                ? $mainMenu['active']
                : array()
        );
        $this->config->set_item(
            'frontend_pages',
            $this->MenuModel->getAllowedPages()
        );
        $this->config->set_item('frontend_footer_navigation', array(
            'one' => isset($footerOne['active']) && is_array($footerOne['active'])
                ? $footerOne['active']
                : array(),
            'two' => isset($footerTwo['active']) && is_array($footerTwo['active'])
                ? $footerTwo['active']
                : array(),
        ));
    }

    /** Use a saved visitor choice before falling back to Website Settings. */
    private function preferredFrontendLocale(array $settings)
    {
        $savedLocale = strtolower(trim((string) $this->input->cookie(
            self::LANGUAGE_COOKIE,
            true
        )));
        if (in_array($savedLocale, array('en', 'ar'), true)) {
            return $savedLocale;
        }

        $defaultLanguage = isset($settings['default_language'])
            ? strtolower(trim((string) $settings['default_language']))
            : '';

        return $defaultLanguage === 'arabic' ? 'ar' : 'en';
    }

    /** Persist an explicit /en or /ar visit without exposing it to scripts. */
    private function rememberFrontendLocale($locale)
    {
        $savedLocale = strtolower(trim((string) $this->input->cookie(
            self::LANGUAGE_COOKIE,
            true
        )));
        if ($savedLocale === $locale) {
            return;
        }

        $this->input->set_cookie(array(
            'name' => self::LANGUAGE_COOKIE,
            'value' => $locale,
            'expire' => self::LANGUAGE_COOKIE_LIFETIME,
            'path' => '/',
            'secure' => (bool) $this->config->item('cookie_secure'),
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    }

    /** Build the complete view model used by the public homepage. */
    private function buildHomeViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Tour_model');
        $this->load->model('Guide_model');
        $this->load->model('Blog_model');
        $this->load->model('Faq_model');
        $this->load->model('Review_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 1, true);
        $sliderId = (int) (isset($page['page_slider']) ? $page['page_slider'] : 0);

        $homepageProducts = $this->Tour_model->get_tours_experiences(6, $locale);

        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(1, $locale),
            array(),
            array('discover_madinah' => 4),
            array('how_booking_works')
        );
        $heroSlides = $this->Webpage_model->get_hero_slides($sliderId, $locale);

        return array(
            'config' => $this->frontend_presenter->homeConfig($page, $heroSlides),
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'heroSlides' => $heroSlides,
            'heroSearch' => $this->buildHeroSearchData($locale),
            'sections' => $sections,
            'sectionOrder' => array_keys($sections),
            'cta' => $this->buildPlanCtaBand($locale),
            'featuredTours' => $this->frontend_presenter->tourCards(
                $homepageProducts['tours']
            ),
            'featuredExperiences' => $this->frontend_presenter->experienceCards(
                $homepageProducts['experiences']
            ),
            'guides' => $this->frontend_presenter->guideCards(
                $this->Guide_model->get_homepage_guides(3, $locale)
            ),
            'testimonials' => $this->frontend_presenter->testimonials(
                $this->Review_model->get_reviews(3, $locale)
            ),
            'homeFaqs' => $this->frontend_presenter->faqs(
                $this->Faq_model->get_home_faqs($locale)
            ),
            'posts' => $this->frontend_presenter->homePosts(
                $this->Blog_model->get_homepage_posts(3, $locale)
            ),
        );
    }

    /** Build the complete view model used by the public 404 page. */
    private function buildErrorViewData()
    {
        return array(
            'config' => array(
                'page_title' => $this->frontendLine('error404.meta.title'),
                'meta_description' => $this->frontendLine('error404.meta.description'),
                'active' => '',
                'robots' => 'noindex, follow',
            ),
        );
    }

    /** Build the complete view model used by the About Us page. */
    private function buildAboutViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Tour_model');
        $this->load->model('Guide_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 2, true);
        $products = $this->Tour_model->get_tours_experiences(3, $locale);
        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(2, $locale),
            array('knowledge_matters' => 'reason'),
            array(
                'our_approach' => 6,
                'knowledge_matters' => 6,
                'guiding_principles' => 6,
            ),
            array('guiding_principles')
        );

        return array(
            'config' => $this->frontend_presenter->managedPageConfig(
                $page,
                $locale,
                'about',
                2
            ),
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'sections' => $sections,
            'sectionOrder' => array_keys($sections),
            'featuredTours' => $this->frontend_presenter->tourCards(
                $products['tours'],
                '',
                false
            ),
            'featuredExperiences' => $this->frontend_presenter->experienceCards(
                $products['experiences']
            ),
            'guides' => $this->frontend_presenter->guideCards(
                $this->Guide_model->get_homepage_guides(3, $locale)
            ),
        );
    }

    /** Build the complete view model used by the public Tours listing. */
    private function buildToursViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Tour_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 3, true);
        $listing = $this->Tour_model->get_tour_listing($locale);
        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(3, $locale)
        );

        $config = $this->frontend_presenter->managedPageConfig(
            $page,
            $locale,
            'tours',
            3,
            'compact',
            array(
                'select2' => true,
                'seo_images' => $this->listingImageCandidates($listing['tours']),
            )
        );
        $config['schema'] = array(
            $this->listingItemList($listing['tours'], $this->managedPageUrl(3, $locale), $locale, false),
        );

        return array(
            'config' => $config,
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'searchSection' => isset($sections['search_filters'])
                ? $sections['search_filters']
                : array(),
            'tours' => $this->frontend_presenter->tourCards(
                $listing['tours'],
                '',
                false
            ),
            'filterCategories' => $listing['categories'],
            'filterCapacities' => $listing['capacities'],
            'filterLanguages' => $listing['languages'],
        );
    }

    /** Build the complete view model used by the public Experiences listing. */
    private function buildExperiencesViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Tour_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 4, true);
        $listing = $this->Tour_model->get_experience_listing($locale);
        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(4, $locale)
        );

        $config = $this->frontend_presenter->managedPageConfig(
            $page,
            $locale,
            'experiences',
            4,
            'compact',
            array('seo_images' => $this->listingImageCandidates($listing['experiences']))
        );
        $config['schema'] = array(
            $this->listingItemList($listing['experiences'], $this->managedPageUrl(4, $locale), $locale, true),
        );

        return array(
            'config' => $config,
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'searchSection' => isset($sections['search_filters'])
                ? $sections['search_filters']
                : array(),
            'experiences' => $this->frontend_presenter->experienceCards($listing['experiences']),
            'filterCategories' => $listing['categories'],
        );
    }

    /** Build the complete view model used by the public Tour Guides listing. */
    private function buildTourGuidesViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Guide_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 11, true);
        $listing = $this->Guide_model->get_guide_listing($locale);
        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(11, $locale)
        );

        // The first listed guides' photos, as sharing-image fallbacks.
        $seoImages = array();
        foreach (array_slice($listing['guides'], 0, 3) as $guide) {
            $seoImages[] = array(
                'tour-guides',
                isset($guide['tour_guide_image']) ? $guide['tour_guide_image'] : '',
            );
        }

        $config = $this->frontend_presenter->managedPageConfig(
            $page,
            $locale,
            'tour_guides',
            11,
            'compact',
            array('seo_images' => $seoImages)
        );

        return array(
            'config' => $config,
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'searchSection' => isset($sections['search_filters'])
                ? $sections['search_filters']
                : array(),
            'guides' => $this->frontend_presenter->guideCards($listing['guides']),
            'filterLanguages' => $listing['languages'],
        );
    }

    /** The first listed tours main images, as sharing-image fallbacks for a listing page. */
    private function listingImageCandidates(array $rows)
    {
        $images = array();
        foreach (array_slice($rows, 0, 3) as $row) {
            $images[] = array('tours', isset($row['tour_image']) ? $row['tour_image'] : '');
        }

        return $images;
    }

    /** ItemList structured data for a Tours or Experiences listing. */
    private function listingItemList(array $rows, $listingUrl, $locale, $isExperience)
    {
        $items = array();
        foreach ($rows as $row) {
            $items[] = array(
                'name' => isset($row['tour_name']) ? $row['tour_name'] : '',
                'url' => $this->frontend_seo->tourUrl(
                    isset($row['tour_slug']) ? $row['tour_slug'] : '',
                    $locale,
                    $isExperience
                ),
            );
        }

        return $this->frontend_seo->itemListNode($listingUrl, $items);
    }

    /** Build the complete view model used by the public Blog listing. */
    private function buildBlogViewData($categorySlug = '')
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Blog_model');

        $categorySlug = trim(urldecode((string) $categorySlug));
        $category = $categorySlug !== ''
            ? $this->Blog_model->get_category_by_slug($categorySlug, $locale)
            : null;
        if ($categorySlug !== '' && $category === null) {
            /* An unknown or disabled category is a 404. Serving the full
               listing at that address would publish a duplicate of the Blog
               page for every mistyped or removed category URL. */
            return null;
        }
        $categoryId = $category !== null ? (int) $category['cat_id'] : 0;

        $perPage = self::BLOG_PER_PAGE;
        $requestedPage = (int) $this->input->get('page');
        $page = $requestedPage > 0 ? $requestedPage : 1;

        $total = $this->Blog_model->get_published_count($categoryId);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        /* The page actually shown, so an out-of-range ?page=99 is canonical to
           the last real page rather than to itself. */
        if (!defined('FRONTEND_LISTING_PAGE')) {
            define('FRONTEND_LISTING_PAGE', $page);
        }

        $rows = $this->Blog_model->get_blog_listing($categoryId, $offset, $perPage, $locale);
        $posts = $this->prepareBlogCards($rows);
        $lead = isset($posts[0]) ? $posts[0] : null;
        $rest = $lead !== null ? array_slice($posts, 1) : array();

        $managedPage = $this->Webpage_model->get_page($locale, 'id', 8, true);
        $config = $category !== null
            ? $this->prepareBlogCategoryConfig($category, $locale, $posts)
            : $this->frontend_presenter->managedPageConfig(
                $managedPage,
                $locale,
                'blog',
                8,
                'compact',
                array(
                    'seo_images' => array(
                        array('blogs', isset($rows[0]['blog_image']) ? $rows[0]['blog_image'] : ''),
                        array('blogs', isset($rows[0]['blog_cover_image']) ? $rows[0]['blog_cover_image'] : ''),
                    ),
                )
            );

        /* Every page of a listing is its own search result, so pages after the
           first carry their number in the title to keep titles distinct. */
        if ($page > 1) {
            $suffix = ' - ' . $this->frontendLine('pagination.pagePrefix') . $page;
            $config['page_title'] = (isset($config['page_title']) ? $config['page_title'] : '') . $suffix;
        }

        $listItems = array();
        foreach ($posts as $post) {
            $listItems[] = array(
                'name' => $post['title'],
                'url' => $this->frontend_seo->blogPostUrl($post['slug'], $locale),
            );
        }
        $config['schema'] = array(
            $this->frontend_seo->itemListNode(
                $categorySlug !== ''
                    ? $this->frontend_seo->blogCategoryUrl($categorySlug, $locale)
                    : $this->managedPageUrl(8, $locale),
                $listItems
            ),
        );

        return array(
            'config' => $config,
            'pageContent' => isset($managedPage['page_text'])
                ? (string) $managedPage['page_text']
                : '',
            'nav' => $this->prepareBlogCategoryNav($locale),
            'activeCategory' => $categorySlug,
            'categoryLabel' => $category !== null ? $this->frontendText($category, 'cat_name') : '',
            'lead' => $lead,
            'posts' => $rest,
            'totalPosts' => $total,
            'pagination' => array(
                'current' => $page,
                'total' => $totalPages,
                'base' => $categorySlug !== ''
                    ? $this->blogCategoryUrl($categorySlug, $locale)
                    : $this->managedPageUrl(8, $locale),
                'query' => array(),
            ),
            'cta' => $this->buildPlanCtaBand($locale),
        );
    }

    /** Build the complete view model used by one Blog Post, or null for a 404. */
    private function buildBlogPostViewData($slug)
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Blog_model');

        $slug = trim(urldecode((string) $slug));
        $row = $slug !== '' ? $this->Blog_model->get_post_by_slug($slug, $locale) : null;
        if ($row === null) {
            return null;
        }

        $blogId = (int) $row['blog_id'];
        $categoryIds = array_map(
            'intval',
            isset($row['category_ids']) && is_array($row['category_ids']) ? $row['category_ids'] : array()
        );

        $railCount = 4;
        $relatedCount = 3;
        $railRows = $this->Blog_model->get_rail_posts($blogId, $railCount, $locale);
        $railIds = array_column($railRows, 'blog_id');
        $relatedRows = $this->Blog_model->get_related_posts(
            $blogId,
            $railIds,
            $categoryIds,
            $relatedCount,
            $locale
        );

        $authorId = isset($row['blog_author']) ? (int) $row['blog_author'] : 0;
        $authors = $this->Blog_model->get_authors(array($authorId));

        $post = $this->prepareBlogPost($row, $authors, $locale);

        return array(
            'config' => $this->prepareBlogPostConfig($row, $post, $locale),
            'post' => $post,
            'slugs' => $this->managedSlugs($row),
            'nav' => $this->prepareBlogCategoryNav($locale),
            'rail' => $this->prepareBlogCards($railRows),
            'railContent' => $this->prepareFeaturedPostsRailContent($locale),
            'related' => $this->prepareBlogCards($relatedRows),
        );
    }

    /** Build the complete view model used by the shared Tour/Experience detail page, or null for a 404. */
    private function buildTourExperienceViewData($slug)
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Tour_model');

        $slug = trim(urldecode((string) $slug));
        $row = $slug !== '' ? $this->Tour_model->get_by_slug($slug, $locale) : null;
        if ($row === null) {
            return null;
        }

        $tourId = (int) $row['tour_id'];
        $isExperience = $row['tour_type'] === 'Experience';

        $related = $this->Tour_model->get_tours($row['tour_type'], 4, $locale);
        $related = array_values(array_filter($related, static function ($t) use ($tourId) {
            return (int) $t['tour_id'] !== $tourId;
        }));
        $related = array_map(function ($t) use ($isExperience) {
            return $this->prepareTourCard($t, $isExperience);
        }, array_slice($related, 0, 3));

        /* Experiences carry no guide concept, so the detail page never
           shows a Guides section for them regardless of any assignment
           data — mirrors the type check in Tour_model::get_tours(). */
        $guides = $isExperience ? array() : $this->Tour_model->get_tour_guides($tourId, $locale);
        $vehicles = $this->Tour_model->get_tour_vehicles($tourId, $locale);

        $this->load->library('content_section_service');
        $misc = $this->content_section_service->get_miscellaneous_contents($locale);
        $helpCard = isset($misc['prefer_to_talk']) && is_array($misc['prefer_to_talk'])
            ? $this->prepareSupportCard($misc['prefer_to_talk'], $locale)
            : null;

        $galleryRows = $this->Tour_model->get_tour_images($tourId, $locale);
        $breadcrumbItems = $this->tourBreadcrumbItems($row, $locale, $isExperience);

        $tour = $this->prepareTourExperience($row, $slug, $isExperience);
        if (trim((string) $tour['price_details']) === '') {
            $tour['price_details'] = $this->defaultTourPriceDetails($misc, $isExperience);
        }

        return array(
            'config' => $this->prepareTourExperienceConfig($row, $isExperience, $galleryRows, $breadcrumbItems),
            'tour' => $tour,
            'isExperience' => $isExperience,
            'slugs' => $this->managedSlugs($row),
            'attractions' => array_map(array($this, 'prepareTourAttraction'), $this->Tour_model->get_tour_attractions($tourId, $locale)),
            'journey' => array_map(array($this, 'prepareTourJourneyStep'), $this->Tour_model->get_tour_itinerary($tourId, $locale)),
            'gallery' => $this->prepareTourGallery($galleryRows),
            'guidesForTour' => array_map(array($this, 'prepareTourGuide'), $guides),
            'vehiclesForTour' => array_map(function ($v) use ($isExperience) {
                return $this->prepareTourVehicle($v, $isExperience);
            }, $vehicles),
            'related' => $related,
            'breadcrumbItems' => $breadcrumbItems,
            'capacityLabel' => trim((string) $row['tour_group_size']),
            'bookUrl' => base_url($locale . '/book?i=' . rawurlencode($this->bookingReference('tour', $tourId))),
            'helpCard' => $helpCard,
            'sectionNavigationLabels' => $this->tourSectionNavigationLabels($misc),
        );
    }

    /** Price Details CMS text for the tour type, used when the tour has none of its own. */
    private function defaultTourPriceDetails(array $misc, $isExperience)
    {
        $section = isset($misc['tour_price_details']) && is_array($misc['tour_price_details'])
            ? $misc['tour_price_details']
            : array();

        return $this->frontendText($section, $isExperience ? 'experience' : 'tour');
    }

    /** Map the Tour / Experience Navigation CMS section onto the anchor ids tour_details.php renders. */
    private function tourSectionNavigationLabels(array $misc)
    {
        $fieldBySectionId = array(
            'overview' => 'overview',
            'highlights' => 'places',
            'journey' => 'itinerary',
            'prepare' => 'practical_information',
            'gallery' => 'gallery',
            'vehicles' => 'vehicles',
            'guides' => 'guides',
            'faqs' => 'faqs',
        );

        $section = isset($misc['tour_navigation']) && is_array($misc['tour_navigation'])
            ? $misc['tour_navigation']
            : array();

        $labels = array();
        foreach ($fieldBySectionId as $sectionId => $fieldKey) {
            $value = $this->frontendText($section, $fieldKey);
            $labels[$sectionId] = $value !== '' ? $value : $this->frontendLine('tour.section.' . $sectionId);
        }

        return $labels;
    }

    /** Map one `tours` row into the `$tour` shape tour_details.php already consumes. */
    private function prepareTourExperience(array $row, $slug, $isExperience)
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $backgroundImageFields = $locale === 'ar'
            ? array('tour_bg_image_ar', 'tour_bg_image', 'tour_image_ar', 'tour_image')
            : array('tour_bg_image', 'tour_image');
        $backgroundImageCandidates = array();
        foreach ($backgroundImageFields as $field) {
            $backgroundImageCandidates[] = isset($row[$field])
                ? $row[$field]
                : null;
        }
        $backgroundImage = upload_thumb_candidates(
            'tours',
            $backgroundImageCandidates,
            960,
            640
        );

        $faqs = array();
        foreach ($this->Tour_model->get_faqs_for_tour($row['tour_faqs_general'], $row['tour_faqs_specific'], $locale) as $faq) {
            $faqs[] = array('q' => $faq['faq_question'], 'a' => $faq['faq_answer']);
        }

        $prepare = array();
        $pinfoFields = array(
            'tour_pinfo_pickup' => 'pickup',
            'tour_pinfo_departure_point' => 'departurePoint',
            'tour_pinfo_departure_time' => 'departureTime',
            'tour_pinfo_duration' => 'duration',
            'tour_pinfo_group_size' => 'groupSize',
            'tour_pinfo_transportation' => 'transportation',
            'tour_pinfo_lang' => 'languages',
            'tour_pinfo_meals' => 'meals',
            'tour_pinfo_refreshment' => 'refreshments',
            'tour_pinfo_weather' => 'weather',
            'tour_pinfo_bring' => 'bring',
            'tour_pinfo_access' => 'accessibility',
            'tour_pinfo_family' => 'familyFriendly',
            'tour_pinfo_return' => 'return',
        );
        foreach ($pinfoFields as $field => $labelKey) {
            $value = trim((string) $row[$field]);
            if ($value !== '') {
                $prepare[] = array(
                    'label' => $this->frontendLine('tour.prepare.label.' . $labelKey),
                    'value' => $value,
                );
            }
        }

        $tourImageFields = $locale === 'ar'
            ? array('tour_image_ar', 'tour_image')
            : array('tour_image');
        $tourImageCandidates = array();
        foreach ($tourImageFields as $field) {
            $tourImageCandidates[] = isset($row[$field]) ? $row[$field] : null;
        }
        $tourImage = upload_thumb_candidates(
            'tours',
            $tourImageCandidates,
            960,
            640
        );

        /* A Tour's languages come from its assigned guides — the same real
           data the Tours listing cards show. An Experience has no guide
           concept, so it uses the admin-entered `tour_lang` field instead. */
        if ($isExperience) {
            $languages = trim((string) $row['tour_lang']);
        } else {
            $languageNames = $this->Tour_model->get_tour_languages_for((int) $row['tour_id'], $locale);
            $languages = !empty($languageNames)
                ? implode($this->frontendLine('format.listSeparator'), $languageNames)
                : '';
        }
       
        return array(
            'title' => $row['tour_name'],
            'category' => $this->frontendLine(
                $isExperience ? 'tour.type.experience' : 'tour.type.tour'
            ),
            'summary' => $row['tour_short_description'],
            'image' => $tourImage,
            'background_image' => $backgroundImage,
            'duration' => $row['tour_duration'],
            'languages' => $languages,
            'walking' => $row['tour_walking'],
            'pickup' => $row['tour_pickup'],
            'price_details' => $row['tour_price_details'],
            'price' => (int) $row['tour_price'],
            'slug' => $slug,
            'overview' => $row['tour_overview'],
            'experience' => $row['tour_highlights'],
            'itinerary' => $row['tour_itinerary'],
            'pinfo' => $row['tour_pinfo'],
            'gallery' => $row['tour_gallery'],
            'guide_info' => $row['tour_guide_info'],
            'vehicle_info' => $row['tour_vehicle_text'],
            'faqs_info' => $row['tour_faqs'],
            'prepare' => $prepare,
            'faqs' => $faqs,
        );
    }

    private function prepareTourAttraction(array $a)
    {
        return array(
            'slug' => (string) $a['attraction_id'],
            'title' => $a['attraction_name'],
            'image' => upload_thumb(
                'attractions',
                $a['attraction_image'],
                960,
                640,
                ''
            ),
            'alt' => $a['attraction_name'],
            'short' => $a['attraction_short_desc'],
            'details' => array($a['attraction_desc']),
        );
    }

    private function prepareTourJourneyStep(array $j)
    {
        $image = upload_thumb(
            'tour-itineraries',
            isset($j['image']) ? $j['image'] : null,
            960,
            640,
            ''
        );
        if ($image === '') {
            $image = upload_thumb(
                'attractions',
                isset($j['attraction_image']) ? $j['attraction_image'] : null,
                960,
                640,
                ''
            );
        }

        return array(
            'time' => $j['day_hour'],
            'title' => $j['title'],
            'text' => $j['details'],
            'image' => $image,
            'alt' => (string) $j['attraction_name'],
        );
    }

    private function prepareTourGallery(array $images)
    {
        $out = array();
        foreach ($images as $image) {
            /* Grid thumbnail: fixed 600x600, centre-cropped square (matches
               the "aspect-square object-cover" tile in tour_details.php). */
            $src = upload_thumb(
                'tour-images',
                $image['image_image'],
                600,
                600,
                ''
            );
            if ($src === '') {
                continue;
            }

            $out[] = array(
                'src' => $src,
                /* Lightbox view: width-capped at 1600, height 0 so
                   Imagethumb::_dimensions() derives it from the source's own
                   aspect ratio instead of centre-cropping to a square. */
                'full' => upload_thumb('tour-images', $image['image_image'], 1600, 0, ''),
                'alt' => trim((string) $image['image_name']) !== ''
                    ? $image['image_name']
                    : $image['image_desc'],
            );
        }

        return $out;
    }

    private function prepareTourGuide(array $g)
    {
        $name = $g['tour_guide_name'];

        return array(
            'name' => $name,
            'title' => $g['tour_guide_title'],
            'bio' => $g['tour_guide_desc'],
            'image' => upload_thumb(
                'tour-guides',
                $g['tour_guide_image'],
                0,
                0,
                ''
            ),
            'image_alt' => $name,
            'languages' => $g['languages'],
        );
    }

    /**
     * A Tour's guests ride with a guide, an Experience's do not, so each
     * takes up one fewer seat than a Tour for the same vehicle — mirrors the
     * business rule as given rather than a generic "minus crew" constant.
     */
    /** Guests a vehicle can carry: its seats minus the guide (and the driver on Tours). */
    private function vehicleGuestCapacity($maxCapacity, $isExperience)
    {
        return max(0, (int) $maxCapacity - ($isExperience ? 1 : 2));
    }

    private function prepareTourVehicle(array $v, $isExperience)
    {
        return array(
            'name' => $v['vehicle_name'],
            'capacity' => $this->vehicleGuestCapacity(
                $v['vehicle_max_capacity'],
                $isExperience
            ),
            'details' => $v['vehicle_details'],
            'image' => upload_thumb(
                'vehicles',
                $v['vehicle_image'],
                640,
                0,
                ''
            ),
        );
    }

    private function prepareTourCard(array $t, $isExperience = false)
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $imageCandidates = $locale === 'ar'
            ? array($t['tour_image_ar'], $t['tour_image'])
            : array($t['tour_image']);

        /* A Tour's languages come from its assigned guides — the same real
           data the Tours listing cards show. An Experience has no guide
           concept, so it uses the admin-entered `tour_lang` field instead. */
        if ($isExperience) {
            $languageLabel = trim((string) $t['tour_lang']);
        } else {
            $languageNames = $this->Tour_model->get_tour_languages_for((int) $t['tour_id'], $locale);
            $languageLabel = !empty($languageNames)
                ? implode($this->frontendLine('format.listSeparator'), $languageNames)
                : '';
        }

        return array(
            'slug' => $t['tour_slug'],
            'title' => $t['tour_name'],
            'image' => upload_thumb_candidates(
                'tours',
                $imageCandidates,
                960,
                640
            ),
            'summary' => $t['tour_short_description'],
            'duration' => $t['tour_duration'],
            /* Group size is stored as an already-formatted string (e.g. "Up
               to 15 guests"); tour_card() prints it as-is when non-numeric,
               same convention used by Tour_model::get_tour_listing(). */
            'capacity' => $t['tour_group_size'],
            'language_label' => $languageLabel,
            'category' => '',
            'price' => (int) $t['tour_price'],
        );
    }

    private function tourBreadcrumbItems(array $row, $locale, $isExperience)
    {
        $pageId = $isExperience ? 4 : 3;

        return array(
            array('label' => $this->frontend_presenter->menuLabel(1), 'href' => base_url($locale)),
            array('label' => $this->frontend_presenter->menuLabel($pageId), 'href' => $this->managedPageUrl($pageId, $locale)),
            array('label' => $row['tour_name']),
        );
    }

    /**
     * Search, sharing and structured-data fields for one tour or experience.
     * A blank field falls back to the tour's own content and images: the
     * sharing image to its main image, background image, then first gallery
     * photo; the meta description to its short description, then overview.
     */
    private function prepareTourExperienceConfig(array $row, $isExperience, array $galleryRows, array $breadcrumbItems)
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $title = trim((string) $row['tour_name']);

        $images = array();
        if ($locale === 'ar') {
            $images[] = array('tours', $row['tour_image_ar']);
        }
        $images[] = array('tours', $row['tour_image']);
        if ($locale === 'ar') {
            $images[] = array('tours', $row['tour_bg_image_ar']);
        }
        $images[] = array('tours', $row['tour_bg_image']);
        foreach (array_slice($galleryRows, 0, 3) as $galleryRow) {
            $images[] = array('tour-images', $galleryRow['image_image']);
        }

        $config = $this->frontend_presenter->pageConfig($row, array(
            'page_title' => $title,
            'meta_description' => '',
            'active' => $isExperience ? 'experiences' : 'tours',
            /* GLightbox powers both the "Places & highlights" attraction popup
               and the photo gallery on this page — see js/lightbox-init.js. */
            'styles' => array('vendor/glightbox/css/glightbox.min.css'),
            'scripts' => array(
                'vendor/glightbox/js/glightbox.min.js',
                'js/lightbox-init.js',
            ),
        ), 'tours', array(
            'name' => $title,
            'description' => array(
                $row['tour_short_description'],
                $row['tour_overview'],
                $row['tour_highlights'],
            ),
            'images' => $images,
            'image_alt' => $title,
        ));

        $slugs = $this->managedSlugs($row);
        $config['breadcrumbs'] = $breadcrumbItems;
        $config['schema'] = array(
            $this->frontend_seo->tourNode(array(
                'url' => $this->frontend_seo->tourUrl($slugs[$locale === 'ar' ? 'ar' : 'en'], $locale, $isExperience),
                'name' => $title,
                'description' => isset($config['meta_description']) ? $config['meta_description'] : '',
                'tourist_type' => $this->frontendLine('tour.schema.touristType'),
                'locale_tag' => $locale === 'ar' ? 'ar-SA' : 'en',
                'image' => isset($config['og_image']) ? $config['og_image'] : '',
                'price' => isset($row['tour_price']) ? (int) $row['tour_price'] : 0,
                'currency' => $this->siteCurrency(),
            )),
        );

        return $config;
    }

    /** ISO currency code from Website Settings, used in structured-data offers. */
    private function siteCurrency()
    {
        $settings = $this->config->item('frontend_site_settings');

        return is_array($settings) && isset($settings['currency_unit'])
            ? trim((string) $settings['currency_unit'])
            : '';
    }
    /** Build the complete view model used by the public Contact page (GET). */
    private function buildContactViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Contact_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 7, true);
        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(7, $locale)
        );

        $countries = array();
        foreach ($this->Contact_model->get_countries($locale) as $row) {
            $countries[] = array(
                'id' => (int) $row['id'],
                'label' => $this->frontendText($row, 'label'),
            );
        }

        $formSettings = $this->contactFormSettings($locale);

        $errors = $this->session->flashdata('contact_errors');
        $old = $this->session->flashdata('contact_old');
        $status = $this->session->flashdata('contact_status');
        $statusMessage = $this->session->flashdata('contact_status_message');

        return array(
            'config' => $this->prepareContactConfig($page, $locale),
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'formSection' => isset($sections['contact_form']) ? $sections['contact_form'] : array(),
            'privacySection' => isset($sections['contact_form_privacy'])
                ? $sections['contact_form_privacy']
                : array(),
            'countries' => $countries,
            'subjects' => $formSettings['subjects'],
            'successMessage' => $formSettings['success_message'],
            'support' => $this->buildContactSupportCards($locale),
            'business' => $this->prepareContactBusiness($locale),
            'errors' => is_array($errors) ? $errors : array(),
            'old' => is_array($old) ? $old : array(),
            'status' => is_string($status) ? $status : '',
            'statusMessage' => is_string($statusMessage) ? $statusMessage : '',
            'formToken' => $this->contactFormToken(),
            'formAction' => base_url($locale.'/contact'),
            'recaptchaSiteKey' => RECAPTCHA_ENTERPRISE_SITE_KEY,
            'recaptchaAction' => self::CONTACT_RECAPTCHA_ACTION,
        );
    }

    /** Validate and persist a POST to the Contact page, then redirect (Post/Redirect/Get). */
    private function processContactSubmission()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $redirectUrl = base_url($locale.'/contact');
        /* CI3's own AJAX detection (the X-Requested-With header jQuery sets
           on every $.ajax() call) — not a header we invent or trust beyond
           this. A request without it (JavaScript disabled, or a direct
           POST) gets the original full-page Post/Redirect/Get behavior
           unchanged. */
        $isAjax = $this->input->is_ajax_request();

        if (!$this->consumeContactFormToken()) {
            $message = $this->frontendLine('contact.error.security');
            if ($isAjax) {
                $this->respondContactJson(403, false, array(), $message);
                return;
            }

            $this->session->set_flashdata('contact_status', 'error');
            $this->session->set_flashdata('contact_status_message', $message);
            redirect($redirectUrl);
            return;
        }

        $this->load->model('Contact_model');

        $values = $this->contactFormValues();
        $formSettings = $this->contactFormSettings($locale);
        $errors = $this->validateContactSubmission($values, $formSettings['subjects']);

        if (!empty($errors)) {
            if ($isAjax) {
                $this->respondContactJson(422, false, $errors, $this->frontendLine('contact.error.summary'));
                return;
            }
            $this->session->set_flashdata('contact_errors', $errors);
            $this->session->set_flashdata('contact_old', $values);
            redirect($redirectUrl);
            return;
        }

        if (!$this->verifyContactRecaptcha()) {
            $message = $this->frontendLine('contact.error.recaptcha');
            if ($isAjax) {
                $this->respondContactJson(403, false, array(), $message);
                return;
            }

            $this->session->set_flashdata('contact_status', 'error');
            $this->session->set_flashdata('contact_status_message', $message);
            $this->session->set_flashdata('contact_old', $values);
            redirect($redirectUrl);
            return;
        }

        $requestData = array(
            'first_name' => $values['first_name'],
            'last_name' => $values['last_name'],
            'email' => $values['email'],
            'subject' => $values['subject'],
            'phone' => $values['phone'] !== '' ? $values['phone'] : null,
            'message' => $values['message'],
            'country' => $values['country'] > 0 ? $values['country'] : null,
            'ip' => $this->input->ip_address(),
            'user_agent' => $this->safeUserAgent(),
            'website' => $locale === 'ar' ? 'Arabic' : 'English',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $insertId = $this->Contact_model->create_request($requestData);

        if (!$insertId) {
            log_message('error', 'Contact_model::create_request failed to insert a public contact request.');
            if ($isAjax) {
                $this->respondContactJson(500, false, array(), $this->frontendLine('contact.error.general'));
                return;
            }
            $this->session->set_flashdata('contact_status', 'error');
            $this->session->set_flashdata('contact_old', $values);
            redirect($redirectUrl);
            return;
        }

        $requestData['id'] = (int) $insertId;
        $this->sendContactNotification($requestData);
        $this->sendContactAcknowledgement($requestData);

        if ($isAjax) {
            $this->respondContactJson(200, true, array(), $formSettings['success_message']);
            return;
        }

        $this->session->set_flashdata('contact_status', 'success');
        redirect($redirectUrl);
    }

    /** Notify every valid administrator address configured in Website Settings. */
    private function sendContactNotification(array $request)
    {
        $settings = $this->SqlModel->getSingleRecord(
            'site_settings',
            array('id' => 1)
        );
        if (empty($settings)) {
            log_message('error', 'Contact notification email was not sent because site settings are missing.');
            return false;
        }

        $recipients = $this->contactNotificationRecipients(
            isset($settings['notification_emails'])
                ? $settings['notification_emails']
                : ''
        );
        if (empty($recipients)) {
            log_message('error', 'Contact notification email was not sent because no valid notification emails are configured.');
            return false;
        }

        $values = $this->contactShortTagValues($request, 'en');
        $requestId = $values['id'];
        $fullName = trim($values['first_name'].' '.$values['last_name']);
        $subject = 'New Contact Request: '.$values['subject'];
        $body =
            '<p>A new contact request has been submitted through the website.</p>'.
            '<table cellpadding="0" cellspacing="0" style="margin:16px 0">'.
            $this->contactNotificationRow('Request ID', $requestId).
            $this->contactNotificationRow('Name', $fullName).
            $this->contactNotificationRow('Email', $values['email']).
            $this->contactNotificationRow('Phone', $values['phone']).
            $this->contactNotificationRow('Country', $values['country']).
            $this->contactNotificationRow('Subject', $values['subject']).
            $this->contactNotificationRow('Website', $values['website']).
            '</table>'.
            '<h3>Message</h3>'.
            '<p>'.nl2br($this->escapeEmailValue($values['message']), false).'</p>';

        $this->load->library('EmailService');
        $message = $this->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => $subject,
            'body' => $body,
        ), 'English');

        if ($message === false) {
            log_message('error', 'Contact notification email template could not be rendered for request ID '.$requestId.'.');
            return false;
        }

        $sent = $this->emailservice->send(array(
            'to' => $recipients,
            'reply_to' => $values['email'],
            'reply_to_name' => $fullName,
            'subject' => $subject,
            'message' => $message,
            'alt_message' => $this->contactNotificationText($requestId, $values),
            'config' => array(
                'useragent' => trim((string) $settings['website_title']),
            ),
        ));

        if (!$sent) {
            log_message(
                'error',
                'Contact notification email failed for request ID '.$requestId.': '.
                $this->emailservice->getLastError()
            );
        }

        return $sent;
    }

    /** Send the locale-specific acknowledgement defined by email template 1. */
    private function sendContactAcknowledgement(array $request)
    {
        $template = $this->SqlModel->getSingleRecord(
            'email_templates',
            array('id' => 1)
        );
        if (empty($template)) {
            log_message('error', 'Contact acknowledgement was not sent because email template 1 is missing.');
            return false;
        }

        $isArabic = $request['website'] === 'Arabic';
        $locale = $isArabic ? 'ar' : 'en';
        $subjectField = $isArabic ? 'subject_ar' : 'subject';
        $headingField = $isArabic ? 'heading_ar' : 'heading';
        $contentsField = $isArabic ? 'contents_ar' : 'contents';
        $subjectTemplate = trim((string) $template[$subjectField]);
        $contentsTemplate = trim((string) $template[$contentsField]);

        if ($subjectTemplate === '' || $contentsTemplate === '') {
            log_message(
                'error',
                'Contact acknowledgement was not sent because email template 1 has incomplete '.
                ($isArabic ? 'Arabic' : 'English').' fields.'
            );
            return false;
        }

        $this->load->library('EmailService');
        $shortTagValues = $this->contactShortTagValues($request, $locale);
        $headerShortTagValues = array();
        $htmlShortTagValues = array();
        foreach ($shortTagValues as $field => $value) {
            $headerShortTagValues[$field] = trim(
                preg_replace('/[\r\n]+/', ' ', (string) $value)
            );
            $htmlShortTagValues[$field] = $this->escapeEmailValue($value);
        }
        $htmlShortTagValues['message'] = nl2br(
            $this->escapeEmailValue($shortTagValues['message']),
            false
        );

        $subject = $this->emailservice->parseContactShortTags(
            $subjectTemplate,
            $headerShortTagValues
        );
        $heading = $this->emailservice->parseContactShortTags(
            isset($template[$headingField]) ? $template[$headingField] : '',
            $shortTagValues
        );
        $contents = $this->emailservice->parseContactShortTags(
            $contentsTemplate,
            $htmlShortTagValues
        );
        $message = $this->emailservice->renderTemplate(array(
            'heading' => $heading,
            'body' => $contents,
        ), $isArabic ? 'Arabic' : 'English');

        if ($message === false) {
            log_message(
                'error',
                'Contact acknowledgement template could not be rendered for request ID '.
                (int) $request['id'].'.'
            );
            return false;
        }

        $sent = $this->emailservice->send(array(
            'to' => $request['email'],
            'subject' => $subject,
            'message' => $message,
            'alt_message' => $this->contactAcknowledgementText(
                $heading,
                $contents
            ),
        ));

        if (!$sent) {
            log_message(
                'error',
                'Contact acknowledgement email failed for request ID '.
                (int) $request['id'].': '.$this->emailservice->getLastError()
            );
        }

        return $sent;
    }

    private function contactShortTagValues(array $request, $locale)
    {
        $values = array(
            'id' => (int) $request['id'],
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'email' => $request['email'],
            'subject' => $request['subject'],
            'phone' => $request['phone'],
            'message' => $request['message'],
            'ip' => $request['ip'],
            'user_agent' => $request['user_agent'],
            'created_at' => $this->contactEmailDateTime($request['created_at'], $locale),
            'updated_at' => $this->contactEmailDateTime($request['updated_at'], $locale),
            'country' => $this->contactCountryName($request['country'], $locale),
            'website' => $request['website'],
        );

        foreach ($values as $field => $value) {
            if ($value === null || trim((string) $value) === '') {
                $values[$field] = $this->contactEmailMissingValue($field, $locale);
            }
        }

        return $values;
    }

    /** Date and time for an email; Arabic emails get Arabic month names and ص / م. */
    private function contactEmailDateTime($value, $locale = 'en')
    {
        $this->load->library('EmailService');

        return $this->emailservice->formatDateTime($value, $locale);
    }

    private function contactCountryName($countryId, $locale)
    {
        $countryId = (int) $countryId;
        if ($countryId <= 0) {
            return '';
        }

        $country = $this->SqlModel->getSingleRecord(
            'countries',
            array('id' => $countryId)
        );
        if (empty($country)) {
            return '';
        }

        if ($locale === 'ar' && !empty($country['name_ar'])) {
            return (string) $country['name_ar'];
        }

        return isset($country['name']) ? (string) $country['name'] : '';
    }

    private function contactAcknowledgementText($heading, $contents)
    {
        $contents = preg_replace('/<br\s*\/?>/i', "\n", (string) $contents);
        $contents = html_entity_decode(
            strip_tags($contents),
            ENT_QUOTES,
            'UTF-8'
        );

        return trim((string) $heading)."\n\n".trim($contents);
    }

    /** Split newline-separated notification addresses and retain valid emails only. */
    private function contactNotificationRecipients($value)
    {
        $recipients = array();
        $lines = preg_split('/\r\n|\n\r|\r|\n/', (string) $value);

        foreach ($lines as $line) {
            $email = trim($line);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $recipients[$email] = $email;
            }
        }

        return array_values($recipients);
    }

    private function contactNotificationRow($label, $value)
    {
        return '<tr>'.
            '<td style="padding:4px 16px 4px 0;vertical-align:top"><strong>'.
            $this->escapeEmailValue($label).
            ':</strong></td>'.
            '<td style="padding:4px 0;vertical-align:top">'.
            $this->escapeEmailValue($value).
            '</td>'.
            '</tr>';
    }

    private function contactNotificationText($requestId, array $values)
    {
        return implode("\n", array(
            'A new contact request has been submitted through the website.',
            '',
            'Request ID: '.(int) $requestId,
            'Name: '.trim($values['first_name'].' '.$values['last_name']),
            'Email: '.$values['email'],
            'Phone: '.$values['phone'],
            'Country: '.$values['country'],
            'Subject: '.$values['subject'],
            'Website: '.$values['website'],
            '',
            'Message:',
            $values['message'],
        ));
    }

    private function escapeEmailValue($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Verify the one-use Contact token and reject low-confidence traffic. */
    private function verifyContactRecaptcha()
    {
        $token = trim((string) $this->input->post('recaptcha_token'));
        if ($token === '') {
            return false;
        }

        $this->load->library('google_recaptcha');
        $result = $this->google_recaptcha->create_assessment(
            $token,
            self::CONTACT_RECAPTCHA_ACTION,
            $this->input->ip_address(),
            $this->safeUserAgent()
        );

        if (empty($result['success'])) {
            log_message(
                'error',
                'Contact reCAPTCHA Enterprise verification failed: '
                . (isset($result['message']) ? $result['message'] : 'Unknown error.')
            );
            return false;
        }

        $score = isset($result['score']) ? (float) $result['score'] : 0.0;
        if ($score < self::CONTACT_RECAPTCHA_MIN_SCORE) {
            log_message('info', 'Contact reCAPTCHA Enterprise rejected a score of ' . $score . '.');
            return false;
        }

        return true;
    }

    private function respondContactJson($status, $success, array $errors, $message)
    {
        $this->output->set_status_header($status);
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode(array(
            'success' => $success,
            'errors' => $errors,
            'message' => $message,
            'form_token' => $this->contactFormToken(),
        )));
    }

    /** Untrusted POST values, trimmed to strings — validated by validateContactSubmission(). */
    private function contactFormValues()
    {
        return array(
            'first_name' => trim((string) $this->input->post('first_name')),
            'last_name' => trim((string) $this->input->post('last_name')),
            'email' => trim((string) $this->input->post('email')),
            'country' => (int) $this->input->post('country'),
            'phone' => trim((string) $this->input->post('phone')),
            'subject' => trim((string) $this->input->post('subject')),
            'message' => trim((string) $this->input->post('message')),
        );
    }

    /** Server-side validation; returns a [field => localized message] map, empty when valid. */
    private function validateContactSubmission(array $values, array $subjects)
    {
        $this->load->model('Contact_model');
        $errors = array();

        if ($values['first_name'] === '' || mb_strlen($values['first_name']) > 255) {
            $errors['first_name'] = $this->frontendLine('contact.error.firstName');
        }
        if ($values['last_name'] === '' || mb_strlen($values['last_name']) > 255) {
            $errors['last_name'] = $this->frontendLine('contact.error.lastName');
        }
        if ($values['email'] === '' || mb_strlen($values['email']) > 100
            || !$this->validContactEmail($values['email'])
        ) {
            $errors['email'] = $this->frontendLine('contact.error.email');
        }
        if ($values['country'] > 0 && !$this->Contact_model->country_exists($values['country'])) {
            $errors['country'] = $this->frontendLine('contact.error.country');
        }
        if ($values['phone'] === ''
            || mb_strlen($values['phone']) > 50 || preg_match('/^\+[1-9]\d{6,14}$/', $values['phone']) !== 1
        ) {
            $errors['phone'] = $this->frontendLine('contact.error.phone');
        }
        $subjectValues = array_column($subjects, 'value');
        if ($values['subject'] === '' || !in_array($values['subject'], $subjectValues, true)) {
            $errors['subject'] = $this->frontendLine('contact.error.subject');
        }
        if ($values['message'] === ''
            || mb_strlen($values['message']) > 5000
            || $this->contactMessageWordCount($values['message']) < self::CONTACT_MESSAGE_MIN_WORDS
        ) {
            $errors['message'] = $this->frontendLine('contact.error.message');
        }

        return $errors;
    }

    /** Count Unicode words so English and Arabic messages use the same rule. */
    private function contactMessageWordCount($message)
    {
        $matched = preg_match_all(
            "/\\p{L}[\\p{L}\\p{M}\\p{N}'’-]*/u",
            (string) $message,
            $words
        );

        return $matched === false ? 0 : $matched;
    }

    /** Require a valid address with a dot-separated public-style domain. */
    private function validContactEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $separator = strrpos($email, '@');
        $domain = $separator !== false ? substr($email, $separator + 1) : '';

        return strpos($domain, '.') > 0 && substr($domain, -1) !== '.';
    }

    /** Return the session-bound token used only by the public Contact form. */
    private function contactFormToken()
    {
        $token = (string) $this->session->userdata('contact_form_token');
        if (strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('contact_form_token', $token);
        }

        return $token;
    }

    /** Validate and rotate the one-time Contact form token. */
    private function consumeContactFormToken()
    {
        $expected = (string) $this->session->userdata('contact_form_token');
        $submitted = (string) $this->input->post('form_token');
        $this->session->unset_userdata('contact_form_token');

        return strlen($expected) === 64
            && strlen($submitted) === 64
            && hash_equals($expected, $submitted);
    }

    /** Resolve the managed Contact settings used by display and submission. */
    private function contactFormSettings($locale)
    {
        $settings = $this->Contact_model->get_form_settings($locale);
        $subjects = $this->contactSubjects($settings);
        $successMessage = is_array($settings)
            ? trim((string) $settings['success_message'])
            : '';

        return array(
            'subjects' => $subjects,
            'success_message' => $successMessage !== ''
                ? $successMessage
                : $this->frontendLine('contact.sent'),
        );
    }

    /** Build locale-resolved subject values and labels for the Contact form. */
    private function contactSubjects($settings = null)
    {
        $subjects = array();

        if (is_array($settings)) {
            $values = preg_split('/\R/u', (string) $settings['subject_values']);
            $labels = preg_split('/\R/u', (string) $settings['subject_labels']);

            foreach ($values as $index => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $label = isset($labels[$index]) ? trim((string) $labels[$index]) : '';
                $localizedSubject = $label !== '' ? $label : $value;
                $subjects[] = array(
                    'value' => $localizedSubject,
                    'label' => $localizedSubject,
                );
            }
        }

        return $subjects;
    }

    private function prepareContactConfig(array $page, $locale)
    {
        return $this->frontend_presenter->managedPageConfig(
            $page,
            $locale,
            'contact',
            7,
            'default',
            array('select2' => true, 'phone' => true, 'validate' => true)
        );
    }

    /**
     * The three support-rail cards, built entirely from the managed
     * miscellaneous content sections (`where_we_are`, `call_or_message`,
     * `email_us`) — never from static translation strings. A section that is
     * disabled, unassigned, or has no meaningful managed content simply
     * contributes no card.
     */
    private function buildContactSupportCards($locale)
    {
        $this->load->library('content_section_service');
        $misc = $this->content_section_service->get_miscellaneous_contents($locale);

        $cards = array();
        foreach (array('where_we_are', 'call_or_message', 'email_us') as $key) {
            if (!isset($misc[$key]) || !is_array($misc[$key])) {
                continue;
            }

            $card = $this->prepareSupportCard($misc[$key], $locale);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    private function prepareSupportCard(array $section, $locale)
    {
        $eyebrow = $this->frontendText($section, 'pre_heading');
        $title = $this->frontendText($section, 'heading');
        $text = $this->frontendText($section, 'contents');
        if ($eyebrow === '' && $title === '' && $text === '') {
            return null;
        }

        $ctaLabel = $this->frontendText($section, 'button_text');
        $ctaUrl = $this->sanitizeContactUrl(isset($section['button_url']) ? $section['button_url'] : '', $locale);
        $cta = false;
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $cta = array('label' => $ctaLabel, 'href' => $ctaUrl);
            $ctaIcon = trim(isset($section['button_icon']) ? (string) $section['button_icon'] : '');
            if ($ctaIcon !== '') {
                $cta['icon'] = $ctaIcon;
            }
            $ctaIconPos = trim(isset($section['button_icon_pos']) ? (string) $section['button_icon_pos'] : '');
            if ($ctaIconPos !== '') {
                $cta['icon_pos'] = $ctaIconPos;
            }
        }

        return array(
            'icon' => trim(isset($section['icon']) ? (string) $section['icon'] : ''),
            'eyebrow' => $eyebrow,
            'title' => $title,
            'text' => $text,
            'cta' => $cta,
        );
    }

    /**
     * Only `tel:`, `mailto:`, `http(s):` links, and a site-relative path
     * (e.g. `/tours`, as `plan_with_us`/`browse_tours` store it) — the
     * schemes this project already renders safely. A relative path is
     * resolved to a locale-aware internal URL using the same locale-prefix
     * convention every other frontend link already follows.
     */
    private function sanitizeContactUrl($url, $locale = null)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^(tel|mailto|https?):/i', $url) === 1) {
            return $url;
        }
        if ($url[0] === '/' && $locale !== null) {
            $path = ltrim($url, '/');
            if (preg_match('#^(en|ar)(?:/|$)#', $path) === 1) {
                $path = preg_replace('#^(en|ar)/?#', '', $path);
            }

            return base_url(($locale === 'ar' ? 'ar/' : 'en/') . $path);
        }

        return '';
    }

    /**
     * The company/license line under the support rail, from localized
     * `site_settings` only. A missing title or license simply leaves that
     * part out — never a hard-coded business name.
     */
    private function prepareContactBusiness($locale)
    {
        $settings = (array) $this->config->item('frontend_site_settings');

        return array(
            'name' => $this->settingText($settings, 'website_title', $locale),
            'license' => $this->settingText($settings, 'license_number', $locale),
        );
    }

    private function settingText(array $settings, $key, $locale)
    {
        if ($locale === 'ar') {
            $arabic = trim(isset($settings[$key . '_ar']) ? (string) $settings[$key . '_ar'] : '');
            if ($arabic !== '') {
                return html_entity_decode($arabic, ENT_QUOTES, 'UTF-8');
            }
        }

        $value = trim(isset($settings[$key]) ? (string) $settings[$key] : '');

        return $value !== '' ? html_entity_decode($value, ENT_QUOTES, 'UTF-8') : '';
    }

    /** The request's User-Agent, trimmed to a safe storage length, or null. */
    private function safeUserAgent()
    {
        $agent = trim((string) $this->input->user_agent());

        return $agent !== '' ? mb_substr($agent, 0, 500) : null;
    }

    private function prepareBlogCategoryNav($locale)
    {
        $this->load->model('Blog_model');

        $items = array();
        foreach ($this->Blog_model->get_blog_category_nav($locale) as $row) {
            $items[] = array(
                'id' => (int) $row['cat_id'],
                'slug' => (string) $row['cat_slug'],
                'label' => $this->frontendText($row, 'cat_name'),
            );
        }

        return array('items' => $items);
    }

    /**
     * $posts are the category's posts on the current page; their titles build
     * the meta description when the category has no description or content.
     */
    private function prepareBlogCategoryConfig(array $category, $locale, array $posts = array())
    {
        $categoryName = $this->frontendText($category, 'cat_name');
        $postTitles = array();
        foreach (array_slice($posts, 0, 3) as $post) {
            $postTitles[] = $post['title'];
        }
        $postSummary = empty($postTitles)
            ? ''
            : $this->frontendLine('blog.category.meta', array(
                'category' => $categoryName,
                'posts' => implode($this->frontendLine('format.listSeparator'), $postTitles),
            ));
        $config = $this->frontend_presenter->pageConfig($category, array(
            'page_title' => $categoryName,
            'meta_description' => '',
            'active' => 'blog',
        ), 'blog-categories', array(
            'name' => $categoryName,
            'description' => array(
                isset($category['cat_desc']) ? $category['cat_desc'] : '',
                isset($category['cat_contents']) ? $category['cat_contents'] : '',
                $postSummary,
            ),
            'images' => array(
                array('blog-categories', isset($category['cat_cover_image']) ? $category['cat_cover_image'] : ''),
                array('blog-categories', isset($category['banner_background']) ? $category['banner_background'] : ''),
            ),
            'image_alt' => $categoryName,
        ));

        $crumbs = array(
            array(
                'label' => $this->frontend_presenter->menuLabel(1),
                'href' => base_url($locale === 'ar' ? 'ar' : 'en'),
            ),
            array(
                'label' => $this->frontend_presenter->menuLabel(8),
                'href' => $this->managedPageUrl(8, $locale),
            ),
            array('label' => $this->frontendText($category, 'cat_name')),
        );

        $config = $this->frontend_presenter->managedBanner(
            $config,
            $category,
            'compact',
            $crumbs,
            'blog-categories'
        );

        if (!isset($category['show_top_banner']) || (int) $category['show_top_banner'] !== 1) {
            $config['banner'] = array(
                'eyebrow' => '',
                'title' => $this->frontendText($category, 'cat_name'),
                'text' => $this->frontendText($category, 'cat_desc'),
                'crumbs' => $crumbs,
                'image' => upload_thumb(
                    'blog-categories',
                    isset($category['cat_cover_image']) ? $category['cat_cover_image'] : null,
                    1920,
                    720,
                    ''
                ),
                'overlay' => true,
                'size' => 'compact',
            );
        }

        $config['breadcrumbs'] = $crumbs;

        return $config;
    }

    /**
     * A record's English and Arabic managed slugs, for canonical and hreflang
     * URLs. A record with no Arabic slug is served at its English one.
     */
    private function managedSlugs(array $row)
    {
        $english = isset($row['seo_slug_en']) ? trim((string) $row['seo_slug_en']) : '';
        $arabic = isset($row['seo_slug_ar']) ? trim((string) $row['seo_slug_ar']) : '';

        return array(
            'en' => $english,
            'ar' => $arabic !== '' ? $arabic : $english,
        );
    }

    /**
     * Search, sharing and structured-data fields for one blog post. A blank
     * field falls back to the post's own content and images.
     */
    private function prepareBlogPostConfig(array $row, array $post, $locale)
    {
        $title = $this->frontendText($row, 'blog_name');
        $config = $this->frontend_presenter->pageConfig($row, array(
            'page_title' => $title,
            'meta_description' => '',
            'active' => 'blog',
            'og_type' => 'article',
        ), 'blogs', array(
            'name' => $title,
            'description' => array(
                isset($row['blog_short_description']) ? $row['blog_short_description'] : '',
                isset($row['blog_text']) ? $row['blog_text'] : '',
            ),
            'images' => array(
                array('blogs', isset($row['blog_image']) ? $row['blog_image'] : ''),
                array('blogs', isset($row['blog_cover_image']) ? $row['blog_cover_image'] : ''),
                array('blogs', isset($row['banner_background']) ? $row['banner_background'] : ''),
            ),
            'image_alt' => $title,
        ));

        $published = $this->frontend_seo->isoDate(
            !empty($row['blog_pdate']) ? $row['blog_pdate'] : (isset($row['blog_added']) ? $row['blog_added'] : '')
        );
        $modified = $this->frontend_seo->isoDate(isset($row['blog_updated']) ? $row['blog_updated'] : '');
        $section = isset($row['category_name']) ? trim((string) $row['category_name']) : '';

        $config['article'] = array(
            'published' => $published,
            'modified' => $modified,
            'author' => $post['author'],
            'section' => $section,
        );
        $config['breadcrumbs'] = $this->blogPostCrumbs($row, $locale);

        $slugs = $this->managedSlugs($row);
        $config['schema'] = array(
            $this->frontend_seo->articleNode(array(
                'url' => $this->frontend_seo->blogPostUrl($slugs[$locale === 'ar' ? 'ar' : 'en'], $locale),
                'headline' => $title,
                'description' => isset($config['meta_description']) ? $config['meta_description'] : '',
                'locale_tag' => $locale === 'ar' ? 'ar-SA' : 'en',
                'published' => $published,
                'modified' => $modified,
                'author' => $post['author'],
                'image' => isset($config['og_image']) ? $config['og_image'] : '',
                'section' => $section,
                'keywords' => isset($config['keywords']) ? $config['keywords'] : '',
            )),
        );

        return $config;
    }

    /** Home > Blog > (Category) > Post, shared by the banner and the structured data. */
    private function blogPostCrumbs(array $row, $locale)
    {
        $crumbs = array(
            array(
                'label' => $this->frontend_presenter->menuLabel(1),
                'href' => base_url($locale === 'ar' ? 'ar' : 'en'),
            ),
            array(
                'label' => $this->frontend_presenter->menuLabel(8),
                'href' => $this->managedPageUrl(8, $locale),
            ),
        );
        $categoryName = isset($row['category_name']) ? trim((string) $row['category_name']) : '';
        $categorySlug = isset($row['category_slug']) ? trim((string) $row['category_slug']) : '';
        if ($categoryName !== '') {
            $categoryCrumb = array('label' => $categoryName);
            if ($categorySlug !== '') {
                $categoryCrumb['href'] = $this->blogCategoryUrl($categorySlug, $locale);
            }
            $crumbs[] = $categoryCrumb;
        }
        $crumbs[] = array('label' => $this->frontendText($row, 'blog_name'));

        return $crumbs;
    }

    /** Prepare the managed miscellaneous content used by the featured-post rail. */
    private function prepareFeaturedPostsRailContent($locale)
    {
        $this->load->library('content_section_service');
        $sections = $this->content_section_service->get_miscellaneous_contents($locale);
        if (!isset($sections['featured_posts']) || !is_array($sections['featured_posts'])) {
            return array();
        }

        $section = $sections['featured_posts'];
        $linkText = $this->frontendText($section, 'link_text');
        $linkUrl = $this->sanitizeContactUrl(
            isset($section['link_url']) ? $section['link_url'] : '',
            $locale
        );

        return array(
            'icon' => trim(isset($section['icon']) ? (string) $section['icon'] : ''),
            'eyebrow' => $this->frontendText($section, 'pre_heading'),
            'title' => $this->frontendText($section, 'heading'),
            'text' => $this->frontendText($section, 'contents'),
            'link' => array(
                'text' => $linkText,
                'url' => $linkUrl,
                'icon' => trim(isset($section['link_icon']) ? (string) $section['link_icon'] : ''),
                'icon_pos' => trim(isset($section['link_icon_pos']) ? (string) $section['link_icon_pos'] : ''),
            ),
        );
    }

    private function prepareBlogCards(array $rows)
    {
        $items = array();
        foreach ($rows as $row) {
            $category = isset($row['category_name']) ? (string) $row['category_name'] : '';
            if ($category === '') {
                $category = $this->frontendLine('blog.category.uncategorized');
            }

            $items[] = array(
                'id' => (int) $row['blog_id'],
                'slug' => isset($row['blog_slug']) ? (string) $row['blog_slug'] : '',
                'title' => $this->frontendText($row, 'blog_name'),
                'excerpt' => $this->frontendText($row, 'blog_short_description'),
                'image' => upload_thumb_candidates(
                    'blogs',
                    array(
                        isset($row['blog_image']) ? $row['blog_image'] : null,
                        isset($row['blog_cover_image']) ? $row['blog_cover_image'] : null,
                    ),
                    1200,
                    675,
                    ''
                ),
                'read' => $this->frontendLine('post.readTime', array(
                    'minutes' => isset($row['blog_time_to_read']) ? (int) $row['blog_time_to_read'] : 0,
                )),
                'date' => $this->resolveBlogDate($row),
                'category' => $category,
                'category_slug' => isset($row['category_slug']) ? (string) $row['category_slug'] : '',
                'categories' => isset($row['categories']) && is_array($row['categories'])
                    ? $row['categories']
                    : array(),
            );
        }

        return $items;
    }

    private function prepareBlogPost(array $row, array $authors, $locale)
    {
        $category = isset($row['category_name']) ? (string) $row['category_name'] : '';
        if ($category === '') {
            $category = $this->frontendLine('blog.category.uncategorized');
        }

        $authorId = isset($row['blog_author']) ? (int) $row['blog_author'] : 0;

        return array(
            'id' => (int) $row['blog_id'],
            'slug' => isset($row['blog_slug']) ? (string) $row['blog_slug'] : '',
            'title' => $this->frontendText($row, 'blog_name'),
            'excerpt' => $this->frontendText($row, 'blog_short_description'),
            'body_html' => isset($row['blog_text']) ? trim((string) $row['blog_text']) : '',
            'image' => upload_thumb_candidates(
                'blogs',
                array(
                    isset($row['blog_image']) ? $row['blog_image'] : null,
                    isset($row['blog_cover_image']) ? $row['blog_cover_image'] : null,
                ),
                1200,
                675,
                ''
            ),
            'header_image' => upload_thumb_candidates(
                'blogs',
                array(
                    isset($row['blog_cover_image']) ? $row['blog_cover_image'] : null,
                    isset($row['blog_image']) ? $row['blog_image'] : null,
                ),
                1920,
                640,
                ''
            ),
            'date' => $this->resolveBlogDate($row),
            'read' => $this->frontendLine('post.readTime', array(
                'minutes' => isset($row['blog_time_to_read']) ? (int) $row['blog_time_to_read'] : 0,
            )),
            'featured' => isset($row['blog_featured']) && $row['blog_featured'] === 'Yes',
            'author' => isset($authors[$authorId]) ? $authors[$authorId] : '',
            'category' => $category,
            'category_slug' => isset($row['category_slug']) ? (string) $row['category_slug'] : '',
            'categories' => isset($row['categories']) && is_array($row['categories']) ? $row['categories'] : array(),
            'banner' => $this->prepareBlogPostBanner($row, $locale),
        );
    }

    private function prepareBlogPostBanner(array $row, $locale)
    {
        if (!isset($row['show_top_banner']) || (int) $row['show_top_banner'] !== 1) {
            return array();
        }

        $eyebrow = $this->frontendText($row, 'banner_title');
        $heading = $this->frontendText($row, 'banner_heading');
        $text = $this->frontendText($row, 'banner_text');
        if ($eyebrow === '' && $heading === '' && $text === '') {
            return array();
        }

        $image = upload_thumb_candidates(
            'blogs',
            array(
                isset($row['banner_background']) ? $row['banner_background'] : null,
                isset($row['blog_cover_image']) ? $row['blog_cover_image'] : null,
                isset($row['blog_image']) ? $row['blog_image'] : null,
            ),
            1920,
            640,
            ''
        );

        return array(
            'eyebrow' => $eyebrow,
            'title' => $heading,
            'text' => $text,
            'crumbs' => $this->blogPostCrumbs($row, $locale),
            'image' => $image,
            'overlay' => isset($row['banner_overlay']) && $row['banner_overlay'] === 'Yes',
            'size' => 'compact',
            'background_colors' => array(
                isset($row['banner_background_color_1']) ? $row['banner_background_color_1'] : '',
                isset($row['banner_background_color_2']) ? $row['banner_background_color_2'] : '',
            ),
            'eyebrow_colors' => array(
                isset($row['banner_title_color_1']) ? $row['banner_title_color_1'] : '',
                isset($row['banner_title_color_2']) ? $row['banner_title_color_2'] : '',
            ),
            'heading_colors' => array(
                isset($row['banner_heading_color_1']) ? $row['banner_heading_color_1'] : '',
                isset($row['banner_heading_color_2']) ? $row['banner_heading_color_2'] : '',
            ),
            'text_colors' => array(
                isset($row['banner_text_color_1']) ? $row['banner_text_color_1'] : '',
                isset($row['banner_text_color_2']) ? $row['banner_text_color_2'] : '',
            ),
        );
    }

    private function resolveBlogDate(array $row)
    {
        $date = trim(isset($row['blog_pdate']) ? (string) $row['blog_pdate'] : '');
        if ($date === '' || strpos($date, '0000-00-00') === 0) {
            $date = isset($row['blog_added']) ? (string) $row['blog_added'] : '';
        }

        return substr($date, 0, 10);
    }

    /** Build the complete view model used by the public FAQs page. */
    private function buildFaqsViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Faq_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 6, true);

        $faqGroups = array();
        foreach ($this->Faq_model->get_faq_groups($locale) as $group) {
            $items = array();
            foreach ($group['items'] as $item) {
                $question = $this->frontendText($item, 'question');
                $answer = $this->frontendText($item, 'answer');
                if ($question === '' || $answer === '') {
                    continue;
                }
                $items[] = array('id' => (int) $item['id'], 'q' => $question, 'a' => $answer);
            }
            if (empty($items)) {
                continue;
            }

            $faqGroups[] = array(
                'id' => (int) $group['id'],
                'title' => $this->frontendText($group, 'title'),
                'description' => $this->frontendPlainText($group, 'description'),
                'items' => $items,
            );
        }

        $misc = $this->content_section_service->get_miscellaneous_contents($locale);
        $supportCard = isset($misc['still_need_help']) && is_array($misc['still_need_help'])
            ? $this->prepareSupportCard($misc['still_need_help'], $locale)
            : null;

        return array(
            'config' => $this->frontend_presenter->managedPageConfig(
                $page,
                $locale,
                'faqs',
                6,
                'default'
            ),
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'faqGroups' => $faqGroups,
            'supportCard' => $supportCard,
            'faqSchemaJson' => $this->prepareFaqSchema($faqGroups),
        );
    }

    /** Build the managed content and supporting panels for the Privacy Policy. */
    private function buildPrivacyPolicyViewData()
    {
        return $this->buildLegalPolicyViewData(
            9,
            'privacy_policy_card',
            'privacy-section'
        );
    }

    /** Build the managed content and supporting panels for the Cancellation Policy. */
    private function buildCancellationPolicyViewData()
    {
        return $this->buildLegalPolicyViewData(
            10,
            'cancellation_policy_card',
            'cancellation-section'
        );
    }

    /** Build the shared view model used by CMS-managed legal policy pages. */
    private function buildLegalPolicyViewData($pageId, $supportCardKey, $sectionIdPrefix)
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', (int) $pageId, true);
        $content = $this->prepareLegalContent(
            isset($page['page_text']) ? $page['page_text'] : '',
            $sectionIdPrefix
        );
        $misc = $this->content_section_service->get_miscellaneous_contents($locale);

        $supportCard = isset($misc[$supportCardKey])
            && is_array($misc[$supportCardKey])
            ? $this->prepareSupportCard($misc[$supportCardKey], $locale)
            : null;
        $contact = isset($misc['get_in_touch']) && is_array($misc['get_in_touch'])
            ? $this->prepareLegalContact($misc['get_in_touch'], $locale)
            : null;

        $config = $this->frontend_presenter->managedPageConfig(
            $page,
            $locale,
            '',
            $pageId
        );
        $config['page_heading'] = $this->frontend_presenter->text($page, 'page_name');

        return array(
            'config' => $config,
            'legalIntroHtml' => $content['intro_html'],
            'legalSections' => $content['sections'],
            'legalSupportCard' => $supportCard,
            'legalContact' => $contact,
        );
    }

    /**
     * Split managed policy HTML at its H2 headings. The headings become the
     * table of contents while all other editor markup remains unchanged.
     */
    private function prepareLegalContent($html, $idPrefix)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return array('intro_html' => '', 'sections' => array());
        }

        $parts = preg_split(
            '/(<h2\b[^>]*>.*?<\/h2>)/isu',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        if (!is_array($parts)) {
            return array('intro_html' => $html, 'sections' => array());
        }

        $intro = '';
        $sections = array();
        $current = -1;
        foreach ($parts as $part) {
            if (preg_match('/^<h2\b[^>]*>(.*?)<\/h2>$/isu', $part, $match) === 1) {
                $title = $this->frontendPlainText(array('heading' => $match[1]), 'heading');
                if ($title === '') {
                    if ($current >= 0) {
                        $sections[$current]['html'] .= $part;
                    } else {
                        $intro .= $part;
                    }
                    continue;
                }

                $sections[] = array(
                    'id' => $idPrefix . '-' . (count($sections) + 1),
                    'title' => $title,
                    'html' => '',
                );
                $current = count($sections) - 1;
                continue;
            }

            if ($current >= 0) {
                $sections[$current]['html'] .= $part;
            } else {
                $intro .= $part;
            }
        }

        return array(
            'intro_html' => trim($intro),
            'sections' => $sections,
        );
    }

    /** Prepare the policy contact panel without supplying static content. */
    private function prepareLegalContact(array $section, $locale)
    {
        $settings = (array) $this->config->item('frontend_site_settings');
        $phone = $this->settingText($settings, 'phone', $locale);
        $phoneHref = preg_replace('/[^0-9+]/', '', $phone);
        $contact = array(
            'title' => $this->frontendText($section, 'heading'),
            'text' => $this->frontendText($section, 'contents'),
            'email' => $this->settingText($settings, 'email', $locale),
            'phone' => $phone,
            'phone_href' => is_string($phoneHref) ? $phoneHref : '',
        );

        if ($contact['title'] === '' && $contact['text'] === ''
            && $contact['email'] === '' && $contact['phone'] === ''
        ) {
            return null;
        }

        return $contact;
    }

    /**
     * Safely encoded FAQPage JSON-LD built from exactly the FAQ groups the
     * page is about to render — never from raw model rows independently.
     * Returns '' when there is nothing valid to publish, so the view omits
     * the script tag entirely rather than printing an empty FAQPage.
     */
    private function prepareFaqSchema(array $faqGroups)
    {
        $entities = array();
        foreach ($faqGroups as $group) {
            foreach ($group['items'] as $item) {
                $entities[] = array(
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => array('@type' => 'Answer', 'text' => $item['a']),
                );
            }
        }

        if (empty($entities)) {
            return '';
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        );

        return json_encode(
            $schema,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /** CI session key holding the visitor's own, unguessable Plan Your Trip draft token. Never exposed in the URL. */
    const PLAN_SESSION_KEY = 'plan_your_visit_session_token';

    /** Build the complete view model used by the public Plan Your Trip page (GET). */
    private function buildPlanYourTripViewData()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';

        $this->load->model('Webpage_model');
        $this->load->model('Plan_your_visit_model');
        $this->load->model('Contact_model');
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page($locale, 'id', 5, true);
        $sections = $this->frontend_presenter->sections(
            $this->content_section_service->get_web_page_sections(5, $locale)
        );
        $config = $this->preparePlanConfig($page, $locale);
        $support = $this->buildPlanSupportCards($locale);
        $cta = $this->buildPlanCtaBand($locale);

        if ($this->session->flashdata('plan_completed') === true) {
            return array(
                'config' => $config,
                'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
                'sections' => $sections,
                'sectionOrder' => array_keys($sections),
                'countries' => array(),
                'languages' => array(),
                'timeOptions' => array(),
                'interestOptions' => array(),
                'interestsLabel' => '',
                'timeLabel' => '',
                'support' => $support,
                'cta' => $cta,
                'saveStepUrl' => $this->managedPageUrl(5, $locale) . '/save-step',
                'formAction' => $this->managedPageUrl(5, $locale),
                'formToken' => '',
                'recaptchaSiteKey' => RECAPTCHA_ENTERPRISE_SITE_KEY,
                'recaptchaAction' => self::PLAN_RECAPTCHA_ACTION,
                'form' => array(
                    'values' => array(),
                    'errors' => array(),
                    'activeStep' => 4,
                    'completed' => true,
                    'successMessage' => (string) $this->session->flashdata('plan_success_message'),
                ),
            );
        }

        $token = $this->planDraftToken();
        $draft = $token !== '' ? $this->Plan_your_visit_model->find_by_session_token($token) : null;
        if ($token !== '' && ($draft === null || (int) $draft['step_completed'] >= 4)) {
            $this->clearPlanDraftToken();
            $draft = null;
        }

        $flashErrors = $this->session->flashdata('plan_errors');
        $flashOld = $this->session->flashdata('plan_old');

        $activeStep = 1;
        $values = $this->emptyPlanValues();
        if (is_array($flashOld)) {
            $values = array_merge($values, $flashOld);
            $activeStep = isset($flashOld['_active_step']) ? max(1, min(4, (int) $flashOld['_active_step'])) : 1;
        } elseif ($draft !== null) {
            $values = $this->planDraftToViewValues($draft, $locale);
            $activeStep = max(1, min(4, (int) $draft['step_completed'] + 1));
        }

        $countries = array();
        foreach ($this->Contact_model->get_countries($locale) as $row) {
            $countries[] = array('id' => (int) $row['id'], 'label' => $this->frontendText($row, 'label'));
        }

        $languages = array();
        foreach ($this->Plan_your_visit_model->get_languages($locale) as $row) {
            $languages[] = array('id' => (int) $row['lang_id'], 'label' => $this->frontendText($row, 'label'));
        }

        $settings = $this->Plan_your_visit_model->get_form_settings();
        $interestOptions = $this->planSettingOptions(
            $settings,
            'pyt_interests',
            'pyt_interests_ar',
            $locale
        );
        $timeOptions = $this->planSettingOptions(
            $settings,
            'pyt_visit_time',
            'pyt_visit_time_ar',
            $locale
        );

        return array(
            'config' => $config,
            'pageContent' => isset($page['page_text']) ? (string) $page['page_text'] : '',
            'sections' => $sections,
            'sectionOrder' => array_keys($sections),
            'countries' => $countries,
            'languages' => $languages,
            'timeOptions' => $timeOptions,
            'interestOptions' => $interestOptions,
            'interestsLabel' => $this->frontendLine('plan.field.interests'),
            'timeLabel' => $this->frontendLine('plan.field.slot'),
            'support' => $support,
            'cta' => $cta,
            'saveStepUrl' => $this->managedPageUrl(5, $locale) . '/save-step',
            'formAction' => $this->managedPageUrl(5, $locale),
            'formToken' => $this->planFormToken(),
            'recaptchaSiteKey' => RECAPTCHA_ENTERPRISE_SITE_KEY,
            'recaptchaAction' => self::PLAN_RECAPTCHA_ACTION,
            'form' => array(
                'values' => $values,
                'errors' => is_array($flashErrors) ? $flashErrors : array(),
                'activeStep' => $activeStep,
                'completed' => false,
                'successMessage' => '',
            ),
        );
    }

    /** Final, revalidated, transactional Submit — a plain POST/Redirect/Get, never AJAX. */
    private function processPlanFinalSubmit()
    {
        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $redirectUrl = $this->managedPageUrl(5, $locale);
        /* Same AJAX detection as processContactSubmission(): a request without
           the X-Requested-With header (JavaScript disabled, or a direct POST)
           keeps the original full-page Post/Redirect/Get behavior. */
        $isAjax = $this->input->is_ajax_request();

        $this->load->model('Plan_your_visit_model');
        $this->load->model('Contact_model');

        $step1 = $this->planStep1Values();
        $step2 = $this->planStep2Values();
        $step3 = $this->planStep3Values();
        $step4 = $this->planStep4Values();
        $old = array_merge(
            $step1,
            $step2,
            array('interests' => $step3['interests'], 'preferred_slot' => $step3['preferred_time']),
            array('notes' => $step4['message'])
        );

        if (!$this->consumePlanFormToken()) {
            if ($isAjax) {
                return $this->respondPlanJson(403, array(
                    'success' => false,
                    'expired' => true,
                    'errors' => array(),
                    'message' => $this->frontendLine('plan.error.security'),
                ));
            }

            $old['_active_step'] = 4;
            $this->session->set_flashdata('plan_errors', array(
                '_general' => $this->frontendLine('plan.error.security'),
            ));
            $this->session->set_flashdata('plan_old', $old);
            redirect($redirectUrl);
            return;
        }

        $errors = array_merge(
            $this->validatePlanStep1($step1),
            $this->validatePlanStep2($step2),
            $this->validatePlanStep3($step3),
            $this->validatePlanStep4($step4)
        );

        if (!empty($errors)) {
            if ($isAjax) {
                return $this->respondPlanJson(422, array(
                    'success' => false,
                    'errors' => $errors,
                    'message' => $this->frontendLine('plan.error.summary'),
                ));
            }

            $old['_active_step'] = $this->firstInvalidPlanStep($errors);
            $this->session->set_flashdata('plan_errors', $errors);
            $this->session->set_flashdata('plan_old', $old);
            redirect($redirectUrl);
            return;
        }

        if (!$this->verifyPlanRecaptcha()) {
            if ($isAjax) {
                return $this->respondPlanJson(403, array(
                    'success' => false,
                    'errors' => array(),
                    'message' => $this->frontendLine('plan.error.recaptcha'),
                ));
            }

            $old['_active_step'] = 4;
            $this->session->set_flashdata('plan_errors', array(
                '_general' => $this->frontendLine('plan.error.recaptcha'),
            ));
            $this->session->set_flashdata('plan_old', $old);
            redirect($redirectUrl);
            return;
        }

        $fullData = array(
            'name' => $step1['name'],
            'email' => $step1['email'],
            'phone' => $step1['phone'],
            'country' => $step1['country'] > 0 ? $step1['country'] : null,
            'arrival_date' => $step2['arrival_date'] !== '' ? $step2['arrival_date'] : null,
            'departure_date' => $step2['departure_date'] !== '' ? $step2['departure_date'] : null,
            'guests' => $step2['guests'],
            'preferred_language' => $this->resolvePlanLanguageStorage($step2['preferred_language']),
            'interests' => !empty($step3['interests']) ? implode(', ', $step3['interests']) : null,
            'preferred_time' => $step3['preferred_time'] !== '' ? $step3['preferred_time'] : null,
            'message' => $step4['message'] !== '' ? $step4['message'] : null,
        );

        $token = $this->planDraftToken();
        $draft = $token !== '' ? $this->Plan_your_visit_model->find_by_session_token($token) : null;
        if ($token !== '' && ($draft === null || (int) $draft['step_completed'] >= 4)) {
            $token = '';
            $draft = null;
        }

        if ($draft !== null) {
            $ok = $this->Plan_your_visit_model->complete_request($token, $fullData);
        } else {
            $token = $this->createPlanDraftWithToken(array_merge($fullData, array('step_completed' => 4)), $locale);
            $ok = $token !== null;
        }

        if (!$ok) {
            log_message('error', 'Plan_your_visit_model final completion failed.');

            if ($isAjax) {
                return $this->respondPlanJson(500, array(
                    'success' => false,
                    'errors' => array(),
                    'message' => $this->frontendLine('plan.error.saveFailed'),
                ));
            }

            $old['_active_step'] = 4;
            $this->session->set_flashdata('plan_errors', array('_general' => $this->frontendLine('plan.error.saveFailed')));
            $this->session->set_flashdata('plan_old', $old);
            redirect($redirectUrl);
            return;
        }

        $completedRequest = $this->Plan_your_visit_model->find_by_session_token($token);
        if ($completedRequest === null) {
            log_message('error', 'Completed Plan Your Trip request could not be reloaded for email delivery.');
        } else {
            $this->sendPlanAdminNotification($completedRequest, true);
            $this->sendPlanAcknowledgement($completedRequest);
        }

        $this->clearPlanDraftToken();

        $settings = $this->Plan_your_visit_model->get_form_settings();
        $successMessage = '';
        if (is_array($settings)) {
            $successMessage = trim((string) ($locale === 'ar' ? $settings['plan_success_ar'] : $settings['plan_success']));
        }

        if ($isAjax) {
            return $this->respondPlanJson(200, array(
                'success' => true,
                'completed' => true,
                'errors' => array(),
                'message' => $successMessage !== '' ? $successMessage : $this->frontendLine('plan.completed.fallbackLabel'),
            ));
        }

        $this->session->set_flashdata('plan_completed', true);
        $this->session->set_flashdata('plan_success_message', $successMessage);
        redirect($redirectUrl);
    }

    private function respondPlanStep1($token, $draft, $locale)
    {
        $values = $this->planStep1Values();
        $errors = $this->validatePlanStep1($values);
        if (!empty($errors)) {
            return $this->respondPlanJson(422, array(
                'success' => false, 'saved_step' => 1, 'next_step' => null,
                'errors' => $errors, 'message' => $this->frontendLine('plan.error.summary'),
            ));
        }

        $columns = array(
            'name' => $values['name'],
            'email' => $values['email'],
            'phone' => $values['phone'],
            'country' => $values['country'] > 0 ? $values['country'] : null,
        );

        if ($draft === null) {
            $newToken = $this->createPlanDraftWithToken(array_merge($columns, array('step_completed' => 1)), $locale);
            if ($newToken === null) {
                log_message('error', 'Plan_your_visit_model::create_draft failed on step 1.');

                return $this->respondPlanJson(500, array(
                    'success' => false, 'errors' => array(), 'message' => $this->frontendLine('plan.error.saveFailed'),
                ));
            }
            $this->setPlanDraftToken($newToken);

            $createdDraft = $this->Plan_your_visit_model->find_by_session_token($newToken);
            if ($createdDraft === null) {
                log_message('error', 'Plan Your Trip step-1 draft could not be reloaded for admin notification.');
            } else {
                $this->sendPlanAdminNotification($createdDraft, false);
            }
        } else {
            $this->load->model('Plan_your_visit_model');
            if (!$this->Plan_your_visit_model->update_draft($token, 1, $columns)) {
                log_message('error', 'Plan_your_visit_model::update_draft failed on step 1.');

                return $this->respondPlanJson(500, array(
                    'success' => false, 'errors' => array(), 'message' => $this->frontendLine('plan.error.saveFailed'),
                ));
            }
        }

        return $this->respondPlanJson(200, array(
            'success' => true, 'saved_step' => 1, 'next_step' => 2, 'errors' => array(), 'message' => '',
        ));
    }

    private function respondPlanStep2($token)
    {
        $this->load->model('Plan_your_visit_model');

        $values = $this->planStep2Values();
        $errors = $this->validatePlanStep2($values);
        if (!empty($errors)) {
            return $this->respondPlanJson(422, array(
                'success' => false, 'saved_step' => 2, 'next_step' => null,
                'errors' => $errors, 'message' => $this->frontendLine('plan.error.summary'),
            ));
        }

        $columns = array(
            'arrival_date' => $values['arrival_date'] !== '' ? $values['arrival_date'] : null,
            'departure_date' => $values['departure_date'] !== '' ? $values['departure_date'] : null,
            'guests' => $values['guests'],
            'preferred_language' => $this->resolvePlanLanguageStorage($values['preferred_language']),
        );

        if (!$this->Plan_your_visit_model->update_draft($token, 2, $columns)) {
            log_message('error', 'Plan_your_visit_model::update_draft failed on step 2.');

            return $this->respondPlanJson(500, array(
                'success' => false, 'errors' => array(), 'message' => $this->frontendLine('plan.error.saveFailed'),
            ));
        }

        return $this->respondPlanJson(200, array(
            'success' => true, 'saved_step' => 2, 'next_step' => 3, 'errors' => array(), 'message' => '',
        ));
    }

    private function respondPlanStep3($token)
    {
        $this->load->model('Plan_your_visit_model');

        $values = $this->planStep3Values();
        $errors = $this->validatePlanStep3($values);
        if (!empty($errors)) {
            return $this->respondPlanJson(422, array(
                'success' => false, 'saved_step' => 3, 'next_step' => null,
                'errors' => $errors, 'message' => $this->frontendLine('plan.error.summary'),
            ));
        }

        $columns = array(
            'interests' => !empty($values['interests']) ? implode(', ', $values['interests']) : null,
            'preferred_time' => $values['preferred_time'] !== '' ? $values['preferred_time'] : null,
        );

        if (!$this->Plan_your_visit_model->update_draft($token, 3, $columns)) {
            log_message('error', 'Plan_your_visit_model::update_draft failed on step 3.');

            return $this->respondPlanJson(500, array(
                'success' => false, 'errors' => array(), 'message' => $this->frontendLine('plan.error.saveFailed'),
            ));
        }

        return $this->respondPlanJson(200, array(
            'success' => true, 'saved_step' => 3, 'next_step' => 4, 'errors' => array(), 'message' => '',
        ));
    }

    /** Send the English step-1 or final Plan Your Trip notice to all administrators. */
    private function sendPlanAdminNotification(array $request, $completed)
    {
        $settings = $this->SqlModel->getSingleRecord(
            'site_settings',
            array('id' => 1)
        );
        if (empty($settings)) {
            log_message('error', 'Plan Your Trip admin email was not sent because site settings are missing.');
            return false;
        }

        $recipients = $this->contactNotificationRecipients(
            isset($settings['notification_emails'])
                ? $settings['notification_emails']
                : ''
        );
        if (empty($recipients)) {
            log_message('error', 'Plan Your Trip admin email was not sent because no valid notification emails are configured.');
            return false;
        }

        $values = $this->planEmailShortTagValues($request, 'en');
        $requestId = (int) $values['id'];
        $name = trim(preg_replace('/[\r\n]+/', ' ', (string) $values['name']));
        $subject = $completed
            ? 'Completed Plan Your Trip Request: '.$name
            : 'New Plan Your Trip Request (Step 1): '.$name;
        $intro = $completed
            ? 'A visitor has completed the Plan Your Trip form.'
            : 'A visitor has completed step 1 of the Plan Your Trip form.';
        $details = array(
            'Request ID' => $requestId,
            'Step Completed' => $values['step_completed'].' of 4',
            'Name' => $values['name'],
            'Email' => $values['email'],
            'Phone' => $values['phone'],
            'Country' => $values['country'],
            'Website' => $values['website'],
        );

        if ($completed) {
            $details = array_merge($details, array(
                'Arrival Date' => $values['arrival_date'],
                'Departure Date' => $values['departure_date'],
                'Guests' => $values['guests'],
                'Preferred Language' => $values['preferred_language'],
                'Preferred Time' => $values['preferred_time'],
                'Interests' => $values['interests'],
            ));
        }

        $rows = '';
        $textLines = array($intro, '');
        foreach ($details as $label => $value) {
            $rows .= $this->contactNotificationRow($label, $value);
            $textLines[] = $label.': '.$value;
        }

        $body = '<p>'.$intro.'</p>'.
            '<table cellpadding="0" cellspacing="0" style="margin:16px 0">'.
            $rows.
            '</table>';
        if ($completed) {
            $body .= '<h3>Additional Notes</h3>'.
                '<p>'.nl2br($this->escapeEmailValue($values['message']), false).'</p>';
            $textLines[] = '';
            $textLines[] = 'Additional Notes:';
            $textLines[] = $values['message'];
        }

        $this->load->library('EmailService');
        $message = $this->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => $subject,
            'body' => $body,
        ), 'English');
        if ($message === false) {
            log_message('error', 'Plan Your Trip admin template could not be rendered for request ID '.$requestId.'.');
            return false;
        }

        $sent = $this->emailservice->send(array(
            'to' => $recipients,
            'reply_to' => $values['email'],
            'reply_to_name' => $name,
            'subject' => $subject,
            'message' => $message,
            'alt_message' => implode("\n", $textLines),
            'config' => array(
                'useragent' => trim((string) $settings['website_title']),
            ),
        ));

        if (!$sent) {
            log_message(
                'error',
                'Plan Your Trip admin email failed for request ID '.$requestId.': '.
                $this->emailservice->getLastError()
            );
        }

        return $sent;
    }

    /** Send the locale-specific completed-form acknowledgement from email template 2. */
    private function sendPlanAcknowledgement(array $request)
    {
        $template = $this->SqlModel->getSingleRecord(
            'email_templates',
            array('id' => 2)
        );
        if (empty($template)) {
            log_message('error', 'Plan Your Trip acknowledgement was not sent because email template 2 is missing.');
            return false;
        }

        $isArabic = $request['website'] === 'Arabic';
        $locale = $isArabic ? 'ar' : 'en';
        $subjectField = $isArabic ? 'subject_ar' : 'subject';
        $headingField = $isArabic ? 'heading_ar' : 'heading';
        $contentsField = $isArabic ? 'contents_ar' : 'contents';
        $subjectTemplate = trim((string) $template[$subjectField]);
        $contentsTemplate = trim((string) $template[$contentsField]);

        if ($subjectTemplate === '' || $contentsTemplate === '') {
            log_message(
                'error',
                'Plan Your Trip acknowledgement was not sent because email template 2 has incomplete '.
                ($isArabic ? 'Arabic' : 'English').' fields.'
            );
            return false;
        }

        $this->load->library('EmailService');
        $shortTagValues = $this->planEmailShortTagValues($request, $locale);
        $headerShortTagValues = array();
        $htmlShortTagValues = array();
        foreach ($shortTagValues as $field => $value) {
            $headerShortTagValues[$field] = trim(
                preg_replace('/[\r\n]+/', ' ', (string) $value)
            );
            $htmlShortTagValues[$field] = $this->escapeEmailValue($value);
        }

        $subject = $this->emailservice->parsePlanYourTripShortTags(
            $subjectTemplate,
            $headerShortTagValues
        );
        $heading = $this->emailservice->parsePlanYourTripShortTags(
            isset($template[$headingField]) ? $template[$headingField] : '',
            $headerShortTagValues
        );
        $contents = $this->emailservice->parsePlanYourTripShortTags(
            $contentsTemplate,
            $htmlShortTagValues
        );
        $message = $this->emailservice->renderTemplate(array(
            'heading' => $heading,
            'body' => $contents,
        ), $isArabic ? 'Arabic' : 'English');

        if ($message === false) {
            log_message(
                'error',
                'Plan Your Trip acknowledgement template could not be rendered for request ID '.
                (int) $request['id'].'.'
            );
            return false;
        }

        $sent = $this->emailservice->send(array(
            'to' => $request['email'],
            'subject' => $subject,
            'message' => $message,
            'alt_message' => $this->contactAcknowledgementText($heading, $contents),
        ));

        if (!$sent) {
            log_message(
                'error',
                'Plan Your Trip acknowledgement email failed for request ID '.
                (int) $request['id'].': '.$this->emailservice->getLastError()
            );
        }

        return $sent;
    }

    /** Build localized, human-readable Plan Your Trip email placeholder values. */
    private function planEmailShortTagValues(array $request, $locale)
    {
        $values = array(
            'id' => isset($request['id']) ? (int) $request['id'] : 0,
            'name' => isset($request['name']) ? $request['name'] : '',
            'email' => isset($request['email']) ? $request['email'] : '',
            'phone' => isset($request['phone']) ? $request['phone'] : '',
            'country' => $this->contactCountryName(
                isset($request['country']) ? $request['country'] : 0,
                $locale
            ),
            'arrival_date' => $this->planEmailDate(
                isset($request['arrival_date']) ? $request['arrival_date'] : '',
                $locale
            ),
            'departure_date' => $this->planEmailDate(
                isset($request['departure_date']) ? $request['departure_date'] : '',
                $locale
            ),
            'guests' => isset($request['guests']) ? $request['guests'] : '',
            'step_completed' => isset($request['step_completed']) ? (int) $request['step_completed'] : 0,
            'preferred_language' => $this->planEmailLanguage(
                isset($request['preferred_language']) ? $request['preferred_language'] : '',
                $locale
            ),
            'preferred_time' => isset($request['preferred_time']) ? $request['preferred_time'] : '',
            'interests' => isset($request['interests']) ? $request['interests'] : '',
            'message' => isset($request['message']) ? $request['message'] : '',
            'created_at' => $this->contactEmailDateTime(
                isset($request['created_at']) ? $request['created_at'] : '',
                $locale
            ),
            'updated_at' => $this->contactEmailDateTime(
                isset($request['updated_at']) ? $request['updated_at'] : '',
                $locale
            ),
            'ip' => isset($request['ip']) ? $request['ip'] : '',
            'user_agent' => isset($request['user_agent']) ? $request['user_agent'] : '',
            'website' => isset($request['website']) ? $request['website'] : '',
        );

        foreach ($values as $field => $value) {
            if ($value === null || trim((string) $value) === '') {
                $values[$field] = $this->planEmailMissingValue($field, $locale);
            }
        }

        return $values;
    }

    /** The `frontend` language catalogue for an arbitrary locale, cached per locale for the request. */
    private function frontendLangCatalog($locale)
    {
        static $catalogs = array();

        if (isset($catalogs[$locale])) {
            return $catalogs[$locale];
        }

        $idiom = $locale === 'ar' ? 'arabic' : 'english';
        $catalog = $this->lang->load('frontend', $idiom, true);

        $catalogs[$locale] = is_array($catalog) ? $catalog : array();

        return $catalogs[$locale];
    }

    /** Natural fallback copy for optional or unexpectedly empty Plan Your Trip email fields. */
    private function planEmailMissingValue($field, $locale)
    {
        return $this->frontendEmailMissingValue('plan.email.missing.', $field, $locale);
    }

    /** Natural fallback copy for optional or unexpectedly empty Contact email fields. */
    private function contactEmailMissingValue($field, $locale)
    {
        return $this->frontendEmailMissingValue('contact.email.missing.', $field, $locale);
    }

    /** Shared lookup behind planEmailMissingValue() and contactEmailMissingValue(). */
    private function frontendEmailMissingValue($keyPrefix, $field, $locale)
    {
        $catalog = $this->frontendLangCatalog($locale);

        $key = $keyPrefix.$field;
        if (isset($catalog[$key]) && is_string($catalog[$key]) && trim($catalog[$key]) !== '') {
            return $catalog[$key];
        }

        $defaultKey = $keyPrefix.'default';

        return isset($catalog[$defaultKey]) && is_string($catalog[$defaultKey])
            ? $catalog[$defaultKey]
            : 'Not provided';
    }

    /** The trip date shown to the visitor, with Arabic month names for Arabic emails. */
    private function planEmailDate($value, $locale)
    {
        if (trim((string) $value) === '') {
            return '';
        }

        $this->load->library('EmailService');

        return $this->emailservice->formatDate($value, $locale);
    }

    private function planEmailLanguage($storedLanguage, $locale)
    {
        $storedLanguage = trim((string) $storedLanguage);
        if ($storedLanguage === '' || $locale !== 'ar') {
            return $storedLanguage;
        }

        $this->load->model('Plan_your_visit_model');
        foreach ($this->Plan_your_visit_model->get_languages('ar') as $language) {
            if ((string) $language['name_en'] === $storedLanguage) {
                return (string) $language['label'];
            }
        }

        return $storedLanguage;
    }

    private function preparePlanConfig(array $page, $locale)
    {
        return $this->frontend_presenter->managedPageConfig(
            $page,
            $locale,
            'plan',
            5,
            'default',
            array(
                'select2' => true,
                'phone' => true,
                'datepicker' => true,
                'scripts' => array('js/plan-your-trip.js'),
            )
        );
    }

    private function buildPlanSupportCards($locale)
    {
        $this->load->library('content_section_service');
        $misc = $this->content_section_service->get_miscellaneous_contents($locale);

        $cards = array();
        foreach (array('browse_tours', 'talk_to_us') as $key) {
            if (!isset($misc[$key]) || !is_array($misc[$key])) {
                continue;
            }
            $card = $this->prepareSupportCard($misc[$key], $locale);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    private function buildPlanCtaBand($locale)
    {
        $this->load->library('content_section_service');
        $misc = $this->content_section_service->get_miscellaneous_contents($locale);
        if (!isset($misc['plan_with_us']) || !is_array($misc['plan_with_us'])) {
            return null;
        }
        $section = $misc['plan_with_us'];

        $eyebrow = $this->frontendText($section, 'pre_heading');
        $title = $this->frontendText($section, 'heading');
        $text = $this->frontendText($section, 'contents');
        if ($eyebrow === '' && $title === '' && $text === '') {
            return null;
        }

        $primary = $this->prepareCtaButton($section, 'button_1', $locale);
        if ($primary === null) {
            return null;
        }
        $secondary = $this->prepareCtaButton($section, 'button_2', $locale);

        return array(
            'eyebrow' => $eyebrow,
            'title' => $title,
            'text' => $text,
            'primary' => $primary,
            'secondary' => $secondary !== null ? $secondary : false,
        );
    }

    private function prepareCtaButton(array $section, $prefix, $locale)
    {
        $label = $this->frontendText($section, $prefix . '_text');
        $url = $this->sanitizeContactUrl(isset($section[$prefix . '_url']) ? $section[$prefix . '_url'] : '', $locale);
        if ($label === '' || $url === '') {
            return null;
        }

        return array('label' => $label, 'href' => $url);
    }

    private function planDraftToken()
    {
        $token = $this->session->userdata(self::PLAN_SESSION_KEY);

        return is_string($token) ? $token : '';
    }

    private function setPlanDraftToken($token)
    {
        $this->session->set_userdata(self::PLAN_SESSION_KEY, (string) $token);
    }

    private function clearPlanDraftToken()
    {
        $this->session->unset_userdata(self::PLAN_SESSION_KEY);
    }

    private function generatePlanToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Insert one new plan_your_visit row with a fresh, verified-unique
     * token, retrying a bounded number of times on the practically
     * impossible chance of a collision or a transient insert failure.
     * $columns must already contain every validated, server-owned value
     * this insert needs except `session_token`/`website`/`ip`/`user_agent`/
     * the timestamps, which are always set here. Returns the token used, or
     * null when every attempt failed.
     */
    private function createPlanDraftWithToken(array $columns, $locale)
    {
        $this->load->model('Plan_your_visit_model');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $token = $this->generatePlanToken();
            if ($this->Plan_your_visit_model->session_token_exists($token)) {
                continue;
            }

            $payload = array_merge($columns, array(
                'session_token' => $token,
                'website' => $locale === 'ar' ? 'Arabic' : 'English',
                'ip' => $this->input->ip_address(),
                'user_agent' => $this->safeUserAgent(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));

            if ($this->Plan_your_visit_model->create_draft($payload)) {
                return $token;
            }
        }

        return null;
    }

    private function emptyPlanValues()
    {
        return array(
            'name' => '', 'email' => '', 'phone' => '', 'country' => 0,
            'arrival_date' => '', 'departure_date' => '', 'guests' => 2, 'language' => 0,
            'interests' => array(), 'preferred_slot' => '', 'notes' => '',
        );
    }

    /** Reconstruct redisplayable form values from a stored draft row. */
    private function planDraftToViewValues(array $draft, $locale)
    {
        $this->load->model('Plan_your_visit_model');

        $languageId = 0;
        $storedLanguage = trim((string) $draft['preferred_language']);
        if ($storedLanguage !== '') {
            foreach ($this->Plan_your_visit_model->get_languages($locale) as $lang) {
                if ($lang['name_en'] === $storedLanguage) {
                    $languageId = (int) $lang['lang_id'];
                    break;
                }
            }
        }

        $timeValue = '';
        $storedTime = trim((string) $draft['preferred_time']);
        if ($storedTime !== '') {
            $settings = $this->Plan_your_visit_model->get_form_settings();
            foreach ($this->planSettingOptions($settings, 'pyt_visit_time', 'pyt_visit_time_ar', $locale) as $option) {
                if ($option['value'] === $storedTime) {
                    $timeValue = $storedTime;
                    break;
                }
            }
        }

        $interests = array();
        $storedInterests = trim((string) $draft['interests']);
        if ($storedInterests !== '') {
            $interests = array_values(array_filter(array_map('trim', explode(',', $storedInterests))));
        }

        return array(
            'name' => (string) $draft['name'],
            'email' => (string) $draft['email'],
            'phone' => (string) $draft['phone'],
            'country' => (int) $draft['country'],
            'arrival_date' => (string) $draft['arrival_date'],
            'departure_date' => (string) $draft['departure_date'],
            'guests' => $draft['guests'] !== null ? (int) $draft['guests'] : 2,
            'language' => $languageId,
            'interests' => $interests,
            'preferred_slot' => $timeValue,
            'notes' => (string) $draft['message'],
        );
    }

    private function planStep1Values()
    {
        return array(
            'name' => trim((string) $this->input->post('name')),
            'email' => trim((string) $this->input->post('email')),
            'phone' => trim((string) $this->input->post('phone')),
            'country' => (int) $this->input->post('country'),
        );
    }

    private function planStep2Values()
    {
        return array(
            'arrival_date' => trim((string) $this->input->post('arrival_date')),
            'departure_date' => trim((string) $this->input->post('departure_date')),
            'guests' => (int) $this->input->post('guests'),
            'preferred_language' => (int) $this->input->post('language'),
        );
    }

    private function planStep3Values()
    {
        $posted = $this->input->post('interests');
        $interests = array();
        if (is_array($posted)) {
            foreach ($posted as $value) {
                if (is_string($value)) {
                    $interests[] = trim($value);
                }
            }
        }

        return array(
            'interests' => array_values(array_unique($interests)),
            'preferred_time' => trim((string) $this->input->post('preferred_slot')),
        );
    }

    private function planStep4Values()
    {
        return array(
            'message' => trim((string) $this->input->post('notes')),
        );
    }

    private function validatePlanStep1(array $values)
    {
        $this->load->model('Contact_model');
        $errors = array();

        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 255) {
            $errors['name'] = $this->frontendLine('plan.error.name');
        }
        if ($values['email'] === '' || mb_strlen($values['email']) > 255
            || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors['email'] = $this->frontendLine('plan.error.email');
        }
        if ($values['phone'] === '' || mb_strlen($values['phone']) > 50
            || preg_match('/^\+[1-9]\d{6,14}$/', $values['phone']) !== 1
        ) {
            $errors['phone'] = $this->frontendLine('plan.error.phone');
        }
        if ($values['country'] <= 0 || !$this->Contact_model->country_exists($values['country'])) {
            $errors['country'] = $this->frontendLine('plan.error.country');
        }

        return $errors;
    }

    private function validatePlanStep2(array $values)
    {
        $this->load->model('Plan_your_visit_model');
        $errors = array();
        $today = date('Y-m-d');

        $arrival = $values['arrival_date'];
        $departure = $values['departure_date'];
        $arrivalOk = $arrival === '' || ($this->isValidPlanDate($arrival) && $arrival >= $today);
        if (!$arrivalOk) {
            $errors['arrival_date'] = $this->frontendLine('plan.error.arrival');
        }

        $departureOk = $departure === '' || ($this->isValidPlanDate($departure) && $departure >= $today);
        if (!$departureOk) {
            $errors['departure_date'] = $this->frontendLine('plan.error.departure');
        } elseif ($arrivalOk && $arrival !== '' && $departure !== '' && $departure < $arrival) {
            $errors['departure_date'] = $this->frontendLine('plan.error.dateOrder');
        }

        if ($values['guests'] < 1 || $values['guests'] > 60) {
            $errors['guests'] = $this->frontendLine('plan.error.guests');
        }

        if ($values['preferred_language'] > 0
            && $this->Plan_your_visit_model->get_language($values['preferred_language']) === null
        ) {
            $errors['language'] = $this->frontendLine('plan.error.language');
        }

        return $errors;
    }

    private function validatePlanStep3(array $values)
    {
        $this->load->model('Plan_your_visit_model');
        $errors = array();

        $locale = defined('FRONTEND_LOCALE') ? (string) FRONTEND_LOCALE : 'en';
        $settings = $this->Plan_your_visit_model->get_form_settings();
        $allowed = array_column(
            $this->planSettingOptions($settings, 'pyt_interests', 'pyt_interests_ar', $locale),
            'value'
        );
        if (count($values['interests']) > count($allowed)) {
            $errors['interests'] = $this->frontendLine('plan.error.interests');
        } else {
            foreach ($values['interests'] as $value) {
                if (!in_array($value, $allowed, true)) {
                    $errors['interests'] = $this->frontendLine('plan.error.interests');
                    break;
                }
            }
        }

        $allowedTimes = array_column(
            $this->planSettingOptions($settings, 'pyt_visit_time', 'pyt_visit_time_ar', $locale),
            'value'
        );
        if ($values['preferred_time'] !== ''
            && !in_array($values['preferred_time'], $allowedTimes, true)) {
            $errors['preferred_slot'] = $this->frontendLine('plan.error.slot');
        }

        return $errors;
    }

    private function validatePlanStep4(array $values)
    {
        $errors = array();
        if (mb_strlen($values['message']) > 5000) {
            $errors['notes'] = $this->frontendLine('plan.error.message');
        }

        return $errors;
    }

    private function firstInvalidPlanStep(array $errors)
    {
        $stepFields = array(
            1 => array('name', 'email', 'phone', 'country'),
            2 => array('arrival_date', 'departure_date', 'guests', 'language'),
            3 => array('interests', 'preferred_slot'),
            4 => array('notes'),
        );
        foreach ($stepFields as $step => $fields) {
            foreach ($fields as $field) {
                if (isset($errors[$field])) {
                    return $step;
                }
            }
        }

        return 1;
    }

    private function isValidPlanDate($value)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }

    private function resolvePlanLanguageStorage($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $this->load->model('Plan_your_visit_model');
        $lang = $this->Plan_your_visit_model->get_language($id);

        return $lang !== null ? $lang['name_en'] : null;
    }

    /** Build the locale's options from its newline-delimited form setting. */
    private function planSettingOptions($settings, $englishField, $arabicField, $locale)
    {
        if (!is_array($settings)) {
            return array();
        }

        $field = $locale === 'ar' ? $arabicField : $englishField;
        $values = preg_split('/\R/u', (string) $settings[$field]);
        $options = array();

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $options[] = array(
                'value' => $value,
                'label' => $value,
            );
        }

        return $options;
    }

    /** Return the session-bound, one-use token for the public Plan Your Visit form. */
    private function planFormToken()
    {
        $token = (string) $this->session->userdata('plan_form_token');
        if (strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('plan_form_token', $token);
        }

        return $token;
    }

    /** Validate and rotate the one-use token for Plan Your Visit writes. */
    private function consumePlanFormToken()
    {
        $expected = (string) $this->session->userdata('plan_form_token');
        $submitted = (string) $this->input->post('form_token');
        $this->session->unset_userdata('plan_form_token');

        return strlen($expected) === 64
            && strlen($submitted) === 64
            && hash_equals($expected, $submitted);
    }

    /** Verify reCAPTCHA Enterprise before completing a Plan Your Visit request. */
    private function verifyPlanRecaptcha()
    {
        $token = trim((string) $this->input->post('recaptcha_token'));
        if ($token === '') {
            return false;
        }

        $this->load->library('google_recaptcha');
        $result = $this->google_recaptcha->create_assessment(
            $token,
            self::PLAN_RECAPTCHA_ACTION,
            $this->input->ip_address(),
            $this->safeUserAgent()
        );

        if (empty($result['success'])) {
            log_message(
                'error',
                'Plan Your Visit reCAPTCHA Enterprise verification failed: '
                . (isset($result['message']) ? $result['message'] : 'Unknown error.')
            );
            return false;
        }

        $score = isset($result['score']) ? (float) $result['score'] : 0.0;
        if ($score < self::PLAN_RECAPTCHA_MIN_SCORE) {
            log_message('info', 'Plan Your Visit reCAPTCHA Enterprise rejected a score of ' . $score . '.');
            return false;
        }

        return true;
    }

    /** Emit a wizard response and rotate the one-use form token. */
    private function respondPlanJson($status, array $payload)
    {
        $payload['form_token'] = $this->planFormToken();

        return $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    /** Controller-local aliases keep request orchestration concise. */
    private function frontendText(array $row, $field)
    {
        return $this->frontend_presenter->text($row, $field);
    }

    private function frontendPlainText(array $row, $field)
    {
        return $this->frontend_presenter->plainText($row, $field);
    }

    private function frontendLine($key, array $params = array())
    {
        return $this->frontend_presenter->line($key, $params);
    }
}
