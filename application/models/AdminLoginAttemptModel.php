<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class AdminLoginAttemptModel extends CI_Model
{
    const TABLE = 'admin_login_attempts';

    public function record($username, $adminId, $wasSuccessful, $failureReason, $ipAddress, $userAgent)
    {
        if (!$this->db->table_exists(self::TABLE)) {
            log_message('error', 'Admin login attempt could not be recorded because '.self::TABLE.' is missing.');
            return FALSE;
        }

        return $this->db->insert(self::TABLE, array(
            'admin_id' => $adminId === NULL ? NULL : (int) $adminId,
            'username' => substr(trim((string) $username), 0, 100),
            'was_successful' => $wasSuccessful ? 1 : 0,
            'failure_reason' => $failureReason === NULL
                ? NULL
                : substr((string) $failureReason, 0, 50),
            'ip_address' => substr((string) $ipAddress, 0, 45),
            'user_agent' => substr((string) $userAgent, 0, 512),
            'attempted_at' => date('Y-m-d H:i:s')
        ));
    }
}
