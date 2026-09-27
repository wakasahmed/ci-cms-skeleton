<?php
$action = base_url('manage/'.$this->controller).'/saveManageAvailability';

$tourGuideSelectUsers = array();
if (!empty($tour_guides)) {
    foreach ($tour_guides as $g) {
        $tourGuideSelectUsers[] = array(
            'id' => $g['tour_guide_id'],
            'full_name' => $g['tour_guide_name'],
            'phone' => isset($g['tour_guide_phone']) ? $g['tour_guide_phone'] : '',
            'avatar' => isset($g['tour_guide_image']) ? $g['tour_guide_image'] : '',
        );
    }
}
?>

<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => 'Add or Remove Availability', 'active' => TRUE)))); ?>

<?php if ($alert === 'error') { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert">x</button><div class="alert alert-danger"><strong>Error!</strong> Please select at least one tour guide, a date range, at least one slot, and an action.</div></div></div>
<?php } ?>

<?php $this->load->view('admin/partials/module_header', array('title' => 'Add or Remove Availability', 'description' => 'Select the tour guide(s), a date range, and the slot(s), then add availability for those dates or remove availability that has not been booked.')); ?>

<div class="card admin-card">
    <div class="card-body">
        <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" class="validate" novalidate data-submit-lock>

            <div class="row g-3">

                <div class="admin-field col-md-6 mb-0">
                    <label class="form-label is-required" for="avail_action">Action</label>
                    <select autocomplete="off" name="avail_action" id="avail_action" class="select2 required" data-placeholder="Select action" data-minimum-results-for-search="-1" required>
                        <option value=""></option>
                        <option value="Add">Add Availability</option>
                        <option value="Remove">Remove Availability</option>
                    </select>
                </div>

                <div class="admin-field col-md-6 mb-0">
                    <label class="form-label is-required" for="avail_date">Date</label>
                    <input type="text" name="avail_date" id="avail_date" class="form-control availability-date-range" data-validate="required" placeholder="Select date range" autocomplete="off" required />
                    <input type="hidden" name="avail_date_start" id="avail_date_start" value="" />
                    <input type="hidden" name="avail_date_end" id="avail_date_end" value="" />
                </div>

                <div class="admin-field col-12 mb-0">
                    <label class="form-label is-required" for="avail_slot_id">Slots</label>
                    <select autocomplete="off" name="avail_slot_id[]" id="avail_slot_id" class="select2 required" data-placeholder="Select slot(s)" multiple required>
                        <?php
                        if (!empty($slots)) {
                            foreach ($slots as $s) {
                                echo '<option value="'.$s['slot_id'].'">'.htmlspecialchars($s['slot_title'], ENT_QUOTES, 'UTF-8').'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="admin-field col-12 mb-0">
                    <label class="form-label is-required" for="avail_tour_guide_id">Guide(s)</label>
                    <?php $this->load->view('admin/partials/user_select', array(
                        'name' => 'avail_tour_guide_id',
                        'id' => 'avail_tour_guide_id',
                        'users' => $tourGuideSelectUsers,
                        'multiple' => TRUE,
                        'required' => TRUE,
                        'select_all' => TRUE,
                        'empty_label' => 'Select tour guide(s)',
                        'image_directory' => 'assets/frontend/images/tour-guides',
                    )); ?>
                    <div class="form-text">Choose one or more active guides for the selected date range and slots.</div>
                </div>

                <div class="admin-form-actions col-12 mb-0">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location='<?php echo ADMIN_URL.$this->controller; ?>'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
jQuery(function ($) {
    var $start = $('#avail_date_start');
    var $end = $('#avail_date_end');

    flatpickr('#avail_date', {
        allowInput: true,
        mode: 'range',
        conjunction: ' to ',
        dateFormat: 'd-M-Y',
        disableMobile: true,
        onChange: function (selectedDates) {
            $start.val(selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '');
            $end.val(selectedDates[1] ? flatpickr.formatDate(selectedDates[1], 'Y-m-d') : (selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : ''));
        }
    });
});
</script>
