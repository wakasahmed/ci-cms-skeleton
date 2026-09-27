<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Closes the markup opened by report_start and adds the pagination footer
 * (screen mode only).
 */
$reportIsPrint = !empty($is_print);
?>
    </div>

    <?php if (!$reportIsPrint) { ?>
        <?php $this->load->view('admin/partials/table_listing_footer', array(
            'total_rows' => $total_rows,
            'per_page' => $per_page,
            'selected_per_page' => $per_page,
            'page_offset' => $page_numb,
            'pagination' => $paginate,
            'pagination_label' => $report_config['title'] . ' pagination',
            'per_page_id' => 'report_per_page',
        )); ?>
    <?php } ?>
</section>
