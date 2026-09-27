<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
	public $moduleName = "Home";
	public $controller = "home";
	public $user_data = array();

    /** Reporting periods (in days) the dashboard accepts through ?range=. */
    private $dashboardRanges = array(7, 30, 90);

	 public function __construct(){

        // Call the Model constructor
	   parent::__construct();
	 
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'),$this->session->userdata('admin_id'));
		
		if(empty($this->user_data))
		{
			redirect(base_url('manage/login'));
		}
		
		
    }
	
    public function index()
    {
        $this->load->model('Dashboard_model');
        $rangeDays = $this->dashboardRange();

        $data = array(
            'dashBoard' => 1,
            'page_title' => PROJECT_TITLE.' | Dashboard',
            'userdata' => $this->user_data,
            'dashboard' => $this->Dashboard_model->build($rangeDays),
            'dashboardRange' => $rangeDays,
            'dashboardRanges' => $this->dashboardRanges,
            'useDashboardCharts' => TRUE,
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/dashboard');
        $this->load->view('admin/footer');
    }

    private function dashboardRange()
    {
        $requested = $this->input->get('range');

        if (is_string($requested) && ctype_digit($requested) && in_array((int) $requested, $this->dashboardRanges, TRUE)) {
            return (int) $requested;
        }

        return 30;
    }

	public function settings($alert="")
	{
		
		$data['page_title'] = PROJECT_TITLE." | Account Settings";
		$data['alert'] = $alert;
		$data['userdata'] = $this->user_data;
		$this->load->view('admin/header',$data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/adminSettings');
		$this->load->view('admin/footer');
	}
	
	
	
	public function savesettings()
	{
		if($this->input->post('admin_name')=="" || $this->input->post('admin_email')=="")
		{
		redirect(base_url().'manage/home/settings/error', 'location');
		exit();
		}
		
		if($this->SqlModel->countRecords('admin_users' , array('email'=>$this->input->post('admin_email'),'id !='=>$this->user_data['id']))>0)
		{
		redirect(base_url().'manage/home/settings/exist', 'location');
		exit();
		}
		
		
		
		$this->SqlModel->updateRecord('admin_users' , array('full_name'=>$this->input->post('admin_name'), 'email'=>$this->input->post('admin_email')) , array('id'=>$this->user_data['id']));
		
		
		if($this->input->post('admin_current_pwd')!="")
		{
			$currentPassword = (string) $this->input->post('admin_current_pwd');
			$storedPassword = $this->user_data['pwd'];
			$passwordMatches = password_verify($currentPassword, $storedPassword);
			if(!$passwordMatches)
			{
			redirect(base_url().'manage/home/settings/perror', 'location');	
			exit();
			}
			else{
				$this->SqlModel->updateRecord('admin_users' , array('pwd'=>password_hash($this->input->post('admin_new_pwd'), PASSWORD_DEFAULT)) , array('id'=>$this->user_data['id']));
			}
		}
		
		redirect(base_url().'manage/home/settings/success', 'location');
	}
	
	
	
	public function logout(){
		$this->load->model('AdminRememberTokenModel');
		$this->AdminRememberTokenModel->revokeCookie(
			(string) $this->input->cookie(AdminRememberTokenModel::COOKIE_NAME)
		);
		$this->AdminRememberTokenModel->clearCookie();
		$this->session->sess_destroy();
		redirect(base_url().'manage','location');
	}
	
	/*
	public function table($alert="")
	{
		$data['page_title'] = PROJECT_TITLE." | Dashboard";
		$data['alert'] = $alert;
		$data['userdata'] = $this->user_data;
		$this->load->view('admin/header',$data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/mainTable');
		$this->load->view('admin/footer');
	}
	
	public function form($alert="")
	{
		$data['page_title'] = PROJECT_TITLE." | Dashboard";
		$data['alert'] = $alert;
		$data['userdata'] = $this->user_data;
		$this->load->view('admin/header',$data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/form');
		$this->load->view('admin/footer');
	}
	
	*/
}
