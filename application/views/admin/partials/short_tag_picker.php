<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Short tags for a notification template form. Each tag has a copy action
 * (the shared data-copy-value handler in admin.js, which needs $useSweetAlert for
 * its toast) and an insert action, which inserts the tag into the field marked
 * data-short-tag-target that last had focus (or the one marked
 * data-short-tag-default), including CKEditor fields.
 * Insert behaviour: assets/admin/js/short-tag-picker.js (loaded with $useShortTagPicker).
 *
 * Parameters:
 * - tags: array('tag_name' => 'Example value')
 * - entity_label: e.g. "Contact request", shown in the heading
 * - description: optional help text
 * - id: optional unique ID prefix
 */
$shortTagPickerTags = isset($tags) && is_array($tags) ? $tags : array();
$shortTagPickerEntity = isset($entity_label) ? trim((string) $entity_label) : '';
$shortTagPickerId = isset($id) && trim((string) $id) !== '' ? trim((string) $id) : 'short-tag-picker';
$shortTagPickerDescription = isset($description) && trim((string) $description) !== ''
    ? trim((string) $description)
    : 'Copy a tag, or use + to insert it where your cursor is in the field you are editing.';
$shortTagPickerEscape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<div class="short-tag-picker mb-3" id="<?php echo $shortTagPickerEscape($shortTagPickerId); ?>" data-short-tag-picker>
    <p class="form-label mb-1" id="<?php echo $shortTagPickerEscape($shortTagPickerId); ?>-label">
        <?php echo $shortTagPickerEscape($shortTagPickerEntity !== '' ? $shortTagPickerEntity . ' ' : ''); ?>Short Tags
    </p>
    <p class="form-text mt-0"><?php echo $shortTagPickerEscape($shortTagPickerDescription); ?></p>
    <?php if (!empty($shortTagPickerTags)) { ?>
        <div class="short-tag-picker-tags" role="group" aria-labelledby="<?php echo $shortTagPickerEscape($shortTagPickerId); ?>-label">
            <?php foreach ($shortTagPickerTags as $shortTag => $shortTagExample) { ?>
                <?php $shortTagText = $shortTagPickerEscape('{{' . $shortTag . '}}'); ?>
                <span class="short-tag-picker-item">
                    <code class="short-tag-picker-code" title="Example: <?php echo $shortTagPickerEscape($shortTagExample); ?>"><?php echo $shortTagText; ?></code>
                    <button
                        type="button"
                        class="admin-action-icon short-tag-picker-action"
                        data-copy-value="<?php echo $shortTagText; ?>"
                        data-copy-message="<?php echo $shortTagText; ?> copied to clipboard."
                        aria-label="Copy <?php echo $shortTagText; ?>"
                        title="Copy"
                    ><i class="bi bi-copy" aria-hidden="true"></i></button>
                    <button
                        type="button"
                        class="admin-action-icon short-tag-picker-action"
                        data-short-tag-insert="<?php echo $shortTagPickerEscape($shortTag); ?>"
                        aria-label="Insert <?php echo $shortTagText; ?>"
                        title="Insert"
                    ><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                </span>
            <?php } ?>
        </div>
    <?php } else { ?>
        <p class="form-text text-warning-emphasis">No short tags are configured for this template.</p>
    <?php } ?>
</div>
