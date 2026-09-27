<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Email delivery profiles
| -------------------------------------------------------------------------
| Select a profile with the EMAIL_HOST constant in constants.php.
| Delivery and SMTP values are defined in application/config/constants.php.
*/

$emailCommon = array(
    'protocol' => 'smtp',
    'mailtype' => 'html',
    'charset' => 'utf-8',
    'wordwrap' => TRUE,
    'validate' => TRUE,
    'newline' => "\r\n",
    'crlf' => "\r\n",
    'smtp_timeout' => 10
);

$emailProfiles = array(
    'mailgun' => array(
        'smtp_host' => MAILGUN_SMTP_HOST,
        'smtp_port' => MAILGUN_SMTP_PORT,
        'smtp_user' => MAILGUN_SMTP_USER,
        'smtp_pass' => MAILGUN_SMTP_PASS,
        'smtp_crypto' => 'tls'
    ),
    'local' => array(
        'smtp_host' => LOCAL_SMTP_HOST,
        'smtp_port' => LOCAL_SMTP_PORT,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_crypto' => ''
    ),
    // EmailService writes messages to FCPATH/email_logs for this profile.
    'log' => array(
        'protocol' => 'mail'
    )
);

$emailServer = strtolower((string) EMAIL_HOST);
if (!isset($emailProfiles[$emailServer])) {
    throw new InvalidArgumentException('EMAIL_HOST must be "mailgun", "local", or "log".');
}

$config = array_merge($emailCommon, $emailProfiles[$emailServer]);
