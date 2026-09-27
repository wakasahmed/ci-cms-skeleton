<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discounts & Referrals report. Also rendered in print mode (no links or controls).
 */
$ctx = $report_ctx;
$isPrint = !empty($is_print);
?>
<?php $this->load->view('admin/partials/report_start'); ?>
        <table
            id="table-reports-referrals"
            class="table pages-listing-table admin-records-table report-table report-table-rows"
            <?php echo $isPrint ? '' : 'data-report-commission-endpoint="' . report_e($commission_endpoint) . '"'; ?>
        >
            <thead>
                <tr>
                    <?php echo report_th('Reference', 'reference', $ctx); ?>
                    <?php echo report_th('Customer', 'customer', $ctx); ?>
                    <?php echo report_th('Referral', 'referral', $ctx); ?>
                    <?php echo report_th('Promo code', 'promo', $ctx); ?>
                    <?php echo report_th('Promotion name', 'promotion', $ctx); ?>
                    <?php echo report_th('Tour / experience', 'tour', $ctx); ?>
                    <?php echo report_th('Tour date', 'date', $ctx); ?>
                    <?php echo report_th('Original total', 'original', $ctx, 'text-end'); ?>
                    <?php echo report_th('Discount', 'discount', $ctx, 'text-end'); ?>
                    <?php echo report_th('Final total', 'total', $ctx, 'text-end'); ?>
                    <?php echo report_th('Referral commission', 'commission', $ctx, 'text-end'); ?>
                    <?php echo report_th('Commission paid', 'paid', $ctx); ?>
                    <?php echo report_th('Status', 'status', $ctx); ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php
                        $bookId = (int) $record['book_id'];
                        $reference = report_reference($bookId, $record['book_res_code']);
                        $rowCurrency = !empty($record['book_currency']) ? $record['book_currency'] : $currency;
                        $discountRule = '';
                        if ($record['book_discount_type'] === 'Percentage' && $record['book_discount_value'] !== NULL) {
                            $discountRule = (int) $record['book_discount_value'] . '%';
                        }
                        ?>
                        <tr>
                            <td><?php echo report_booking_link($bookId, $reference, $isPrint); ?></td>
                            <td><?php echo report_booking_link($bookId, $record['book_name'], $isPrint); ?></td>
                            <td><?php echo report_dash($record['ref_name']); ?></td>
                            <td><?php echo report_dash($record['book_promo_code']); ?></td>
                            <td><?php echo report_dash($record['discount_name']); ?></td>
                            <td><?php echo report_dash($record['tour_name']); ?></td>
                            <td class="report-nowrap"><?php echo report_date($record['book_date']); ?></td>
                            <td class="text-end"><?php echo report_money($record['book_original_total'], $rowCurrency); ?></td>
                            <td class="text-end">
                                <?php echo report_money($record['book_discount_amount'], $rowCurrency); ?>
                                <?php if ($discountRule !== '') { ?>
                                    <span class="pages-cell-meta"><?php echo report_e($discountRule); ?> off</span>
                                <?php } ?>
                            </td>
                            <td class="text-end"><?php echo report_money($record['book_fee'], $rowCurrency); ?></td>
                            <td class="text-end"><?php echo report_money($record['book_ref_commission'], $rowCurrency); ?></td>
                            <td>
                                <?php echo report_commission_control(
                                    $bookId,
                                    $reference,
                                    $record['book_ref_commission_received'],
                                    $record['book_status'] === 'Completed',
                                    $isPrint,
                                    'referral'
                                ); ?>
                            </td>
                            <td><?php echo report_status_badge($record['book_status']); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <?php $this->load->view('admin/partials/report_empty_state', array('columns' => 13)); ?>
                <?php } ?>
            </tbody>
        </table>
<?php $this->load->view('admin/partials/report_end'); ?>
