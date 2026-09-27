<?php defined('BASEPATH') OR exit('No direct script access allowed');

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$routeValue = function ($value) {
    return rawurlencode($value === '' ? '-' : $value);
};
$statusClasses = array(
    'New' => 'status-warning',
    'Approved' => 'status-enabled',
    'Hidden' => 'status-disabled',
);
$sortUrl = function ($column) use ($status, $keywords, $order) {
    return base_url(
        'manage/tour-reviews/index/' . $column . '/' . $order . '/'
        . rawurlencode($status) . '/' . rawurlencode($keywords)
    );
};
$this->load->view('admin/partials/breadcrumb', array(
    'items' => array(array('label' => 'Tour Reviews', 'active' => true)),
));
$this->load->view('admin/partials/module_header', array(
    'title' => 'Tour Reviews',
    'description' => 'Customer ratings submitted after completed tours and experiences.',
    'id' => 'tour-reviews-title',
));
?>

<section class="admin-records-listing" aria-labelledby="tour-reviews-title">
    <?php if (!$schema_ready) { ?>
        <div class="alert alert-warning">Apply <code>db/tour-reviews-migration.sql</code> to enable this module.</div>
    <?php } ?>
    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="tour-reviews-filter-form" role="search">
            <label class="visually-hidden" for="search_keywords">Search tour reviews</label>
            <div class="pages-search-control"><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? $escape($keywords) : ''; ?>" id="search_keywords" placeholder="Search customer, email, tour or comments..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control" hidden><label class="visually-hidden" for="review_status_filter">Filter by status</label>
            <select class="form-select select2" id="review_status_filter" data-minimum-results-for-search="-1">
                <option value="-"<?php echo $status === '-' ? ' selected' : ''; ?>>All statuses</option>
                <?php foreach (array('New', 'Approved', 'Hidden') as $option) { ?><option value="<?php echo $option; ?>"<?php echo $status === $option ? ' selected' : ''; ?>><?php echo $option; ?></option><?php } ?>
            </select>
            </div>
        </form>
    </div>

    <div class="card admin-card">
        <div class="table-responsive">
            <table class="table pages-table align-middle mb-0">
                <thead><tr>
                    <th scope="col"><a href="<?php echo $sortUrl('tour_review_id'); ?>">ID</a></th>
                    <th scope="col"><a href="<?php echo $sortUrl('tour_review_customer_name'); ?>">Customer</a></th>
                    <th scope="col"><a href="<?php echo $sortUrl('tour_review_tour_name'); ?>">Tour / Experience</a></th>
                    <th scope="col"><a href="<?php echo $sortUrl('tour_review_overall_rating'); ?>">Overall rating</a></th>
                    <th scope="col" hidden><a href="<?php echo $sortUrl('tour_review_status'); ?>">Status</a></th>
                    <th scope="col" class="d-none d-md-table-cell"><a href="<?php echo $sortUrl('tour_review_added'); ?>">Submitted</a></th>
                    <th scope="col">Actions</th>
                </tr></thead>
                <tbody>
                <?php if ($records) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php $rating = max(1, min(5, (int) $record['tour_review_overall_rating'])); ?>
                        <tr>
                            <td>#<?php echo (int) $record['tour_review_id']; ?></td>
                            <td><a class="pages-name-link" href="<?php echo base_url('manage/tour-reviews/control/view/' . (int) $record['tour_review_id']); ?>"><?php echo $escape($record['tour_review_customer_name']); ?></a><span class="pages-cell-meta"><?php echo $escape($record['tour_review_customer_email']); ?></span></td>
                            <td><?php echo $escape($record['tour_review_tour_name']); ?><span class="pages-cell-meta"><?php echo $escape($record['tour_review_tour_type']); ?> · Booking #<?php echo (int) $record['tour_review_booking_id']; ?></span></td>
                            <td><span class="admin-star-display" aria-label="<?php echo $rating; ?> out of 5 stars"><?php for ($star = 1; $star <= 5; $star++) { ?><i class="bi <?php echo $star <= $rating ? 'bi-star-fill' : 'bi-star'; ?>" aria-hidden="true"></i><?php } ?></span></td>
                            <td hidden><span class="pages-status-static status-badge <?php echo $statusClasses[$record['tour_review_status']]; ?>"><?php echo $escape($record['tour_review_status']); ?></span></td>
                            <td class="d-none d-md-table-cell"><?php echo date(ADMIN_DATETIME_FORMAT, strtotime($record['tour_review_added'])); ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?php echo base_url('manage/tour-reviews/control/view/' . (int) $record['tour_review_id']); ?>"><i class="bi bi-eye" aria-hidden="true"></i> View</a></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td class="pages-empty-state" colspan="7"><strong><?php echo $keywords !== '-' || $status !== '-' ? 'No reviews match your filters.' : 'No tour reviews yet.'; ?></strong><span>Reviews will appear after eligible customers submit them.</span></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php $this->load->view('admin/partials/table_listing_footer', array(
        'total_rows' => $total_rows,
        'per_page' => $per_page,
        'page_offset' => $page_numb,
        'pagination' => $paginate,
        'pagination_label' => 'Tour reviews pagination',
    )); ?>
</section>
<script>
jQuery(function ($) {
    var $form = $('#tour-reviews-filter-form');
    var $search = $('#search_keywords');
    var $status = $('#review_status_filter');
    var filterUrl = <?php echo json_encode(base_url('manage/tour-reviews/index/' . $sortby . '/' . ($order === 'ASC' ? 'DESC' : 'ASC'))); ?>;

    function applyFilters() {
        var keyword = $.trim($search.val());
        var encoded = keyword === '' ? '-' : encodeURIComponent(keyword).replace(/%20/g, '+');
        window.location = filterUrl + '/' + $status.val() + '/' + encoded;
    }

    $form.on('submit', function (event) {
        event.preventDefault();
        applyFilters();
    });
    $status.on('change', applyFilters);
});
</script>
