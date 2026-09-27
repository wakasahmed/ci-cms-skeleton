<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tours extends CI_Controller {

    public $tblName         =     'tours';
    public $colPrefix         =     'tour_';
    public $pKey            =    'tour_id';
    public $moduleName         =    'Tours';
    public $moduleNameSingular =    'Tour';
    public $moduleDesc         =    'Manage tour content, pricing, imagery, and availability status.';
    public $controller         =    'tours';
    public $per_page         =    10;
    public $tStatus         =    'tour_status';
    public $listView         =    'tours';
    public $addEditView     =    'addTour';
    public $user_data         =     array();

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data))
        {
            redirect(base_url('manage/login'));
        }
        $this->load->library('manage_translation_service');
    }

    public function index($sortby = 'tour_order', $order = 'ASC', $status = '-', $keywords = '-', $type = '-', $pg_no = '')
    {
        $allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'image', $this->colPrefix.'name', $this->colPrefix.'slug', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $type = in_array($type, array('Tour', 'Experience'), TRUE) ? $type : '-';

        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && (int) $requestedPerPage <= 100)
        {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== NULL)
        {
            $this->per_page = (int) $this->session->userdata('per_page');
        }

        $keywords = urldecode((string) $keywords);
        $where = array();
        if ($status !== '-') $where[$this->tStatus] = $status;
        if ($type !== '-') $where[$this->colPrefix.'type'] = $type;
        $search = $keywords !== '-' ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'name_ar,'.$this->colPrefix.'slug', 'value' => $keywords) : array();
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$type);
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 9;
        $offset = max(0, (int) $this->uri->segment($uriSegment, 0));

        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
        $recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->colPrefix.'name' : $sortby;
        $fields = '*, (SELECT COUNT(*) FROM tour_images WHERE tour_images.image_tour_id = '.$this->tblName.'.'.$this->pKey.') AS images'
            .', (SELECT COUNT(*) FROM tour_itineraries WHERE tour_itineraries.tour_id = '.$this->tblName.'.'.$this->pKey.') AS itineraries';
        $records = $this->SqlModel->getRecords($fields, $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
        $recordIds = array();
        foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
        $translationStatuses = array();
        $statusBatchSize = max(1, (int) $this->config->item('manage_translation_max_status_ids', 'manage_translations'));
        foreach (array_chunk($recordIds, $statusBatchSize) as $recordIdBatch) $translationStatuses = array_replace($translationStatuses, $this->manage_translation_service->statuses('tours', $recordIdBatch));

        $data = array(
            'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data, 'toursActive' => 1,
            'total_rows' => $totalRows, 'per_page' => $this->per_page,
            'records' => $records, 'translation_statuses' => $translationStatuses,
            'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
            'status' => $status, 'keywords' => $keywords, 'type' => $type,
            'useManageTranslations' => TRUE,
        );
        $this->render($this->listView, $data);
    }

    public function control($alert = '', $editID = '')
    {
        // Preserve bookmarked old-style "control/{id}" links alongside the new "control/edit/{id}" pattern.
        if ($alert !== 'edit' && $alert !== '' && ctype_digit((string) $alert))
        {
            $editID = $alert;
            $alert = 'edit';
        }
        $isEdit = ($alert === 'edit' && $editID !== '');
        $record = array();
        $postedData = $this->session->flashdata($this->controller.'_data');
        $requestedLocale = is_array($postedData) && isset($postedData['active_locale']) ? $postedData['active_locale'] : $this->input->get('lang', TRUE);
        $activeLocale = $isEdit ? $this->manage_translation_service->locale($requestedLocale) : 'en';
        if ($isEdit)
        {
            $editID = (int) $editID;
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
            if (empty($record))
            {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));
                return;
            }
        }

        $sharedColumns = array_merge(
            array(
                $this->colPrefix.'slug',
                $this->colPrefix.'fee',
                $this->colPrefix.'days',
                $this->tStatus,
                $this->colPrefix.'type',
                $this->colPrefix.'faqs_general',
                $this->colPrefix.'faqs_specific',
                'robots_index',
                'robots_follow',
            ),
            $this->hourlyPriceColumns()
        );
        $viewRecord = $record;
        if (empty($record))
        {
            $viewRecord['robots_index'] = 1;
            $viewRecord['robots_follow'] = 1;
        }
        if (!empty($record))
        {
            $slugSuffix = $activeLocale === 'ar' ? '_ar' : '';
            $viewRecord[$this->colPrefix.'slug'] = isset($record[$this->colPrefix.'slug'.$slugSuffix]) ? $record[$this->colPrefix.'slug'.$slugSuffix] : '';
        }
        if (is_array($postedData))
        {
            foreach ($sharedColumns as $sharedColumn) if (array_key_exists($sharedColumn, $postedData)) $viewRecord[$sharedColumn] = $postedData[$sharedColumn];
        }
        $localizedValues = $this->manage_translation_service->localized_values('tours', $record, $activeLocale);
        if (is_array($postedData))
        {
            foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
        }
        $translationState = $isEdit ? $this->manage_translation_service->state('tours', $editID) : NULL;
        $assignments = $isEdit ? $this->assignedRelations($editID) : $this->emptyRelations();
        if (is_array($postedData))
        {
            foreach ($this->relationDefinitions() as $relation => $definition)
            {
                if (isset($postedData[$definition['field']]))
                {
                    $assignments[$relation] = array_map('intval', (array) $postedData[$definition['field']]);
                }
            }
        }

        $this->load->library('ckeditor');
        $this->load->library('ckfinder');
        $this->ckeditor->basePath = base_url('assets/ckeditor/');
        $this->ckeditor->config['removePlugins'] = 'save, preview, newpage, forms, flash';
        $this->ckeditor->config['height'] = '260px';
        $this->ckeditor->config['contentsLangDirection'] = $activeLocale === 'ar' ? 'rtl' : 'ltr';
        $this->ckfinder->SetupCKEditor($this->ckeditor, '../../../../assets/ckfinder/');

        // Order matters: the CKEditor library's <script src> tag is only emitted once,
        // attached to whichever field is processed first here — so this list must match
        // the order the fields actually appear in the view's DOM, or an earlier-rendered
        // field's CKEDITOR.replace() call executes before the library has loaded.
        $ckeditorFieldNames = array('localized_vehicle_text', 'localized_overview', 'localized_highlights', 'localized_itinerary', 'localized_pinfo', 'localized_gallery', 'localized_guide_info', 'localized_faqs');
        $ckeditorDir = $activeLocale === 'ar' ? 'rtl' : 'ltr';
        $ckeditorFields = array();
        foreach ($ckeditorFieldNames as $fieldName)
        {
            $fieldValue = isset($localizedValues[$fieldName]) ? (string) $localizedValues[$fieldName] : '';
            if (!$isEdit && $activeLocale === 'en' && trim($fieldValue) === '')
            {
                $fieldValue = $this->ckEditorFieldDefault($fieldName);
            }
            $ckeditorFields[$fieldName] = $this->ckEditorField($fieldName, $fieldValue, $ckeditorDir);
        }

        $siteSettings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $currencyColumn = $activeLocale === 'ar' ? 'currency_unit_ar' : 'currency_unit';
        $currencyUnit = !empty($siteSettings[$currencyColumn]) ? $siteSettings[$currencyColumn] : 'SAR';

        $data = array(
            'toursActive' => 1, 'alert' => $isEdit ? 'edit' : '',
            'currency_unit' => $currencyUnit,
            'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
            'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
            'manage_locales' => $this->manage_translation_service->locales(),
            'translation_state' => $translationState, 'useManageTranslations' => TRUE, 'useSortableJs' => TRUE, 'useSweetAlert' => TRUE,
            'attractions' => $this->SqlModel->getRecords('attraction_id,attraction_name', 'attractions', 'attraction_name', 'ASC', array('attraction_status' => 'Enable')),
            'tour_attractions' => $assignments['attractions'],
            'tour_categories' => $this->SqlModel->getRecords('cat_id,cat_name', 'tour_categories', 'cat_name', 'ASC', array('cat_status' => 'Enable')),
            'tour_categories_assigned' => $assignments['categories'],
            'tour_vehicles' => $this->SqlModel->getRecords('vehicle_id,vehicle_title', 'vehicles', 'vehicle_order', 'ASC', array('vehicle_status' => 'Enable')),
            'tour_vehicles_assigned' => $assignments['vehicles'],
            'tour_slots' => $this->SqlModel->getRecords('slot_id,slot_title', 'tour_slots', 'slot_order', 'ASC', array('slot_status' => 'Enable')),
            'tour_slots_assigned' => $assignments['slots'],
            'faqs_categories' => $this->SqlModel->getRecords('cat_id,cat_name,cat_status', 'faqs_categories', 'cat_name', 'ASC'),
            'ckeditor_fields' => $ckeditorFields,
        );
        $this->render($this->addEditView, $data);
    }

    public function addRecord()
    {
        $activeLocale = 'en';
        $relations = $this->postedRelations();
        if (!$this->manage_translation_service->required_localized_input_valid('tours', $activeLocale, (array) $this->input->post(NULL, FALSE))) return $this->formFailure('Enter the tour name.');
        $slug = $this->validatedSlug($this->colPrefix.'slug', 0);
        if ($slug === NULL) return $this->formFailure('Enter a URL slug for this tour.');
        if (!$this->requiredHourlyPricesValid()) return $this->formFailure('Enter a price greater than 0 for at least one hourly option (2, 4, 6, or 8 hours).');
        if (!$this->validSharedNumericInput()) return $this->formFailure('Enter whole numbers for the price fields, and valid numeric values for the fee and day fields.');
        if (empty($relations['vehicles'])) return $this->formFailure('Select at least one vehicle for this tour.');
        if (empty($relations['slots'])) return $this->formFailure('Select at least one time slot for this tour.');

        $image = $this->saveUploadedFile('uploadfile', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($image['error'] !== '') return $this->formFailure('Tour image: '.$image['error']);
        if ($image['filename'] === '') return $this->formFailure('A tour image is required.');
        $bgImage = $this->saveUploadedFile('uploadfile2', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($bgImage['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            return $this->formFailure('English background image: '.$bgImage['error']);
        }
        $bgImageAr = $this->saveUploadedFile('uploadfile3', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($bgImageAr['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            return $this->formFailure('Arabic background image: '.$bgImageAr['error']);
        }
        $imageAr = $this->saveUploadedFile('uploadfile4', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($imageAr['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImageAr['filename'], $this->colPrefix.'bg_image_ar');
            return $this->formFailure('Arabic tour image: '.$imageAr['error']);
        }
        $ogImage = $this->saveUploadedFile('og_image_upload', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($ogImage['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImageAr['filename'], $this->colPrefix.'bg_image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $imageAr['filename'], $this->colPrefix.'image_ar');

            return $this->formFailure('Sharing image: '.$ogImage['error']);
        }

        $data = $this->postedTourData($activeLocale);
        $data[$this->colPrefix.'slug'] = $slug;
        $data[$this->colPrefix.'image'] = $image['filename'];
        if ($bgImage['filename'] !== '') $data[$this->colPrefix.'bg_image'] = $bgImage['filename'];
        if ($bgImageAr['filename'] !== '') $data[$this->colPrefix.'bg_image_ar'] = $bgImageAr['filename'];
        if ($imageAr['filename'] !== '') $data[$this->colPrefix.'image_ar'] = $imageAr['filename'];
        if ($ogImage['filename'] !== '') $data['og_image'] = $ogImage['filename'];
        $this->load->library('admin_record_sorter');
        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
        $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

        $this->db->trans_begin();
        $id = $this->SqlModel->insertRecord($this->tblName, $data);
        if ($id) $this->syncRelations($id, $relations);
        if (!$id || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImageAr['filename'], $this->colPrefix.'bg_image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $imageAr['filename'], $this->colPrefix.'image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $ogImage['filename'], 'og_image');
            return $this->formFailure('The tour could not be saved. Please try again.');
        }
        $this->db->trans_commit();
        $this->queueTranslationSafely($id);
        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/'.$this->controller));
    }

    public function editRecord($editID = '')
    {
        $editID = (int) $editID;
        $activeLocale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
        $current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
        $relations = $this->postedRelations();
        if (empty($current))
        {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        if (!$this->manage_translation_service->required_localized_input_valid('tours', $activeLocale, (array) $this->input->post(NULL, FALSE))) return $this->formFailure('Enter the tour name.', $editID, $activeLocale);
        $slugColumn = $activeLocale === 'ar' ? $this->colPrefix.'slug_ar' : $this->colPrefix.'slug';
        $slug = $this->validatedSlug($slugColumn, $editID);
        if ($slug === NULL) return $this->formFailure('Enter a URL slug for this tour.', $editID, $activeLocale);
        if (!$this->requiredHourlyPricesValid()) return $this->formFailure('Enter a price greater than 0 for at least one hourly option (2, 4, 6, or 8 hours).', $editID, $activeLocale);
        if (!$this->validSharedNumericInput()) return $this->formFailure('Enter whole numbers for the price fields, and valid numeric values for the fee and day fields.', $editID, $activeLocale);
        if (empty($relations['vehicles'])) return $this->formFailure('Select at least one vehicle for this tour.', $editID, $activeLocale);
        if (empty($relations['slots'])) return $this->formFailure('Select at least one time slot for this tour.', $editID, $activeLocale);

        $image = $this->saveUploadedFile('uploadfile', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($image['error'] !== '') return $this->formFailure('Tour image: '.$image['error'], $editID, $activeLocale);
        if ($image['filename'] === '' && empty($current[$this->colPrefix.'image'])) return $this->formFailure('A tour image is required.', $editID, $activeLocale);
        $bgImage = $this->saveUploadedFile('uploadfile2', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($bgImage['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            return $this->formFailure('English background image: '.$bgImage['error'], $editID, $activeLocale);
        }
        $bgImageAr = $this->saveUploadedFile('uploadfile3', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($bgImageAr['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            return $this->formFailure('Arabic background image: '.$bgImageAr['error'], $editID, $activeLocale);
        }
        $imageAr = $this->saveUploadedFile('uploadfile4', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($imageAr['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImageAr['filename'], $this->colPrefix.'bg_image_ar');
            return $this->formFailure('Arabic tour image: '.$imageAr['error'], $editID, $activeLocale);
        }
        $ogImage = $this->saveUploadedFile('og_image_upload', 'assets/frontend/images/tours', UPLOAD_IMAGE_MIMES);
        if ($ogImage['error'] !== '')
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImageAr['filename'], $this->colPrefix.'bg_image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $imageAr['filename'], $this->colPrefix.'image_ar');

            return $this->formFailure('Sharing image: '.$ogImage['error'], $editID, $activeLocale);
        }

        $data = $this->postedTourData($activeLocale);
        $data[$slugColumn] = $slug;
        $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
        if ($image['filename'] !== '') $data[$this->colPrefix.'image'] = $image['filename'];
        if ($bgImage['filename'] !== '') $data[$this->colPrefix.'bg_image'] = $bgImage['filename'];
        if ($bgImageAr['filename'] !== '') $data[$this->colPrefix.'bg_image_ar'] = $bgImageAr['filename'];
        if ($imageAr['filename'] !== '') $data[$this->colPrefix.'image_ar'] = $imageAr['filename'];
        $ogImageColumn = $activeLocale === 'ar' ? 'og_image_ar' : 'og_image';
        if ($ogImage['filename'] !== '') $data[$ogImageColumn] = $ogImage['filename'];

        $this->db->trans_begin();
        $updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
        if ($updated) $this->syncRelations($editID, $relations);
        if (!$updated || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            $this->deleteUploadedFile('assets/frontend/images/tours', $image['filename'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImage['filename'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $bgImageAr['filename'], $this->colPrefix.'bg_image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $imageAr['filename'], $this->colPrefix.'image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $ogImage['filename'], $ogImageColumn);
            return $this->formFailure('The tour could not be updated. Please try again.', $editID, $activeLocale);
        }
        $this->db->trans_commit();
        if ($image['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tours', $current[$this->colPrefix.'image'], $this->colPrefix.'image');
        if ($bgImage['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tours', $current[$this->colPrefix.'bg_image'], $this->colPrefix.'bg_image');
        if ($bgImageAr['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tours', $current[$this->colPrefix.'bg_image_ar'], $this->colPrefix.'bg_image_ar');
        if ($imageAr['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tours', $current[$this->colPrefix.'image_ar'], $this->colPrefix.'image_ar');
        if ($ogImage['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tours', $current[$ogImageColumn], $ogImageColumn);
        if ($activeLocale === 'en') $this->queueTranslationSafely($editID);
        $this->session->set_flashdata('alert', 'editsuccess');
        $redirectLocale = $this->input->post('redirect_lang', TRUE);
        if (is_string($redirectLocale) && $redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale)
        {
            redirect(base_url('manage/'.$this->controller.'/control/edit/'.$editID.'?lang='.$redirectLocale));
            return;
        }
        redirect(base_url('manage/'.$this->controller));
    }

    public function delete($deleteID = '')
    {
        $result = $this->deleteTourRecords(array($deleteID));
        $this->session->set_flashdata('alert', $this->deleteAlertFor($result));
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = (array) $this->input->post('records');
        $result = empty($ids) ? 'error' : $this->deleteTourRecords($ids);
        $this->session->set_flashdata('alert', $this->deleteAlertFor($result));
        redirect(base_url('manage/'.$this->controller));
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->output->set_content_type('application/json');
        $id = (int) $id;
        $record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
        if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => $id)))
        {
            return $this->output->set_output(json_encode(array('status' => 'false')));
        }
        return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
    }

    public function duplicate($id = 0)
    {
        $sourceId = (int) $id;
        $data = $sourceId > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $sourceId)) : array();
        if (empty($data))
        {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        unset($data[$this->pKey]);
        $data[$this->colPrefix.'name'] = trim((string) $data[$this->colPrefix.'name']).' Duplicate';
        $data[$this->colPrefix.'slug'] = $this->uniqueSlug($this->colPrefix.'slug', $this->normalizeSlug($data[$this->colPrefix.'slug'].'-copy'));
        if (trim((string) $data[$this->colPrefix.'slug_ar']) !== '') $data[$this->colPrefix.'slug_ar'] = $this->uniqueSlug($this->colPrefix.'slug_ar', $this->normalizeSlug($data[$this->colPrefix.'slug_ar'].'-copy'));
        $data[$this->colPrefix.'image'] = $this->copyImage($data[$this->colPrefix.'image']);
        $data[$this->colPrefix.'image_ar'] = $this->copyImage($data[$this->colPrefix.'image_ar']);
        $data[$this->colPrefix.'bg_image'] = $this->copyImage($data[$this->colPrefix.'bg_image']);
        $data[$this->colPrefix.'bg_image_ar'] = $this->copyImage($data[$this->colPrefix.'bg_image_ar']);
        $data['og_image'] = $this->copyImage($data['og_image']);
        $data['og_image_ar'] = $this->copyImage($data['og_image_ar']);
        $this->load->library('admin_record_sorter');
        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

        $this->db->trans_begin();
        $newId = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$newId || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            foreach (array($this->colPrefix.'image', $this->colPrefix.'image_ar', $this->colPrefix.'bg_image', $this->colPrefix.'bg_image_ar', 'og_image', 'og_image_ar') as $column)
            {
                if ($data[$column] === '') continue;
                delete_uploaded_file(FCPATH.'assets'.DIRECTORY_SEPARATOR.'frontend'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'tours', $data[$column]);
            }
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        $this->db->trans_commit();
        $this->queueTranslationSafely($newId);
        redirect(base_url('manage/'.$this->controller.'/control/edit/'.$newId));
    }

    // Back-compat alias for the legacy "removeimage/{id}" link (Tours had no page-specific caller, kept for any external bookmarks).
    public function removeimage($id = '')
    {
        $this->removefile($id, $this->colPrefix.'bg_image');
    }

    public function removefile($id = 0, $key = '')
    {
        $this->output->set_content_type('application/json');
        $id = (int) $id;
        $allowedColumns = array($this->colPrefix.'image', $this->colPrefix.'image_ar', $this->colPrefix.'bg_image', $this->colPrefix.'bg_image_ar', 'og_image', 'og_image_ar');
        if ($id <= 0 || !in_array($key, $allowedColumns, TRUE))
        {
            return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Invalid request.')));
        }
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));
        if (empty($record))
        {
            return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Record not found.')));
        }
        if (!$this->SqlModel->updateRecord($this->tblName, array($key => ''), array($this->pKey => $id)))
        {
            return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'The record could not be updated.')));
        }
        $this->deleteUploadedFile('assets/frontend/images/tours', $record[$key], $key);
        return $this->output->set_output(json_encode(array('success' => TRUE, 'id' => $id, 'key' => $key)));
    }

    private function render($view, $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }

    private function postedTourData($locale)
    {
        $status = $this->input->post($this->tStatus);
        $data = array(
            $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
            $this->colPrefix.'type' => $this->validatedTourType($this->input->post($this->colPrefix.'type')),
            $this->colPrefix.'faqs_general' => $this->validatedFaqCategoryId($this->input->post($this->colPrefix.'faqs_general')),
            $this->colPrefix.'faqs_specific' => $this->validatedFaqCategoryId($this->input->post($this->colPrefix.'faqs_specific')),
            'robots_index' => $this->input->post('robots_index') === '1' ? 1 : 0,
            'robots_follow' => $this->input->post('robots_follow') === '1' ? 1 : 0,
        );
        foreach ($this->hourlyPriceColumns() as $column)
        {
            $data[$column] = $this->validatedPriceValue($this->input->post($column));
        }
        $post = $this->input->post(NULL, FALSE);
        return array_merge($data, $this->manage_translation_service->localized_post_data('tours', $locale, is_array($post) ? $post : array()));
    }

    private function hourlyPriceColumns()
    {
        $columns = array();
        foreach (array($this->colPrefix.'price', $this->colPrefix.'arabic_guide_price', $this->colPrefix.'english_guide_price') as $prefix)
        {
            foreach (array(2, 4, 6, 8) as $hours)
            {
                $columns[] = $prefix.'_'.$hours;
            }
        }

        return $columns;
    }

    private function priceFields()
    {
        return $this->hourlyPriceColumns();
    }

    private function hourlyBaseColumns()
    {
        $columns = array();
        foreach (array(2, 4, 6, 8) as $hours)
        {
            $columns[] = $this->colPrefix.'price_'.$hours;
        }

        return $columns;
    }

    private function requiredHourlyPricesValid()
    {
        foreach ($this->hourlyBaseColumns() as $column)
        {
            $value = trim((string) $this->input->post($column));
            if ($value !== '' && ctype_digit($value) && (int) $value > 0) return TRUE;
        }

        return FALSE;
    }

    private function validatedPriceValue($value)
    {
        $value = trim((string) $value);

        return $value !== '' && ctype_digit($value) ? (int) $value : 0;
    }

    private function validatedTourType($value)
    {
        return in_array($value, array('Tour', 'Experience'), TRUE) ? $value : 'Tour';
    }

    private function validatedFaqCategoryId($value)
    {
        if ($value === NULL || trim((string) $value) === '' || !ctype_digit((string) $value)) return NULL;
        $id = (int) $value;
        return $this->SqlModel->countRecords('faqs_categories', array('cat_id' => $id)) > 0 ? $id : NULL;
    }

    private function ckEditorField($name, $value, $dir)
    {
        $this->ckeditor->textareaAttributes = array('id' => $name, 'rows' => 8, 'cols' => 60, 'data-translation-field' => $name, 'dir' => $dir);
        ob_start();
        $this->ckeditor->editor($name, $value);
        return ob_get_clean();
    }

    /**
     * Starting-point content for an empty English CKEditor field, so a new
     * tour/experience isn't opened to seven blank editors. Each default is a
     * self-contained section: eyebrow, heading, then a guidance paragraph —
     * because the field's HTML is what tour_details.php prints directly, no
     * heading is hardcoded there for admins to rely on (see the "overview"
     * and "experience" sections in tour_details.php, and
     * Frontend::prepareTourExperience() for how each field reaches the
     * page). "itinerary", "pinfo", "gallery", "guide_info", "faqs", and
     * "vehicle_text" aren't wired to any frontend output yet, but get the
     * same shape so they're ready if that changes. Every default mentions
     * "Tour/Experience" since this one form and column set serves both
     * product types.
     */
    private function ckEditorFieldDefault($fieldName)
    {
        $defaults = array(
            'localized_overview' => '<span class="eyebrow">Tour/Experience overview</span>'
                .'<h2 class="mt-3.5 text-h3">What this tour/experience is for</h2>'
                .'<p>Write the Tour/Experience overview here</p>',
            'localized_highlights' => '<span class="eyebrow">Places & highlights</span>'
                .'<h2 class="mt-3.5 text-h3">Where the tour/experience goes</h2>'
                .'<p>Describe what guests actually see and visit on this Tour/Experience'
                .'&mdash; the parts that make this Tour/Experience memorable.</p>',
            'localized_itinerary' => '<span class="eyebrow">Tour/Experience itinerary</span>'
                .'<h2 class="mt-3.5 text-h3">How this Tour/Experience unfolds</h2>'
                .'<p>Outline the day-by-day or stop-by-stop plan for this Tour/Experience.</p>',
            'localized_pinfo' => '<span class="eyebrow">Practical information</span>'
                .'<h2 class="mt-3.5 text-h3">What to know before you go</h2>'
                .'<p>Add any practical notes guests should know before booking this Tour/Experience.</p>',
            'localized_gallery' => '<span class="eyebrow">Tour/Experience gallery</span>'
                .'<h2 class="mt-3.5 text-h3">See it before you book</h2>'
                .'<p>Add a short note about the photos in this Tour/Experience\'s gallery.</p>',
            'localized_guide_info' => '<span class="eyebrow">Your guide</span>'
                .'<h2 class="mt-3.5 text-h3">Who will guide this Tour/Experience</h2>'
                .'<p>Describe the guide(s) leading this Tour/Experience.</p>',
            'localized_faqs' => '<span class="eyebrow">Tour/Experience FAQs</span>'
                .'<h2 class="mt-3.5 text-h3">Common questions about this Tour/Experience</h2>'
                .'<p>List common questions guests ask about this Tour/Experience.</p>',
            'localized_vehicle_text' => '<span class="eyebrow">Vehicles</span>'
                .'<h2 class="mt-3.5 text-h3">What guests travel in on this Tour/Experience</h2>'
                .'<p>Describe the vehicles used for this Tour/Experience and what makes them comfortable for guests.</p>',
        );

        return isset($defaults[$fieldName]) ? $defaults[$fieldName] : '';
    }

    private function relationDefinitions()
    {
        return array(
            'attractions' => array(
                'field' => $this->colPrefix.'attractions',
                'source_table' => 'attractions',
                'source_key' => 'attraction_id',
                'assignment_table' => 'tour_assigned_attractions',
                'assignment_key' => 'attraction_id',
                'order_by' => '`order`',
                'has_sort_order' => TRUE,
            ),
            'categories' => array(
                'field' => $this->colPrefix.'categories',
                'source_table' => 'tour_categories',
                'source_key' => 'cat_id',
                'assignment_table' => 'tour_assigned_categories',
                'assignment_key' => 'cat_id',
                'order_by' => 'cat_id',
                'has_sort_order' => FALSE,
            ),
            'vehicles' => array(
                'field' => $this->colPrefix.'vehicles',
                'source_table' => 'vehicles',
                'source_key' => 'vehicle_id',
                'assignment_table' => 'tour_assigned_vehicles',
                'assignment_key' => 'vehicle_id',
                'order_by' => 'vehicle_id',
                'has_sort_order' => FALSE,
            ),
            'slots' => array(
                'field' => $this->colPrefix.'slots',
                'source_table' => 'tour_slots',
                'source_key' => 'slot_id',
                'assignment_table' => 'tour_assigned_slots',
                'assignment_key' => 'slot_id',
                'order_by' => 'slot_id',
                'has_sort_order' => FALSE,
            ),
        );
    }

    private function emptyRelations()
    {
        return array_fill_keys(array_keys($this->relationDefinitions()), array());
    }

    private function postedRelations()
    {
        $relations = $this->emptyRelations();

        foreach ($this->relationDefinitions() as $relation => $definition)
        {
            $ids = array_values(array_unique(array_filter(array_map(
                'intval',
                (array) $this->input->post($definition['field'])
            ))));

            if (empty($ids))
            {
                continue;
            }

            $existing = $this->db
                ->select($definition['source_key'])
                ->where_in($definition['source_key'], $ids)
                ->get($definition['source_table'])
                ->result_array();
            $validIds = array();

            foreach ($existing as $record)
            {
                $validIds[] = (int) $record[$definition['source_key']];
            }

            $relations[$relation] = array_values(array_intersect($ids, $validIds));
        }

        return $relations;
    }

    private function validSharedNumericInput()
    {
        foreach ($this->priceFields() as $field)
        {
            $value = $this->input->post($field);
            if ($value !== NULL && trim((string) $value) !== '' && !ctype_digit(trim((string) $value))) return FALSE;
        }
        $fee = $this->input->post($this->colPrefix.'fee');
        if ($fee !== NULL && trim((string) $fee) !== '' && !is_numeric($fee)) return FALSE;
        $days = $this->input->post($this->colPrefix.'days');
        if ($days !== NULL && trim((string) $days) !== '' && !ctype_digit(ltrim((string) $days, '-')) && !is_numeric($days)) return FALSE;
        return TRUE;
    }

    private function validatedSlug($column, $excludeId)
    {
        $slug = $this->normalizeSlug($this->input->post($this->colPrefix.'slug'));
        if ($slug === '') return NULL;
        return $this->uniqueSlug($column, $slug, $excludeId);
    }

    private function uniqueSlug($column, $base, $excludeId = 0)
    {
        $base = trim((string) $base);
        if ($base === '') $base = 'tour';
        $base = $this->truncate($base, 250);
        $excludeId = (int) $excludeId;
        if (!$this->slugExists($column, $base, $excludeId)) return $base;
        for ($suffix = 2; $suffix <= 50; $suffix++)
        {
            $candidate = $this->truncate($base, 250 - strlen('-'.$suffix)).'-'.$suffix;
            if (!$this->slugExists($column, $candidate, $excludeId)) return $candidate;
        }
        $candidate = $this->truncate($base, 240).'-'.bin2hex(random_bytes(4));
        return $this->slugExists($column, $candidate, $excludeId) ? $candidate.'-'.time() : $candidate;
    }

    private function slugExists($column, $slug, $excludeId)
    {
        // The public site finds a tour by either slug column, so a slug another tour already uses in
        // English or Arabic would make two addresses ambiguous.
        $this->db->from($this->tblName);
        $this->db->group_start();
        $this->db->where($this->colPrefix.'slug', $slug);
        $this->db->or_where($this->colPrefix.'slug_ar', $slug);
        $this->db->group_end();
        if ($excludeId > 0) $this->db->where($this->pKey.' !=', $excludeId);
        return $this->db->count_all_results() > 0;
    }

    private function normalizeSlug($value)
    {
        $value = html_entity_decode(trim((string) $value), ENT_QUOTES, 'UTF-8');
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = preg_replace('/[\s_]+/u', '-', $value);
        $value = preg_replace('/[^\p{L}\p{N}-]+/u', '', $value);
        $value = preg_replace('/-+/', '-', $value);
        return trim($this->truncate($value, 255), '-');
    }

    private function truncate($value, $maxLength)
    {
        if (function_exists('mb_substr')) return mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8');
        return substr((string) $value, 0, (int) $maxLength);
    }

    private function formFailure($message, $editID = 0, $locale = 'en')
    {
        $posted = $this->input->post(NULL, FALSE);
        if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata('form_error', $message);
        redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
    }

    private function assignedRelations($tourId)
    {
        $relations = $this->emptyRelations();

        foreach ($this->relationDefinitions() as $relation => $definition)
        {
            $records = $this->SqlModel->getRecords(
                $definition['assignment_key'],
                $definition['assignment_table'],
                $definition['order_by'],
                'ASC',
                array('tour_id' => (int) $tourId)
            );

            foreach ($records as $record)
            {
                $relations[$relation][] = (int) $record[$definition['assignment_key']];
            }
        }

        return $relations;
    }

    private function syncRelations($tourId, array $relations)
    {
        foreach ($this->relationDefinitions() as $relation => $definition)
        {
            $this->SqlModel->deleteRecord(
                $definition['assignment_table'],
                array('tour_id' => (int) $tourId)
            );

            $order = 1;
            $ids = isset($relations[$relation]) ? $relations[$relation] : array();

            foreach ($ids as $id)
            {
                $assignment = array(
                    $definition['assignment_key'] => (int) $id,
                    'tour_id' => (int) $tourId,
                );

                if ($definition['has_sort_order'])
                {
                    $assignment['order'] = $order;
                    $order++;
                }

                $this->SqlModel->insertRecord($definition['assignment_table'], $assignment);
            }
        }
    }

    private function saveUploadedFile($field, $relativeDirectory, $allowedTypes)
    {
        $result = array('filename' => '', 'error' => '');
        if (empty($_FILES[$field]['name'])) return $result;
        if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK)
        {
            $result['error'] = 'The upload did not complete successfully.';
            return $result;
        }
        if ((int) $_FILES[$field]['size'] > (int) UPLOAD_SIZE)
        {
            $result['error'] = 'The file must be '.UPLOAD_SIZE_MB.' MB or smaller.';
            return $result;
        }
        $uploadPath = FCPATH.trim($relativeDirectory, '/\\').DIRECTORY_SEPARATOR;
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE))
        {
            $result['error'] = 'The upload directory could not be created.';
            return $result;
        }
        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path' => $uploadPath, 'allowed_types' => $allowedTypes,
            'max_size' => UPLOAD_SIZE_MB * 1024, 'encrypt_name' => TRUE, 'remove_spaces' => TRUE,
        ));
        if (!$this->upload->do_upload($field))
        {
            $result['error'] = strip_tags($this->upload->display_errors('', ''));
            return $result;
        }
        $file = $this->upload->data();
        $result['filename'] = $file['file_name'];
        return $result;
    }

    private function deleteUploadedFile($relativeDirectory, $filename, $column)
    {
        if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
        if ($this->SqlModel->countRecords($this->tblName, array($column => $filename)) > 0) return;
        delete_uploaded_file(FCPATH.trim($relativeDirectory, '/\\'), $filename);
    }

    private function copyImage($filename)
    {
        $filename = basename((string) $filename);
        if ($filename === '') return '';
        $source = FCPATH.'assets'.DIRECTORY_SEPARATOR.'frontend'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'tours'.DIRECTORY_SEPARATOR.$filename;
        if (!is_file($source)) return '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = array_filter(explode('|', UPLOAD_IMAGE_MIMES));
        if (!in_array($extension, $allowed, TRUE)) return '';
        try { $copy = bin2hex(random_bytes(16)).'.'.$extension; }
        catch (Throwable $exception) { return ''; }
        $destination = FCPATH.'assets'.DIRECTORY_SEPARATOR.'frontend'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'tours'.DIRECTORY_SEPARATOR.$copy;
        return @copy($source, $destination) ? $copy : '';
    }

    private function deleteTourRecords(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) return 'error';
        $records = $this->db->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
        if (count($records) !== count($ids)) return 'error';
        if ($this->db->where_in('book_tour_id', $ids)->count_all_results('tour_bookings') > 0) return 'blocked';
        if ($this->db->where_in('tour_id', $ids)->count_all_results('tour_guide_assigned_tours') > 0) return 'blocked';
        if ($this->db->where_in('image_tour_id', $ids)->count_all_results('tour_images') > 0) return 'blocked';
        if ($this->db->where_in('tour_id', $ids)->count_all_results('tour_itineraries') > 0) return 'blocked';

        $this->db->trans_begin();
        foreach ($ids as $id)
        {
            $this->SqlModel->deleteRecord('tour_assigned_attractions', array('tour_id' => $id));
            $this->SqlModel->deleteRecord('tour_assigned_categories', array('tour_id' => $id));
            $this->SqlModel->deleteRecord('tour_assigned_vehicles', array('tour_id' => $id));
            $this->SqlModel->deleteRecord('tour_assigned_slots', array('tour_id' => $id));
            $this->manage_translation_service->delete_jobs('tours', $id);
        }
        $this->db->where_in($this->pKey, $ids)->delete($this->tblName);
        $deleted = $this->db->affected_rows();
        if ($this->db->trans_status() === FALSE || $deleted !== count($ids))
        {
            $this->db->trans_rollback();
            return 'error';
        }
        $this->db->trans_commit();
        foreach ($records as $record)
        {
            $this->deleteUploadedFile('assets/frontend/images/tours', $record[$this->colPrefix.'image'], $this->colPrefix.'image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $record[$this->colPrefix.'image_ar'], $this->colPrefix.'image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $record[$this->colPrefix.'bg_image'], $this->colPrefix.'bg_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $record[$this->colPrefix.'bg_image_ar'], $this->colPrefix.'bg_image_ar');
            $this->deleteUploadedFile('assets/frontend/images/tours', $record['og_image'], 'og_image');
            $this->deleteUploadedFile('assets/frontend/images/tours', $record['og_image_ar'], 'og_image_ar');
        }
        return 'success';
    }

    private function deleteAlertFor($result)
    {
        if ($result === 'success') return 'deletesuccess';
        if ($result === 'blocked') return 'deleteblocked';
        return 'deleteerror';
    }

    private function queueTranslationSafely($tourId)
    {
        try
        {
            $this->manage_translation_service->queue('tours', (int) $tourId);
        }
        catch (Throwable $exception)
        {
            log_message('error', 'Tour translation could not be queued for record '.(int) $tourId.'.');
        }
    }
}
