<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Guide Performance report (route: reports/gevaluation). Also rendered in print mode.
 *
 * "Profile rating" is the rating stored on the guide's profile; it is not an
 * average of customer feedback.
 */
$ctx = $report_ctx;
?>
<?php $this->load->view('admin/partials/report_start'); ?>
        <table id="table-reports-gevaluation" class="table pages-listing-table admin-records-table report-table">
            <thead>
                <tr>
                    <?php echo report_th('Guide', 'guide_name', $ctx); ?>
                    <?php echo report_th('Assigned bookings', 'total_bookings', $ctx, 'text-end'); ?>
                    <?php echo report_th('Completed', 'completed', $ctx, 'text-end'); ?>
                    <?php echo report_th('Pending', 'pending', $ctx, 'text-end'); ?>
                    <?php echo report_th('Cancelled', 'cancelled', $ctx, 'text-end'); ?>
                    <?php echo report_th('Refunded', 'refunded', $ctx, 'text-end'); ?>
                    <?php echo report_th('Completion rate', 'completion_rate', $ctx, 'text-end'); ?>
                    <?php echo report_th('Guests served', 'guests', $ctx, 'text-end'); ?>
                    <?php echo report_th('Paid', 'paid_amount', $ctx, 'text-end'); ?>
                    <?php echo report_th('Net collected', 'net_collected', $ctx, 'text-end'); ?>
                    <?php echo report_th('Profile rating', 'profile_rating', $ctx, 'text-end'); ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php $isUnassigned = empty($record['group_id']); ?>
                        <tr>
                            <td>
                                <?php if ($isUnassigned) { ?>
                                    <em>Unassigned</em>
                                    <span class="pages-cell-meta">Bookings with no guide</span>
                                <?php } else { ?>
                                    <?php echo report_dash($record['guide_name']); ?>
                                <?php } ?>
                            </td>
                            <td class="text-end"><?php echo report_number($record['total_bookings']); ?></td>
                            <td class="text-end"><?php echo report_number($record['completed']); ?></td>
                            <td class="text-end"><?php echo report_number($record['pending']); ?></td>
                            <td class="text-end"><?php echo report_number($record['cancelled']); ?></td>
                            <td class="text-end"><?php echo report_number($record['refunded']); ?></td>
                            <td class="text-end"><?php echo report_e($record['completion_rate']); ?>%</td>
                            <td class="text-end"><?php echo report_number($record['guests']); ?></td>
                            <td class="text-end"><?php echo report_money($record['paid_amount'], $currency); ?></td>
                            <td class="text-end"><?php echo report_money($record['net_collected'], $currency); ?></td>
                            <td class="text-end"><?php echo ($isUnassigned || $record['profile_rating'] === NULL) ? '&mdash;' : report_e($record['profile_rating']) . ' / 5'; ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <?php $this->load->view('admin/partials/report_empty_state', array('columns' => 11)); ?>
                <?php } ?>
            </tbody>
        </table>
<?php $this->load->view('admin/partials/report_end'); ?>
