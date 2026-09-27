<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_itineraries extends CI_Controller
{
    public $tblName = 'tour_itineraries';
    public $pKey = 'id';
    public $moduleName = 'Tour Itineraries';
    public $moduleNameSingular = 'Itinerary';
    public $moduleDesc = 'Manage the day-by-day itinerary for this tour.';
    public $controller = 'tour-itineraries';
    public $per_page = 10;
    public $tStatus = 'status';
    public $orderColumn = 'itinerary_order';
    public $listView = 'tourItineraries';
    public $addEditView = 'addTourItinerary';
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

    public function index($tourID = 0, $sortby = 'itinerary_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $tour = $this->tour((int) $tourID);
        if (empty($tour)) {
            redirect(base_url('manage/tours'));
            return;
        }

        $allowedSorts = array($this->pKey, $this->orderColumn, 'day_hour', 'title', $this->tStatus, 'added_on', 'updated_on');
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->orderColumn;
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
        $where = array('tour_id' => (int) $tourID);
        if ($status !== '-') {
            $where[$this->tStatus] = $status;
        }
        $searchColumns = 'title,title_ar,day_hour,day_hour_ar,details,details_ar';
        $search = $keywords === '-' ? array() : array('cols' => $searchColumns, 'value' => $keywords);
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.(int) $tourID.'/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 9;
        $offset = max(0, (int) $this->uri->segment($uriSegment, 0));

        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
        $recordSort = $sortby === $this->orderColumn ? $this->orderColumn.',title,'.$this->pKey : $sortby;
        $records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);

        $attractionIds = array();
        $recordIds = array();
        foreach ($records as $record) {
            $recordIds[] = (int) $record[$this->pKey];
            if (!empty($record['attraction_id'])) {
                $attractionIds[] = (int) $record['attraction_id'];
            }
        }
        $translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('tour_itineraries', $recordIds);
        $attractionNames = $this->attractionNames($attractionIds);

        $this->render($this->listView, array(
            'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$tour['tour_name'].' Itineraries',
            'userdata' => $this->user_data, 'toursActive' => 1, 'tourData' => $tour, 'itinerary_tour_id' => (int) $tourID,
            'total_rows' => $totalRows, 'per_page' => $this->per_page, 'records' => $records,
            'attraction_names' => $attractionNames,
            'translation_statuses' => $translationStatuses,
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
        $record = $isEdit ? $this->itinerary($id, (int) $tourID) : array();
        if ($isEdit && empty($record)) {
            redirect(base_url('manage/'.$this->controller.'/index/'.(int) $tourID));
            return;
        }

        $posted = $this->session->flashdata($this->controller.'_data');
        $locale = $isEdit ? $this->manage_translation_service->locale(is_array($posted) && isset($posted['active_locale']) ? $posted['active_locale'] : $this->input->get('lang', TRUE)) : 'en';
        $values = $this->manage_translation_service->localized_values('tour_itineraries', $record, $locale);
        $viewRecord = $record;
        if (is_array($posted)) {
            foreach (array_keys($values) as $key) {
                if (array_key_exists($key, $posted)) {
                    $values[$key] = $posted[$key];
                }
            }
            foreach (array('attraction_id', $this->tStatus) as $key) {
                if (array_key_exists($key, $posted)) {
                    $viewRecord[$key] = $posted[$key];
                }
            }
        }

        $this->render($this->addEditView, array(
            'toursActive' => 1, 'tourData' => $tour, 'itinerary_tour_id' => (int) $tourID, 'tbl_data' => $viewRecord,
            'is_edit' => $isEdit, 'active_locale' => $locale, 'localized_values' => $values,
            'manage_locales' => $this->manage_translation_service->locales(),
            'translation_state' => $isEdit ? $this->manage_translation_service->state('tour_itineraries', $id) : NULL,
            'attractions' => $this->attractionOptions($isEdit ? $record : array()),
            'form_error' => $this->session->flashdata('form_error'), 'useManageTranslations' => TRUE,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
        ));
    }

    public function addRecord($tourID = 0)
    {
        $tour = $this->tour((int) $tourID);
        if (empty($tour)) return $this->redirectTours();
        if (!$this->validRequiredInput('en')) return $this->formFailure('Enter the day/hour, title, and short description.', $tourID);
        $attractionId = $this->validatedAttractionId($this->input->post('attraction_id'));
        if ($attractionId === FALSE) return $this->formFailure('Select a valid attraction.', $tourID);

        $upload = $this->saveImage('uploadfile');
        if ($upload['error'] !== '') return $this->formFailure($upload['error'], $tourID);

        $data = $this->postedItineraryData('en', $attractionId);
        $data['day_hour_ar'] = '';
        $data['title_ar'] = '';
        $data['details_ar'] = '';
        $data['tour_id'] = (int) $tourID;
        if ($upload['filename'] !== '') $data['image'] = $upload['filename'];
        $data[$this->orderColumn] = $this->nextOrder($tourID);
        $data['added_on'] = $data['updated_on'] = date('Y-m-d H:i:s');

        $id = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$id) {
            $this->deleteImageFile($upload['filename']);
            return $this->formFailure('The itinerary could not be saved. Please try again.', $tourID);
        }
        $this->queueTranslationSafely($id);
        $this->session->set_flashdata('alert', 'success');
        $this->redirectToTour($tourID);
    }

    public function editRecord($tourID = 0, $editID = 0)
    {
        $record = $this->itinerary((int) $editID, (int) $tourID);
        if (empty($record)) return $this->redirectToTour($tourID);
        $locale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
        if (!$this->validRequiredInput($locale)) return $this->formFailure('Enter the day/hour, title, and short description.', $tourID, $editID, $locale);
        $attractionId = $this->validatedAttractionId($this->input->post('attraction_id'));
        if ($attractionId === FALSE) return $this->formFailure('Select a valid attraction.', $tourID, $editID, $locale);

        $upload = $this->saveImage('uploadfile');
        if ($upload['error'] !== '') return $this->formFailure($upload['error'], $tourID, $editID, $locale);

        $data = $this->postedItineraryData($locale, $attractionId);
        $data['updated_on'] = date('Y-m-d H:i:s');
        if ($upload['filename'] !== '') $data['image'] = $upload['filename'];

        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => (int) $editID, 'tour_id' => (int) $tourID))) {
            $this->deleteImageFile($upload['filename']);
            return $this->formFailure('The itinerary could not be updated. Please try again.', $tourID, $editID, $locale);
        }
        if ($upload['filename'] !== '') $this->deleteImageFile($record['image']);
        if ($locale === 'en') $this->queueTranslationSafely($editID);
        $this->session->set_flashdata('alert', 'editsuccess');
        $redirectLocale = $this->input->post('redirect_lang', TRUE);
        if (is_string($redirectLocale) && $redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale) {
            redirect(base_url('manage/'.$this->controller.'/control/'.(int) $tourID.'/'.(int) $editID.'?lang='.$redirectLocale));
            return;
        }
        $this->redirectToTour($tourID);
    }

    public function delete($id = 0)
    {
        $record = $this->itinerary((int) $id);
        $tourID = !empty($record) ? (int) $record['tour_id'] : 0;
        $this->session->set_flashdata('alert', !empty($record) && $this->deleteItineraryRecord($record) ? 'deletesuccess' : 'deleteerror');
        $tourID > 0 ? $this->redirectToTour($tourID) : $this->redirectTours();
    }

    public function deleteall()
    {
        $tourID = (int) $this->input->post('tour_id');
        $ids = array_unique(array_filter(array_map('intval', (array) $this->input->post('records'))));
        $records = empty($ids) || $tourID < 1 ? array() : $this->db->where('tour_id', $tourID)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
        $deleted = 0;
        foreach ($records as $record) {
            if ($this->deleteItineraryRecord($record)) $deleted++;
        }
        $this->session->set_flashdata('alert', $deleted > 0 && $deleted === count($ids) ? 'deletesuccess' : 'deleteerror');
        $this->redirectToTour($tourID);
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->output->set_content_type('application/json');
        $id = (int) $id;
        if ($id < 1 || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => $id))) {
            return $this->output->set_output(json_encode(array('status' => 'false')));
        }
        return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
    }

    private function render($view, array $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }

    private function tour($id)
    {
        return $id > 0 ? $this->SqlModel->getSingleRecord('tours', array('tour_id' => $id)) : array();
    }

    private function itinerary($id, $tourID = 0)
    {
        if ($id < 1) return array();
        $where = array($this->pKey => $id);
        if ($tourID > 0) $where['tour_id'] = $tourID;
        return $this->SqlModel->getSingleRecord($this->tblName, $where);
    }

    private function attractionOptions(array $currentRecord)
    {
        $attractions = $this->SqlModel->getRecords('attraction_id,attraction_name,attraction_short_desc,attraction_short_desc_ar', 'attractions', 'attraction_name', 'ASC', array('attraction_status' => 'Enable'));
        $currentId = !empty($currentRecord['attraction_id']) ? (int) $currentRecord['attraction_id'] : 0;
        if ($currentId < 1) return $attractions;
        foreach ($attractions as $attraction) {
            if ((int) $attraction['attraction_id'] === $currentId) return $attractions;
        }
        $disabled = $this->SqlModel->getSingleRecord('attractions', array('attraction_id' => $currentId));
        if (!empty($disabled)) {
            $attractions[] = array('attraction_id' => $disabled['attraction_id'], 'attraction_name' => $disabled['attraction_name'], 'attraction_short_desc' => $disabled['attraction_short_desc'], 'attraction_short_desc_ar' => $disabled['attraction_short_desc_ar']);
        }
        return $attractions;
    }

    private function attractionNames(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) return array();
        $rows = $this->db->select('attraction_id,attraction_name')->where_in('attraction_id', $ids)->get('attractions')->result_array();
        $names = array();
        foreach ($rows as $row) {
            $names[(int) $row['attraction_id']] = $row['attraction_name'];
        }
        return $names;
    }

    private function validatedAttractionId($value)
    {
        $value = trim((string) $value);
        if ($value === '') return NULL;
        if (!ctype_digit($value)) return FALSE;
        $id = (int) $value;
        return $this->SqlModel->countRecords('attractions', array('attraction_id' => $id)) > 0 ? $id : FALSE;
    }

    private function postedItineraryData($locale, $attractionId)
    {
        $status = $this->input->post($this->tStatus);
        $data = array(
            'attraction_id' => $attractionId,
            $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Enable',
        );
        $post = $this->input->post(NULL, FALSE);
        return array_merge($data, $this->manage_translation_service->localized_post_data('tour_itineraries', $locale, is_array($post) ? $post : array()));
    }

    private function validRequiredInput($locale)
    {
        $post = $this->input->post(NULL, FALSE);
        return $this->manage_translation_service->required_localized_input_valid('tour_itineraries', $locale, is_array($post) ? $post : array());
    }

    private function nextOrder($tourID)
    {
        $this->load->library('admin_record_sorter');
        return $this->admin_record_sorter->nextOrder('tour-itineraries', (int) $tourID);
    }

    private function saveImage($field)
    {
        $result = array('filename' => '', 'error' => '');
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
            'allowed_types' => UPLOAD_IMAGE_MIMES,
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

    private function formFailure($message, $tourID, $editID = 0, $locale = 'en')
    {
        $posted = $this->input->post(NULL, FALSE);
        if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata('form_error', $message);
        redirect(base_url('manage/'.$this->controller.'/control/'.(int) $tourID.($editID ? '/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
    }

    private function deleteItineraryRecord(array $record)
    {
        $this->db->trans_begin();
        $this->manage_translation_service->delete_jobs('tour_itineraries', $record[$this->pKey]);
        $deleted = $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $record[$this->pKey]));
        if (!$deleted || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        $this->deleteImageFile($record['image']);
        return TRUE;
    }

    private function deleteImageFile($filename)
    {
        $filename = basename((string) $filename);
        if ($filename === '' || $this->SqlModel->countRecords($this->tblName, array('image' => $filename)) > 0) return;
        delete_uploaded_file(FCPATH.'assets/frontend/images/'.$this->controller, $filename);
    }

    private function queueTranslationSafely($id)
    {
        try {
            $this->manage_translation_service->queue('tour_itineraries', (int) $id);
        } catch (Throwable $exception) {
            log_message('error', 'Tour itinerary translation could not be queued for record '.(int) $id.'.');
        }
    }

    private function redirectToTour($tourID)
    {
        redirect(base_url('manage/'.$this->controller.'/index/'.(int) $tourID));
    }

    private function redirectTours()
    {
        redirect(base_url('manage/tours'));
    }
}
