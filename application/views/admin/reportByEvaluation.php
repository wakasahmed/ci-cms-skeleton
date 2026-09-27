<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tour Performance report (route: reports/evaluation). Also rendered in print mode.
 */
$ctx = $report_ctx;
?>
<?php $this->load->view('admin/partials/report_start'); ?>
        <table id="table-reports-evaluation" class="table pages-listing-table admin-records-table report-table">
            <thead>
                <tr>
                    <?php echo report_th('Tour / experience', 'tour_name', $ctx); ?>
                    <?php echo report_th('Total bookings', 'total_bookings', $ctx, 'text-end'); ?>
                    <?php echo report_th('Completed', 'completed', $ctx, 'text-end'); ?>
                    <?php echo report_th('Pending', 'pending', $ctx, 'text-end'); ?>
                    <?php echo report_th('Cancelled', 'cancelled', $ctx, 'text-end'); ?>
                    <?php echo report_th('Refunded', 'refunded', $ctx, 'text-end'); ?>
                    <?php echo report_th('Completion rate', 'completion_rate', $ctx, 'text-end'); ?>
                    <?php echo report_th('Guests', 'guests', $ctx, 'text-end'); ?>
                    <?php echo report_th('Booking value', 'final_total', $ctx, 'text-end'); ?>
                    <?php echo report_th('Paid', 'paid_amount', $ctx, 'text-end'); ?>
                    <?php echo report_th('Refunded amount', 'refunded_amount', $ctx, 'text-end'); ?>
                    <?php echo report_th('Net collected', 'net_collected', $ctx, 'text-end'); ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <tr>
                            <td>
                                <?php echo report_dash($record['tour_name']); ?>
                                <?php if (trim((string) $record['tour_type']) === 'Experience') { ?>
                                    <span class="pages-cell-meta">Experience</span>
                                <?php } ?>
                            </td>
                            <td class="text-end"><?php echo report_number($record['total_bookings']); ?></td>
                            <td class="text-end"><?php echo report_number($record['completed']); ?></td>
                            <td class="text-end"><?php echo report_number($record['pending']); ?></td>
                            <td class="text-end"><?php echo report_number($record['cancelled']); ?></td>
                            <td class="text-end"><?php echo report_number($record['refunded']); ?></td>
                            <td class="text-end"><?php echo report_e($record['completion_rate']); ?>%</td>
                            <td class="text-end"><?php echo report_number($record['guests']); ?></td>
                            <td class="text-end"><?php echo report_money($record['final_total'], $currency); ?></td>
                            <td class="text-end"><?php echo report_money($record['paid_amount'], $currency); ?></td>
                            <td class="text-end"><?php echo report_money($record['refunded_amount'], $currency); ?></td>
                            <td class="text-end"><?php echo report_money($record['net_collected'], $currency); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <?php $this->load->view('admin/partials/report_empty_state', array('columns' => 12)); ?>
                <?php } ?>
            </tbody>
        </table>
<?php $this->load->view('admin/partials/report_end'); ?>
