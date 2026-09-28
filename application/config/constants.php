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
define('BLOG_URI', 'blog/');
define('BLOG_CATEGORY_URI', BLOG_URI.'category/');

// Google reCAPTCHA Enterprise configuration (frontend use only). All values,
// including the service-account credentials file path, belong in the
// environment constants file.
defined('RECAPTCHA_ENTERPRISE_PROJECT_NUMBER') OR define('RECAPTCHA_ENTERPRISE_PROJECT_NUMBER', '');
defined('RECAPTCHA_ENTERPRISE_PROJECT_ID') OR define('RECAPTCHA_ENTERPRISE_PROJECT_ID', '');
defined('RECAPTCHA_ENTERPRISE_SITE_KEY') OR define('RECAPTCHA_ENTERPRISE_SITE_KEY', '');
defined('RECAPTCHA_ENTERPRISE_SECRET_KEY') OR define('RECAPTCHA_ENTERPRISE_SECRET_KEY', '');
defined('RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE') OR define('RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE', '');


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
