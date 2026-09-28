<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$columnClasses = array(
    'full' => 'col-12',
    'half' => 'col-12 col-lg-6',
    'third' => 'col-12 col-md-6 col-lg-4',
);
$lastGroup = '__initial__';
$contentImageTypes = (string) $this->config->item(
    'content_section_image_types',
    'content_sections'
);
$contentImagePath = trim((string) $this->config->item(
    'content_section_image_path',
    'content_sections'
), '/').'/';
?>
<div class="row content-section-fields">
    <?php foreach ($section['fields'] as $field) { ?>
        <?php
        $name = $this->content_section_service->field_name(
            $section['key'],
            $field['key']
        );
        $id = 'content-section-'.$name;
        $value = isset($section['values'][$field['key']])
            ? $section['values'][$field['key']]
            : '';
        $error = isset($errors[$name]) ? $errors[$name] : '';
        $group = $field['group'];
        $isRequired = $field['required'];
        $column = isset($columnClasses[$field['layout']])
            ? $columnClasses[$field['layout']]
            : $columnClasses['full'];
        ?>

        <?php if ($group !== $lastGroup && $group !== NULL) { ?>
            <div class="col-12 content-section-group">
                <h4><?php echo $escape($group); ?></h4>
            </div>
        <?php } ?>
        <?php $lastGroup = $group; ?>

        <div class="<?php echo $column; ?>">
            <?php if ($field['type'] === 'icon-picker') { ?>
                <?php
                $iconPickerName = $name;
                $iconPickerId = $id;
                $iconPickerLabel = $field['label'];
                $iconPickerValue = $value;
                $iconPickerHelp = '';
                $iconPickerError = $error;
                $iconPickerRequired = $isRequired;
                $this->load->view(
                    'admin/partials/icon_picker_field',
                    compact(
                        'iconPickerName',
                        'iconPickerId',
                        'iconPickerLabel',
                        'iconPickerValue',
                        'iconPickerHelp',
                        'iconPickerError',
                        'iconPickerRequired'
                    )
                );
                ?>
            <?php } elseif ($field['type'] === 'image') { ?>
                <div class="admin-field mb-3">
                    <?php $this->load->view('admin/partials/file_upload', array(
                        'name' => $name,
                        'id' => $id,
                        'label' => $field['label'],
                        'allowed_types' => $contentImageTypes,
                        'required' => $isRequired,
                        'current_path' => $value ? $contentImagePath.$value : '',
                        'preview_alt' => 'Current '.$field['label'],
                        'preview_shape' => 'square',
                        'preview_size' => 160,
                        'help' => 'Allowed: GIF, JPEG, PNG, or WebP. Maximum 10 MB.',
                        'recommended_size' => isset($field['recommended_size']) ? $field['recommended_size'] : '',
                        'size_note' => isset($field['size_note']) ? $field['size_note'] : '',
                    )); ?>

                    <?php if ($value) { ?>
                        <div class="form-check mt-2">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                value="1"
                                name="<?php echo $escape($name); ?>_remove"
                                id="<?php echo $escape($id); ?>-remove"
                            >
                            <label class="form-check-label" for="<?php echo $escape($id); ?>-remove">
                                Remove current image
                            </label>
                        </div>
                    <?php } ?>

                    <?php if ($error) { ?>
                        <div class="invalid-feedback d-block"><?php echo $escape($error); ?></div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="admin-field mb-3">
                    <label class="form-label<?php echo $isRequired ? ' is-required' : ''; ?>" for="<?php echo $escape($id); ?>">
                        <?php echo $escape($field['label']); ?>
                    </label>

                    <?php if ($field['type'] === 'text-editor') { ?>
                        <?php
                        $ckeditorAttributes = array(
                            'id' => $id,
                            'rows' => 8,
                            'cols' => 60,
                        );
                        if ($isRequired) {
                            $ckeditorAttributes['data-validate'] = 'htmlrequired';
                        }
                        $this->ckeditor->textareaAttributes = $ckeditorAttributes;
                        echo $this->ckeditor->editor(
                            $name,
                            (string) $value
                        );
                        ?>
                    <?php } elseif ($field['type'] === 'textarea') { ?>
                        <textarea
                            class="form-control<?php echo $error ? ' is-invalid' : ''; ?>"
                            name="<?php echo $escape($name); ?>"
                            id="<?php echo $escape($id); ?>"
                            rows="5"
                            <?php echo $isRequired ? 'data-validate="required" required' : ''; ?>
                        ><?php echo $escape($value); ?></textarea>
                    <?php } elseif ($field['type'] === 'select') { ?>
                        <select
                            class="form-select select2<?php echo $error ? ' is-invalid' : ''; ?>"
                            name="<?php echo $escape($name); ?>"
                            id="<?php echo $escape($id); ?>"
                            data-minimum-results-for-search="-1"
                            <?php echo $isRequired ? 'data-validate="required" required' : ''; ?>
                        >
                            <option value="">Select an option</option>
                            <?php foreach ($field['options'] as $optionValue => $optionLabel) { ?>
                                <option value="<?php echo $escape($optionValue); ?>"<?php echo (string) $value === (string) $optionValue ? ' selected' : ''; ?>>
                                    <?php echo $escape($optionLabel); ?>
                                </option>
                            <?php } ?>
                        </select>
                    <?php } else { ?>
                        <?php $isURL = (bool) preg_match('/(^|_)url$/', $field['key']); ?>
                        <input
                            type="text"
                            class="form-control<?php echo $error ? ' is-invalid' : ''; ?>"
                            name="<?php echo $escape($name); ?>"
                            id="<?php echo $escape($id); ?>"
                            value="<?php echo $escape($value); ?>"
                            maxlength="255"
                            <?php echo $isRequired ? 'data-validate="required,maxlength[255]" required' : 'data-validate="maxlength[255]"'; ?>
                            <?php echo $isURL ? 'dir="ltr"' : ''; ?>
                        >
                    <?php } ?>

                    <?php if ($error) { ?>
                        <div class="invalid-feedback d-block"><?php echo $escape($error); ?></div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
