<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Appointment reports for Manage > Reports.
 *
 * Everything is built from the service rows of the appointments dated in the
 * chosen range (appointment_services), so a visit shared by two artists is
 * split between them. Values are the list prices saved with each booking
 * ("from" prices), so they are estimates. Cancelled appointments are counted
 * but left out of the hours and value.
 */
class Report_model extends CI_Model
{
    /** Appointment statuses, in display order (as on the dashboard). */
    const STATUSES = array('New', 'Confirmed', 'Completed', 'Cancelled');

    /**
     * Service rows matching $filters, earliest first. $filters keys: from, to
     * (Y-m-d), status ('' for all), artist and service (0 for all).
     */
    public function rows(array $filters)
    {
        // Rows saved before Phase 8 have no artist of their own: use the visit's.
        $artistId = 'COALESCE(s.service_artist_id, a.appointment_artist_id)';
        $artistName = 'COALESCE(s.service_artist_name, a.appointment_artist_name)';

        $this->db
            ->select('a.appointment_id, a.appointment_reference, a.appointment_date, a.appointment_time,'
                .' a.appointment_status, a.customer_name, a.customer_email, a.customer_phone,'
                .' s.service_id, s.service_name, s.service_price, s.service_duration_minutes,'
                .' COALESCE(s.service_start_time, a.appointment_time) AS service_start_time,'
                .' '.$artistId.' AS artist_id, '.$artistName.' AS artist_name', FALSE)
            ->from('appointment_services s')
            ->join('appointments a', 'a.appointment_id = s.appointment_id')
            ->where('a.appointment_date >=', $filters['from'])
            ->where('a.appointment_date <=', $filters['to']);

        if ($filters['status'] !== '') {
            $this->db->where('a.appointment_status', $filters['status']);
        }
        if ($filters['artist'] > 0) {
            $this->db->where($artistId.' =', (int) $filters['artist'], FALSE);
        }
        if ($filters['service'] > 0) {
            $this->db->where('s.service_id', (int) $filters['service']);
        }

        return $this->db
            ->order_by('a.appointment_date', 'ASC')
            ->order_by('a.appointment_time', 'ASC')
            ->order_by('a.appointment_id', 'ASC')
            ->order_by('service_start_time', 'ASC', FALSE)
            ->get()
            ->result_array();
    }

    /**
     * The report for $filters: summary figures, the breakdowns by artist and
     * by service, and the appointments with their service rows.
     */
    public function build(array $filters)
    {
        $rows = $this->rows($filters);
        $summary = array(
            'appointments' => 0,
            'status' => array_fill_keys(self::STATUSES, 0),
            'minutes' => 0,
            'value' => 0.0,
            'cancelled_value' => 0.0,
        );
        $appointments = array();
        $byArtist = array();
        $byService = array();

        foreach ($rows as $row) {
            $id = (int) $row['appointment_id'];
            $cancelled = $row['appointment_status'] === 'Cancelled';
            $price = (float) $row['service_price'];
            $minutes = (int) $row['service_duration_minutes'];

            if (!isset($appointments[$id])) {
                $appointments[$id] = array(
                    'id' => $id,
                    'reference' => $row['appointment_reference'],
                    'date' => $row['appointment_date'],
                    'time' => $row['appointment_time'],
                    'status' => $row['appointment_status'],
                    'client' => $row['customer_name'],
                    'email' => $row['customer_email'],
                    'services' => array(),
                    'value' => 0.0,
                    'minutes' => 0,
                );
                $summary['appointments']++;
                if (isset($summary['status'][$row['appointment_status']])) {
                    $summary['status'][$row['appointment_status']]++;
                }
            }

            $appointments[$id]['services'][] = array(
                'name' => $row['service_name'],
                'artist' => (string) $row['artist_name'],
                'start' => $row['service_start_time'],
            );
            $appointments[$id]['value'] += $price;
            $appointments[$id]['minutes'] += $minutes;

            if ($cancelled) {
                $summary['cancelled_value'] += $price;
                continue;
            }

            $summary['minutes'] += $minutes;
            $summary['value'] += $price;

            $artistKey = $row['artist_id'] !== NULL ? 'id'.(int) $row['artist_id'] : 'name'.(string) $row['artist_name'];
            $this->addToGroup($byArtist, $artistKey, (string) $row['artist_name'] !== '' ? $row['artist_name'] : 'No artist', $id, $minutes, $price);
            $this->addToGroup($byService, 'id'.(int) $row['service_id'], $row['service_name'], $id, $minutes, $price);
        }

        return array(
            'summary' => $summary,
            'by_artist' => $this->sortGroups($byArtist),
            'by_service' => $this->sortGroups($byService),
            'appointments' => array_values($appointments),
        );
    }

    /** Artists and services for the filter selects. */
    public function filterOptions()
    {
        return array(
            'artists' => $this->db
                ->select('artist_id, artist_name, artist_status')
                ->order_by('artist_order', 'ASC')
                ->order_by('artist_name', 'ASC')
                ->get('artists')
                ->result_array(),
            'services' => $this->db
                ->select('s.service_id, s.service_name, c.category_name')
                ->from('services s')
                ->join('service_categories c', 'c.category_id = s.service_category_id', 'left')
                ->order_by('c.category_order', 'ASC')
                ->order_by('s.service_order', 'ASC')
                ->get()
                ->result_array(),
        );
    }

    private function addToGroup(array &$groups, $key, $label, $appointmentId, $minutes, $price)
    {
        if (!isset($groups[$key])) {
            $groups[$key] = array(
                'label' => $label,
                'appointments' => array(),
                'services' => 0,
                'minutes' => 0,
                'value' => 0.0,
            );
        }

        $groups[$key]['appointments'][$appointmentId] = TRUE;
        $groups[$key]['services']++;
        $groups[$key]['minutes'] += $minutes;
        $groups[$key]['value'] += $price;
    }

    /** Groups by value, then hours, highest first, with the appointment count. */
    private function sortGroups(array $groups)
    {
        $groups = array_map(function ($group) {
            $group['appointments'] = count($group['appointments']);

            return $group;
        }, array_values($groups));

        usort($groups, function ($a, $b) {
            if ($a['value'] != $b['value']) {
                return $a['value'] < $b['value'] ? 1 : -1;
            }

            return $b['minutes'] - $a['minutes'];
        });

        return $groups;
    }
}
