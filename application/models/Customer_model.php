<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Website customer accounts (Phase 10): the customers table, their sign-in
 * attempts and the appointments that belong to them.
 *
 * Accounts are optional. A booking made while signed in is linked through
 * appointments.customer_id; guest bookings made with an address are linked
 * to the account once that address is confirmed (claimGuestAppointments()).
 */
class Customer_model extends CI_Model
{
    const TABLE = 'customers';

    /** Failed sign-ins allowed per email address, and per IP, in the window. */
    const MAX_FAILED_BY_EMAIL = 5;
    const MAX_FAILED_BY_IP = 20;
    const FAILED_WINDOW_SECONDS = 900;

    public function find($customerId)
    {
        return $this->db
            ->where('customer_id', (int) $customerId)
            ->get(self::TABLE)
            ->row_array();
    }

    public function findByEmail($email)
    {
        return $this->db
            ->where('customer_email', $this->normalizeEmail($email))
            ->get(self::TABLE)
            ->row_array();
    }

    /** Lower-case and trimmed, the form email addresses are stored and matched in. */
    public function normalizeEmail($email)
    {
        return mb_strtolower(trim((string) $email));
    }

    /** Returns the new customer ID, or FALSE (for example when the email is taken). */
    public function create($name, $email, $phone, $passwordHash)
    {
        $now = date('Y-m-d H:i:s');
        $created = $this->db->insert(self::TABLE, array(
            'customer_name' => $name,
            'customer_email' => $this->normalizeEmail($email),
            'customer_phone' => $phone !== '' ? $phone : NULL,
            'customer_password' => $passwordHash,
            'customer_status' => 'Enable',
            'customer_added' => $now,
            'customer_updated' => $now,
        ));

        return $created ? (int) $this->db->insert_id() : FALSE;
    }

    public function update($customerId, array $changes)
    {
        $changes['customer_updated'] = date('Y-m-d H:i:s');

        return $this->db
            ->where('customer_id', (int) $customerId)
            ->update(self::TABLE, $changes);
    }

    public function recordSignIn($customerId, $ipAddress)
    {
        return $this->db
            ->where('customer_id', (int) $customerId)
            ->update(self::TABLE, array(
                'customer_last_login' => date('Y-m-d H:i:s'),
                'customer_last_ip' => substr((string) $ipAddress, 0, 45),
            ));
    }

    /** Mark the address confirmed and attach the guest bookings made with it. */
    public function markVerified($customerId)
    {
        $customer = $this->find($customerId);
        if (empty($customer)) {
            return FALSE;
        }

        $this->db->trans_start();
        if ($customer['customer_email_verified_at'] === NULL) {
            $this->update($customerId, array('customer_email_verified_at' => date('Y-m-d H:i:s')));
        }
        $this->claimGuestAppointments($customerId, $customer['customer_email']);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /** Guest bookings made with $email become this customer's. */
    public function claimGuestAppointments($customerId, $email)
    {
        return $this->db
            ->where('customer_id IS NULL', NULL, FALSE)
            ->where('LOWER(customer_email) =', $this->normalizeEmail($email))
            ->update('appointments', array('customer_id' => (int) $customerId));
    }

    /** A customer's appointments, each with its 'services' rows, latest first. */
    public function appointments($customerId)
    {
        $appointments = $this->db
            ->where('customer_id', (int) $customerId)
            ->order_by('appointment_date', 'DESC')
            ->order_by('appointment_time', 'DESC')
            ->get('appointments')
            ->result_array();

        return $this->withServices($appointments);
    }

    /** One of the customer's appointments by reference, with its services, or NULL. */
    public function appointment($customerId, $reference)
    {
        $appointment = $this->db
            ->where('customer_id', (int) $customerId)
            ->where('appointment_reference', (string) $reference)
            ->get('appointments')
            ->row_array();

        if (empty($appointment)) {
            return NULL;
        }

        $rows = $this->withServices(array($appointment));

        return $rows[0];
    }

    public function recordAttempt($email, $customerId, $wasSuccessful, $failureReason, $ipAddress, $userAgent)
    {
        return $this->db->insert('customer_login_attempts', array(
            'customer_id' => $customerId !== NULL ? (int) $customerId : NULL,
            'email' => $email !== '' ? mb_substr($this->normalizeEmail($email), 0, 190) : NULL,
            'was_successful' => $wasSuccessful ? 1 : 0,
            'failure_reason' => $failureReason,
            'ip_address' => substr((string) $ipAddress, 0, 45),
            'user_agent' => $userAgent !== NULL ? mb_substr((string) $userAgent, 0, 512) : NULL,
            'attempted_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Seconds until another sign-in may be tried for $email from $ipAddress
     * (0 when allowed). Counted in the database, so clearing cookies does not
     * reset it.
     */
    public function retryAfter($email, $ipAddress)
    {
        $since = date('Y-m-d H:i:s', time() - self::FAILED_WINDOW_SECONDS);
        $checks = array(
            array('email', $this->normalizeEmail($email), self::MAX_FAILED_BY_EMAIL),
            array('ip_address', (string) $ipAddress, self::MAX_FAILED_BY_IP),
        );
        $wait = 0;

        foreach ($checks as $check) {
            list($column, $value, $limit) = $check;
            if ($value === '') {
                continue;
            }

            $row = $this->db
                ->select('COUNT(*) AS failures, MIN(attempted_at) AS first_failure', FALSE)
                ->where($column, $value)
                ->where('was_successful', 0)
                ->where('failure_reason', 'invalid_credentials')
                ->where('attempted_at >=', $since)
                ->get('customer_login_attempts')
                ->row_array();

            if ((int) $row['failures'] >= $limit) {
                $wait = max($wait, self::FAILED_WINDOW_SECONDS - (time() - strtotime($row['first_failure'])));
            }
        }

        return max(0, $wait);
    }

    private function withServices(array $appointments)
    {
        if (empty($appointments)) {
            return array();
        }

        $rows = $this->db
            ->where_in('appointment_id', array_map('intval', array_column($appointments, 'appointment_id')))
            ->order_by('id', 'ASC')
            ->get('appointment_services')
            ->result_array();

        $services = array();
        foreach ($rows as $row) {
            $services[(int) $row['appointment_id']][] = $row;
        }

        foreach ($appointments as &$appointment) {
            $id = (int) $appointment['appointment_id'];
            $appointment['services'] = isset($services[$id]) ? $services[$id] : array();
        }
        unset($appointment);

        return $appointments;
    }
}
