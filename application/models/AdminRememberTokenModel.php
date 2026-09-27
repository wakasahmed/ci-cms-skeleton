<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class AdminRememberTokenModel extends CI_Model
{
    const TABLE = 'admin_remember_tokens';
    const COOKIE_NAME = 'admin_remember';
    const LIFETIME = 2592000;

    public function isReady()
    {
        return $this->db->table_exists(self::TABLE);
    }

    public function createToken($adminId, $ipAddress, $userAgent)
    {
        if (!$this->isReady()) {
            return FALSE;
        }

        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
        } catch (Exception $exception) {
            log_message('error', 'Unable to generate an admin remember token: '.$exception->getMessage());
            return FALSE;
        }

        $created = $this->db->insert(self::TABLE, array(
            'admin_id' => (int) $adminId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $validator),
            'expires_at' => date('Y-m-d H:i:s', time() + self::LIFETIME),
            'created_ip' => substr((string) $ipAddress, 0, 45),
            'user_agent' => substr((string) $userAgent, 0, 512),
            'created_at' => date('Y-m-d H:i:s')
        ));

        if (!$created) {
            return FALSE;
        }

        return $selector.':'.$validator;
    }

    public function authenticate($cookieValue)
    {
        $parts = $this->parseCookie($cookieValue);
        if (!$this->isReady() || $parts === FALSE) {
            return NULL;
        }

        $record = $this->db
            ->select('t.id AS token_id, t.token_hash, t.expires_at, u.*')
            ->from(self::TABLE.' t')
            ->join('admin_users u', 'u.id = t.admin_id', 'inner')
            ->where('t.selector', $parts['selector'])
            ->where('t.expires_at >=', date('Y-m-d H:i:s'))
            ->where('u.status', 'Enable')
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($record) || !hash_equals($record['token_hash'], hash('sha256', $parts['validator']))) {
            $this->revokeBySelector($parts['selector']);
            return NULL;
        }

        try {
            $newValidator = bin2hex(random_bytes(32));
        } catch (Exception $exception) {
            log_message('error', 'Unable to rotate an admin remember token: '.$exception->getMessage());
            return NULL;
        }

        $updated = $this->db
            ->where('id', (int) $record['token_id'])
            ->update(self::TABLE, array(
                'token_hash' => hash('sha256', $newValidator),
                'last_used_at' => date('Y-m-d H:i:s')
            ));

        if (!$updated) {
            return NULL;
        }

        $record['cookie_value'] = $parts['selector'].':'.$newValidator;
        $record['cookie_lifetime'] = max(1, strtotime($record['expires_at']) - time());
        unset($record['token_id'], $record['token_hash']);

        return $record;
    }

    public function revokeCookie($cookieValue)
    {
        $parts = $this->parseCookie($cookieValue);
        if ($this->isReady() && $parts !== FALSE) {
            $this->revokeBySelector($parts['selector']);
        }
    }

    public function revokeAllForAdmin($adminId)
    {
        if (!$this->isReady()) {
            return TRUE;
        }

        return $this->db->delete(self::TABLE, array('admin_id' => (int) $adminId));
    }

    public function setCookie($value, $lifetime = self::LIFETIME)
    {
        $this->input->set_cookie(array(
            'name' => self::COOKIE_NAME,
            'value' => (string) $value,
            'expire' => max(1, (int) $lifetime),
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => TRUE,
            'samesite' => 'Lax'
        ));
    }

    public function clearCookie()
    {
        $this->input->set_cookie(array(
            'name' => self::COOKIE_NAME,
            'value' => '',
            'expire' => '',
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => TRUE,
            'samesite' => 'Lax'
        ));
    }

    private function revokeBySelector($selector)
    {
        return $this->db->delete(self::TABLE, array('selector' => $selector));
    }

    private function isSecureRequest()
    {
        $https = strtolower((string) $this->input->server('HTTPS'));

        return $https !== '' && $https !== 'off';
    }

    private function parseCookie($cookieValue)
    {
        if (!is_string($cookieValue)) {
            return FALSE;
        }

        $parts = explode(':', $cookieValue, 2);
        if (count($parts) !== 2
            || !preg_match('/^[a-f0-9]{32}$/i', $parts[0])
            || !preg_match('/^[a-f0-9]{64}$/i', $parts[1])) {
            return FALSE;
        }

        return array(
            'selector' => strtolower($parts[0]),
            'validator' => strtolower($parts[1])
        );
    }
}
