<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * One labelled input on a public form (account pages), with its hint and the
 * server-side error in the markup js/form.js also uses (.ci-field-error).
 *
 * $field keys:
 *   id, name, label   required
 *   type              input type (default 'text')
 *   value             current value (never pass a password back)
 *   required          bool
 *   autocomplete      autocomplete token
 *   placeholder, maxlength, minlength
 *   hint              help text under the label
 *   error             server-side error message
 *   match             id of the field this one must equal (password confirmation)
 */
$field = array_merge(array(
    'type' => 'text',
    'value' => '',
    'required' => FALSE,
    'autocomplete' => '',
    'placeholder' => '',
    'maxlength' => '',
    'minlength' => '',
    'hint' => '',
    'error' => '',
    'match' => '',
), $field);

$describedBy = trim(
    ($field['hint'] !== '' ? $field['id'].'-hint' : '')
    .($field['error'] !== '' ? ' '.$field['id'].'-error' : '')
);
?>
<div>
    <label class="block text-sm font-semibold text-foreground" for="<?php echo html_escape($field['id']); ?>">
        <?php echo html_escape($field['label']); ?><?php if ($field['required']) { ?><span class="ml-1 text-primary" aria-hidden="true">*</span><?php } ?>
    </label>
    <?php if ($field['hint'] !== '') { ?>
        <p class="mt-1.5 text-sm text-muted-foreground" id="<?php echo html_escape($field['id']); ?>-hint"><?php echo html_escape($field['hint']); ?></p>
    <?php } ?>
    <div class="mt-2">
        <input
            class="<?php echo html_escape(frontend_input_class($field['error'] !== '')); ?> h-12"
            id="<?php echo html_escape($field['id']); ?>"
            name="<?php echo html_escape($field['name']); ?>"
            type="<?php echo html_escape($field['type']); ?>"
            value="<?php echo html_escape($field['value']); ?>"
            <?php echo $field['required'] ? 'required' : ''; ?>
            <?php echo $field['autocomplete'] !== '' ? 'autocomplete="'.html_escape($field['autocomplete']).'"' : ''; ?>
            <?php echo $field['placeholder'] !== '' ? 'placeholder="'.html_escape($field['placeholder']).'"' : ''; ?>
            <?php echo $field['maxlength'] !== '' ? 'maxlength="'.(int) $field['maxlength'].'"' : ''; ?>
            <?php echo $field['minlength'] !== '' ? 'minlength="'.(int) $field['minlength'].'"' : ''; ?>
            <?php echo $field['match'] !== '' ? 'data-match="#'.html_escape($field['match']).'"' : ''; ?>
            <?php echo $field['error'] !== '' ? 'aria-invalid="true"' : ''; ?>
            <?php echo $describedBy !== '' ? 'aria-describedby="'.html_escape($describedBy).'"' : ''; ?>
        >
        <?php if ($field['error'] !== '') { ?>
            <p class="ci-field-error mt-1 text-sm text-destructive" id="<?php echo html_escape($field['id']); ?>-error"><?php echo html_escape($field['error']); ?></p>
        <?php } ?>
    </div>
</div>
