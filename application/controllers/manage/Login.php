<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller
{
    const USERNAME_MAX_LENGTH = 100;
    const PASSWORD_MAX_LENGTH = 72;
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_ATTEMPT_WINDOW = 300;

    public $controller = 'login';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('PasswordResetModel');
        $this->load->model('AdminRememberTokenModel');
        $this->load->model('AdminLoginAttemptModel');
        $this->SqlModel->setTitle();
    }

    public function index()
    {
        if ($this->redirectIfAuthenticated()) {
            return;
        }

        $this->login();
    }

    public function login()
    {
        if ($this->redirectIfAuthenticated()) {
            return;
        }

        $this->renderScreen('admin/loginScreen', PROJECT_TITLE.' | Login');
    }

    public function forgot()
    {
        if ($this->redirectIfAuthenticated()) {
            return;
        }

        $this->renderScreen('admin/forgotScreen', PROJECT_TITLE.' | Forgot Your Password');
    }

    public function forgotpwd()
    {
        if (!$this->requirePost(ADMIN_URL.'login/forgot')) {
            return;
        }

        if (!$this->requireValidFormToken(ADMIN_URL.'login/forgot')) {
            return;
        }

        $this->form_validation->set_rules(
            'identifier',
            'Username or email address',
            'trim|required|max_length['.self::USERNAME_MAX_LENGTH.']',
            array(
                'required' => 'Please enter your username or email address.',
                'max_length' => 'Username or email address cannot be longer than '.self::USERNAME_MAX_LENGTH.' characters.'
            )
        );

        $identifier = trim((string) $this->input->post('identifier'));
        $this->session->set_flashdata('reset_identifier', $identifier);

        if (!$this->form_validation->run()) {
            $this->setMessage(
                $this->firstValidationError('Enter a username or email address within the allowed length.'),
                'danger'
            );
            redirect(ADMIN_URL.'login/forgot');
            return;
        }

        $admin = $this->findAdminForReset($identifier);

        if (!empty($admin) && !empty($admin['email'])) {
            if (!$this->PasswordResetModel->isReady()) {
                log_message('error', 'Password reset requested before admin_password_resets table was installed.');
            } else {
                $token = $this->PasswordResetModel->createToken($admin['id'], $this->input->ip_address());
                if ($token !== FALSE && !$this->sendPasswordResetLink($admin, $token)) {
                    $this->PasswordResetModel->deleteToken($token);
                }
            }
        }

        // Keep the response identical whether or not the address exists.
        $this->setMessage(
            'If an enabled administrator account matches those details, a password-reset link has been sent.',
            'success'
        );
        redirect(ADMIN_URL.'login');
    }

    public function reset($token = '')
    {
        if ($this->redirectIfAuthenticated()) {
            return;
        }

        $reset = $this->PasswordResetModel->findValidToken($token);
        if (empty($reset)) {
            $this->setMessage('This password-reset link is invalid or has expired.', 'danger');
            redirect(ADMIN_URL.'login/forgot');
            return;
        }

        $this->renderScreen(
            'admin/resetPasswordScreen',
            PROJECT_TITLE.' | Reset Password',
            array('reset_token' => $token)
        );
    }

    public function resetpwd()
    {
        if (!$this->requirePost(ADMIN_URL.'login/forgot')) {
            return;
        }

        $token = trim((string) $this->input->post('reset_token'));
        if (!$this->consumeFormToken()) {
            $this->setMessage('Your form session expired. Please try again.', 'danger');
            $this->redirectToResetForm($token);
            return;
        }

        $this->form_validation->set_rules(
            'reset_token',
            'Reset token',
            'required|exact_length[64]|regex_match[/^[a-f0-9]{64}$/i]',
            array(
                'required' => 'This password-reset link is invalid or has expired.',
                'exact_length' => 'This password-reset link is invalid or has expired.',
                'regex_match' => 'This password-reset link is invalid or has expired.'
            )
        );
        $this->form_validation->set_rules(
            'new_password',
            'New password',
            'required|min_length['.PASSWORD_MIN_LENGTH.']|max_length['.self::PASSWORD_MAX_LENGTH.']',
            array(
                'required' => 'Please enter a new password.',
                'min_length' => 'New password must be at least '.PASSWORD_MIN_LENGTH.' characters.',
                'max_length' => 'New password cannot be longer than '.self::PASSWORD_MAX_LENGTH.' characters.'
            )
        );
        $this->form_validation->set_rules(
            'confirm_password',
            'Confirm new password',
            'required|matches[new_password]',
            array(
                'required' => 'Please confirm your new password.',
                'matches' => 'Your new password and confirmation do not match.'
            )
        );

        if (!$this->form_validation->run()) {
            $this->setMessage(
                $this->firstValidationError('Check your new password and try again.'),
                'danger'
            );
            $this->redirectToResetForm($token);
            return;
        }

        $reset = $this->PasswordResetModel->findValidToken($token);
        if (empty($reset)) {
            $this->setMessage('This password-reset link is invalid or has expired.', 'danger');
            redirect(ADMIN_URL.'login/forgot');
            return;
        }

        $newPassword = (string) $this->input->post('new_password');
        if ($this->verifyPassword($newPassword, $reset['pwd'])) {
            $this->setMessage('Your new password must be different from your current password.', 'danger');
            $this->redirectToResetForm($token);
            return;
        }

        $changed = $this->PasswordResetModel->consumeToken(
            $reset,
            password_hash($newPassword, PASSWORD_DEFAULT)
        );
        if (!$changed) {
            $this->setMessage('This password-reset link was already used or has expired.', 'danger');
            redirect(ADMIN_URL.'login/forgot');
            return;
        }

        if (!$this->sendPasswordChangedNotice($reset)) {
            log_message('error', 'Password changed notification failed for administrator ID '.$reset['admin_id'].'.');
        }

        $this->setMessage('Your password has been changed. You can now sign in with the new password.', 'success');
        redirect(ADMIN_URL.'login');
    }

    public function auth()
    {
        if (!$this->requirePost(ADMIN_URL.'login')) {
            return;
        }

        if (!$this->requireValidFormToken(ADMIN_URL.'login')) {
            return;
        }

        $this->form_validation->set_rules(
            'username',
            'Username',
            'trim|required|max_length['.self::USERNAME_MAX_LENGTH.']',
            array(
                'required' => 'Please enter your username.',
                'max_length' => 'Username cannot be longer than '.self::USERNAME_MAX_LENGTH.' characters.'
            )
        );
        $this->form_validation->set_rules(
            'password',
            'Password',
            'required|max_length['.self::PASSWORD_MAX_LENGTH.']',
            array(
                'required' => 'Please enter your password.',
                'max_length' => 'Password cannot be longer than '.self::PASSWORD_MAX_LENGTH.' characters.'
            )
        );

        $username = trim((string) $this->input->post('username'));
        $rememberMe = $this->input->post('remember_me') === '1';
        $this->session->set_flashdata('login_username', $username);
        $this->session->set_flashdata('login_remember_me', $rememberMe ? '1' : '0');

        if (!$this->form_validation->run()) {
            $this->recordLoginAttempt($username, NULL, FALSE, 'validation_failed');
            $this->setMessage(
                $this->firstValidationError('Enter a username and password within the allowed lengths.'),
                'danger'
            );
            redirect(ADMIN_URL.'login');
            return;
        }

        $retryAfter = $this->retryAfter();
        if ($retryAfter > 0) {
            $this->recordLoginAttempt($username, NULL, FALSE, 'rate_limited');
            $this->setMessage(
                'Too many sign-in attempts. Try again in '.ceil($retryAfter / 60).' minute(s).',
                'danger'
            );
            redirect(ADMIN_URL.'login');
            return;
        }

        $password = (string) $this->input->post('password');

        // getSingleRecord() uses Query Builder, which safely escapes these values.
        $admin = $this->SqlModel->getSingleRecord('admin_users', array(
            'user_name' => $username,
            'status' => 'Enable'
        ));

        $passwordIsValid = !empty($admin)
            ? $this->verifyPassword($password, $admin['pwd'])
            : password_verify($password, '$2y$10$vVwA7.nMEoStCUPydSWp2O7pA4q7q4rq9mWwthjiu7NkqkXjU0tOa');

        if (empty($admin) || !$passwordIsValid) {
            $this->recordFailedAttempt();
            $this->recordLoginAttempt(
                $username,
                empty($admin) ? NULL : $admin['id'],
                FALSE,
                'invalid_credentials'
            );
            $this->setMessage('Invalid username or password.', 'danger');
            redirect(ADMIN_URL.'login');
            return;
        }

        $this->recordLoginAttempt($username, $admin['id'], TRUE, NULL);
        $this->completeLogin($admin, $password, $rememberMe);
    }

    private function completeLogin(array $admin, $password, $rememberMe)
    {
        $this->session->unset_userdata('admin_login_attempts');
        $this->session->sess_regenerate(TRUE);

        $this->session->set_userdata(array(
            'admin_auth' => 'allow',
            'admin_role' => $admin['user_role'],
            'admin_user_name' => $admin['user_name'],
            'admin_full_name' => $admin['full_name'],
            'admin_id' => $admin['id'],
            'last_login' => $admin['last_login'],
            'last_ip' => $admin['ip']
        ));

        $changes = array(
            'last_login' => date('Y-m-d H:i:s'),
            'ip' => substr($this->input->ip_address(), 0, 25),
            'user_agent' => $this->input->user_agent()
        );

        if (password_needs_rehash($admin['pwd'], PASSWORD_DEFAULT)) {
            $changes['pwd'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->SqlModel->updateRecord('admin_users', $changes, array('id' => $admin['id']));

        $currentCookie = (string) $this->input->cookie(AdminRememberTokenModel::COOKIE_NAME);
        $this->AdminRememberTokenModel->revokeCookie($currentCookie);
        $this->AdminRememberTokenModel->clearCookie();

        if ($rememberMe) {
            $rememberToken = $this->AdminRememberTokenModel->createToken(
                $admin['id'],
                $this->input->ip_address(),
                $this->input->user_agent()
            );
            if ($rememberToken !== FALSE) {
                $this->AdminRememberTokenModel->setCookie($rememberToken);
            } else {
                log_message('error', 'Unable to create a remember-me token for administrator ID '.$admin['id'].'.');
            }
        }

        redirect(base_url('manage'), 'location');
    }

    private function verifyPassword($password, $storedHash)
    {
        return password_verify($password, $storedHash);
    }

    private function sendPasswordResetLink(array $admin, $token)
    {
        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        if (empty($settings)) {
            log_message('error', 'Unable to send password-reset link: site settings are missing.');
            return FALSE;
        }

        $resetUrl = ADMIN_URL.'login/reset/'.$token;
        $safeName = htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8');
        $safeProject = htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $expiresInMinutes = (int) (PASSWORD_RESET_TTL / 60);
        $body =
            'Hello '.$safeName.',<br><br>'.
            'We received a request to reset your administration password. '.
            'Use the button below within '.$expiresInMinutes.' minutes:'.
            '<div style="margin:24px 0;text-align:center">'.
            '<a href="'.$safeUrl.'" style="display:inline-block;padding:12px 20px;border-radius:6px;'.
            'background:#63569b;color:#fff;text-decoration:none;font-weight:700">Reset password</a>'.
            '</div>'.
            'If the button does not work, copy this link into your browser:<br>'.
            '<a href="'.$safeUrl.'">'.$safeUrl.'</a><br><br>'.
            'If you did not request this reset, no action is required. Your current password has not changed.<br><br>'.
            'Admin Team,<br>'.$safeProject;

        $subject = 'Reset your '.$settings['website_title'].' CMS password';
        $this->load->library('EmailService');
        $message = $this->emailservice->renderTemplate(array(
            'site_settings' => $settings,
            'heading' => $subject,
            'body' => $body,
        ), 'English');

        if ($message === FALSE) {
            log_message('error', 'Password-reset email template could not be rendered.');
            return FALSE;
        }

        return $this->sendAccountEmail(
            $admin,
            $settings,
            $subject,
            $message,
            $this->htmlToText($body)
        );
    }

    private function sendPasswordChangedNotice(array $admin)
    {
        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        if (empty($settings)) {
            return FALSE;
        }

        $message =
            'Hello '.htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8').',<br><br>'.
            'The password for your administration account was changed on '.
            htmlspecialchars(date('j F Y \a\t g:i A'), ENT_QUOTES, 'UTF-8').'.<br><br>'.
            'If you made this change, no further action is required. If you did not, contact the site administrator immediately.<br><br>'.
            'Admin Team,<br>'.htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8');

        return $this->sendAccountEmail(
            $admin,
            $settings,
            'Your '.$settings['website_title'].' CMS password was changed',
            $message
        );
    }

    private function sendAccountEmail(array $admin, array $settings, $subject, $message, $altMessage = NULL)
    {
        $this->load->library('EmailService');
        $sent = $this->emailservice->send(array(
            'to' => trim($admin['email']),
            'subject' => $subject,
            'message' => $message,
            'alt_message' => $altMessage === NULL ? $this->htmlToText($message) : $altMessage,
            'config' => array('useragent' => trim($settings['website_title']))
        ));

        if (!$sent) {
            log_message('error', 'Account email failed: '.$this->emailservice->getLastError());
        }

        return $sent;
    }

    private function htmlToText($message)
    {
        $message = preg_replace('/<br\s*\/?>/i', "\n", $message);
        return html_entity_decode(strip_tags($message), ENT_QUOTES, 'UTF-8');
    }

    private function findAdminForReset($identifier)
    {
        // Query Builder escapes both alternatives and keeps the OR expression grouped.
        $query = $this->db
            ->select('*')
            ->from('admin_users')
            ->where('status', 'Enable')
            ->group_start()
            ->where('user_name', $identifier)
            ->or_where('email', $identifier)
            ->group_end()
            ->limit(1)
            ->get();

        return $query->row_array();
    }

    private function retryAfter()
    {
        $attempts = $this->recentAttempts();
        if (count($attempts) < self::MAX_LOGIN_ATTEMPTS) {
            return 0;
        }

        return max(1, self::LOGIN_ATTEMPT_WINDOW - (time() - min($attempts)));
    }

    private function recordFailedAttempt()
    {
        $attempts = $this->recentAttempts();
        $attempts[] = time();
        $this->session->set_userdata('admin_login_attempts', $attempts);
    }

    private function recordLoginAttempt($username, $adminId, $wasSuccessful, $failureReason)
    {
        $this->AdminLoginAttemptModel->record(
            $username,
            $adminId,
            $wasSuccessful,
            $failureReason,
            $this->input->ip_address(),
            $this->input->user_agent()
        );
    }

    private function recentAttempts()
    {
        $cutoff = time() - self::LOGIN_ATTEMPT_WINDOW;
        $attempts = (array) $this->session->userdata('admin_login_attempts');
        $attempts = array_values(array_filter($attempts, function ($attempt) use ($cutoff) {
            return is_numeric($attempt) && $attempt > $cutoff;
        }));
        $this->session->set_userdata('admin_login_attempts', $attempts);

        return $attempts;
    }

    private function isAuthenticated()
    {
        return !empty($this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        ));
    }

    private function redirectIfAuthenticated()
    {
        if (!$this->isAuthenticated()) {
            return FALSE;
        }

        redirect(base_url('manage'));
        return TRUE;
    }

    private function requirePost($fallbackRoute)
    {
        if ($this->input->method(TRUE) === 'POST') {
            return TRUE;
        }

        redirect($fallbackRoute);
        return FALSE;
    }

    private function requireValidFormToken($fallbackRoute)
    {
        if ($this->consumeFormToken()) {
            return TRUE;
        }

        $this->setMessage('Your form session expired. Please try again.', 'danger');
        redirect($fallbackRoute);
        return FALSE;
    }

    private function firstValidationError($fallback)
    {
        $errors = $this->form_validation->error_array();

        return empty($errors) ? $fallback : reset($errors);
    }

    private function setMessage($message, $type)
    {
        $this->session->set_flashdata('login_message', $message);
        $this->session->set_flashdata('login_message_type', $type);
    }

    private function formToken()
    {
        $token = (string) $this->session->userdata('admin_login_form_token');
        if (strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('admin_login_form_token', $token);
        }

        return $token;
    }

    private function consumeFormToken()
    {
        $expected = (string) $this->session->userdata('admin_login_form_token');
        $submitted = (string) $this->input->post('form_token');
        $this->session->unset_userdata('admin_login_form_token');

        return strlen($expected) === 64
            && strlen($submitted) === 64
            && hash_equals($expected, $submitted);
    }

    private function redirectToResetForm($token)
    {
        if (preg_match('/^[a-f0-9]{64}$/i', $token)) {
            redirect(ADMIN_URL.'login/reset/'.$token);
            return;
        }

        redirect(ADMIN_URL.'login/forgot');
    }

    private function renderScreen($view, $pageTitle, array $extraData = array())
    {
        $data = array(
            'loginSection' => 1,
            'page_title' => $pageTitle,
            'logo' => './assets/frontend/images/logo/'.$this->SqlModel->getSingleField(
                'logo',
                'site_settings',
                array('id' => 1)
            ),
            'flash_message' => (string) $this->session->flashdata('login_message'),
            'flash_type' => (string) $this->session->flashdata('login_message_type'),
            'old_username' => (string) $this->session->flashdata('login_username'),
            'old_remember_me' => $this->session->flashdata('login_remember_me') === '1',
            'old_reset_identifier' => (string) $this->session->flashdata('reset_identifier'),
            'form_token' => $this->formToken()
        );
        $data = array_merge($data, $extraData);

        $this->load->view('admin/header', $data);
        $this->load->view($view);
        $this->load->view('admin/footer');
    }
}
