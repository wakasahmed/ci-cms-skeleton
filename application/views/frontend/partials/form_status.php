<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * The result of the last form submission, above the form (as on the
 * contact page). js/form.js moves focus to it so screen readers announce it.
 *
 * $message   text to show ('' shows nothing)
 * $success   TRUE for a confirmation, FALSE for a problem
 */
?>
<?php if ($message !== '') { ?>
    <div
        role="<?php echo $success ? 'status' : 'alert'; ?>"
        tabindex="-1"
        data-form-status
        class="mb-6 flex items-start gap-3 rounded-lg px-5 py-4 <?php echo $success ? 'bg-petal text-foreground' : 'bg-destructive/10 text-destructive'; ?>"
    >
        <i class="<?php echo $success ? 'fa-solid fa-circle-check text-primary' : 'fa-solid fa-circle-exclamation'; ?> mt-1 size-4 shrink-0" aria-hidden="true"></i>
        <p><?php echo html_escape($message); ?></p>
    </div>
<?php } ?>
