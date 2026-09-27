<?php defined('BASEPATH') OR exit('No direct script access allowed');

$avatarFieldName = isset($field_name) ? (string) $field_name : 'avatar';
$avatarLabel = isset($label) ? (string) $label : 'Avatar image';
$avatarCurrentPath = isset($current_path) ? (string) $current_path : '';
$avatarFallback = isset($fallback) && trim((string) $fallback) !== '' ? strtoupper(substr(trim((string) $fallback), 0, 1)) : 'A';
$avatarRequired = !empty($required);
$avatarAllowRemove = !empty($allow_remove);
$avatarRemoveName = isset($remove_name) ? (string) $remove_name : 'remove_avatar';
$avatarCropTitle = isset($crop_title) ? (string) $crop_title : 'Adjust avatar';
$avatarApplyLabel = isset($apply_label) ? (string) $apply_label : 'Use this avatar';
$avatarInstructions = isset($instructions) ? (string) $instructions : AVATAR_UPLOAD_INSTRUCTIONS;
$avatarHasImage = image_thumb_path($avatarCurrentPath) !== FALSE;
$avatarKey = preg_replace('/[^a-z0-9_-]+/i', '-', $avatarFieldName);
$avatarSourceAccept = upload_accept_types(UPLOAD_IMAGE_MIMES);
$avatarOutputAccept = upload_accept_types('png');
?>
<div class="admin-field mb-4 admin-avatar-field" data-avatar-editor data-avatar-required="<?php echo $avatarRequired ? 'true' : 'false'; ?>" data-avatar-has-image="<?php echo $avatarHasImage ? 'true' : 'false'; ?>" data-avatar-fallback="<?php echo htmlspecialchars($avatarFallback, ENT_QUOTES, 'UTF-8'); ?>" data-avatar-max-bytes="<?php echo (int) AVATAR_UPLOAD_MAX_MB * 1024 * 1024; ?>" data-avatar-output-size="<?php echo (int) AVATAR_OUTPUT_SIZE; ?>">
    <label class="form-label<?php echo $avatarRequired ? ' is-required' : ''; ?>"><?php echo htmlspecialchars($avatarLabel, ENT_QUOTES, 'UTF-8'); ?></label>
    <div class="admin-avatar-input-row">
        <div class="admin-avatar-preview" data-avatar-preview>
            <?php if ($avatarHasImage) { ?>
            <a class="fancybox admin-avatar-preview-link" href="<?php echo image_thumb_escape(image_thumb_url(image_thumb_path($avatarCurrentPath))); ?>" aria-label="Preview <?php echo htmlspecialchars(strtolower($avatarLabel), ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo image_thumb_escape(image_thumb_url(image_thumb_path($avatarCurrentPath))); ?>" alt="Current <?php echo htmlspecialchars(strtolower($avatarLabel), ENT_QUOTES, 'UTF-8'); ?>">
            </a>
            <?php } else { ?><span data-avatar-initial><?php echo htmlspecialchars($avatarFallback, ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
        </div>
        <div>
            <input class="visually-hidden" type="file" id="<?php echo $avatarKey; ?>-source" accept="<?php echo htmlspecialchars($avatarSourceAccept, ENT_QUOTES, 'UTF-8'); ?>" data-avatar-source>
            <input class="visually-hidden" type="file" id="<?php echo $avatarKey; ?>-output" name="<?php echo htmlspecialchars($avatarFieldName, ENT_QUOTES, 'UTF-8'); ?>" accept="<?php echo htmlspecialchars($avatarOutputAccept, ENT_QUOTES, 'UTF-8'); ?>" data-avatar-output>
            <input type="hidden" name="<?php echo htmlspecialchars($avatarRemoveName, ENT_QUOTES, 'UTF-8'); ?>" value="0" data-avatar-remove-input>
            <button class="btn btn-outline-primary" type="button" data-avatar-choose><i class="bi bi-image" aria-hidden="true"></i> Choose and adjust</button>
            <?php if ($avatarAllowRemove && $avatarHasImage) { ?><button class="btn btn-outline-danger ms-1" type="button" data-avatar-remove>Remove</button><?php } ?>
            <div class="form-text"><?php echo htmlspecialchars($avatarInstructions, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="form-text text-primary" data-avatar-status aria-live="polite"></div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" aria-labelledby="<?php echo $avatarKey; ?>-crop-title" aria-hidden="true" data-avatar-modal>
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="<?php echo $avatarKey; ?>-crop-title"><?php echo htmlspecialchars($avatarCropTitle, ENT_QUOTES, 'UTF-8'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="admin-avatar-crop-stage"><img alt="Image selected for cropping" data-avatar-crop-image></div>
                <div class="admin-avatar-crop-tools" role="toolbar" aria-label="Image editing controls">
                    <button type="button" class="btn btn-outline-secondary" data-avatar-action="zoom-out" title="Zoom out"><i class="bi bi-zoom-out"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-avatar-action="zoom-in" title="Zoom in"><i class="bi bi-zoom-in"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-avatar-action="rotate-left" title="Rotate left"><i class="bi bi-arrow-counterclockwise"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-avatar-action="rotate-right" title="Rotate right"><i class="bi bi-arrow-clockwise"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-avatar-action="reset">Reset</button>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" data-avatar-apply><?php echo htmlspecialchars($avatarApplyLabel, ENT_QUOTES, 'UTF-8'); ?></button></div>
        </div></div>
    </div>
</div>
