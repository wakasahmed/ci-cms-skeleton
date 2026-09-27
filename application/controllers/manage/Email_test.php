<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Diagnostic controller for confirming the active email delivery profile
 * (for example Mailgun) is configured and sending correctly.
 */
class Email_test extends CI_Controller
{
    public $user_data = array();

    private $recipients = array(
        'wakas@outlook.com',
        'wakasahmed21@gmail.com',
    );

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));

        if (empty($this->user_data))
        {
            redirect(base_url('manage/login'));
            return;
        }

        if (!isset($this->user_data['user_role']) || $this->user_data['user_role'] !== 'Super Admin')
        {
            redirect(ADMIN_URL);
        }
    }

    /** Send a test email through the active EMAIL_HOST profile (mailgun/local/log). */
    public function send()
    {
        $this->load->library('EmailService');

        $sentAt = date('Y-m-d H:i:s');
        $subject = 'Mailgun Test Email - '.$sentAt;
        $message = '<p>This is a test email sent from '.htmlspecialchars((string) PROJECT_TITLE, ENT_QUOTES, 'UTF-8').
            ' to confirm the Mailgun SMTP configuration is working correctly.</p>'.
            '<p>Sent at: '.$sentAt.'</p>'.
            '<p dir="rtl" lang="ar">هذه رسالة اختبار للتأكد من أن إعدادات البريد الإلكتروني تعمل بشكل صحيح.</p>';
        $altMessage = 'This is a test email sent from '.PROJECT_TITLE.
            ' to confirm the Mailgun SMTP configuration is working correctly.'.
            "\n".'Sent at: '.$sentAt;

        $sent = $this->emailservice->send(array(
            'to' => $this->recipients,
            'subject' => $subject,
            'message' => $message,
            'alt_message' => $altMessage,
        ));

        $resultMessage = $sent
            ? 'Test email sent to '.implode(', ', $this->recipients).' via the "'.EMAIL_HOST.'" profile.'
            : 'Test email failed via the "'.EMAIL_HOST.'" profile: '.$this->emailservice->getLastError();

        return $this->json($sent ? 200 : 500, $sent, $resultMessage);
    }

    private function json($code, $success, $message)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($code)
            ->set_output(json_encode(array('success' => $success, 'message' => $message)));
    }
}
