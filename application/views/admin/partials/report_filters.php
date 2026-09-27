<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared filter toolbar for every report: primary filters, a collapsible
 * "More filters" section, Apply / Clear all actions and the active-filter chips.
 * Filters are plain GET parameters; empty fields are removed by reports.js
 * before submit so URLs stay short and readable.
 */
$moreControls = $filter_controls['more'];
$moreActiveCount = 0;
foreach ($moreControls as $moreControl) {
    if ($moreControl['value'] !== '') {
        $moreActiveCount++;
    }
}
?>
<div class="pages-listing-toolbar report-toolbar">
    <form
        class="pages-filter-form report-filter-form"
        id="report-filter-form"
        method="get"
        action="<?php echo report_e($report_url); ?>"
        role="search"
        aria-label="Filter <?php echo report_e($report_config['title']); ?>"
        data-report-filter
    >
        <?php foreach ($form_hidden as $hiddenName => $hiddenValue) { ?>
            <input type="hidden" name="<?php echo report_e($hiddenName); ?>" value="<?php echo report_e($hiddenValue); ?>">
        <?php } ?>

        <div class="report-filter-grid">
            <?php foreach ($filter_controls['primary'] as $control) { ?>
                <?php $this->load->view('admin/partials/report_filter_field', array('control' => $control)); ?>
            <?php } ?>
        </div>

        <?php if (!empty($moreControls)) { ?>
            <div class="collapse report-filter-more<?php echo $moreActiveCount > 0 ? ' show' : ''; ?>" id="report-more-filters">
                <div class="report-filter-grid">
                    <?php foreach ($moreControls as $control) { ?>
                        <?php $this->load->view('admin/partials/report_filter_field', array('control' => $control)); ?>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="report-filter-actions">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-funnel" aria-hidden="true"></i> Apply filters
            </button>
            <?php if (!empty($moreControls)) { ?>
                <button
                    class="btn btn-outline-secondary"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#report-more-filters"
                    aria-expanded="<?php echo $moreActiveCount > 0 ? 'true' : 'false'; ?>"
                    aria-controls="report-more-filters"
                >
                    <i class="bi bi-sliders" aria-hidden="true"></i> More filters
                    <?php if ($moreActiveCount > 0) { ?>
                        <span class="badge text-bg-secondary"><?php echo (int) $moreActiveCount; ?></span>
                    <?php } ?>
                </button>
            <?php } ?>
            <?php if ($has_active_filters) { ?>
                <a class="btn btn-link pages-clear-filters" href="<?php echo report_e($clear_url); ?>">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Clear all
                </a>
            <?php } ?>
        </div>
    </form>
</div>

<?php if ($has_active_filters) { ?>
    <div class="report-active-filters" role="group" aria-label="Active filters">
        <span class="report-active-label">Active filters</span>
        <ul class="report-chip-list">
            <?php foreach ($filter_chips as $chip) { ?>
                <li class="report-chip">
                    <span><strong><?php echo report_e($chip['label']); ?>:</strong> <?php echo report_e($chip['text']); ?></span>
                    <a
                        class="report-chip-remove"
                        href="<?php echo report_e($chip['remove_url']); ?>"
                        aria-label="Remove filter <?php echo report_e($chip['label'] . ': ' . $chip['text']); ?>"
                    ><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>
