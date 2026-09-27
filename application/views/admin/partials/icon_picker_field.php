<?php
$iconPickerId = isset($iconPickerId) ? $iconPickerId : $iconPickerName;
$iconPickerValue = isset($iconPickerValue) ? trim($iconPickerValue) : '';
$iconPickerHelp = isset($iconPickerHelp) ? $iconPickerHelp : '';
$iconPickerError = isset($iconPickerError) ? $iconPickerError : '';
$iconPickerRequired = !empty($iconPickerRequired);
$iconPickerSources = array(
    array(
        'key' => 'fa7-solid',
        'prefix' => 'fa-solid fa-',
        'url' => ADMIN_ASSETS.'vendor/fontawesome-free/icons/solid.json?v=7.3.1'
    ),
    array(
        'key' => 'fa7-regular',
        'prefix' => 'fa-regular fa-',
        'url' => ADMIN_ASSETS.'vendor/fontawesome-free/icons/regular.json?v=7.3.1'
    ),
    array(
        'key' => 'fa7-brands',
        'prefix' => 'fa-brands fa-',
        'url' => ADMIN_ASSETS.'vendor/fontawesome-free/icons/brands.json?v=7.3.1'
    )
);
?>
<div class="admin-field mb-3 admin-icon-picker-field">
    <label class="form-label<?php echo $iconPickerRequired ? ' is-required' : ''; ?>" for="<?php echo htmlspecialchars($iconPickerId, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($iconPickerLabel, ENT_QUOTES, 'UTF-8'); ?>
    </label>
    <div class="input-group admin-icon-picker-control">
        <span class="input-group-text admin-icon-picker-preview" aria-hidden="true">
            <i<?php echo $iconPickerValue !== '' ? ' class="'.htmlspecialchars($iconPickerValue, ENT_QUOTES, 'UTF-8').'"' : ''; ?>></i>
        </span>
        <input
            type="text"
            class="form-control admin-icon-picker-input<?php echo $iconPickerError !== '' ? ' is-invalid' : ''; ?>"
            name="<?php echo htmlspecialchars($iconPickerName, ENT_QUOTES, 'UTF-8'); ?>"
            id="<?php echo htmlspecialchars($iconPickerId, ENT_QUOTES, 'UTF-8'); ?>"
            value="<?php echo htmlspecialchars($iconPickerValue, ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="255"
            placeholder="Select a Font Awesome icon"
            autocomplete="off"
            readonly
            data-icon-picker
            data-icon-sources="<?php echo htmlspecialchars(json_encode($iconPickerSources), ENT_QUOTES, 'UTF-8'); ?>"
            <?php echo $iconPickerRequired ? 'data-validate="required" required' : ''; ?>
        >
        <button class="btn btn-outline-secondary admin-icon-picker-open" type="button">Browse</button>
        <button class="btn btn-outline-secondary admin-icon-picker-clear" type="button"<?php echo $iconPickerValue === '' ? ' disabled' : ''; ?>>Clear</button>
    </div>
    <?php if ($iconPickerHelp !== '') { ?>
    <div class="form-text"><?php echo htmlspecialchars($iconPickerHelp, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>
    <?php if ($iconPickerError !== '') { ?>
    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($iconPickerError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>
</div>
