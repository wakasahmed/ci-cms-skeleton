<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Environment constants template
|--------------------------------------------------------------------------
|
| This file is a template only; CodeIgniter never loads it.
|
| Copy it to application/config/<ENVIRONMENT>/constants.php, for example:
|
|     application/config/development/constants.php
|     application/config/production/constants.php
|
| and fill in the values for that environment. Those folders are gitignored.
| CodeIgniter loads the environment file before application/config/constants.php,
| so every value defined here overrides the empty fallback there.
|
| ENVIRONMENT comes from the CI_ENV server variable (see index.php and
| .htaccess_prod) and defaults to "development".
|
| Never commit real values and never reuse credentials from another project.
|
*/

// Database connection.
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_DATABASE', '');

// A long random string, unique per environment. Generate one with:
// php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
define('APP_ENCRYPTION_KEY', '');

// Sender used by outgoing email.
define('EMAIL_SENDER_NAME', 'Blossom Ewa Mazur');
define('EMAIL_ADDRESS', '');

// Email delivery type: "mailgun", "local", or "log". Defaults to "log" on the
// local Windows install and "mailgun" elsewhere when not defined.
// define('EMAIL_HOST', 'log');

// Mailgun SMTP credentials (production only).
define('MAILGUN_SMTP_USER', '');
define('MAILGUN_SMTP_PASS', '');

// Google reCAPTCHA Enterprise (contact and booking-request forms).
define('RECAPTCHA_ENTERPRISE_PROJECT_NUMBER', '');
define('RECAPTCHA_ENTERPRISE_PROJECT_ID', '');
define('RECAPTCHA_ENTERPRISE_SITE_KEY', '');
define('RECAPTCHA_ENTERPRISE_SECRET_KEY', '');
// Absolute path to the Google service-account JSON file, stored outside the web root.
define('RECAPTCHA_ENTERPRISE_CREDENTIALS_FILE', '');
