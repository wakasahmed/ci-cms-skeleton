<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * URL slug normalisation and uniqueness for manage/admin modules.
 *
 * normalize() mirrors generateSlug() in assets/admin/js/custom.js so the slug
 * an administrator sees while typing is the slug that gets saved.
 *
 * Table and column names passed to unique() must come from controller
 * configuration, never from the request.
 */
class Admin_slug
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function normalize($value, $maxLength = 160)
    {
        $value = html_entity_decode(trim((string) $value), ENT_QUOTES, 'UTF-8');

        if (class_exists('Normalizer')) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KD);
        }

        $value = preg_replace('/\p{Mn}+/u', '', $value);
        $value = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $value = $this->truncate(trim($value, '-'), $maxLength);

        return trim($value, '-');
    }

    /**
     * Returns $base, or $base with a numeric suffix, so that no other row in
     * $table uses it in $column. $excludeId is the record being edited.
     */
    public function unique($table, $column, $primaryKey, $base, $excludeId = 0, $maxLength = 160)
    {
        $base = $this->truncate(trim((string) $base), $maxLength);
        $excludeId = (int) $excludeId;

        if (!$this->exists($table, $column, $primaryKey, $base, $excludeId)) {
            return $base;
        }

        for ($suffix = 2; $suffix <= 50; $suffix++) {
            $ending = '-'.$suffix;
            $candidate = $this->truncate($base, $maxLength - strlen($ending)).$ending;

            if (!$this->exists($table, $column, $primaryKey, $candidate, $excludeId)) {
                return $candidate;
            }
        }

        return $this->truncate($base, $maxLength - 9).'-'.bin2hex(random_bytes(4));
    }

    private function exists($table, $column, $primaryKey, $slug, $excludeId)
    {
        $this->CI->db->from($table);
        $this->CI->db->where($column, $slug);

        if ($excludeId > 0) {
            $this->CI->db->where($primaryKey.' !=', $excludeId);
        }

        return $this->CI->db->count_all_results() > 0;
    }

    private function truncate($value, $maxLength)
    {
        return function_exists('mb_substr')
            ? mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8')
            : substr((string) $value, 0, (int) $maxLength);
    }
}
