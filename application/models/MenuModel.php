<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MenuModel extends SqlModel
{
    private $table = 'pages';

    private $fields = 'page_id,page_parent_id,page_slug,page_slug_ar,'
        .'page_name,page_name_ar,menu_name,menu_name_ar,'
        .'menu_parent_id,menu_order,menu_active';

    private $maxDepth = 2;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('page_menu_hierarchy');
    }

    /**
     * Returns the Main Menu screen's full working set in one query:
     * an 'active' hierarchy (max 2 levels, matching the frontend menu
     * generator) and a flat 'available' list of pages not in the menu.
     */
    public function getMenuData()
    {
        $rows = $this->getRecords($this->fields, $this->table, 'menu_order', 'ASC', array('page_status' => 'Published'));

        return $this->page_menu_hierarchy->buildTree($rows, $this->maxDepth);
    }

    /**
     * Returns every allowed page_id => row (page_status = Published) so the
     * controller can validate a submitted payload against the real dataset.
     */
    public function getAllowedPages()
    {
        $rows = $this->getRecords($this->fields, $this->table, 'page_id', 'ASC', array('page_status' => 'Published'));
        $indexed = array();

        foreach ($rows as $row) {
            $indexed[(int) $row['page_id']] = $row;
        }

        return $indexed;
    }

    public function maxDepth()
    {
        return $this->maxDepth;
    }
}
