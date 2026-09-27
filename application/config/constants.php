<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ', 'rb');
define('FOPEN_READ_WRITE', 'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_READ_WRITE_CREATE', 'a+b');
define('FOPEN_WRITE_CREATE_STRICT', 'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Secrets and environment-specific values
|--------------------------------------------------------------------------
|
| Credentials, keys and tokens are never stored in this file. They are
| defined in application/config/<ENVIRONMENT>/constants.php (gitignored),
| which CodeIgniter loads before this file. Every secret below therefore
| uses "defined() OR define()" with an empty fallback. Copy
| application/config/secrets.example.php to start a new environment.
|
*/

// local, dev, staging, or production. The Windows WAMP machine is the only
// local install; it needs 'local' for log-only email.
defined('SERVER_TYPE') OR define('SERVER_TYPE', stripos(PHP_OS, 'WIN') === 0 ? 'local' : 'dev');

// Database connection (read by application/config/database.php).
defined('DB_HOSTNAME') OR define('DB_HOSTNAME', 'localhost');
defined('DB_USERNAME') OR define('DB_USERNAME', '');
defined('DB_PASSWORD') OR define('DB_PASSWORD', '');
defined('DB_DATABASE') OR define('DB_DATABASE', '');

// Encryption key (read by application/config/config.php).
defined('APP_ENCRYPTION_KEY') OR define('APP_ENCRYPTION_KEY', '');

defined('EMAIL_SENDER_NAME') OR define('EMAIL_SENDER_NAME', 'Blossom Ewa Mazur');
defined('EMAIL_ADDRESS') OR define('EMAIL_ADDRESS', '');
// Email delivery type: "mailgun", "local", or "log".
defined('EMAIL_HOST') OR define('EMAIL_HOST', (SERVER_TYPE === 'local' ? 'log' : 'mailgun'));
// Backwards-compatible alias for the previously introduced setting name.
defined('EMAIL_SERVER') OR define('EMAIL_SERVER', EMAIL_HOST);

// Local SMTP server (for example Mailpit or MailHog).
define('LOCAL_SMTP_HOST', '127.0.0.1');
define('LOCAL_SMTP_PORT', 1025);

// Mailgun SMTP settings. Account credentials belong in the environment constants file.
define('MAILGUN_SMTP_HOST', 'smtp.mailgun.org');
define('MAILGUN_SMTP_PORT', 587);
defined('MAILGUN_SMTP_USER') OR define('MAILGUN_SMTP_USER', '');
defined('MAILGUN_SMTP_PASS') OR define('MAILGUN_SMTP_PASS', '');

// Administrator password reset policy.
define('PASSWORD_RESET_TTL', 3600);
define('PASSWORD_RESET_MAX_REQUESTS', 3);
define('PASSWORD_RESET_WINDOW', 900);
define('PASSWORD_MIN_LENGTH', 8);
define('TOUR_LIMIT', 9);
define('PRICE_TOUR_ID', '8');
define('CAR1','490');
define('CAR2','690');
define('CAR3','890');
define('CAR1_COM','140');
define('CAR2_COM','190');
define('CAR3_COM','220');
define('BOOK_HOUR_LIMIT', 6);
define('CONSUME_HOUR_LIMIT', 6);
define('CANCEL_HOUR_LIMIT', 3);
define('DISCOUNT_CODE_LENGTH', 8);
define('DISCOUNT_CODE_USE_BY_ONE_CUSTOMER', 3);

/*
|--------------------------------------------------------------------------
| Manage/Admin Date and Time Display Format
|--------------------------------------------------------------------------
|
| PHP date() format strings used when displaying dates and times inside
| the manage/admin area (record listings, view details, etc). Change the
| value here to update the display format everywhere at once. This does
| not affect date/time picker fields.
|
*/
defined('ADMIN_DATE_FORMAT')     OR define('ADMIN_DATE_FORMAT', 'M d, Y');
defined('ADMIN_TIME_FORMAT')     OR define('ADMIN_TIME_FORMAT', 'h:i a');
defined('ADMIN_DATETIME_FORMAT') OR define('ADMIN_DATETIME_FORMAT', ADMIN_DATE_FORMAT.' '.ADMIN_TIME_FORMAT);

/*
|--------------------------------------------------------------------------
| Email Date and Time Display Format
|--------------------------------------------------------------------------
|
| PHP date() format strings used for human-readable dates and times in
| frontend-generated emails. Database storage formats are not affected.
|
*/
defined('EMAIL_DATE_FORMAT')     OR define('EMAIL_DATE_FORMAT', 'M d, Y');
defined('EMAIL_TIME_FORMAT')     OR define('EMAIL_TIME_FORMAT', 'h:i a');
defined('EMAIL_DATETIME_FORMAT') OR define('EMAIL_DATETIME_FORMAT', EMAIL_DATE_FORMAT.' '.EMAIL_TIME_FORMAT);

/*
|--------------------------------------------------------------------------
| Tour Booking Cutoff
|--------------------------------------------------------------------------
|
| Number of hours a Pending frontend booking may stay open, counted from
| tour_bookings.book_added. After this the booking_cron release_holds job
| cancels it and releases any guide slots it holds.
|
*/
defined('TOUR_BOOKING_CUTOFF_HOURS') OR define('TOUR_BOOKING_CUTOFF_HOURS', 2);

/*
|--------------------------------------------------------------------------
| Moyasar Payments
|--------------------------------------------------------------------------
|
| Change only MOYASAR_SANDBOX when promoting between the configured test and
| live accounts. Publishable keys may be sent to Moyasar's browser form;
| secret keys and webhook secrets must remain server-side.
|
*/
define('MOYASAR_SANDBOX', true);

// Moyasar is removed in Phase 2 of PROJECT_PLAN.md. The keys stay empty so
// MOYASAR_ENABLED is false until then.
defined('MOYASAR_SANDBOX_PUBLISHABLE_KEY') OR define('MOYASAR_SANDBOX_PUBLISHABLE_KEY', '');
defined('MOYASAR_SANDBOX_SECRET_KEY') OR define('MOYASAR_SANDBOX_SECRET_KEY', '');
defined('MOYASAR_SANDBOX_WEBHOOK_SECRET') OR define('MOYASAR_SANDBOX_WEBHOOK_SECRET', '');

defined('MOYASAR_PRODUCTION_PUBLISHABLE_KEY') OR define('MOYASAR_PRODUCTION_PUBLISHABLE_KEY', '');
defined('MOYASAR_PRODUCTION_SECRET_KEY') OR define('MOYASAR_PRODUCTION_SECRET_KEY', '');
defined('MOYASAR_PRODUCTION_WEBHOOK_SECRET') OR define('MOYASAR_PRODUCTION_WEBHOOK_SECRET', '');

define(
    'MOYASAR_PUBLISHABLE_KEY',
    MOYASAR_SANDBOX
        ? MOYASAR_SANDBOX_PUBLISHABLE_KEY
        : MOYASAR_PRODUCTION_PUBLISHABLE_KEY
);
define(
    'MOYASAR_SECRET_KEY',
    MOYASAR_SANDBOX
        ? MOYASAR_SANDBOX_SECRET_KEY
        : MOYASAR_PRODUCTION_SECRET_KEY
);
define(
    'MOYASAR_WEBHOOK_SECRET',
    MOYASAR_SANDBOX
        ? MOYASAR_SANDBOX_WEBHOOK_SECRET
        : MOYASAR_PRODUCTION_WEBHOOK_SECRET
);
define('MOYASAR_API_URL', 'https://api.moyasar.com/v1');
define('MOYASAR_FORM_VERSION', '1.15.0');
define('MOYASAR_TIMEOUT_SECONDS', 20);
define('MOYASAR_CURRENCY_EXPONENT', 2);
define('MOYASAR_KEY_ENVIRONMENT', MOYASAR_SANDBOX ? 'test' : 'live');
define(
    'MOYASAR_ENABLED',
    strpos(MOYASAR_PUBLISHABLE_KEY, 'pk_' . MOYASAR_KEY_ENVIRONMENT . '_') === 0
        && strpos(MOYASAR_SECRET_KEY, 'sk_' . MOYASAR_KEY_ENVIRONMENT . '_') === 0
);
define('MOYASAR_PAYMENT_METHODS', array('creditcard'));
define('MOYASAR_CARD_NETWORKS', array('mada', 'visa', 'mastercard', 'amex', 'unionpay'));

/*
|--------------------------------------------------------------------------
| WhatsApp Cloud API
|--------------------------------------------------------------------------
|
| Values come from the Meta app dashboard (WhatsApp > API Setup and
| App settings > Basic). Use a permanent System User access token; the
| temporary dashboard token expires after 24 hours. The verify token must
| match the one entered beside the webhook callback URL in the dashboard.
| All values must remain server-side.
|
*/
define('WHATSAPP_GRAPH_API_URL', 'https://graph.facebook.com');
define('WHATSAPP_GRAPH_API_VERSION', 'v26.0');
// WhatsApp is removed in Phase 3 of PROJECT_PLAN.md. The values stay empty so
// WHATSAPP_ENABLED is false until then.
defined('WHATSAPP_PHONE_NUMBER_ID') OR define('WHATSAPP_PHONE_NUMBER_ID', '');
defined('WHATSAPP_BUSINESS_ACCOUNT_ID') OR define('WHATSAPP_BUSINESS_ACCOUNT_ID', '');
defined('WHATSAPP_ACCESS_TOKEN') OR define('WHATSAPP_ACCESS_TOKEN', '');
defined('WHATSAPP_APP_SECRET') OR define('WHATSAPP_APP_SECRET', '');
defined('WHATSAPP_WEBHOOK_VERIFY_TOKEN') OR define('WHATSAPP_WEBHOOK_VERIFY_TOKEN', '');
define('WHATSAPP_TIMEOUT_SECONDS', 20);
// Writes a one-line summary of each webhook event to application/logs/whatsapp_webhook.log.
define('WHATSAPP_WEBHOOK_LOG', true);
// Booking WhatsApp notifications. When false, the WhatsApp consent checkbox is hidden
// on the booking Review step and no booking message is sent.
define('WHATSAPP_BOOKING_ENABLED', false);
// TEMPORARY, for the Meta app review recording: the Meta sample template sent when a
// booking is confirmed, filled with the customer's name, booking reference and tour date.
// It only exists on Meta's test WhatsApp Business Account. Set to '' to stop sending.
define('WHATSAPP_BOOKING_TEST_TEMPLATE', 'jaspers_market_order_confirmation_v1');
define(
    'WHATSAPP_ENABLED',
    WHATSAPP_PHONE_NUMBER_ID !== ''
        && WHATSAPP_ACCESS_TOKEN !== ''
);

/*
|--------------------------------------------------------------------------
| Frontend Date and Time Display Format
|--------------------------------------------------------------------------
|
| PHP date() format strings used for human-readable dates and times on the
| public website. Database storage formats are not affected.
|
*/
defined('FRONTEND_DATE_FORMAT')     OR define('FRONTEND_DATE_FORMAT', 'j F Y');
defined('FRONTEND_TIME_FORMAT')     OR define('FRONTEND_TIME_FORMAT', 'h:i a');
defined('FRONTEND_DATETIME_FORMAT') OR define('FRONTEND_DATETIME_FORMAT', FRONTEND_DATE_FORMAT.' '.FRONTEND_TIME_FORMAT);

/*
|--------------------------------------------------------------------------
| Public Content URIs
|--------------------------------------------------------------------------
|
| URI prefixes used when building public links in the manage modules.
|
*/
define('TOUR_URI', 'tour/');
define('EXPERIENCE_URI', 'experience/');
define('BLOG_URI', 'blog/');
define('BLOG_CATEGORY_URI', BLOG_URI.'category/');

/*
|--------------------------------------------------------------------------
| Discount Code Value Limits
|--------------------------------------------------------------------------
|
| Allowed range for discount_codes.discount_value, per discount type.
|
*/
define('DISCOUNT_VALUE_MIN_PERCENTAGE', 1);
define('DISCOUNT_VALUE_MAX_PERCENTAGE', 70);
define('DISCOUNT_VALUE_MIN_FIXED', 1);
define('DISCOUNT_VALUE_MAX_FIXED', 500);

/*
|--------------------------------------------------------------------------
| Discount Code Referral Commission Limits
|--------------------------------------------------------------------------
|
| Allowed range for discount_codes.discount_ref_commission, per commission type.
|
*/
define('DISCOUNT_REF_COMMISSION_MIN_PERCENTAGE', 1);
define('DISCOUNT_REF_COMMISSION_MAX_PERCENTAGE', 25);
define('DISCOUNT_REF_COMMISSION_MIN_FIXED', 1);
define('DISCOUNT_REF_COMMISSION_MAX_FIXED', 250);

// Google reCAPTCHA Enterprise configuration (frontend use only). All values,
// including the service-account credentials file path, belong in the
// environment constants file.
defined('RECAPTCHA_ENTERPRISE_PROJECT_NUMBER') OR define('RECAPTCHA_ENTERPRISE_PROJECT_NUMBER', '');
defined('RECAPTCHA_ENTERPRISE_PROJECT_ID') OR define('RECAPTCHA_ENTERPRISE_PROJECT_ID', '');
defined('RECAPTCHA_ENTERPRISE_SITE_KEY') OR define('RECAPTCHA_ENTERPRISE_SITE_KEY', '');
defined('RECAPTCHA_ENTERPRISE_SECRET_KEY') OR define('RECAPTCHA_ENTERPRISE_SECRET_KEY', '');
defined('RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE') OR define('RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE', '');
// Google Maps and Places are removed in Phase 3 of PROJECT_PLAN.md.
defined('GOOGLE_MAPS_API_KEY') OR define('GOOGLE_MAPS_API_KEY', '');

// Google Cloud Translation configuration (used by the admin's Manage Translations feature).
define('GOOGLE_TRANSLATION_PROJECT_ID', RECAPTCHA_ENTERPRISE_PROJECT_ID);
define('GOOGLE_TRANSLATION_CREDENTIALS_FILE', RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE);
define('GOOGLE_TRANSLATION_LOCATION', 'global');
define('GOOGLE_TRANSLATION_SOURCE_LANGUAGE', 'en');
define('GOOGLE_TRANSLATION_TARGET_LANGUAGE', 'ar');
define('GOOGLE_API_CA_BUNDLE', APPPATH.'third_party/google_api/cacert.pem');

// Google Places API (New) configuration (frontend booking form pickup-location autocomplete).
// The server authenticates with the service account above. GOOGLE_MAPS_API_KEY is a
// browser key restricted by HTTP referrer, so server-side requests cannot use it.
define('GOOGLE_PLACES_AUTOCOMPLETE_URL', 'https://places.googleapis.com/v1/places:autocomplete');
define('GOOGLE_PLACES_DETAILS_URL', 'https://places.googleapis.com/v1/places/');
define('GOOGLE_PLACES_OAUTH_SCOPE', 'https://www.googleapis.com/auth/cloud-platform');
define('GOOGLE_PLACES_REGION_CODE', 'sa');
define('GOOGLE_PLACES_MIN_INPUT_LENGTH', 2);
define('GOOGLE_PLACES_MAX_INPUT_LENGTH', 120);
define('GOOGLE_PLACES_TIMEOUT_SECONDS', 6);
// A cached OAuth token is refreshed this long before Google says it expires.
define('GOOGLE_PLACES_TOKEN_EXPIRY_MARGIN_SECONDS', 300);
// Suggestions and selected places are restricted to this rectangle around Madinah.
define('GOOGLE_PLACES_MADINAH_BOUNDS', array(
    'south' => 24.30,
    'west' => 39.40,
    'north' => 24.70,
    'east' => 39.90,
));
// Per visitor session: at most this many suggestion requests inside the window.
define('GOOGLE_PLACES_RATE_LIMIT_REQUESTS', 120);
define('GOOGLE_PLACES_RATE_LIMIT_WINDOW_SECONDS', 600);

//Defining the assests dir
$baseURL = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$baseURL .= "://".(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
$baseURL .= str_replace(basename($scriptName), "", $scriptName);
define('ADMIN_ASSETS', $baseURL.'assets/admin/');
define('ADMIN_URL', $baseURL.'manage/');
defined('UPLOAD_SIZE_MB')      OR define('UPLOAD_SIZE_MB', 20);
defined('UPLOAD_SIZE')      OR define('UPLOAD_SIZE', UPLOAD_SIZE_MB * 1024 * 1024);
defined('UPLOAD_IMAGE_MIMES')  OR define('UPLOAD_IMAGE_MIMES', 'png|jpg|jpeg|webp');
defined('UPLOAD_DOC_MIMES')    OR define('UPLOAD_DOC_MIMES', 'doc|docx|pdf');
defined('AVATAR_UPLOAD_MAX_MB') OR define('AVATAR_UPLOAD_MAX_MB', 10);
defined('AVATAR_OUTPUT_SIZE')   OR define('AVATAR_OUTPUT_SIZE', 512);
defined('AVATAR_UPLOAD_INSTRUCTIONS') OR define('AVATAR_UPLOAD_INSTRUCTIONS', 'JPG, PNG or WebP, up to '.AVATAR_UPLOAD_MAX_MB.' MB. Saved as a '.AVATAR_OUTPUT_SIZE.' × '.AVATAR_OUTPUT_SIZE.' square PNG and displayed as a circle.');
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0);
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1);
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3);
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4);
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5);
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6);
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7);
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8);
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9);
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125);

/* End of file constants.php */
/* Location: ./application/config/constants.php */
