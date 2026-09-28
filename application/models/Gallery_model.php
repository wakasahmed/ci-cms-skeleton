<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only gallery queries backing the public frontend. An image is public
 * when it is enabled and its category (if any) is enabled; lists follow the
 * gallery order in Manage > Gallery.
 */
class Gallery_model extends SqlModel
{
    /** The first public images in gallery order ("Recent work"). */
    public function get_recent($limit = 6)
    {
        $this->publicImages();
        $this->db->limit((int) $limit);

        return $this->db->get()->result_array();
    }

    /** Featured public images, in gallery order (home page). */
    public function get_featured($limit = 6)
    {
        $this->publicImages();
        $this->db->where('i.image_featured', 1);
        $this->db->limit((int) $limit);

        return $this->db->get()->result_array();
    }

    /** Every public image, in gallery order. */
    public function get_all()
    {
        $this->publicImages();

        return $this->db->get()->result_array();
    }

    /** Enabled categories that have public images, with their image counts. */
    public function get_categories()
    {
        return $this->db
            ->select('c.category_name, c.category_slug, COUNT(i.image_id) AS image_count')
            ->from('gallery_categories c')
            ->join('gallery_images i', "i.image_category_id = c.category_id AND i.image_status = 'Enable'", 'inner', FALSE)
            ->where('c.category_status', 'Enable')
            ->group_by('c.category_id')
            ->order_by('c.category_order', 'ASC')
            ->order_by('c.category_id', 'ASC')
            ->get()
            ->result_array();
    }

    /** Starts a query over public images with their category name. */
    private function publicImages()
    {
        $this->db->select('i.image_id, i.image_file, i.image_caption, c.category_name, c.category_slug');
        $this->db->from('gallery_images i');
        $this->db->join('gallery_categories c', 'c.category_id = i.image_category_id', 'left');
        $this->db->where('i.image_status', 'Enable');
        $this->db->group_start();
        $this->db->where('c.category_status', 'Enable');
        $this->db->or_where('i.image_category_id IS NULL', NULL, FALSE);
        $this->db->group_end();
        $this->db->order_by('i.image_order', 'ASC');
        $this->db->order_by('i.image_id', 'ASC');
    }
}
