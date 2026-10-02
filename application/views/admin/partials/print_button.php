<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Module header action that prints the page (handled by [data-print-page] in admin.js).
$printButtonLabel = isset($print_label) ? (string) $print_label : 'Print';
?>
<button type="button" class="btn btn-outline-secondary" data-print-page>
    <i class="bi bi-printer" aria-hidden="true"></i> <?php echo htmlspecialchars($printButtonLabel, ENT_QUOTES, 'UTF-8'); ?>
</button>
