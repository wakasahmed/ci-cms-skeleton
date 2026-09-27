<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tours & Time Slots report. Slot names and times come from the booking
 * snapshot, so editing a slot later does not change past bookings. Also
 * rendered in print mode.
 */
$ctx = $report_ctx;
?>
<?php $this->load->view('admin/partials/report_start'); ?>
        <table id="table-reports-timeslots" class="table pages-listing-table admin-records-table report-table">
            <thead>
                <tr>
                    <?php echo report_th('Tour / experience', 'tour_name', $ctx); ?>
                    <?php echo report_th('Slot', 'slot_name', $ctx); ?>
                    <?php echo report_th('Slot time', 'slot_time', $ctx); ?>
                    <?php echo report_th('Total bookings', 'total_bookings', $ctx, 'text-end'); ?>
                    <?php echo report_th('Completed', 'completed', $ctx, 'text-end'); ?>
                    <?php echo report_th('Pending', 'pending', $ctx, 'text-end'); ?>
                    <?php echo report_th('Cancelled', 'cancelled', $ctx, 'text-end'); ?>
                    <?php echo report_th('Refunded', 'refunded', $ctx, 'text-end'); ?>
                    <?php echo report_th('Guests', 'guests', $ctx, 'text-end'); ?>
                    <?php echo report_th('Completion rate', 'completion_rate', $ctx, 'text-end'); ?>
                    <?php echo report_th('Net collected', 'net_collected', $ctx, 'text-end'); ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php
                        $slotName = trim((string) $record['slot_name']);
                        $slotRange = report_time_range($record['slot_start'], $record['slot_end']);
                        $slotHours = $record['slot_hours'] !== NULL ? (int) $record['slot_hours'] . ' h' : '';
                        ?>
                        <tr>
                            <td><?php echo report_dash($record['tour_name']); ?></td>
                            <td>
                                <?php if ($slotName === '') { ?>
                                    <em>No slot selected yet</em>
                                <?php } else { ?>
                                    <?php echo report_e($slotName); ?>
                                <?php } ?>
                            </td>
                            <td>
                                <?php echo report_dash($slotRange); ?>
                                <?php if ($slotHours !== '') { ?>
                                    <span class="pages-cell-meta"><?php echo report_e($slotHours); ?></span>
                                <?php } ?>
                            </td>
                            <td class="text-end"><?php echo report_number($record['total_bookings']); ?></td>
                            <td class="text-end"><?php echo report_number($record['completed']); ?></td>
                            <td class="text-end"><?php echo report_number($record['pending']); ?></td>
                            <td class="text-end"><?php echo report_number($record['cancelled']); ?></td>
                            <td class="text-end"><?php echo report_number($record['refunded']); ?></td>
                            <td class="text-end"><?php echo report_number($record['guests']); ?></td>
                            <td class="text-end"><?php echo report_e($record['completion_rate']); ?>%</td>
                            <td class="text-end"><?php echo report_money($record['net_collected'], $currency); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <?php $this->load->view('admin/partials/report_empty_state', array('columns' => 11)); ?>
                <?php } ?>
            </tbody>
        </table>
<?php $this->load->view('admin/partials/report_end'); ?>
