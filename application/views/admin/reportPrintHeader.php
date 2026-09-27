<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Standalone printer-friendly layout for the reports (no admin chrome). It is
 * read-only and shows the complete filtered result set. Closed by
 * reportPrintFooter.
 */
$autoPrint = (string) $this->input->get('autoprint') === '1';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo report_e($report_config['title'] . ' | ' . $company_name); ?></title>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>css/admin.css?v=<?php echo (int) @filemtime(FCPATH . 'assets/admin/css/admin.css'); ?>">
</head>
<body class="report-print-page report-print-<?php echo report_e($report_config['orientation']); ?>"<?php echo $autoPrint ? ' data-report-autoprint="1"' : ''; ?>>
    <div class="report-print-toolbar report-no-print">
        <button type="button" class="btn btn-primary" data-report-print>
            <i class="bi bi-printer" aria-hidden="true"></i> Print
        </button>
        <a class="btn btn-outline-secondary" href="<?php echo report_e($back_url); ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to report
        </a>
        <span class="report-print-hint">
            Printer-friendly view of the complete filtered report
            (<?php echo report_e(ucfirst($report_config['orientation'])); ?> layout recommended).
        </span>
    </div>

    <header class="report-print-masthead">
        <p class="report-print-company"><?php echo report_e($company_name); ?></p>
        <h1 id="report-title"><?php echo report_e($report_config['title']); ?></h1>
        <p class="report-print-meta">
            Generated <?php echo report_e($generated_at); ?>
            &middot; <?php echo report_number($total_rows); ?> record<?php echo (int) $total_rows === 1 ? '' : 's'; ?>
        </p>
        <p class="report-print-filters">
            <strong>Filters:</strong>
            <?php if ($has_active_filters) { ?>
                <?php foreach ($filter_chips as $chipIndex => $chip) { ?>
                    <?php echo $chipIndex > 0 ? ' &middot; ' : ''; ?><?php echo report_e($chip['label'] . ': ' . $chip['text']); ?>
                <?php } ?>
            <?php } else { ?>
                None (all records)
            <?php } ?>
        </p>
    </header>
