<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bookings report. Also rendered in print mode (no links or controls).
 */
$ctx = $report_ctx;
$isPrint = !empty($is_print);
?>
<?php $this->load->view('admin/partials/report_start'); ?>
        <table id="table-reports-bookings" class="table pages-listing-table admin-records-table report-table report-table-rows">
            <thead>
                <tr>
                    <?php echo report_th('Reference', 'reference', $ctx); ?>
                    <?php echo report_th('Customer', 'customer', $ctx); ?>
                    <?php echo report_th('Tour / experience', 'tour', $ctx); ?>
                    <?php echo report_th('Tour date & slot', 'date', $ctx); ?>
                    <?php echo report_th('Guide', 'guide', $ctx); ?>
                    <?php echo report_th('Language', 'language', $ctx); ?>
                    <?php echo report_th('Guests', 'guests', $ctx, 'text-end'); ?>
                    <?php echo report_th('Country', 'country', $ctx); ?>
                    <?php echo report_th('Booking progress', 'progress', $ctx); ?>
                    <?php echo report_th('Status', 'status', $ctx); ?>
                    <?php echo report_th('Final total', 'total', $ctx, 'text-end'); ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php
                        $bookId = (int) $record['book_id'];
                        $rowCurrency = !empty($record['book_currency']) ? $record['book_currency'] : $currency;
                        $slotText = trim((string) $record['book_slot_name']);
                        $slotTime = report_time_range($record['book_slot_start_time'], $record['book_slot_end_time']);
                        ?>
                        <tr>
                            <td><?php echo report_booking_link($bookId, report_reference($bookId, $record['book_res_code']), $isPrint); ?></td>
                            <td><?php echo report_booking_link($bookId, $record['book_name'], $isPrint); ?></td>
                            <td><?php echo report_dash($record['tour_name']); ?></td>
                            <td>
                                <?php echo report_date($record['book_date']); ?>
                                <?php if ($slotText !== '') { ?>
                                    <span class="pages-cell-meta"><?php echo report_e($slotText . ($slotTime !== '' ? ' (' . $slotTime . ')' : '')); ?></span>
                                <?php } ?>
                            </td>
                            <td><?php echo report_dash($record['guide_name']); ?></td>
                            <td><?php echo report_dash($record['lang_name']); ?></td>
                            <td class="text-end"><?php echo $record['book_guests'] !== NULL ? report_number($record['book_guests']) : '&mdash;'; ?></td>
                            <td><?php echo report_dash($record['country_name']); ?></td>
                            <td><?php echo report_progress($record['steps_completed'], $record['total_steps'], $record['book_status']); ?></td>
                            <td><?php echo report_status_badge($record['book_status']); ?></td>
                            <td class="text-end"><?php echo report_money($record['book_fee'], $rowCurrency); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <?php $this->load->view('admin/partials/report_empty_state', array('columns' => 11)); ?>
                <?php } ?>
            </tbody>
        </table>
<?php $this->load->view('admin/partials/report_end'); ?>
