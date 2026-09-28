<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Site search (/search) across the public services, offers, artists,
 * journal articles and FAQs. A record matches when every search word
 * appears in at least one of its searched columns. Each group follows the
 * same public rules and order as its own page.
 */
class Search_model extends SqlModel
{
    /** Most results listed per group. */
    const GROUP_LIMIT = 20;

    /** Public services with their category. */
    public function services(array $words)
    {
        $this->db
            ->select('s.service_name, s.service_slug, s.service_summary, s.service_price_from,'
                .' s.service_price_suffix, s.service_duration_label, c.category_name')
            ->from('services s')
            ->join('service_categories c', 'c.category_id = s.service_category_id')
            ->where('s.service_status', 'Enable')
            ->where('c.category_status', 'Enable');
        $this->matchWords($words, array(
            's.service_name',
            's.service_summary',
            's.service_description',
            'c.category_name',
        ));

        return $this->db
            ->order_by('c.category_order', 'ASC')
            ->order_by('s.service_order', 'ASC')
            ->limit(self::GROUP_LIMIT)
            ->get()
            ->result_array();
    }

    /** Enabled offers inside their validity dates. */
    public function offers(array $words)
    {
        $today = date('Y-m-d');

        $this->db
            ->select('offer_title, offer_slug, offer_summary, offer_price, offer_label')
            ->from('offers')
            ->where('offer_status', 'Enable')
            ->group_start()
                ->where('offer_valid_from IS NULL', NULL, FALSE)
                ->or_where('offer_valid_from <=', $today)
            ->group_end()
            ->group_start()
                ->where('offer_valid_to IS NULL', NULL, FALSE)
                ->or_where('offer_valid_to >=', $today)
            ->group_end();
        $this->matchWords($words, array(
            'offer_title',
            'offer_label',
            'offer_summary',
            'offer_inclusions',
        ));

        return $this->db
            ->order_by('offer_order', 'ASC')
            ->limit(self::GROUP_LIMIT)
            ->get()
            ->result_array();
    }

    /** Enabled artists. */
    public function artists(array $words)
    {
        $this->db
            ->select('artist_name, artist_slug, artist_role, artist_specialties')
            ->from('artists')
            ->where('artist_status', 'Enable');
        $this->matchWords($words, array(
            'artist_name',
            'artist_role',
            'artist_specialties',
            'artist_bio',
        ));

        return $this->db
            ->order_by('artist_order', 'ASC')
            ->limit(self::GROUP_LIMIT)
            ->get()
            ->result_array();
    }

    /** Published journal articles, newest first. */
    public function articles(array $words)
    {
        $this->db
            ->select('blog_name, blog_slug, blog_short_description, blog_time_to_read')
            ->from('blogs')
            ->where('blog_status', 'Published');
        $this->matchWords($words, array(
            'blog_name',
            'blog_short_description',
            'blog_text',
        ));

        return $this->db
            ->order_by('COALESCE(blog_pdate, blog_added)', 'DESC', FALSE)
            ->limit(self::GROUP_LIMIT)
            ->get()
            ->result_array();
    }

    /** Enabled FAQs in visible, enabled categories. */
    public function faqs(array $words)
    {
        $this->db
            ->select('f.faq_question, f.faq_answer, c.cat_name')
            ->from('faqs f')
            ->join('faqs_categories c', 'c.cat_id = f.faq_cat_id')
            ->where('f.faq_status', 'Enable')
            ->where('c.cat_status', 'Enable')
            ->where('c.cat_hidden', 'No');
        $this->matchWords($words, array(
            'f.faq_question',
            'f.faq_answer',
        ));

        return $this->db
            ->order_by('c.cat_order', 'ASC')
            ->order_by('f.faq_order', 'ASC')
            ->limit(self::GROUP_LIMIT)
            ->get()
            ->result_array();
    }

    /** Every word must appear in one of $columns (LIKE values are escaped by the query builder). */
    private function matchWords(array $words, array $columns)
    {
        foreach ($words as $word) {
            $this->db->group_start();
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $this->db->like($column, $word);
                } else {
                    $this->db->or_like($column, $word);
                }
            }
            $this->db->group_end();
        }
    }
}
