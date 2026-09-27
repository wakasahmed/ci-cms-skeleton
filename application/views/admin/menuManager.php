<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared active/available page-hierarchy manager screen, used for both the
 * Main Menu (application/controllers/manage/Menu.php) and each Footer Menu
 * column (application/controllers/manage/Foot.php). The controller supplies:
 *
 * - $menuLabel: human name used in headings, alerts, and control labels
 *   (e.g. "Main Menu", "Footer Menu").
 * - $menuDescription: module header description text.
 * - $saveUrl: form action for the save route.
 * - $maxDepth: 2 for the Main Menu (one level of nesting), 1 for a Footer
 *   Menu column (flat, no nesting).
 * - $active, $available: from Page_menu_hierarchy::buildTree().
 */

$activeCount = 0;
foreach ($active as $activeRoot) {
    $activeCount += 1 + count($activeRoot['children']);
}
$availableCount = count($available);
$menuManagerState = json_encode(array('active' => $active, 'available' => $available), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$menuLabelSafe = htmlspecialchars($menuLabel, ENT_QUOTES, 'UTF-8');
$supportsNesting = $maxDepth > 1;
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Menu Manager'), array('label' => $menuLabel, 'active' => TRUE)))); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => $menuLabel,
    'status' => $alert,
    'status_messages' => array(
        'success' => array('success', 'Success!', $menuLabel.' updated successfully.'),
        'error' => array('danger', 'Error!', 'The '.$menuLabel.' could not be updated. No changes were saved.'),
        'invalid' => array('danger', 'Error!', 'The submitted menu arrangement is invalid. No changes were saved.'),
    ),
)); ?>

<section class="admin-records-listing menu-manager" aria-labelledby="main-menu-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $menuLabel,
        'description' => $menuDescription,
        'id' => 'main-menu-title',
    )); ?>

    <p class="menu-manager-instructions">Drag an item by its <i class="bi bi-grip-vertical" aria-hidden="true"></i> handle to reorder it<?php echo $supportsNesting ? ', or drop it onto another '.$menuLabelSafe.' item to nest it one level' : ''; ?>.</p>

    <form method="post" action="<?php echo htmlspecialchars($saveUrl, ENT_QUOTES, 'UTF-8'); ?>" id="menu-manager-form" class="admin-form-actions-host" data-menu-max-depth="<?php echo (int) $maxDepth; ?>" data-menu-label="<?php echo $menuLabelSafe; ?>">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <input type="hidden" name="menu_payload" id="menu-manager-payload" value="">

        <div class="menu-manager-board row">
            <div class="col-lg-6 menu-manager-column">
                <div class="admin-card card">
                    <div class="card-header">
                        <h2 class="menu-manager-column-title"><?php echo $menuLabelSafe; ?></h2>
                        <span class="badge menu-manager-count" id="menu-manager-active-count" data-menu-count="active"><?php echo $activeCount; ?> item<?php echo $activeCount === 1 ? '' : 's'; ?></span>
                    </div>
                    <div class="card-body">
                        <p class="menu-manager-column-description">These pages appear in the <?php echo $menuLabelSafe; ?>, in this order.</p>
                        <ul class="menu-manager-list" id="menu-manager-active-list" data-menu-list="active-root" aria-label="<?php echo $menuLabelSafe; ?> items">
                            <?php foreach ($active as $node) {
                                $this->load->view('admin/partials/menu_manager_item', array('node' => $node, 'status' => 'active', 'depth' => 0, 'maxDepth' => $maxDepth, 'menuLabel' => $menuLabel));
                            } ?>
                        </ul>
                        <p class="menu-manager-empty-state" id="menu-manager-active-empty"<?php echo $activeCount > 0 ? ' hidden' : ''; ?>>No pages are currently in the <?php echo $menuLabelSafe; ?>.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 menu-manager-column">
                <div class="admin-card card">
                    <div class="card-header">
                        <h2 class="menu-manager-column-title">Available Pages</h2>
                        <span class="badge menu-manager-count" id="menu-manager-available-count" data-menu-count="available"><?php echo $availableCount; ?> item<?php echo $availableCount === 1 ? '' : 's'; ?></span>
                    </div>
                    <div class="card-body">
                        <p class="menu-manager-column-description">These pages are not currently shown in the <?php echo $menuLabelSafe; ?>.</p>
                        <ul class="menu-manager-list" id="menu-manager-available-list" data-menu-list="available" aria-label="Available pages">
                            <?php foreach ($available as $node) {
                                $this->load->view('admin/partials/menu_manager_item', array('node' => $node, 'status' => 'available', 'depth' => 0, 'maxDepth' => $maxDepth, 'menuLabel' => $menuLabel));
                            } ?>
                        </ul>
                        <p class="menu-manager-empty-state" id="menu-manager-available-empty"<?php echo $availableCount > 0 ? ' hidden' : ''; ?>>All available pages have been added to the <?php echo $menuLabelSafe; ?>.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="visually-hidden" role="status" aria-live="polite" id="menu-manager-announcer"></div>

        <div class="admin-form-actions">
            <button type="button" class="btn btn-outline-secondary" id="menu-manager-reset" disabled>Reset</button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.location='<?php echo ADMIN_URL; ?>'">Cancel</button>
            <button type="submit" class="btn btn-primary" id="menu-manager-save" disabled>Save Changes</button>
        </div>
    </form>
</section>

<div class="modal fade" id="menu-manager-reset-modal" tabindex="-1" aria-labelledby="menu-manager-reset-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="menu-manager-reset-modal-label">Discard unsaved changes?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p class="mb-0">Discard unsaved changes and restore the last saved <?php echo $menuLabelSafe; ?> arrangement?</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="menu-manager-reset-confirm">Discard Changes</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="menu-manager-data"><?php echo $menuManagerState; ?></script>
