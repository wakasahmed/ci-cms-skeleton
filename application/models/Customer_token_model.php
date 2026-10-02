<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Secrets for customer accounts, stored only as SHA-256 hashes:
 *
 * - one-use links (customer_tokens): 'verify' confirms the email address,
 *   'reset' sets a new password. Request limits reuse the admin reset policy
 *   (PASSWORD_RESET_MAX_REQUESTS per PASSWORD_RESET_WINDOW).
 * - "remember me" cookies (customer_remember_tokens): selector + validator,
 *   the validator rotated on every use, as AdminRememberTokenModel does.
 */
class Customer_token_model extends CI_Model
{
    const LINKS = 'customer_tokens';
    const REMEMBER = 'customer_remember_tokens';
    const COOKIE_NAME = 'customer_remember';
    const REMEMBER_LIFETIME = 2592000;

    /** A new one-use link token for $type ('verify' or 'reset'), or FALSE when rate limited. */
    public function createLink($customerId, $type, $ttl, $ipAddress)
    {
        if (!in_array($type, array('verify', 'reset'), TRUE)) {
            return FALSE;
        }

        $since = date('Y-m-d H:i:s', time() - PASSWORD_RESET_WINDOW);
        $recent = $this->db
            ->where('customer_id', (int) $customerId)
            ->where('token_type', $type)
            ->where('created_at >=', $since)
            ->count_all_results(self::LINKS);
        if ($recent >= PASSWORD_RESET_MAX_REQUESTS) {
            return FALSE;
        }

        $token = bin2hex(random_bytes(32));
        $created = $this->db->insert(self::LINKS, array(
            'customer_id' => (int) $customerId,
            'token_type' => $type,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + (int) $ttl),
            'requested_ip' => substr((string) $ipAddress, 0, 45),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return $created ? $token : FALSE;
    }

    /** The unused, unexpired link of $type with its enabled customer, or NULL. */
    public function findLink($token, $type)
    {
        if (!$this->isTokenFormatValid($token)) {
            return NULL;
        }

        return $this->db
            ->select('t.token_id, t.customer_id, t.expires_at, c.*')
            ->from(self::LINKS.' t')
            ->join('customers c', 'c.customer_id = t.customer_id', 'inner')
            ->where('t.token_hash', hash('sha256', $token))
            ->where('t.token_type', $type)
            ->where('t.used_at IS NULL', NULL, FALSE)
            ->where('t.expires_at >=', date('Y-m-d H:i:s'))
            ->where('c.customer_status', 'Enable')
            ->limit(1)
            ->get()
            ->row_array();
    }

    /**
     * Mark a link used, together with every other open link of its type, so
     * each works once. FALSE when it was used or expired meanwhile.
     */
    public function useLink(array $link, $type)
    {
        $now = date('Y-m-d H:i:s');
        $claimed = $this->db
            ->where('token_id', (int) $link['token_id'])
            ->where('used_at IS NULL', NULL, FALSE)
            ->where('expires_at >=', $now)
            ->update(self::LINKS, array('used_at' => $now));

        if (!$claimed || $this->db->affected_rows() !== 1) {
            return FALSE;
        }

        $this->db
            ->where('customer_id', (int) $link['customer_id'])
            ->where('token_type', $type)
            ->where('used_at IS NULL', NULL, FALSE)
            ->update(self::LINKS, array('used_at' => $now));

        return TRUE;
    }

    public function deleteLink($token)
    {
        if (!$this->isTokenFormatValid($token)) {
            return FALSE;
        }

        return $this->db->delete(self::LINKS, array('token_hash' => hash('sha256', $token)));
    }

    /** "selector:validator" for a new remember-me cookie, or FALSE. */
    public function createRemember($customerId, $ipAddress, $userAgent)
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        $created = $this->db->insert(self::REMEMBER, array(
            'customer_id' => (int) $customerId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $validator),
            'expires_at' => date('Y-m-d H:i:s', time() + self::REMEMBER_LIFETIME),
            'created_ip' => substr((string) $ipAddress, 0, 45),
            'user_agent' => mb_substr((string) $userAgent, 0, 512),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return $created ? $selector.':'.$validator : FALSE;
    }

    /**
     * The enabled customer behind a remember-me cookie, with a rotated
     * 'cookie_value' and its remaining 'cookie_lifetime', or NULL. A cookie
     * whose validator does not match is revoked (it may have been stolen).
     */
    public function useRemember($cookieValue)
    {
        $parts = $this->parseCookie($cookieValue);
        if ($parts === FALSE) {
            return NULL;
        }

        $record = $this->db
            ->select('t.id AS remember_id, t.token_hash, t.expires_at, c.*')
            ->from(self::REMEMBER.' t')
            ->join('customers c', 'c.customer_id = t.customer_id', 'inner')
            ->where('t.selector', $parts['selector'])
            ->where('t.expires_at >=', date('Y-m-d H:i:s'))
            ->where('c.customer_status', 'Enable')
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($record) || !hash_equals($record['token_hash'], hash('sha256', $parts['validator']))) {
            $this->db->delete(self::REMEMBER, array('selector' => $parts['selector']));

            return NULL;
        }

        $validator = bin2hex(random_bytes(32));
        $this->db
            ->where('id', (int) $record['remember_id'])
            ->update(self::REMEMBER, array(
                'token_hash' => hash('sha256', $validator),
                'last_used_at' => date('Y-m-d H:i:s'),
            ));

        $record['cookie_value'] = $parts['selector'].':'.$validator;
        $record['cookie_lifetime'] = max(1, strtotime($record['expires_at']) - time());
        unset($record['remember_id'], $record['token_hash']);

        return $record;
    }

    public function revokeRemember($cookieValue)
    {
        $parts = $this->parseCookie($cookieValue);
        if ($parts !== FALSE) {
            $this->db->delete(self::REMEMBER, array('selector' => $parts['selector']));
        }
    }

    public function revokeAllRemember($customerId)
    {
        return $this->db->delete(self::REMEMBER, array('customer_id' => (int) $customerId));
    }

    private function parseCookie($cookieValue)
    {
        $parts = explode(':', (string) $cookieValue, 2);
        if (count($parts) !== 2
            || !preg_match('/^[a-f0-9]{32}$/i', $parts[0])
            || !preg_match('/^[a-f0-9]{64}$/i', $parts[1])
        ) {
            return FALSE;
        }

        return array(
            'selector' => strtolower($parts[0]),
            'validator' => strtolower($parts[1]),
        );
    }

    private function isTokenFormatValid($token)
    {
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }
}
