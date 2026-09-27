<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Record_sorting extends CI_Controller
{
	public $controller = 'record-sorting';

	public function __construct()
	{
		parent::__construct();
		$userData = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($userData))
		{
			redirect(base_url('manage/login'));
		}

		$this->load->library('admin_record_sorter');
	}

	public function sort($module = '')
	{
		$this->output->set_content_type('application/json');
		if ($this->input->method(TRUE) !== 'POST')
		{
			return $this->jsonResponse(405, FALSE, 'Record sorting requires a POST request.');
		}
		if (!$this->admin_record_sorter->supports($module))
		{
			return $this->jsonResponse(404, FALSE, 'Record sorting is not configured for this module.');
		}

		try
		{
			$updated = $this->admin_record_sorter->reorder(
				$module,
				$this->input->post('records'),
				(int) $this->input->post('offset'),
				$this->input->post('scope')
			);
		}
		catch (InvalidArgumentException $exception)
		{
			return $this->jsonResponse(422, FALSE, $exception->getMessage());
		}

		return $updated
			? $this->jsonResponse(200, TRUE, 'The record order was saved.')
			: $this->jsonResponse(500, FALSE, 'The record order could not be saved.');
	}

	private function jsonResponse($statusCode, $success, $message)
	{
		return $this->output
			->set_status_header($statusCode)
			->set_output(json_encode(array('success' => $success, 'message' => $message)));
	}
}
