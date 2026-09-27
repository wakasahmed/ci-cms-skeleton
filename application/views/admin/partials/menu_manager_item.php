<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Renders one active/available tree node for the Main Menu and Footer Menu
 * manager screens, and recurses for its children where nesting is allowed.
 * Expects: $node (array from Page_menu_hierarchy::buildTree), $status
 * ('active' or 'available'), $depth (0 = root/flat item, 1 = active child),
 * $maxDepth (levels the module supports: 2 for Main Menu, 1 for Footer
 * Menu, which is flat), $menuLabel (human name used in control labels,
 * e.g. "Main Menu" or "Footer Menu").
 *
 * All six move controls are always rendered (indent/outdent only when
 * $maxDepth allows nesting); menu-manager.js keeps their hidden/disabled
 * state in sync with the item's live position after every drag or keyboard
 * move, so the PHP-computed initial state below only has to match what the
 * script would compute for the same position.
 */

$itemPageId = (int) $node['page_id'];
$itemMenuName = html_entity_decode((string) $node['menu_name'], ENT_QUOTES, 'UTF-8');
$itemPageName = html_entity_decode((string) $node['page_name'], ENT_QUOTES, 'UTF-8');
$itemEnglish = trim($itemMenuName) !== '' ? $itemMenuName : (trim($itemPageName) !== '' ? $itemPageName : 'Untitled Page #'.$itemPageId);
$itemChildren = isset($node['children']) && is_array($node['children']) ? $node['children'] : array();
$itemEditUrl = base_url('manage/pages/control/'.(int) $node['page_parent_id'].'/'.$itemPageId);
$itemIsActive = $status === 'active';
$itemIsChild = $depth > 0;
$itemSupportsNesting = $maxDepth > 1;
$itemCanIndent = $itemIsActive && !$itemIsChild && $itemSupportsNesting;
$itemCanOutdent = $itemIsActive && $itemIsChild;
?>
<li class="menu-manager-item" data-page-id="<?php echo $itemPageId; ?>" data-menu-status="<?php echo $itemIsActive ? 'active' : 'available'; ?>">
    <div class="menu-manager-item-row">
        <span class="menu-manager-drag-handle" aria-hidden="true" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>
        <div class="menu-manager-item-labels">
            <span class="menu-manager-item-en"><?php echo htmlspecialchars($itemEnglish, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <a class="admin-action-icon menu-manager-edit-link" href="<?php echo htmlspecialchars($itemEditUrl, ENT_QUOTES, 'UTF-8'); ?>" title="Edit page" aria-label="Edit <?php echo htmlspecialchars($itemEnglish, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil" aria-hidden="true"></i></a>
        <div class="menu-manager-item-controls" role="group" aria-label="Move <?php echo htmlspecialchars($itemEnglish, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="button" class="admin-action-icon menu-manager-control" data-menu-action="move-up" aria-label="Move up" title="Move up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
            <button type="button" class="admin-action-icon menu-manager-control" data-menu-action="move-down" aria-label="Move down" title="Move down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
            <button type="button" class="admin-action-icon menu-manager-control" data-menu-action="indent" aria-label="Nest under previous item" title="Indent"<?php echo $itemCanIndent ? '' : ' hidden'; ?>><i class="bi bi-arrow-bar-right" aria-hidden="true"></i></button>
            <button type="button" class="admin-action-icon menu-manager-control" data-menu-action="outdent" aria-label="Move to top level" title="Outdent"<?php echo $itemCanOutdent ? '' : ' hidden'; ?>><i class="bi bi-arrow-bar-left" aria-hidden="true"></i></button>
            <button type="button" class="admin-action-icon menu-manager-control" data-menu-action="remove" aria-label="Remove from <?php echo htmlspecialchars($menuLabel, ENT_QUOTES, 'UTF-8'); ?>" title="Remove from <?php echo htmlspecialchars($menuLabel, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $itemIsActive ? '' : ' hidden'; ?>><i class="bi bi-dash-circle" aria-hidden="true"></i></button>
            <button type="button" class="admin-action-icon menu-manager-control" data-menu-action="add" aria-label="Add to <?php echo htmlspecialchars($menuLabel, ENT_QUOTES, 'UTF-8'); ?>" title="Add to <?php echo htmlspecialchars($menuLabel, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $itemIsActive ? ' hidden' : ''; ?>><i class="bi bi-plus-circle" aria-hidden="true"></i></button>
        </div>
    </div>
    <?php if ($itemIsActive && !$itemIsChild && $itemSupportsNesting) { ?>
    <ul class="menu-manager-list menu-manager-children" data-menu-list="active-children" aria-label="Items nested under <?php echo htmlspecialchars($itemEnglish, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($itemChildren as $childNode) {
            $this->load->view('admin/partials/menu_manager_item', array('node' => $childNode, 'status' => $status, 'depth' => $depth + 1, 'maxDepth' => $maxDepth, 'menuLabel' => $menuLabel));
        } ?>
    </ul>
    <?php } ?>
</li>
