<?php defined('BASEPATH') OR exit('No direct script access allowed');

$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$ratingLabels = array(
    'tour_review_overall_rating' => 'Overall experience',
    'tour_review_activity_rating' => $record['tour_review_tour_type'] === 'Tour'
        ? 'Tour content & itinerary'
        : 'Experience quality',
    'tour_review_guide_rating' => 'Tour guide',
    'tour_review_vehicle_rating' => 'Vehicle comfort & cleanliness',
    'tour_review_driver_rating' => 'Driver service & safety',
    'tour_review_service_rating' => 'Booking & communication',
);
$id = (int) $record['tour_review_id'];
$this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => 'Tour Reviews', 'url' => base_url('manage/tour-reviews')),
        array('label' => 'Review #' . $id, 'active' => true),
    ),
));
$this->load->view('admin/partials/module_header', array(
    'title' => 'Review #' . $id,
    'description' => $record['tour_review_customer_name'] . ' · ' . $record['tour_review_tour_name'],
    'id' => 'tour-review-title',
));
?>

<?php if (!empty($review_message)) { ?>
    <div class="alert alert-<?php echo $review_message['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert"><?php echo $escape($review_message['text']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="tour-review-title">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="<?php echo base_url('manage/tour-reviews'); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Tour Reviews</a>
        <a class="btn btn-outline-primary" href="<?php echo base_url('manage/bookings/control/view/' . (int) $record['tour_review_booking_id']); ?>"><i class="bi bi-journal-check" aria-hidden="true"></i> View Booking</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <section class="card admin-card mb-4"><div class="card-body">
                <h2 class="h4 mb-4">Ratings</h2>
                <div class="row g-4">
                    <?php foreach ($ratingLabels as $field => $label) { ?>
                        <?php if ($record[$field] === null) { continue; } $rating = max(1, min(5, (int) $record[$field])); ?>
                        <div class="col-sm-6"><span class="booking-label d-block mb-1"><?php echo $escape($label); ?></span><span class="admin-star-display fs-5" aria-label="<?php echo $rating; ?> out of 5 stars"><?php for ($star = 1; $star <= 5; $star++) { ?><i class="bi <?php echo $star <= $rating ? 'bi-star-fill' : 'bi-star'; ?>" aria-hidden="true"></i><?php } ?></span><strong class="ms-2"><?php echo $rating; ?>/5</strong></div>
                    <?php } ?>
                </div>
            </div></section>
            <section class="card admin-card"><div class="card-body">
                <h2 class="h4">Customer comments</h2>
                <?php if (trim((string) $record['tour_review_comments']) !== '') { ?><p class="mb-0 mt-3"><?php echo nl2br($escape($record['tour_review_comments'])); ?></p><?php } else { ?><p class="text-muted mb-0 mt-3">No written comments were provided.</p><?php } ?>
            </div></section>
        </div>
        <aside class="col-lg-4">
            <section class="card admin-card mb-4"><div class="card-body">
                <h2 class="h5">Review details</h2>
                <dl class="booking-fields mb-0">
                    <div class="mb-3"><dt class="booking-label">Customer</dt><dd><?php echo $escape($record['tour_review_customer_name']); ?><br><a href="mailto:<?php echo $escape($record['tour_review_customer_email']); ?>"><?php echo $escape($record['tour_review_customer_email']); ?></a></dd></div>
                    <div class="mb-3"><dt class="booking-label">Tour / Experience</dt><dd><?php echo $escape($record['tour_review_tour_name']); ?></dd></div>
                    <div class="mb-3"><dt class="booking-label">Visit date / time</dt><dd><?php echo $record['book_date'] ? date(ADMIN_DATE_FORMAT, strtotime($record['book_date'])) : 'Not specified'; ?> · <?php echo $escape($record['book_slot_name']); ?></dd></div>
                    <?php if (!empty($record['tour_review_guide_name'])) { ?><div class="mb-3"><dt class="booking-label">Tour guide</dt><dd><?php echo $escape($record['tour_review_guide_name']); ?></dd></div><?php } ?>
                    <div class="mb-3"><dt class="booking-label">May publish</dt><dd><?php echo (int) $record['tour_review_public_consent'] === 1 ? 'Yes' : 'No'; ?></dd></div>
                    <div class="mb-3"><dt class="booking-label">Language</dt><dd><?php echo $record['tour_review_locale'] === 'ar' ? 'Arabic' : 'English'; ?></dd></div>
                    <div><dt class="booking-label">Submitted</dt><dd><?php echo date(ADMIN_DATETIME_FORMAT, strtotime($record['tour_review_added'])); ?></dd></div>
                </dl>
            </div></section>
            <section class="card admin-card" hidden><div class="card-body">
                <h2 class="h5">Moderation status</h2>
                <?php echo form_open('manage/tour-reviews/updateStatus/' . $id); ?>
                    <label class="form-label visually-hidden" for="tour_review_status">Review status</label>
                    <select class="form-select select2" name="tour_review_status" id="tour_review_status" data-minimum-results-for-search="-1">
                        <?php foreach (array('New', 'Approved', 'Hidden') as $status) { ?><option value="<?php echo $status; ?>"<?php echo $record['tour_review_status'] === $status ? ' selected' : ''; ?>><?php echo $status; ?></option><?php } ?>
                    </select>
                    <button class="btn btn-primary w-100 mt-3" type="submit">Update Status</button>
                <?php echo form_close(); ?>
            </div></section>
        </aside>
    </div>
</section>
