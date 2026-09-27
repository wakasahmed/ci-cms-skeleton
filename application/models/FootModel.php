<?php defined('BASEPATH') OR exit('No direct script access allowed');

class FootModel extends SqlModel
{
    private $table = 'pages';

    private $maxDepth = 1;

    /**
     * Allowlisted footer types mapped to their column suffix. Never build
     * this mapping from unchecked request input.
     */
    private $columnSuffixes = array('one' => 'one', 'two' => 'two', 'three' => 'three');

    public function __construct()
    {
        parent::__construct();
        $this->load->library('page_menu_hierarchy');
    }

    public function isValidType($type)
    {
        return isset($this->columnSuffixes[$type]);
    }

    /**
     * Returns one footer column's working set: a flat 'active' list (the
     * frontend footer renders no nesting either) and a flat 'available'
     * list. The type-specific columns are aliased to the generic names
     * Page_menu_hierarchy expects, so the same tree logic used for the
     * Main Menu applies unchanged.
     */
    public function getFooterMenuData($type)
    {
        $rows = $this->getRows($type);

        return $this->page_menu_hierarchy->buildTree($rows, $this->maxDepth);
    }

    public function getAllowedPages($type)
    {
        $rows = $this->getRows($type);
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

    private function getRows($type)
    {
        if (!$this->isValidType($type)) {
            return array();
        }

        $suffix = $this->columnSuffixes[$type];
        $fields = 'page_id,page_parent_id,page_slug,page_slug_ar,'
            .'page_name,page_name_ar,menu_name,menu_name_ar,'
            .'menu_parent_id_'.$suffix.' AS menu_parent_id,'
            .'menu_order_'.$suffix.' AS menu_order,'
            .'menu_active_'.$suffix.' AS menu_active';

        return $this->getRecords($fields, $this->table, 'menu_order_'.$suffix, 'ASC', array('page_status' => 'Published'));
    }
}
