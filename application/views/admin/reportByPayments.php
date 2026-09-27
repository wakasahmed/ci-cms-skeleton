<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payments report. Also rendered in print mode (no links or controls).
 */
$ctx = $report_ctx;
$isPrint = !empty($is_print);
?>
<?php $this->load->view('admin/partials/report_start'); ?>
        <table
            id="table-reports-payments"
            class="table pages-listing-table admin-records-table report-table report-table-rows"
            <?php echo $isPrint ? '' : 'data-report-commission-endpoint="' . report_e($commission_endpoint) . '"'; ?>
        >
            <thead>
                <tr>
                    <?php echo report_th('Reference', 'reference', $ctx); ?>
                    <?php echo report_th('Customer', 'customer', $ctx); ?>
                    <?php echo report_th('Tour / experience', 'tour', $ctx); ?>
                    <?php echo report_th('Tour date', 'date', $ctx); ?>
                    <?php echo report_th('Status', 'status', $ctx); ?>
                    <?php echo report_th('Final total', 'total', $ctx, 'text-end'); ?>
                    <?php echo report_th('Paid', 'paid', $ctx, 'text-end'); ?>
                    <?php echo report_th('Refunded', 'refunded', $ctx, 'text-end'); ?>
                    <?php echo report_th('Net collected', 'net', $ctx, 'text-end'); ?>
                    <?php echo report_th('Payment method', 'method', $ctx); ?>
                    <?php echo report_th('Payment date', 'payment_date', $ctx); ?>
                    <?php echo report_th('Transaction ID', 'transaction', $ctx); ?>
                    <?php echo report_th('Commission', 'commission', $ctx, 'text-end'); ?>
                    <?php echo report_th('Commission received', 'commission_received', $ctx); ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php
                        $bookId = (int) $record['book_id'];
                        $reference = report_reference($bookId, $record['book_res_code']);
                        $rowCurrency = !empty($record['book_currency']) ? $record['book_currency'] : $currency;
                        $paymentDate = $record['book_payment_date'] ? date(ADMIN_DATETIME_FORMAT, strtotime($record['book_payment_date'])) : '';
                        $tourEnd = FALSE;
                        if (!empty($record['book_date']) && !empty($record['book_slot_end_time'])) {
                            $tourEnd = strtotime($record['book_date'] . ' ' . $record['book_slot_end_time']);
                            if (
                                $tourEnd !== FALSE
                                && !empty($record['book_slot_start_time'])
                                && $record['book_slot_end_time'] <= $record['book_slot_start_time']
                            ) {
                                $tourEnd = strtotime('+1 day', $tourEnd);
                            }
                        }
                        $commissionDisabled = $tourEnd !== FALSE && $tourEnd < time();
                        ?>
                        <tr>
                            <td><?php echo report_booking_link($bookId, $reference, $isPrint); ?></td>
                            <td><?php echo report_booking_link($bookId, $record['book_name'], $isPrint); ?></td>
                            <td><?php echo report_dash($record['tour_name']); ?></td>
                            <td class="report-nowrap"><?php echo report_date($record['book_date']); ?></td>
                            <td>
                                <?php echo report_status_badge($record['book_status']); ?>
                                <span class="pages-cell-meta"><?php echo report_payment_state_badge($record['payment_state']); ?></span>
                            </td>
                            <td class="text-end"><?php echo report_money($record['book_fee'], $rowCurrency); ?></td>
                            <td class="text-end"><?php echo report_money($record['book_paid_amount'], $rowCurrency); ?></td>
                            <td class="text-end"><?php echo report_money($record['book_refund_amount'], $rowCurrency); ?></td>
                            <td class="text-end"><?php echo report_money($record['net_collected'], $rowCurrency); ?></td>
                            <td><?php echo report_dash($record['book_payment_method']); ?></td>
                            <td><?php echo report_dash($paymentDate); ?></td>
                            <td class="report-nowrap-break"><?php echo report_dash($record['book_transaction_id']); ?></td>
                            <td class="text-end"><?php echo report_money($record['book_ref_commission'], $rowCurrency); ?></td>
                            <td>
                                <?php echo report_commission_control(
                                    $bookId,
                                    $reference,
                                    $record['book_ref_commission_received'],
                                    $record['book_status'] === 'Completed',
                                    $isPrint,
                                    'payment',
                                    $commissionDisabled
                                ); ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <?php $this->load->view('admin/partials/report_empty_state', array('columns' => 14)); ?>
                <?php } ?>
            </tbody>
        </table>
<?php $this->load->view('admin/partials/report_end'); ?>
