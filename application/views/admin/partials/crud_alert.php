<?php defined('BASEPATH') OR exit('No direct script access allowed');

$crudAlertStatus = isset($status) ? (string) $status : '';
$crudAlertModule = isset($module_name) && trim((string) $module_name) !== '' ? trim((string) $module_name) : 'Record';
$crudAlertMessages = array(
    'success' => array('success', 'Success!', $crudAlertModule.' was added successfully.'),
    'editsuccess' => array('success', 'Success!', $crudAlertModule.' was updated successfully.'),
    'deletesuccess' => array('success', 'Success!', $crudAlertModule.' was deleted successfully.'),
    'deleteerror' => array('danger', 'Error!', $crudAlertModule.' could not be deleted. Please try again.'),
    'error' => array('danger', 'Error!', 'The requested operation for '.$crudAlertModule.' could not be completed. Please try again.'),
);

if (isset($status_messages) && is_array($status_messages)) {
    $crudAlertMessages = array_replace($crudAlertMessages, $status_messages);
}

if (isset($crudAlertMessages[$crudAlertStatus])) {
    $crudAlert = $crudAlertMessages[$crudAlertStatus];
?>
<div class="row alertrow">
    <div class="col-md-12">
        <div class="alert alert-<?php echo htmlspecialchars($crudAlert[0], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
            <strong><?php echo htmlspecialchars($crudAlert[1], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php echo htmlspecialchars($crudAlert[2], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
        </div>
    </div>
</div>
<?php } ?>
