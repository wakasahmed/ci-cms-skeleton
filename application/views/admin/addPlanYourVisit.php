<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$selectedCountry = isset($tbl_data['country']) ? (int) $tbl_data['country'] : 0;
$selectedWebsite = isset($tbl_data['website']) && $tbl_data['website'] === 'Arabic' ? 'Arabic' : 'English';
$stepCompletedValue = isset($tbl_data['step_completed']) && $tbl_data['step_completed'] !== '' ? (int) $tbl_data['step_completed'] : 0;
$arrivalValue = isset($tbl_data['arrival_date']) ? trim((string) $tbl_data['arrival_date']) : '';
if ($arrivalValue !== '' && strpos($arrivalValue, '/') === FALSE) $arrivalValue = date('m/d/Y', strtotime($arrivalValue));
$departureValue = isset($tbl_data['departure_date']) ? trim((string) $tbl_data['departure_date']) : '';
if ($departureValue !== '' && strpos($departureValue, '/') === FALSE) $departureValue = date('m/d/Y', strtotime($departureValue));
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the Plan Your Visit request.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="plan-your-trip-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'plan-your-trip-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2></div>
        <div class="card-body">
            <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" class="validate">
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="name">Name</label><input type="text" name="name" id="name" maxlength="255" value="<?php echo $value('name'); ?>" class="form-control" data-validate="required" required placeholder="Full name"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="email">Email</label><input type="email" name="email" id="email" maxlength="255" value="<?php echo $value('email'); ?>" class="form-control" data-validate="required,email" required placeholder="name@example.com"></div>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="phone">Phone</label><input type="tel" name="phone" id="phone" maxlength="50" value="<?php echo $value('phone'); ?>" class="form-control intl-phone" data-initial-country="sa" data-validate="intlPhone" autocomplete="tel" placeholder="Phone number"></div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="country">Country</label>
                        <select class="form-select select2" name="country" id="country" data-placeholder="Select Country" data-validate="required" required>
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $countryOption) { ?>
                            <option value="<?php echo (int) $countryOption['id']; ?>" <?php echo $selectedCountry === (int) $countryOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($countryOption['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="arrival_date">Arrival Date</label><input type="text" name="arrival_date" id="arrival_date" maxlength="20" value="<?php echo htmlspecialchars($arrivalValue, ENT_QUOTES, 'UTF-8'); ?>" class="form-control datepicker" autocomplete="off" placeholder="MM/DD/YYYY"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="departure_date">Departure Date</label><input type="text" name="departure_date" id="departure_date" maxlength="20" value="<?php echo htmlspecialchars($departureValue, ENT_QUOTES, 'UTF-8'); ?>" class="form-control datepicker" data-validate="notlessthanfield[#arrival_date]" data-msg-notlessthanfield="Departure date cannot be earlier than the arrival date." autocomplete="off" placeholder="MM/DD/YYYY"></div>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="guests">Guests</label><input type="number" name="guests" id="guests" min="1" max="255" value="<?php echo $value('guests'); ?>" class="form-control" placeholder="Number of guests"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="step_completed">Step Completed</label><input type="number" name="step_completed" id="step_completed" min="0" max="255" value="<?php echo $stepCompletedValue; ?>" class="form-control" data-validate="required" required></div>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="preferred_language">Preferred Language</label><input type="text" name="preferred_language" id="preferred_language" maxlength="255" value="<?php echo $value('preferred_language'); ?>" class="form-control" placeholder="Preferred language"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="preferred_time">Preferred Time</label><input type="text" name="preferred_time" id="preferred_time" maxlength="255" value="<?php echo $value('preferred_time'); ?>" class="form-control" placeholder="Preferred time"></div>
                </div>
                <div class="admin-field mb-3"><label class="form-label" for="interests">Interests</label><textarea rows="3" name="interests" id="interests" class="form-control"><?php echo $value('interests'); ?></textarea></div>
                <div class="admin-field mb-3"><label class="form-label" for="message">Message</label><textarea rows="6" name="message" id="message" class="form-control"><?php echo $value('message'); ?></textarea></div>
                <hr>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ip">IP Address</label><input type="text" name="ip" id="ip" maxlength="50" value="<?php echo $value('ip'); ?>" class="form-control" placeholder="0.0.0.0"></div>
                </div>
                <div class="admin-field mb-4"><label class="form-label" for="user_agent">User Agent</label><textarea rows="3" name="user_agent" id="user_agent" class="form-control"><?php echo $value('user_agent'); ?></textarea></div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="website">Website</label>
                        <select class="form-select select2" name="website" id="website" data-minimum-results-for-search="-1" required>
                            <option value="English" <?php echo $selectedWebsite === 'English' ? 'selected' : ''; ?>>English</option>
                            <option value="Arabic" <?php echo $selectedWebsite === 'Arabic' ? 'selected' : ''; ?>>Arabic</option>
                        </select>
                    </div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="session_token">Session Token</label><input type="text" name="session_token" id="session_token" maxlength="64" value="<?php echo $value('session_token'); ?>" class="form-control" placeholder="Session token"></div>
                </div>

                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
