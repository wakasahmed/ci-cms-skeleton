<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class PasswordResetModel extends CI_Model
{
    const TABLE = 'admin_password_resets';

    public function isReady()
    {
        return $this->db->table_exists(self::TABLE);
    }

    public function createToken($adminId, $requestIp)
    {
        if (!$this->isReady() || !$this->canRequest($adminId)) {
            return FALSE;
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (Exception $exception) {
            log_message('error', 'Unable to generate a password-reset token: '.$exception->getMessage());
            return FALSE;
        }

        $created = $this->db->insert(self::TABLE, array(
            'admin_id' => (int) $adminId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + PASSWORD_RESET_TTL),
            'requested_ip' => substr((string) $requestIp, 0, 45),
            'created_at' => date('Y-m-d H:i:s')
        ));

        return $created ? $token : FALSE;
    }

    public function findValidToken($token)
    {
        if (!$this->isReady() || !$this->isTokenFormatValid($token)) {
            return NULL;
        }

        return $this->db
            ->select('r.id AS reset_id, r.admin_id, r.expires_at, u.user_name, u.full_name, u.email, u.pwd')
            ->from(self::TABLE.' r')
            ->join('admin_users u', 'u.id = r.admin_id', 'inner')
            ->where('r.token_hash', hash('sha256', $token))
            ->where('r.used_at IS NULL', NULL, FALSE)
            ->where('r.expires_at >=', date('Y-m-d H:i:s'))
            ->where('u.status', 'Enable')
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function consumeToken(array $reset, $passwordHash)
    {
        $usedAt = date('Y-m-d H:i:s');
        $this->db->trans_begin();

        $claimed = $this->db
            ->where('id', $reset['reset_id'])
            ->where('admin_id', $reset['admin_id'])
            ->where('used_at IS NULL', NULL, FALSE)
            ->where('expires_at >=', $usedAt)
            ->update(self::TABLE, array('used_at' => $usedAt));

        if (!$claimed || $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $updated = $this->db
            ->where('id', $reset['admin_id'])
            ->where('status', 'Enable')
            ->update('admin_users', array(
                'pwd' => $passwordHash,
                'last_modified' => $usedAt
            ));

        if (!$updated || $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return FALSE;
        }

        // A successful reset invalidates every other outstanding link.
        $this->db
            ->where('admin_id', $reset['admin_id'])
            ->where('used_at IS NULL', NULL, FALSE)
            ->update(self::TABLE, array('used_at' => $usedAt));

        $this->load->model('AdminRememberTokenModel');
        if (!$this->AdminRememberTokenModel->revokeAllForAdmin($reset['admin_id'])) {
            $this->db->trans_rollback();
            return FALSE;
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        return $this->db->trans_commit();
    }

    public function deleteToken($token)
    {
        if (!$this->isReady() || !$this->isTokenFormatValid($token)) {
            return FALSE;
        }

        return $this->db->delete(self::TABLE, array('token_hash' => hash('sha256', $token)));
    }

    private function canRequest($adminId)
    {
        $windowStart = date('Y-m-d H:i:s', time() - PASSWORD_RESET_WINDOW);
        $count = $this->db
            ->from(self::TABLE)
            ->where('admin_id', (int) $adminId)
            ->where('created_at >=', $windowStart)
            ->count_all_results();

        return $count < PASSWORD_RESET_MAX_REQUESTS;
    }

    private function isTokenFormatValid($token)
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/i', $token);
    }
}
