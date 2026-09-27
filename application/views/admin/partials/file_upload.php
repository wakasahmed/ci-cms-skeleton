<?php defined('BASEPATH') OR exit('No direct script access allowed');

$uploadName = isset($name) ? (string) $name : 'uploadfile';
$uploadId = isset($id) ? (string) $id : $uploadName;
$uploadLabel = isset($label) ? (string) $label : 'File';
$uploadTypes = isset($allowed_types) ? (string) $allowed_types : UPLOAD_IMAGE_MIMES;
$uploadRequired = !empty($required);
$uploadCurrentPath = isset($current_path) ? (string) $current_path : '';
$uploadHelp = isset($help) ? (string) $help : '';
$uploadAlt = isset($preview_alt) ? (string) $preview_alt : 'Current '.$uploadLabel;
$uploadShape = isset($preview_shape) ? (string) $preview_shape : 'square';
$uploadPreviewSize = isset($preview_size) ? $preview_size : NULL;
// Optional. Dimensions the public website is built around, e.g. '1440 × 960px (3:2)'.
$uploadRecommendedSize = isset($recommended_size) ? trim((string) $recommended_size) : '';
// Optional. Short explanation of how the website uses (crops) the image.
$uploadSizeNote = isset($size_note) ? trim((string) $size_note) : '';

$extensions = array_values(array_filter(array_map('trim', explode('|', $uploadTypes))));
$accept = upload_accept_types($extensions);
$hasCurrentFile = image_thumb_path($uploadCurrentPath) !== FALSE;
?>
<div class="admin-file-upload" data-file-upload data-max-size="<?php echo (int) UPLOAD_SIZE; ?>" data-allowed-extensions="<?php echo htmlspecialchars(implode(',', $extensions), ENT_QUOTES, 'UTF-8'); ?>"<?php echo is_numeric($uploadPreviewSize) ? ' data-preview-size="'.(int) $uploadPreviewSize.'"' : ''; ?>>
    <div class="row g-3 align-items-stretch">
        <div class="col-md-6">
            <div class="admin-file-upload-input h-100">
                <label class="form-label<?php echo $uploadRequired ? ' is-required' : ''; ?>" for="<?php echo htmlspecialchars($uploadId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($uploadLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="file" name="<?php echo htmlspecialchars($uploadName, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($uploadId, ENT_QUOTES, 'UTF-8'); ?>" class="form-control admin-file-input" accept="<?php echo htmlspecialchars($accept, ENT_QUOTES, 'UTF-8'); ?>" data-file-upload-input <?php echo ($uploadRequired && !$hasCurrentFile) ? 'required' : ''; ?>>
                <div class="form-text"><?php echo $uploadHelp !== '' ? htmlspecialchars($uploadHelp, ENT_QUOTES, 'UTF-8') : 'Allowed: '.htmlspecialchars(strtoupper(implode(', ', $extensions)), ENT_QUOTES, 'UTF-8').'. Maximum size: '.(int) UPLOAD_SIZE_MB.' MB.'; ?></div>
                <?php if ($uploadRecommendedSize !== '') { ?>
                    <div class="form-text" data-file-upload-recommended-size>
                        Recommended size:
                        <span class="fw-semibold"><?php echo htmlspecialchars($uploadRecommendedSize, ENT_QUOTES, 'UTF-8'); ?></span>.
                        <?php echo $uploadSizeNote !== '' ? htmlspecialchars($uploadSizeNote, ENT_QUOTES, 'UTF-8') : ''; ?>
                    </div>
                <?php } ?>
                <div class="invalid-feedback" data-file-upload-error></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="admin-file-upload-preview h-100" data-file-upload-preview aria-live="polite">
                <?php echo upload_file_preview($uploadCurrentPath, array('alt' => $uploadAlt, 'shape' => $uploadShape, 'size' => $uploadPreviewSize)); ?>
                <?php if (!$hasCurrentFile) { ?><span class="admin-file-upload-empty" data-file-upload-empty><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><span>No file selected</span></span><?php } ?>
            </div>
        </div>
    </div>
</div>
