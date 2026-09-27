<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Summary cards. Totals always cover every record matching the filters, not
 * only the rows on the current page. Values carry data-summary-key so
 * reports.js can refresh them after a commission change.
 */
?>
<section class="report-summary" aria-label="Report summary">
    <dl class="report-summary-grid" data-cards="<?php echo count($summary_cards); ?>">
        <?php foreach ($summary_cards as $cardKey => $card) { ?>
            <div class="report-summary-card<?php echo $card[2] !== '' ? ' report-summary-card-' . report_e($card[2]) : ''; ?>">
                <dt><?php echo report_e($card[0]); ?></dt>
                <dd data-summary-key="<?php echo report_e($cardKey); ?>"><?php echo $card[1]; ?></dd>
            </div>
        <?php } ?>
    </dl>
    <?php if (!empty($multiple_currencies)) { ?>
        <p class="report-summary-note">These records use more than one currency, so money totals combine different currencies.</p>
    <?php } ?>
</section>
