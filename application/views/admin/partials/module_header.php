<?php defined('BASEPATH') OR exit('No direct script access allowed');

$moduleHeaderTitle = isset($title) ? (string) $title : '';
$moduleHeaderDescription = isset($description) ? (string) $description : '';
$moduleHeaderId = isset($id) && trim((string) $id) !== '' ? trim((string) $id) : 'admin-module-title';
$moduleHeaderActions = isset($actions) && is_array($actions) ? $actions : array();
$moduleHeaderActionsView = isset($actions_view) ? trim((string) $actions_view) : '';
$moduleHeaderActionsData = isset($actions_data) && is_array($actions_data) ? $actions_data : array();
if (isset($action_url) && (string) $action_url !== '') {
    $moduleHeaderActions[] = array(
        'url' => $action_url,
        'label' => isset($action_label) ? $action_label : 'Add New',
        'icon' => isset($action_icon) ? $action_icon : 'bi-plus-lg',
        'class' => isset($action_class) ? $action_class : 'btn-primary pages-add-button',
    );
}
?>
<header class="pages-listing-header admin-module-header">
    <div>
        <h1 class="pages-listing-title" id="<?php echo htmlspecialchars($moduleHeaderId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($moduleHeaderTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($moduleHeaderDescription !== '') { ?><p class="pages-listing-description"><?php echo htmlspecialchars($moduleHeaderDescription, ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
    </div>
    <?php if (!empty($moduleHeaderActions) || $moduleHeaderActionsView !== '') { ?><div class="admin-module-header-actions">
        <?php foreach ($moduleHeaderActions as $moduleHeaderAction) { ?><a class="btn <?php echo htmlspecialchars(isset($moduleHeaderAction['class']) ? $moduleHeaderAction['class'] : 'btn-primary', ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(isset($moduleHeaderAction['url']) ? $moduleHeaderAction['url'] : '#', ENT_QUOTES, 'UTF-8'); ?>"><?php if (!empty($moduleHeaderAction['icon'])) { ?><i class="bi <?php echo htmlspecialchars($moduleHeaderAction['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i><?php } ?> <?php echo htmlspecialchars(isset($moduleHeaderAction['label']) ? $moduleHeaderAction['label'] : '', ENT_QUOTES, 'UTF-8'); ?></a><?php } ?>
        <?php if ($moduleHeaderActionsView !== '') { $this->load->view($moduleHeaderActionsView, $moduleHeaderActionsData); } ?>
    </div><?php } ?>
</header>
