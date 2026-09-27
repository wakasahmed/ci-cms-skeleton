<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_images extends CI_Controller
{
    public $tblName = 'tour_images';
    public $colPrefix = 'image_';
    public $pKey = 'image_id';
    public $moduleName = 'Tour Images';
    public $moduleNameSingular = 'Image';
    public $moduleDesc = 'Manage the image gallery for this tour.';
    public $controller = 'tour-images';
    public $per_page = 10;
    public $tStatus = 'image_status';
    public $listView = 'tourImages';
    public $addEditView = 'addTourImage';
    public $user_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
            return;
        }
        $this->load->library('manage_translation_service');
    }

    public function index($tourID = 0, $sortby = 'image_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $tour = $this->tour((int) $tourID);
        if (empty($tour)) {
            redirect(base_url('manage/tours'));
            return;
        }
        $allowedSorts = array($this->pKey, 'image_order', 'image_image', 'image_name', $this->tStatus, 'image_added', 'image_updated');
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'image_order';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && (int) $requestedPerPage <= 100) {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== NULL) {
            $this->per_page = (int) $this->session->userdata('per_page');
        }
        $keywords = urldecode((string) $keywords);
        $where = array('image_tour_id' => (int) $tourID);
        if ($status !== '-') {
            $where[$this->tStatus] = $status;
        }
        $search = $keywords === '-' ? array() : array('cols' => 'image_name', 'value' => $keywords);
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.(int) $tourID.'/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 9;
        $offset = (int) $this->uri->segment($uriSegment, 0);
        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
        $records = $this->SqlModel->getRecords('*', $this->tblName, $sortby === 'image_order' ? 'image_order,image_name' : $sortby, $order, $where, $search, $this->per_page, $offset, FALSE);
        $ids = array();
        foreach ($records as $record) {
            $ids[] = (int) $record[$this->pKey];
        }
        $this->render($this->listView, array(
            'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$tour['tour_name'].' '.$this->moduleName,
            'userdata' => $this->user_data, 'tourImagesActive' => 1, 'tourData' => $tour, 'image_tour_id' => (int) $tourID,
            'total_rows' => $totalRows, 'per_page' => $this->per_page, 'records' => $records,
            'translation_statuses' => empty($ids) ? array() : $this->manage_translation_service->statuses('tour_images', $ids),
            'paginate' => $this->pagination->create_links(), 'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset, 'status' => $status, 'keywords' => $keywords, 'useManageTranslations' => TRUE,
        ));
    }

    public function control($tourID = 0, $editID = '')
    {
        $tour = $this->tour((int) $tourID);
        if (empty($tour)) {
            redirect(base_url('manage/tours'));
            return;
        }
        $id = (int) $editID;
        $isEdit = $id > 0;
        $record = $isEdit ? $this->image($id, (int) $tourID) : array();
        if ($isEdit && empty($record)) {
            redirect(base_url('manage/'.$this->controller.'/index/'.(int) $tourID));
            return;
        }
        $posted = $this->session->flashdata($this->controller.'_data');
        $locale = $isEdit ? $this->manage_translation_service->locale(is_array($posted) && isset($posted['active_locale']) ? $posted['active_locale'] : $this->input->get('lang', TRUE)) : 'en';
        $values = $this->manage_translation_service->localized_values('tour_images', $record, $locale);
        if (is_array($posted)) {
            foreach (array_keys($values) as $key) {
                if (array_key_exists($key, $posted)) {
                    $values[$key] = $posted[$key];
                }
            }
            foreach (array($this->tStatus) as $key) {
                if (array_key_exists($key, $posted)) {
                    $record[$key] = $posted[$key];
                }
            }
        }
        $this->render($this->addEditView, array(
            'tourImagesActive' => 1, 'tourData' => $tour, 'image_tour_id' => (int) $tourID, 'tbl_data' => $record,
            'is_edit' => $isEdit, 'active_locale' => $locale, 'localized_values' => $values,
            'manage_locales' => $this->manage_translation_service->locales(),
            'translation_state' => $isEdit ? $this->manage_translation_service->state('tour_images', $id) : NULL,
            'form_error' => $this->session->flashdata('form_error'), 'useManageTranslations' => TRUE,
        ));
    }

    public function addRecord($tourID = 0)
    {
        $tour = $this->tour((int) $tourID);
        if (empty($tour)) return $this->redirectTours();
        if (!$this->validName('en')) return $this->formFailure('Enter an image name.', $tourID);
        $upload = $this->saveImage('uploadfile');
        if ($upload['error'] !== '' || $upload['filename'] === '') return $this->formFailure($upload['error'] !== '' ? $upload['error'] : 'Select an image to upload.', $tourID);
        $data = $this->postedImageData('en');
        $data['image_name_ar'] = '';
        $data['image_tour_id'] = (int) $tourID;
        $data['image_image'] = $upload['filename'];
        $data['image_order'] = $this->nextOrder($tourID);
        $data['image_added'] = $data['image_updated'] = date('Y-m-d H:i:s');
        $id = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$id) {
            $this->deleteImageFile($upload['filename']);
            return $this->formFailure('The image could not be saved. Please try again.', $tourID);
        }
        $this->queueTranslationSafely($id);
        $this->session->set_flashdata('alert', 'success');
        $this->redirectToTour($tourID);
    }

    public function editRecord($tourID = 0, $editID = 0)
    {
        $record = $this->image((int) $editID, (int) $tourID);
        if (empty($record)) return $this->redirectToTour($tourID);
        $locale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
        if (!$this->validName($locale)) return $this->formFailure('Enter an image name.', $tourID, $editID, $locale);
        $upload = $this->saveImage('uploadfile');
        if ($upload['error'] !== '') return $this->formFailure($upload['error'], $tourID, $editID, $locale);
        $data = $this->postedImageData($locale);
        $data['image_updated'] = date('Y-m-d H:i:s');
        if ($upload['filename'] !== '') $data['image_image'] = $upload['filename'];
        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => (int) $editID, 'image_tour_id' => (int) $tourID))) {
            $this->deleteImageFile($upload['filename']);
            return $this->formFailure('The image could not be updated. Please try again.', $tourID, $editID, $locale);
        }
        if ($upload['filename'] !== '') $this->deleteImageFile($record['image_image']);
        if ($locale === 'en') $this->queueTranslationSafely($editID);
        $this->session->set_flashdata('alert', 'editsuccess');
        $redirectLocale = $this->input->post('redirect_lang', TRUE);
        if ($redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale) {
            redirect(base_url('manage/'.$this->controller.'/control/'.(int) $tourID.'/'.(int) $editID.'?lang='.$redirectLocale));
            return;
        }
        $this->redirectToTour($tourID);
    }

    public function delete($id = 0)
    {
        $record = $this->image((int) $id);
        $tourID = !empty($record) ? (int) $record['image_tour_id'] : 0;
        $this->session->set_flashdata('alert', !empty($record) && $this->deleteImageRecord($record) ? 'deletesuccess' : 'deleteerror');
        $tourID > 0 ? $this->redirectToTour($tourID) : $this->redirectTours();
    }

    public function deleteall()
    {
        $tourID = (int) $this->input->post('tour_id');
        $ids = array_unique(array_filter(array_map('intval', (array) $this->input->post('records'))));
        $records = empty($ids) ? array() : $this->db->where('image_tour_id', $tourID)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
        $deleted = 0;
        foreach ($records as $record) if ($this->deleteImageRecord($record)) $deleted++;
        $this->session->set_flashdata('alert', $deleted > 0 && $deleted === count($ids) ? 'deletesuccess' : 'deleteerror');
        $this->redirectToTour($tourID);
    }

    public function upload_multiple($tourID = 0)
    {
        $tour = $this->tour((int) $tourID);
        if (empty($tour)) return $this->redirectTours();
        $this->render('imageTourUpload', array('tourImagesActive' => 1, 'tourData' => $tour, 'image_tour_id' => (int) $tourID, 'page_title' => PROJECT_TITLE.' | Upload Tour Images', 'userdata' => $this->user_data, 'useDropzone' => TRUE));
    }

    public function do_upload()
    {
        $this->output->set_content_type('application/json');
        $upload = $this->saveImage('upl');
        if ($upload['error'] !== '' || $upload['filename'] === '') return $this->output->set_output(json_encode(array('status' => 'error', 'message' => $upload['error'] !== '' ? $upload['error'] : 'No image was received.')));
        $thumb = rawurldecode(basename(image_thumb_src(FCPATH.'assets/frontend/images/'.$this->controller.'/'.$upload['filename'], 0, 150, 'webp')));
        return $this->output->set_output(json_encode(array('status' => 'success', 'file_name' => $upload['filename'], 'thumb_image' => $thumb)));
    }

    public function saveimages($tourID = 0)
    {
        if (empty($this->tour((int) $tourID))) return $this->redirectTours();
        $total = min(100, max(0, (int) $this->input->post('totalImages')));
        $inserted = 0;
        for ($index = 1; $index <= $total; $index++) {
            $filename = basename((string) $this->input->post('large_image'.$index, TRUE));
            if ($filename === '' || !is_file(FCPATH.'assets/frontend/images/'.$this->controller.'/'.$filename)) continue;
            $name = trim((string) $this->input->post('name'.$index, FALSE));
            $arabicName = trim((string) $this->input->post('name_ar'.$index, FALSE));

            if ($name === '' || $arabicName === '') {
                continue;
            }

            $id = $this->SqlModel->insertRecord($this->tblName, array(
                'image_name' => $name,
                'image_name_ar' => $arabicName,
                'image_image' => $filename,
                'image_tour_id' => (int) $tourID,
                'image_status' => 'Enable',
                'image_order' => $this->nextOrder($tourID),
                'image_added' => date('Y-m-d H:i:s'),
                'image_updated' => date('Y-m-d H:i:s'),
            ));

            if ($id) {
                $inserted++;
            }
        }
        $this->session->set_flashdata('alert', $inserted > 0 ? 'success' : 'error');
        $this->redirectToTour($tourID);
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->output->set_content_type('application/json');
        $id = (int) $id;
        if ($id < 1 || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => $id))) return $this->output->set_output(json_encode(array('status' => 'false')));
        return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
    }

    private function render($view, array $data) { $this->load->view('admin/header', $data); $this->load->view('admin/navigation'); $this->load->view('admin/'.$view); $this->load->view('admin/footer'); }
    private function tour($id) { return $id > 0 ? $this->SqlModel->getSingleRecord('tours', array('tour_id' => $id)) : array(); }
    private function image($id, $tourID = 0) { $where = array($this->pKey => (int) $id); if ($tourID > 0) $where['image_tour_id'] = (int) $tourID; return $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, $where) : array(); }
    private function postedImageData($locale) { $post = $this->input->post(NULL, FALSE); $data = $this->manage_translation_service->localized_post_data('tour_images', $locale, is_array($post) ? $post : array()); $data[$this->tStatus] = $this->input->post($this->tStatus) === 'Disable' ? 'Disable' : 'Enable'; return $data; }
    private function validName($locale) { $post = $this->input->post(NULL, FALSE); return $this->manage_translation_service->required_localized_input_valid('tour_images', $locale, is_array($post) ? $post : array()); }
    private function nextOrder($tourID) { $row = $this->db->select_max('image_order', 'max_order')->where('image_tour_id', (int) $tourID)->get($this->tblName)->row_array(); return isset($row['max_order']) ? (int) $row['max_order'] + 1 : 1; }
    private function saveImage($field)
    {
        $result = array(
            'filename' => '',
            'error' => '',
        );

        if (empty($_FILES[$field]['name'])) {
            return $result;
        }

        $uploadPath = FCPATH.'assets/frontend/images/'.$this->controller.DIRECTORY_SEPARATOR;

        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE)) {
            $result['error'] = 'The image upload directory could not be created.';

            return $result;
        }

        if (!is_writable($uploadPath)) {
            $result['error'] = 'The image upload directory is not writable.';

            return $result;
        }

        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path' => $uploadPath,
            'allowed_types' => 'gif|jpg|jpeg|png|webp',
            'max_size' => UPLOAD_SIZE_MB * 1024,
            'encrypt_name' => TRUE,
            'remove_spaces' => TRUE,
        ));

        if (!$this->upload->do_upload($field)) {
            $result['error'] = strip_tags($this->upload->display_errors('', ''));

            return $result;
        }

        $file = $this->upload->data();
        $result['filename'] = $file['file_name'];

        return $result;
    }
    private function formFailure($message, $tourID, $editID = 0, $locale = 'en') { $posted = $this->input->post(NULL, FALSE); if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale); $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array()); $this->session->set_flashdata('form_error', $message); redirect(base_url('manage/'.$this->controller.'/control/'.(int) $tourID.($editID ? '/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : ''))); }
    private function deleteImageRecord(array $record) { $this->manage_translation_service->delete_jobs('tour_images', $record[$this->pKey]); $deleted = $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $record[$this->pKey])); if ($deleted) $this->deleteImageFile($record['image_image']); return $deleted; }
    private function deleteImageFile($filename) { $filename = basename((string) $filename); if ($filename === '' || $this->SqlModel->countRecords($this->tblName, array('image_image' => $filename)) > 0) return; delete_uploaded_file(FCPATH.'assets/frontend/images/'.$this->controller, $filename); }
    private function queueTranslationSafely($id) { try { $this->manage_translation_service->queue('tour_images', (int) $id); } catch (Throwable $exception) { log_message('error', 'Tour image translation could not be queued.'); } }
    private function redirectToTour($tourID) { redirect(base_url('manage/'.$this->controller.'/index/'.(int) $tourID)); }
    private function redirectTours() { redirect(base_url('manage/tours')); }
}
