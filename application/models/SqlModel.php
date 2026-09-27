<?php

class SqlModel extends CI_Model
{

    private $table;
    private $result;
    public $langPrefix = '';

    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();

    }




    /****************************** Start General Functions ***********************/

    //insert function
    function insertRecord($table, $colums)
    {
        if ($this->db->insert($table, $colums)) {
            return $this->db->insert_id();
        } else {
            return false;
        }
    }

    //update function
    function updateRecord($table, $colums, $condition)
    {
        if ($this->db->update($table, $colums, $condition)) {
            return true;
        } else {
            return false;
        }
    }

    function batchInsert($table, $data)
    {
        if ($this->db->insert_batch($table, $data)) {
            return true;
        } else {
            return false;
        }
    }

    function batchUpdate($table, $data, $whereKey)
    {
        if ($this->db->update_batch($table, $data, $whereKey)) {
            return true;
        } else {
            return false;
        }
    }

    // delete function
    function deleteRecord($table, $condition)
    {
        if ($this->db->delete($table, $condition)) {
            //echo $this->db->last_query();
            return true;
        } else {
            return false;
        }
    }

    function runQuery($sql, $flag = '')
    {
        $this->result = $this->db->query($sql);
        if ($flag) {
            return $this->result->row_array();
        } else {
            return $this->result->result_array();
        }
    }


    public function getSingleRecord($table, $where)
    {
        $this->db->select('*');
        $this->applyFrom($table);
        $this->db->where($where);
        $count = $this->db->count_all_results();
        if ($count == "1") {
            $this->db->select('*');
            $this->applyFrom($table);
            $this->db->where($where);
            $query = $this->db->get();
            $data = $query->row_array();
            return $data;
        } else {
            return null;
        }
    }

    public function getSingleField($col, $table, $where)
    {
        $this->db->select($col);
        $this->applyFrom($table);
        $this->db->where($where);
        $query = $this->db->get();
        $data = $query->row_array();
        if (isset($data[$col])) {
            return $data[$col];
        } else {
            return null;
        }
    }


    public function getRecords($fields, $table, $sortby = "", $order = "", $where = "", $search = array(), $limit = "0", $start = "0", $addqoutes = TRUE)
    {
        $this->db->select($fields, $addqoutes);
        $this->applyFrom($table);
        if (!empty($where)) {
            $this->db->where($where);
        }

        if (!empty($search) && isset($search['cols']) && isset($search['value']) && $search['cols'] != "" && $search['value'] != "") {
            $like = "(";
            $colArray = explode(",", $search['cols']);
            foreach ($colArray as $c) {
                $like .= " " . trim($c) . " LIKE '%" . $this->db->escape_like_str(trim($search['value'])) . "%' OR ";
            }

            $vs = explode(" ", $search['value']);
            if (count($vs) > 1) {
                foreach ($vs as $v) {
                    foreach ($colArray as $c) {
                        $like .= " " . trim($c) . " LIKE '%" . $this->db->escape_like_str(trim($v)) . "%' OR ";
                    }
                }
            }

            $like = rtrim($like, "OR ");
            $like .= ")";
            $this->db->where($like);
        }

        if ($sortby != "" && $order != "") {
            $this->db->order_by($sortby, $order);
        }

        if ($limit != "0") {
            $this->db->limit($limit, $start);

        }
        $query = $this->db->get();
        $data = $query->result_array();
        return $data;

    }


    public function countRecords($table, $where = array(), $search = array())
    {
        $this->db->select('*');
        $this->applyFrom($table);
        if (!empty($where)) {
            $this->db->where($where);
        }
        if (!empty($search) && isset($search['cols']) && isset($search['value']) && $search['cols'] != "" && $search['value'] != "") {
            $like = "(";
            $colArray = explode(",", $search['cols']);
            foreach ($colArray as $c) {
                $like .= " " . trim($c) . " LIKE '%" . $this->db->escape_like_str($search['value']) . "%' OR ";
            }

            $vs = explode(" ", $search['value']);
            if (count($vs) > 1) {
                foreach ($vs as $v) {
                    foreach ($colArray as $c) {
                        $like .= " " . trim($c) . " LIKE '%" . $this->db->escape_like_str($v) . "%' OR ";
                    }
                }
            }

            $like = rtrim($like, "OR ");
            $like .= ")";
            $this->db->where($like);
        }
        $records = $this->db->count_all_results();
        return $records;
    }

    /**
     * Add a table expression to Query Builder without letting CI3 quote an
     * entire JOIN clause as a single identifier.
     *
     * Existing callers historically pass either a normal table/alias or a
     * complete sequence such as "table t INNER JOIN other o ON ...". CI3's
     * from() protects identifiers more strictly than CI2, so JOIN sequences
     * must be expressed through join().
     */
    private function applyFrom($table)
    {
        if (stripos($table, ' join ') === FALSE) {
            $this->db->from($table);
            return;
        }

        $pattern = '/\s+(?:(LEFT OUTER|RIGHT OUTER|FULL OUTER|INNER|LEFT|RIGHT|FULL|OUTER)\s+)?JOIN\s+(.+?)\s+ON\s+(.+?)(?=\s+(?:(?:LEFT OUTER|RIGHT OUTER|FULL OUTER|INNER|LEFT|RIGHT|FULL|OUTER)\s+)?JOIN\s+|$)/i';

        if (preg_match_all($pattern, $table, $joins, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0) {
            $this->db->from($table);
            return;
        }

        $baseTable = trim(substr($table, 0, $joins[0][0][1]));
        $this->db->from($baseTable);

        foreach ($joins as $join) {
            $type = isset($join[1][0]) ? trim($join[1][0]) : '';
            $joinTable = trim($join[2][0]);
            $condition = trim($join[3][0]);
            $this->db->join($joinTable, $condition, $type);
        }
    }

    public function checkCookie()
    {
        if ($this->input->cookie('zenvita_remember') && $this->input->cookie('zenvita_remember') != "" && $this->session->userdata('usr_auth') != "Yes") {

            $data = $this->SqlModel->getSingleRecord('zen_users', array('user_id' => $this->encrypt->decode($this->input->cookie('zenvita_remember', true)), 'user_status' => 'Enable'));

            if (!empty($data)) {
                $this->session->set_userdata('usr_auth', 'Yes');
                $this->session->set_userdata('usr_id', $data['user_id']);
                $this->session->set_userdata('user_type', $data['user_type']);
            }
        }
    }

    public function truncate($table = "")
    {
        if ($table == "") {
            return;
        }
        if ($this->db->truncate($table)) {
            return true;
        } else {
            return false;
        }
    }

    public function seostring($rstring = "")
    {
        $string = preg_replace('/\%/', ' percentage', $rstring);
        $string = preg_replace('/\@/', ' at ', $string);
        $string = preg_replace('/\&/', ' and ', $string);
        $string = preg_replace('/\s[\s]+/', '-', $string);    // Strip off multiple spaces
        $string = preg_replace('/[\s\W]+/', '-', $string);    // Strip off spaces and non-alpha-numeric
        $string = preg_replace('/^[\-]+/', '', $string); // Strip off the starting hyphens
        $string = preg_replace('/[\-]+$/', '', $string); // // Strip off the ending hyphens
        $string = strtolower($string);
        $string = str_replace(" ", "-", $string) . '.html';
        return $string;
    }


    public function authAdmin($authKey = "", $adminID = "")
    {
        if ($authKey == "allow" && $adminID != "") {
            $adminData = $this->getSingleRecord('admin_users', array('id' => $adminID, 'status' => 'Enable'));
            if (empty($adminData)) {
                return false;
            } else {
                $this->setTitle();
                return $adminData;
            }
        }

        $this->load->model('AdminRememberTokenModel');
        $cookieValue = (string) $this->input->cookie(AdminRememberTokenModel::COOKIE_NAME);
        if ($cookieValue === '') {
            return false;
        }

        $adminData = $this->AdminRememberTokenModel->authenticate($cookieValue);
        if (empty($adminData)) {
            $this->AdminRememberTokenModel->clearCookie();
            return false;
        }

        $cookie = $adminData['cookie_value'];
        $cookieLifetime = $adminData['cookie_lifetime'];
        unset($adminData['cookie_value'], $adminData['cookie_lifetime']);

        $this->session->sess_regenerate(TRUE);
        $this->session->set_userdata(array(
            'admin_auth' => 'allow',
            'admin_role' => $adminData['user_role'],
            'admin_user_name' => $adminData['user_name'],
            'admin_full_name' => $adminData['full_name'],
            'admin_id' => $adminData['id'],
            'last_login' => $adminData['last_login'],
            'last_ip' => $adminData['ip']
        ));

        // Refresh activity fields for this remember-me auto-login the same way a manual login does.
        $this->updateRecord(
            'admin_users',
            array(
                'last_login' => date('Y-m-d H:i:s'),
                'ip' => substr((string) $this->input->ip_address(), 0, 25),
                'user_agent' => $this->input->user_agent()
            ),
            array('id' => $adminData['id'])
        );

        $this->AdminRememberTokenModel->setCookie($cookie, $cookieLifetime);
        $this->setTitle();

        return $adminData;

    }

    public function setTitle()
    {
        $webTitle = $this->getSingleField('website_title', 'site_settings', array('id' => 1));
        if (!defined('PROJECT_TITLE')) {
            define('PROJECT_TITLE', $webTitle);
        }


    }

    //For getting slider images
    public function getSlider($table = "", $id = "0")
    {
        $d = array();
        if ($table != "" && (int) $id > 0) {
            $query = "SELECT * FROM " . $table . " s WHERE s.slider_id = '" . (int) $id . "' AND s.status = 'Enable' ORDER BY s.`order` ASC, s.id ASC";
            $d = $this->runQuery($query);
        }
        return $d;
    }

    //For getting navigation
    public function getNav($page_id = 0)
    {
        $nav = $this->getRecords('page_parent_id,menu_parent_id,menu_name,menu_name_ar,page_name,page_slug,page_slug_ar,page_id', 'pages', 'menu_order', 'ASC', array('page_status' => 'Published', 'menu_active' => 1, 'menu_parent_id' => 0));
        $html = "";
        if (!empty($nav)) {
            if ($this->langPrefix == "_ar") {
                $nav = array_reverse($nav);
            }
            foreach ($nav as $n) {
                $nnav = $this->getRecords('page_parent_id,menu_parent_id,menu_name,menu_name_ar,page_name,page_slug,page_slug_ar,page_id', 'pages', 'menu_order', 'ASC', array('page_status' => 'Published', 'menu_active' => 1, 'menu_parent_id' => $n['page_id']));

                $innerHtml = "";
                $active = "";
                if (!empty($nnav)) {
                    $innerHtml = '<ul>';
                    foreach ($nnav as $nn) {
                        if ($nn['page_id'] == $page_id) {
                            $active = "active";
                        }
                        $innerHtml .= '<li><a  title="' . $nn['menu_name'.$this->langPrefix] . '" href="' . base_url($nn['page_slug'.$this->langPrefix] . '.html') . '">' . $nn['menu_name'.$this->langPrefix] . '</a></li>';
                    }
                    $innerHtml .= '</ul>';
                }
                $html .= '<li ' . ((!empty($nnav)) ? 'class="submenu"' : '') . '><a  class="' . (($page_id == $n['page_id']) ? ' active ' : '') . ' ' . $active . ' ' . ((!empty($nnav)) ? ' show-submenu ' : '') . ' " title="' . $n['menu_name'.$this->langPrefix] . '" href="' . (($n['page_id']=="1") ? base_url() : base_url($n['page_slug'.$this->langPrefix] . '.html')) . '">' . $n['menu_name'.$this->langPrefix] . ((!empty($nnav)) ? ' <i class="icon-down-open-mini"></i> ' : '') . '</a>' . $innerHtml;

                $html .= '</li>';

            }
        }

        return $html;
    }


    public function checkAccess($type = "", $uD = array())
    {
        if ($type == "" || empty($uD)) {
            redirect(ADMIN_URL);
            exit;
        }
        //echo $type.'<Br>'.$uD['user_role'].'<br/>'.$uD[$type];
        //exit;
        if ($uD['user_role'] == "Admin" && (!isset($uD[$type]) || $uD[$type] == "No")) {
            return false;
        } else {
            return true;
        }


    }

    public function publish()
    {
        $page_pub = "UPDATE pages p SET page_status='Published', created_at='0000-00-00 00:00:00' WHERE p.`created_at`<='" . date('Y-m-d H:i:s') . "' AND p.`created_at`!='0000-00-00 00:00:00'";
        @$this->db->query($page_pub);

        $page_pub = "UPDATE pages p SET page_status='Un-Published', updated_at='0000-00-00 00:00:00' WHERE p.`updated_at`<='" . date('Y-m-d H:i:s') . "' AND p.`updated_at`!='0000-00-00 00:00:00'";
        @$this->db->query($page_pub);

        $blog_pub = "UPDATE blogs p SET blog_status='Published', blog_pdate='0000-00-00 00:00:00' WHERE p.`blog_pdate`<='" . date('Y-m-d H:i:s') . "' AND p.`blog_pdate`!='0000-00-00 00:00:00'";
        @$this->db->query($page_pub);

        $blog_pub = "UPDATE blogs p SET blog_status='Un-Published', blog_update='0000-00-00 00:00:00' WHERE p.`blog_update`<='" . date('Y-m-d H:i:s') . "' AND p.`blog_update`!='0000-00-00 00:00:00'";
        @$this->db->query($page_pub);

    }

    //For Sub pages
    public function getSubPages($page_id, $html)
    {
        $subhtml = "";
        if (strpos($html, '{subpages}') !== FALSE) {
            $subPages = $this->runQuery("SELECT p.`page_slug`,p.`page_slug_ar`,p.`page_name` FROM pages p WHERE p.`page_status`='Published' AND p.`created_at`='0000-00-00 00:00:00' AND p.`page_parent_id`=" . $page_id . " ORDER BY p.`page_order` ASC");
            if (!empty($subPages)) {
                $subhtml .= '<blockquote class="orange"><div class="button-wrapper">';
                $i = 1;
                foreach ($subPages as $s) {
                    $subhtml .= '<a href="' . base_url($s['page_slug'.$this->langPrefix]) . '.html">' . $s['page_name'] . '</a>';
                    if ($i < count($subPages)) {
                        $subhtml .= '<br><br>';
                    }
                    $i++;
                }
                $subhtml .= '</div></blockquote>';
                $html = str_replace("{subpages}", $subhtml, $html);
                return $html;

            }

        } else {
            return $html;
        }


    }

    public function getFoot($type = "", $locale = NULL)
    {
        $html = "";
        if ($type != "one" && $type != "two" && $type != "three") {
            return $html;
        }

        $isLocalizedFooter = $locale !== NULL;
        $languagePrefix = $this->langPrefix;
        if ($locale !== NULL) {
            $locale = strtolower(trim((string) $locale));
            $languagePrefix = in_array($locale, array('ar', 'arabic'), TRUE) ? '_ar' : '';
        }

        $type = "_" . strtolower($type);
        $foot = $this->runQuery(
            "SELECT page_id,menu_name,menu_name_ar,page_slug,page_slug_ar "
            ."FROM pages WHERE menu_parent_id".$type." = '0' "
            ."AND menu_active".$type." = 1 ORDER BY menu_order".$type." ASC"
        );
        $totalFoot = count($foot);
        $footCnt = (int) 1;

        if (count($foot) > 0) {
            foreach ($foot as $f) {
                if ($isLocalizedFooter && $footCnt > 1) {
                    $html .= '<span class="footer-link-separator" aria-hidden="true">&bull;</span>';
                }

                $linkPrefix = $isLocalizedFooter ? '' : '- ';
                $html .= '<a href="'.base_url($f['page_slug'.$languagePrefix].'.html').'">'.$linkPrefix
                    .$f['menu_name'.$languagePrefix].'</a>';
                $footCnt++;
            }
        }

        return $html;
    }

    public function checkAuth()
    {

        if ($this->session->userdata('usr_auth') == 'Yes') {
            $userData = $this->getSingleRecord('zen_users', array('user_status' => 'Enable', 'user_id' => $this->session->userdata('usr_id')));
            if (empty($userData)) {
                $this->session->set_userdata('usr_auth', '');
                $this->session->set_userdata('usr_id', '');
                $this->session->set_userdata('user_type', '');

            } else {
                return $userData;
            }
        } else {
            return false;
        }

    }

    public function getLoginUser($un = "")
    {
        $query = "SELECT * FROM zen_users WHERE user_email='" . $this->db->escape_str($un) . "' LIMIT 1";
        //$query = "SELECT * FROM zen_users WHERE user_name='".$this->db->escape_str($un)."' OR user_email='".$this->db->escape_str($un)."' LIMIT 1";
        $userData = $this->runQuery($query, 1);
        return $userData;


    }

    public function getRandomBanner()
    {
        return $this->runQuery("SELECT * FROM banners WHERE type=1 and banner_status='Enable' ORDER BY RAND() ASC LIMIT 1", 1);

    }
    public function getRandomBannerS()
    {
        return $this->runQuery("SELECT * FROM banners WHERE type=2 and banner_status='Enable' ORDER BY RAND() ASC LIMIT 1", 1);

    }
    public function getToursIN($tours)
    {
        $this->db->where_in('tour_id', $tours);
        $query = $this->db->get('tours');
        return $query->result_array();
    }

}

?>
