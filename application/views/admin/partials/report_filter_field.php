<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One control of the shared report filter form.
 *
 * Expects $control (see Reports::filterControls()). Pass
 * 'label_as_placeholder' => TRUE to hide the label visually and show it as the
 * placeholder (used by compact toolbars such as the bookings listing).
 */
$labelAsPlaceholder = !empty($label_as_placeholder);
$fieldId = 'report-filter-' . $control['key'];
$fieldValue = (string) $control['value'];
$isSelectedChoice = function ($value) use ($fieldValue) {
    return $fieldValue !== '' && (string) $value === $fieldValue;
};
?>
<div class="report-filter-field report-filter-field-<?php echo report_e($control['type']); ?>">
    <label class="form-label<?php echo $labelAsPlaceholder ? ' visually-hidden' : ''; ?>" for="<?php echo report_e($fieldId); ?>"><?php echo report_e($control['label']); ?></label>
    <?php if ($control['type'] === 'search') { ?>
        <div class="report-search-control">
            <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
            <input
                class="form-control"
                type="search"
                id="<?php echo report_e($fieldId); ?>"
                name="<?php echo report_e($control['key']); ?>"
                value="<?php echo report_e($fieldValue); ?>"
                placeholder="Name, email, phone&hellip;"
                maxlength="100"
                autocomplete="off"
            >
        </div>
    <?php } elseif ($control['type'] === 'daterange') { ?>
        <input
            class="form-control daterange"
            type="text"
            id="<?php echo report_e($fieldId); ?>"
            name="<?php echo report_e($control['key']); ?>"
            value="<?php echo report_e($fieldValue); ?>"
            placeholder="<?php echo $labelAsPlaceholder ? report_e($control['label']) : 'Any date'; ?>"
            autocomplete="off"
        >
    <?php } else { ?>
        <select
            class="form-select select2"
            id="<?php echo report_e($fieldId); ?>"
            name="<?php echo report_e($control['key']); ?>"
            <?php echo $control['search'] ? '' : 'data-minimum-results-for-search="-1"'; ?>
        >
            <option value=""><?php echo $labelAsPlaceholder ? report_e($control['label']) : 'All'; ?></option>
            <?php foreach ($control['extra'] as $choice) { ?>
                <option value="<?php echo report_e($choice[0]); ?>"<?php echo $isSelectedChoice($choice[0]) ? ' selected' : ''; ?>><?php echo report_e($choice[1]); ?></option>
            <?php } ?>
            <?php foreach ($control['choices'] as $choice) { ?>
                <option value="<?php echo report_e($choice[0]); ?>"<?php echo $isSelectedChoice($choice[0]) ? ' selected' : ''; ?>><?php echo report_e($choice[1]); ?></option>
            <?php } ?>
            <?php foreach ($control['groups'] as $groupLabel => $groupChoices) { ?>
                <optgroup label="<?php echo report_e($groupLabel); ?>">
                    <?php foreach ($groupChoices as $choice) { ?>
                        <option value="<?php echo report_e($choice[0]); ?>"<?php echo $isSelectedChoice($choice[0]) ? ' selected' : ''; ?>><?php echo report_e($choice[1]); ?></option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>
    <?php } ?>
</div>
