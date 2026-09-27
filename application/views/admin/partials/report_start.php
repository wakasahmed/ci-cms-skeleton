<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opens a report page: breadcrumb, header, filters, summary and the table
 * wrapper. In print mode only the summary and table wrapper are rendered (the
 * masthead comes from reportPrintHeader). Closed by report_end.
 */
$reportIsPrint = !empty($is_print);
?>
<?php if (!$reportIsPrint) { ?>
    <?php $this->load->view('admin/partials/breadcrumb', array('items' => array(
        array('label' => 'Reports'),
        array('label' => $report_config['title'], 'active' => TRUE),
    ))); ?>
<?php } ?>

<section class="admin-records-listing report-listing<?php echo $reportIsPrint ? ' report-print-body' : ''; ?>" aria-labelledby="report-title">
    <?php if (!$reportIsPrint) { ?>
        <?php $this->load->view('admin/partials/module_header', array(
            'title' => $report_config['title'],
            'description' => $report_config['description'],
            'id' => 'report-title',
            'actions_view' => 'admin/partials/report_header_actions',
            'actions_data' => array('print_url' => $print_url),
        )); ?>
        <?php $this->load->view('admin/partials/report_filters'); ?>
        <div class="report-alerts" id="report-alerts" role="alert" aria-live="assertive"></div>
    <?php } ?>

    <?php $this->load->view('admin/partials/report_summary'); ?>

    <div
        class="table-responsive pages-table-responsive report-table-wrap"
        <?php echo $reportIsPrint ? '' : 'tabindex="0"'; ?>
        aria-label="<?php echo report_e($report_config['title']); ?> table"
    >
