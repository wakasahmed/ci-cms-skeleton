<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
};
$value = function ($field, $fallback = "Not specified") use ($record) {
    return isset($record[$field]) &&
        $record[$field] !== "" &&
        $record[$field] !== null
        ? $record[$field]
        : $fallback;
};
$formatDateTime = function ($raw, $fallback = "Not specified") {
    return !empty($raw) ? date(ADMIN_DATETIME_FORMAT, strtotime($raw)) : $fallback;
};
$dateTime = function ($field, $fallback = "Not specified") use ($record, $formatDateTime) {
    return $formatDateTime(isset($record[$field]) ? $record[$field] : "", $fallback);
};
$money = function ($amount) use ($record, $escape) {
    return $escape(
        !empty($record["book_currency"]) ? $record["book_currency"] : "SAR",
    ) .
        " " .
        number_format((int) $amount);
};
// Google Maps link for a pickup chosen from the frontend's place suggestions.
// Empty for free-text pickups and for bookings made before the place columns existed.
$mapsUrl = "";
if (
    isset($record["book_latitude"], $record["book_longitude"]) &&
    is_numeric($record["book_latitude"]) &&
    is_numeric($record["book_longitude"])
) {
    $mapsUrl =
        "https://www.google.com/maps/search/?api=1&query=" .
        rawurlencode((float) $record["book_latitude"] . "," . (float) $record["book_longitude"]);
    if (!empty($record["book_place_id"])) {
        $mapsUrl .= "&query_place_id=" . rawurlencode($record["book_place_id"]);
    }
}
$id = (int) $record["book_id"];
$paid =(int) $value("book_paid_amount", 0);
$refunded = (int) $value("book_refund_amount", 0);
$remainingRefund = max(0, $paid - $refunded);
$moyasarPayment = isset($moyasar_payment) && is_array($moyasar_payment)
    ? $moyasar_payment
    : null;
$total = (int) $record["book_fee"];
// Experiences are not led by a tour guide, so all guide options are hidden for them.
$showGuide = $record["tour_type"] !== "Experience";
$editableGuide = $showGuide && in_array(
    $record["book_status"],
    ["Pending", "Completed"],
    true,
);
$statusBadgeClass = "status-warning";
if ($record["book_status"] === "Completed") {
    $statusBadgeClass = "status-enabled";
} elseif ($record["book_status"] === "Cancelled") {
    $statusBadgeClass = "status-disabled";
} elseif ($record["book_status"] === "Refunded") {
    $statusBadgeClass = "status-published";
}
$fields = [
    "Customer" => [
        "Email" => "book_email",
        "Phone" => "book_phone",
        "People" => "book_guests",
        "Country" => "book_country_name",
        "Pickup / address" => "book_address",
        "Customer comments" => "book_notes",
    ],
    "Tour details" => [
        "Tour / Experience" => "book_tour_name",
        "Vehicle" => "book_vehicle_name",
        "Duration (hours)" => "book_slot_hours",
        "Tour time" => "book_slot_name",
        "Start time" => "book_slot_start_time",
        "End time" => "book_slot_end_time",
        "Preferred language" => "book_lang_name",
        "Website language" => "book_lang",
    ],
    "Payment details" => [
        "Payment method" => "book_payment_method",
        "Payer email" => "book_payer_email",
        "Transaction ID" => "book_transaction_id",
        "Payment date" => "book_payment_date",
        "Commission received" => "book_ref_commission_received",
    ],
];
// The preferred language only selects a tour guide's language, so it does not apply to Experiences.
if (!$showGuide) {
    unset($fields["Tour details"]["Preferred language"]);
}
$icons = [
    "Customer" => "people",
    "Tour details" => "calendar3",
    "Payment details" => "credit-card",
];
$this->load->view("admin/partials/breadcrumb", [
    "items" => [
        ["label" => "Bookings", "url" => base_url("manage/bookings")],
        ["label" => "Booking #" . $id, "active" => true],
    ],
]);
// $this->load->view("admin/partials/module_header", [
//     "title" => "Booking #" . $id,
//     "description" => $record["book_name"] . " - " . $record["book_tour_name"],
// ]);
?>
<?php if (!empty($booking_message)) { ?>
    <div class="alert alert-<?php echo $booking_message["success"]
                                ? "success"
                                : "danger"; ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-<?php echo $booking_message["success"]
                            ? "check-circle-fill"
                            : "exclamation-triangle-fill"; ?>" aria-hidden="true"></i>
        <div><?php echo $escape($booking_message["text"]); ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
    </div>
<?php } ?>
<div class="booking-details">
    <!-- <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="<?php echo base_url(
                                                        "manage/bookings",
                                                    ); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Bookings</a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editBooking"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Booking</button>
    </div> -->
    <section class="card admin-card booking-summary mb-4" aria-labelledby="booking-title">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h2 id="booking-title" class="h4">Booking #<?php echo $id; ?> <span class="pages-status-static status-badge <?php echo $statusBadgeClass; ?> fs-6"><?php echo $escape(
                                                                                                                                                                            $record["book_status"],
                                                                                                                                                                        ); ?></span></h2>
                    <p class="text-muted mb-2">
                        Steps completed: <?php echo (int) $record['steps_completed']; ?> out of <?php echo (int) $record['total_steps']; ?>
                    </p>
                    <p class="text-muted mb-0"><?php echo $escape(
                                                    $record["book_tour_name"],
                                                ); ?> &middot; Created: <?php echo $escape(
                                                $dateTime("book_added"),
                                            ); ?> &middot; <?php echo $escape($value("book_guests", "-")); ?> people</p>
                </div>
                <!-- <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNote" <?php echo !$notes_ready
                                                                                                            ? "disabled"
                                                                                                            : ""; ?>>+ Add Note</button> -->
            </div>
        </div>
        <div class="booking-summary-grid">
            <?php foreach (
                [
                    "Tour date" => $record["book_date"]
                        ? date(
                            ADMIN_DATE_FORMAT,
                            strtotime($record["book_date"]),
                        )
                        : "Not specified",
                    "Duration" => $value("book_slot_name"),
                    "Vehicle" => $value("book_vehicle_name"),
                    "Total" => strip_tags($money($total)),
                ]
                as $label => $text
            ) { ?>
                <div><span class="booking-label"><?php echo $escape(
                                                        $label,
                                                    ); ?></span><strong><?php echo $escape($text); ?></strong></div>
            <?php } ?>
        </div>
    </section>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php foreach ($fields as $title => $sectionFields) { ?>
                <section class="card admin-card mb-4" aria-labelledby="section-<?php echo $escape(
                                                                                    str_replace(" ", "-", strtolower($title)),
                                                                                ); ?>">
                    <div class="card-body">
                        <h3 class="booking-section-title" id="section-<?php echo $escape(
                                                                            str_replace(" ", "-", strtolower($title)),
                                                                        ); ?>"><span class="contact-request-detail-icon"><i class="bi bi-<?php echo $icons[$title]; ?>" aria-hidden="true"></i></span><?php echo $escape($title); ?></h3>
                        <?php if ($title === "Customer") { ?>
                            <div class="booking-person mb-4"><span class="booking-avatar" aria-hidden="true"><?php echo $escape(
                                                                                                                    strtoupper(substr($record["book_name"], 0, 1)),
                                                                                                                ); ?></span>
                                <div><strong class="fs-5"><?php echo $escape(
                                                                $record["book_name"],
                                                            ); ?></strong>
                                    <div class="text-muted">Customer &middot; Booking ID #<?php echo $id; ?></div>
                                </div>
                            </div>
                        <?php } ?>
                        <dl class="row mb-0 booking-fields">
                            <?php foreach (
                                $sectionFields
                                as $label => $field
                            ) {
                                $fieldValue =
                                    $field === "book_payment_date"
                                    ? $dateTime($field)
                                    : $value($field);
                            ?>
                                <div class="col-sm-6 mb-3">
                                    <dt class="booking-label"><?php echo $escape(
                                                                    $label,
                                                                ); ?></dt>
                                    <dd class="mb-0"><?php echo nl2br(
                                                            $escape($fieldValue),
                                                        ); ?>
                                        <?php if ($field === "book_address" && $mapsUrl !== "") { ?>
                                            <?php if (!empty($record["book_complete_address"])) { ?>
                                                <div class="text-muted small mt-1"><?php echo $escape($record["book_complete_address"]); ?></div>
                                            <?php } ?>
                                            <div class="d-flex align-items-start gap-2 mt-1">
                                                <a href="<?php echo $escape($mapsUrl); ?>" target="_blank" rel="noopener noreferrer" class="small text-break">
                                                    <?php echo $escape($mapsUrl); ?>
                                                </a>
                                                <a class="admin-action-icon font16" href="<?php echo $escape($mapsUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="Open pickup location in Google Maps in a new window" title="Open in Google Maps" data-bs-toggle="tooltip"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                                                <button type="button" class="admin-action-icon font16" data-copy-value="<?php echo $escape($mapsUrl); ?>" data-copy-message="Map link copied to clipboard." aria-label="Copy Google Maps link" title="Copy Google Maps link" data-bs-toggle="tooltip"><i class="bi bi-copy" aria-hidden="true"></i></button>
                                            </div>
                                        <?php } ?>
                                    </dd>
                                </div>
                            <?php } ?>
                            <?php if ($title === "Payment details") { ?>
                                <div class="col-sm-6 mb-3">
                                    <dt class="booking-label">Total paid</dt>
                                    <dd><?php echo $money(
                                            $paid,
                                        ); ?></dd>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <dt class="booking-label">Commission</dt>
                                    <dd><?php echo $money(
                                            $record["book_ref_commission"],
                                    ); ?></dd>
                                </div>
                                <?php if ($moyasarPayment) { ?>
                                    <div class="col-sm-6 mb-3">
                                        <dt class="booking-label">Moyasar status</dt>
                                        <dd class="mb-0"><?php echo $escape(ucfirst($moyasarPayment['payment_status'])); ?></dd>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <dt class="booking-label">Card network</dt>
                                        <dd class="mb-0"><?php echo $escape(
                                            $moyasarPayment['payment_network']
                                                ? strtoupper($moyasarPayment['payment_network'])
                                                : 'Not specified'
                                        ); ?></dd>
                                    </div>
                                    <?php if (!empty($moyasarPayment['masked_number'])) { ?>
                                        <div class="col-sm-6 mb-3">
                                            <dt class="booking-label">Masked card</dt>
                                            <dd class="mb-0" dir="ltr"><?php echo $escape($moyasarPayment['masked_number']); ?></dd>
                                        </div>
                                    <?php } ?>
                                    <div class="col-sm-6 mb-3">
                                        <dt class="booking-label">Last verified</dt>
                                        <dd class="mb-0"><?php echo $escape($formatDateTime($moyasarPayment['last_verified_at'])); ?></dd>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </dl>
                    </div>
                </section>
            <?php } ?>
            <?php if ($refunded > 0) { ?>
                <section class="card admin-card mb-4" aria-labelledby="refund-title">
                    <div class="card-body">
                        <h3 id="refund-title" class="booking-section-title"><span class="contact-request-detail-icon"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></span>Refund details</h3>
                        <dl class="row mb-0 booking-fields">
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Refund status</dt>
                                <dd class="mb-0"><?php echo $escape(
                                                        $refunded >= $paid ? "Fully refunded" : "Partially refunded",
                                                    ); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Refund method</dt>
                                <dd class="mb-0"><?php echo $escape($moyasarPayment ? "Moyasar" : "Offline"); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Amount refunded</dt>
                                <dd class="mb-0"><?php echo $money($refunded); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Refunded on</dt>
                                <dd class="mb-0"><?php echo $escape($dateTime("book_refunded_at")); ?></dd>
                            </div>
                            <div class="col-sm-12 mb-3">
                                <dt class="booking-label">Reason / internal note</dt>
                                <dd class="mb-0"><?php echo nl2br(
                                                        $escape($value("book_refund_reply", "No reply recorded.")),
                                                    ); ?></dd>
                            </div>
                        </dl>
                        <?php if (!empty($payment_refunds)) { ?>
                            <hr>
                            <h4 id="refund-history-title" class="booking-label mb-3">Refund history</h4>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" aria-labelledby="refund-history-title">
                                    <thead>
                                        <tr>
                                            <th scope="col">Requested</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Provider</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payment_refunds as $refund) { ?>
                                            <tr>
                                                <td><?php echo $escape($formatDateTime($refund["requested_at"])); ?></td>
                                                <td><?php echo $money((int) ($refund["amount_minor"] / (10 ** MOYASAR_CURRENCY_EXPONENT))); ?></td>
                                                <td><?php echo $escape(ucfirst($refund["provider"])); ?></td>
                                                <td><?php echo $escape(ucfirst($refund["refund_status"])); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </section>
            <?php } ?>
            <?php $hasDiscount = $value("book_promo_code", "") !== "" || (int) $value("book_discount_amount", 0) > 0; ?>
            <?php if ($hasDiscount) { ?>
                <section class="card admin-card mb-4" aria-labelledby="promo-title">
                    <div class="card-body">
                        <h3 id="promo-title" class="booking-section-title"><span class="contact-request-detail-icon"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span>Promotion / referral</h3>
                        <p class="text-muted">Saved at the time this discount was applied; later admin edits to the code do not change this record.</p>
                        <dl class="row mb-0 booking-fields">
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Discount code</dt>
                                <dd class="mb-0"><?php echo $escape($value("book_promo_code")); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Discount name</dt>
                                <dd class="mb-0"><?php echo $escape($value("book_discount_name")); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Discount</dt>
                                <dd class="mb-0"><?php echo $escape(
                                                        $value("book_discount_type", "Fixed Amount") === "Percentage"
                                                            ? number_format((int) $value("book_discount_value", 0)) . "%"
                                                            : strip_tags($money($value("book_discount_value", 0)))
                                                    ); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Original total (before tax)</dt>
                                <dd class="mb-0"><?php echo $money($value("book_original_total", $total)); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Discount amount</dt>
                                <dd class="mb-0"><?php echo $money($value("book_discount_amount", 0)); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Payable total</dt>
                                <dd class="mb-0"><?php echo $money($total); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Referral</dt>
                                <dd class="mb-0"><?php echo $escape($value("book_ref_name")); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Referral commission</dt>
                                <dd class="mb-0"><?php echo $escape(
                                                        $value("book_ref_commission_type", "Fixed Amount") === "Percentage"
                                                            ? number_format((int) $value("book_ref_commission_value", 0)) . "%"
                                                            : strip_tags($money($value("book_ref_commission_value", 0)))
                                                    ); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Commission amount</dt>
                                <dd class="mb-0"><?php echo $money($value("book_ref_commission", 0)); ?></dd>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <dt class="booking-label">Commission received</dt>
                                <dd class="mb-0"><?php echo $escape($value("book_ref_commission_received", "No")); ?></dd>
                            </div>
                        </dl>
                    </div>
                </section>
            <?php } ?>
            <?php if ($showGuide) { ?>
            <section class="card admin-card mb-4" aria-labelledby="guide-title">
                <div class="card-body">
                    <h3 id="guide-title" class="booking-section-title"><span class="contact-request-detail-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>Tour guide</h3>
                    <div class="booking-person booking-empty"><span class="booking-avatar" aria-hidden="true"><?php echo $escape(
                                                                                                                    strtoupper(
                                                                                                                        substr($value("book_tour_guide_name", "-"), 0, 1),
                                                                                                                    ),
                                                                                                                ); ?></span>
                        <div class="flex-grow-1"><strong><?php echo $escape(
                                                                $value("book_tour_guide_name", "No guide assigned"),
                                                            ); ?></strong>
                            <div class="text-muted"><?php echo $escape(
                                                        isset($guide["tour_guide_email"]) ? $guide["tour_guide_email"] : "",
                                                    ); ?></div>
                        </div>
                        <?php if (
                            $editableGuide
                        ) { ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignGuide"><?php echo !empty($record["book_tour_guide_id"])
                                                                                                                    ? "Change Guide"
                                                                                                                    : "Assign Guide"; ?></button><?php } ?>
                    </div>
                </div>
            </section>
            <?php } ?>
            <section class="card admin-card mb-4" aria-labelledby="notes-title">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h3 id="notes-title" class="booking-section-title mb-0"><span class="contact-request-detail-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>Internal notes <span class="badge bg-primary"><?php echo count(
                                                                                                                                                                                                                                        $internal_notes,
                                                                                                                                                                                                                                    ); ?></span></h3><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNote" <?php echo !$notes_ready
                                                                                                                        ? "disabled"
                                                                                                                        : ""; ?>>+ Add Note</button>
                    </div>
                    <?php if (
                        !$internal_notes
                    ) { ?><p class="text-muted text-center py-3 mb-0">No internal notes have been added yet.</p><?php } ?>
                    <?php foreach ($internal_notes as $note) { ?>
                        <?php
                        $noteId = (int) $note['id'];
                        $authorName = trim((string) $note['author_name']);
                        if ($authorName === '') {
                            $authorName = !empty($note['author_username'])
                                ? $note['author_username']
                                : 'Former administrator';
                        }
                        ?>
                        <article class="booking-note">
                            <p><?php echo nl2br($escape($note['note'])); ?></p>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <small class="text-muted">
                                    <?php echo $escape($authorName); ?> &middot;
                                    <?php echo $escape($formatDateTime($note['created_at'])); ?>
                                </small>
                                <?php if ((int) $note['author_id'] === (int) $userdata['id']) { ?>
                                    <?php echo form_open(
                                        'manage/bookings/deleteNote/' . $id . '/' . $noteId,
                                        array('id' => 'note-delete-form-' . $noteId, 'class' => 'd-none')
                                    ); ?><?php echo form_close(); ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteNoteModal" data-note-form="note-delete-form-<?php echo $noteId; ?>" aria-label="Delete your internal note" title="Delete note">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                <?php } ?>
                            </div>
                        </article>
                    <?php } ?>
                </div>
            </section>
        </div>
        <aside class="col-lg-4">

            <section class="card admin-card mb-4 booking-price">
                <div class="booking-price-heading">
                    <span class="booking-label">Total payable amount</span>
                    <strong class="d-block fs-2"><?php echo $money($total); ?></strong>
                    <span><?php echo $moyasarPayment ? 'Moyasar payment' : 'Offline payment'; ?></span>
                </div>
                <div class="card-body">
                    <?php
                    $discountAmount = (int) $value("book_discount_amount", 0);
                    $originalTotal = (int) $value("book_original_total", 0);
                    $profitAmount = (int) $value("book_profit_amount", 0);
                    $taxAmount = (int) $value("book_tax_amount", 0);
                    // Bookings created before discounts were stored have no original total.
                    $totalAmount = $originalTotal > 0
                        ? $originalTotal
                        : $total - $taxAmount + $discountAmount;
                    $percent = function ($field) use ($value) {
                        return rtrim(rtrim(number_format((float) $value($field, 0), 2, ".", ""), "0"), ".") . "%";
                    };
                    // book_tour_total is empty for bookings made while the tour price was a flat amount.
                    $tourRows = $value("book_tour_total", null) !== null
                        ? [
                            "Tour price per guest" => $value("book_tour_price", 0),
                            "Tour total" => $value("book_tour_total", 0),
                        ]
                        : ["Tour price" => $value("book_tour_price", $total)];
                    $priceRows = $tourRows + [
                        "Guide price" => $value("book_guide_price", 0),
                        "Vehicle price" => $value("book_vehicle_price", 0),
                        "Meals" => $value("book_meals_total", 0),
                        "Subtotal" => $totalAmount - $profitAmount,
                        "Profit (" . $percent("book_profit_percent") . ")" => $profitAmount,
                        "Total amount" => $totalAmount,
                        "Discount" => $discountAmount,
                        "Tax (" . $percent("book_tax_percent") . ")" => $taxAmount,
                        "Total payable" => $total,
                        "Total paid" => $paid,
                    ];
                    if (!$showGuide) {
                        unset($priceRows["Guide price"]);
                    }
                    if ($refunded > 0) {
                        $priceRows["Refunded"] = $refunded;
                    }
                    // Green for money saved (Discount) and money received (Total paid).
                    $priceRowClasses = [
                        "Discount" => "text-success",
                        "Total paid" => "text-success",
                    ];
                    foreach ($priceRows as $label => $amount) { ?>
                        <div class="booking-price-row">
                            <span><?php echo $escape($label); ?></span>
                            <strong class="<?php echo isset($priceRowClasses[$label]) ? $priceRowClasses[$label] : ""; ?>"><?php echo $money($amount); ?></strong>
                        </div>
                    <?php } ?>
                </div>
            </section>
            <section class="card admin-card mb-4">
                <div class="card-body">
                    <h3 class="booking-section-title"><span class="contact-request-detail-icon"><i class="bi bi-lightning" aria-hidden="true"></i></span>Actions</h3>
                    <div class="d-grid gap-3">
                        <?php if (
                            $editableGuide
                        ) { ?><button class="btn btn-primary text-start" data-bs-toggle="modal" data-bs-target="#assignGuide"><i class="bi bi-person-check" aria-hidden="true"></i> <?php echo !empty($record["book_tour_guide_id"])
                                                                                                                                                                                ? "Change Guide"
                                                                                                                                                                                : "Assign Guide"; ?></button><?php } ?>
                        <button class="btn btn-primary text-start" data-bs-toggle="modal" data-bs-target="#addNote" <?php echo !$notes_ready
                                                                                                                        ? "disabled"
                                                                                                                        : ""; ?>>+ Add Note</button>
                        <button class="btn btn-primary text-start" data-bs-toggle="modal" data-bs-target="#refundBooking" <?php echo $record["book_status"] !== "Completed" || $remainingRefund <= 0
                                                                                                                                ? "disabled"
                                                                                                                                : ""; ?>><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Refund Booking</button>
                        <?php if (
                            $paid <= 0
                        ) { ?><small class="text-muted">Record the offline payment in Edit Booking before refunding.</small><?php } ?>
                        <button class="btn btn-primary text-start" data-bs-toggle="modal" data-bs-target="#cancelBooking" <?php echo $record["book_status"] !== "Pending"
                                                                                                                                ? "disabled"
                                                                                                                                : ""; ?>><i class="bi bi-x-circle" aria-hidden="true"></i> Cancel Booking</button>
                        <?php if (
                            $record["book_status"] !== "Pending"
                        ) { ?><small class="text-muted">Only a pending booking can be cancelled this way.</small><?php } ?>
                    </div>
                </div>
            </section>
            <section class="card admin-card mb-4" aria-labelledby="review-link-title">
                <div class="card-body">
                    <h3 id="review-link-title" class="booking-section-title"><span class="contact-request-detail-icon"><i class="bi bi-star" aria-hidden="true"></i></span>Customer review</h3>
                    <?php if (!$review_schema_ready) { ?>
                        <div class="alert alert-warning mb-0">The tour reviews database migration has not been applied.</div>
                    <?php } elseif (!empty($tour_review)) { ?>
                        <p class="mb-2">This customer submitted a review on <?php echo $escape($formatDateTime($tour_review['tour_review_added'])); ?>.</p>
                        <a class="btn btn-outline-primary w-100" href="<?php echo base_url('manage/tour-reviews/control/view/' . (int) $tour_review['tour_review_id']); ?>"><i class="bi bi-eye" aria-hidden="true"></i> View Review</a>
                    <?php } elseif ($review_eligible) { ?>
                        <p class="text-muted">Copy this private link and send it to the customer.</p>
                        <label class="form-label visually-hidden" for="booking-review-link">Customer review link</label>
                        <div class="input-group">
                            <input class="form-control" id="booking-review-link" type="text" value="<?php echo $escape($review_url); ?>" readonly>
                            <button type="button" class="btn btn-primary" data-copy-value="<?php echo $escape($review_url); ?>" aria-label="Copy customer review link" title="Copy review link"><i class="bi bi-copy" aria-hidden="true"></i> Copy</button>
                        </div>
                        <small class="text-muted d-block mt-2">The link is unique to booking #<?php echo $id; ?> and accepts one review.</small>
                    <?php } else { ?>
                        <p class="text-muted mb-0">The review link becomes available after this booking is Completed and its tour date and end time have passed.</p>
                    <?php } ?>
                </div>
            </section>
            <section class="card admin-card mb-4">
                <div class="card-body">
                    <h3 class="booking-section-title"><span class="contact-request-detail-icon"><i class="bi bi-display" aria-hidden="true"></i></span>User device signature</h3><span class="booking-label">IP address</span>
                    <p><?php echo $escape(
                            $value("book_ip") . " " . $value("book_ip_country", ""),
                        ); ?></p><span class="booking-label">User agent</span>
                    <p class="booking-empty booking-agent mb-0"><?php echo $escape(
                                                                    $value("book_user_agent"),
                                                                ); ?></p>
                </div>
            </section>
        </aside>
    </div>
</div>
<?php
$modalIcons = [
    "assignGuide" => "bi-person-check",
    "addNote" => "bi-journal-plus",
    "editBooking" => "bi-pencil-square",
    "refundBooking" => "bi-arrow-counterclockwise",
    "cancelBooking" => "bi-x-circle",
];
$modalSubtitle = "Booking #" . $id . " · " . $record["book_name"];
$modals = [
    "assignGuide" => [
        "title" => !empty($record["book_tour_guide_id"])
            ? "Change Tour Guide"
            : "Assign Tour Guide",
        "description" => $modalSubtitle,
        "action" => "changeGuide",
        "button" => "Assign Guide",
    ],
    "addNote" => [
        "title" => "Add Note",
        "description" => $modalSubtitle,
        "action" => "addNote",
        "button" => "Save Note",
    ],
    "editBooking" => [
        "title" => "Edit Booking",
        "description" => $modalSubtitle,
        "action" => "saveDetails",
        "button" => "Save Booking",
    ],
    "refundBooking" => [
        "title" => "Refund Booking",
        "description" => $modalSubtitle,
        "action" => "refund",
        "button" => "Refund Booking",
    ],
    "cancelBooking" => [
        "title" => "Cancel Booking",
        "description" => $modalSubtitle,
        "action" => "cancelBooking",
        "button" => "Cancel Booking",
    ],
];
if (!$showGuide) {
    unset($modals["assignGuide"]);
}
$submitted = is_array($booking_values) ? $booking_values : [];
?>
<?php foreach ($modals as $modalId => $modal) { ?>
    <div class="modal fade booking-modal contact-request-modal" id="<?php echo $modalId; ?>" tabindex="-1" aria-labelledby="<?php echo $modalId; ?>-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <?php echo form_open(
                    "manage/bookings/" . $modal["action"] . "/" . $id,
                    [
                        "class" => "validate",
                        "data-booking-form" => $modalId,
                    ],
                ); ?>
                <div class="modal-header">
                    <div class="contact-request-modal-heading"><span class="contact-request-modal-heading-icon" aria-hidden="true"><i class="bi <?php echo $modalIcons[$modalId]; ?>"></i></span>
                        <div>
                            <h2 class="modal-title" id="<?php echo $modalId; ?>-title"><?php echo $escape(
                                                                                            $modal["title"],
                                                                                        ); ?></h2>
                            <p><?php echo $escape(
                                    $modal["description"],
                                ); ?></p>
                        </div>
                    </div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($modalId === "assignGuide") { ?>
                        <div class="contact-request-details-grid mb-3">
                            <div class="contact-request-detail"><span class="contact-request-detail-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                                <div><span class="contact-request-detail-label">Tour</span><span class="contact-request-detail-value"><?php echo $escape(
                                                                                                                                            $record["book_tour_name"],
                                                                                                                                        ); ?></span></div>
                            </div>
                            <div class="contact-request-detail"><span class="contact-request-detail-icon" aria-hidden="true"><i class="bi bi-clock"></i></span>
                                <div><span class="contact-request-detail-label">Tour date / time</span><span class="contact-request-detail-value"><?php echo $escape(
                                                                                                                                                        $record["book_date"]
                                                                                                                                                            ? date(ADMIN_DATE_FORMAT, strtotime($record["book_date"])) . ", " . $value("book_slot_name")
                                                                                                                                                            : "Not specified",
                                                                                                                                                    ); ?></span></div>
                            </div>
                            <div class="contact-request-detail"><span class="contact-request-detail-icon" aria-hidden="true"><i class="bi bi-translate"></i></span>
                                <div><span class="contact-request-detail-label">Preferred language</span><span class="contact-request-detail-value"><?php echo $escape(
                                                                                                                                                        $value("book_lang_name"),
                                                                                                                                                    ); ?></span></div>
                            </div>
                            <div class="contact-request-detail"><span class="contact-request-detail-icon" aria-hidden="true"><i class="bi bi-person-check"></i></span>
                                <div><span class="contact-request-detail-label">Current guide</span><span class="contact-request-detail-value"><?php echo $escape(
                                                                                                                                                    $value("book_tour_guide_name", "No guide assigned"),
                                                                                                                                                ); ?></span></div>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">Available guides are checked for this booking date, time, assigned tour, and language.</p>
                        <div class="admin-field"><label class="form-label is-required" for="guide_id">Tour guide</label><select class="form-select select2" data-user-select data-placeholder="Select Tour Guide" id="guide_id" name="guide_id" required data-validate="required">
                                <option value="">Select Tour Guide</option>
                                <?php foreach (
                                    $guides
                                    as $candidate
                                ) { ?><option value="<?php echo (int) $candidate["tour_guide_id"]; ?>" data-user-name="<?php echo $escape(
                                        $candidate["tour_guide_name"],
                                    ); ?>" data-user-email="<?php echo $escape(
                                        $candidate["tour_guide_email"],
                                    ); ?>" <?php echo (int) $candidate["tour_guide_id"] ===
                                        (int) $record["book_tour_guide_id"]
                                        ? "selected"
                                        : ""; ?>><?php echo $escape(
                                        $candidate["tour_guide_name"],
                                    ); ?></option><?php } ?>
                            </select><?php if (
                                            !$guides
                                        ) { ?><p class="text-muted mt-2">No guides are available for this booking.</p><?php } ?></div>
                    <?php } elseif ($modalId === "addNote") { ?>
                        <div class="admin-field"><label class="form-label is-required" for="internal-note">Note</label><textarea class="form-control" rows="6" id="internal-note" name="note" required data-validate="required" maxlength="10000"><?php echo $escape(
                                                                                                                                                                                                                                                        isset($submitted["note"]) ? $submitted["note"] : "",
                                                                                                                                                                                                                                                    ); ?></textarea></div>
                    <?php } elseif ($modalId === "editBooking") { ?>
                        <div class="row g-3">
                            <?php foreach (
                                [
                                    "book_name" => "Name",
                                    "book_email" => "Email",
                                    "book_phone" => "Phone",
                                    "book_address" => "Pickup / address",
                                    "book_paid_amount" => "Total paid",
                                    "book_payment_method" =>
                                    "Payment method (optional)",
                                    "book_payer_email" =>
                                    "Payer email (optional)",
                                    "book_transaction_id" =>
                                    "Transaction ID (optional)",
                                ]
                                as $field => $label
                            ) {

                                $number = $field === "book_paid_amount";
                                $providerPaymentField = $moyasarPayment && in_array(
                                    $field,
                                    [
                                        "book_paid_amount",
                                        "book_payment_method",
                                        "book_payer_email",
                                        "book_transaction_id",
                                    ],
                                    true,
                                );
                                $required = in_array(
                                    $field,
                                    [
                                        "book_name",
                                        "book_email",
                                        "book_phone",
                                        "book_paid_amount",
                                    ],
                                    true,
                                );
                            ?>
                                <div class="col-sm-6 admin-field"><label class="form-label <?php echo $required
                                                                                                ? "is-required"
                                                                                                : ""; ?>" for="edit-<?php echo $field; ?>"><?php echo $escape(
                                                                                    $label,
                                                                                ); ?></label><input class="form-control" id="edit-<?php echo $field; ?>" name="<?php echo $field; ?>" type="<?php echo $number
                                                                                                                ? "number"
                                                                                                                : (strpos($field, "email") !== false
                                                                                                                    ? "email"
                                                                                                                    : "text"); ?>" value="<?php echo $escape(
                                    isset($submitted[$field])
                                        ? $submitted[$field]
                                        : $value($field, $number ? 0 : ""),
                                ); ?>" <?php echo $required ? "required" : ""; ?> <?php echo $number
                                                        ? 'min="0" step="1" max="' . $escape($total) . '"'
                                                        : 'maxlength="' .
                                                        ($field === "book_payment_method" ? 100 : 255) .
                                                        '"'; ?> <?php echo $providerPaymentField ? "readonly" : ""; ?>></div>
                            <?php
                            } ?>
                        </div>
                    <?php } elseif ($modalId === "refundBooking" || $modalId === "cancelBooking") { ?>
                        <div class="contact-request-details-grid mb-3">
                            <?php foreach (
                                [
                                    ["Customer", $record["book_name"], "bi-person"],
                                    ["Email", $record["book_email"], "bi-envelope"],
                                    ["Tour", $record["book_tour_name"], "bi-calendar3"],
                                    [
                                        "Tour date",
                                        $record["book_date"]
                                            ? date(ADMIN_DATE_FORMAT, strtotime($record["book_date"]))
                                            : "Not specified",
                                        "bi-clock",
                                    ],
                                    [
                                        "Final payable amount",
                                        strip_tags($money($total)),
                                        "bi-receipt",
                                    ],
                                    ["Total paid", strip_tags($money($paid)), "bi-wallet2"],
                                    [
                                        "Customer comments",
                                        $value("book_notes", "No comments recorded."),
                                        "bi-chat-square-text",
                                        "contact-request-detail-wide",
                                    ],
                                ]
                                as $detail
                            ) { ?><div class="contact-request-detail<?php echo isset(
                                                                        $detail[3],
                                                                    )
                                                                        ? " " . $detail[3]
                                                                        : ""; ?>"><span class="contact-request-detail-icon" aria-hidden="true"><i class="bi <?php echo $detail[2]; ?>"></i></span>
                                    <div><span class="contact-request-detail-label"><?php echo $escape(
                                                                                        $detail[0],
                                                                                    ); ?></span><span class="contact-request-detail-value"><?php echo nl2br(
                                                            $escape($detail[1]),
                                                        ); ?></span></div>
                                </div><?php } ?>
                        </div>
                        <?php if ($modalId === "refundBooking") { ?>
                            <hr>
                            <div class="row g-3">
                                <input type="hidden" name="refund_request_token" value="<?php echo $escape($refund_request_token); ?>">
                                <div class="col-sm-6 admin-field"><label class="form-label is-required" for="refund-amount">Refund amount</label><input class="form-control" type="number" id="refund-amount" name="refund_amount" min="1" step="1" max="<?php echo $escape(
                                                                                                                                                                                                                                                                 $remainingRefund,
                                                                                                                                                                                                                                                             ); ?>" value="<?php echo $escape(
                                            isset($submitted["refund_amount"]) ? $submitted["refund_amount"] : $remainingRefund,
                                        ); ?>" required><small class="text-muted">Maximum refundable amount: <?php echo $money(
                                                                            $remainingRefund,
                                                                         ); ?></small></div>
                                <div class="col-sm-6 admin-field">
                                    <label class="form-label" for="refund-percent">Percentage of total paid</label>
                                    <select class="form-select select2" id="refund-percent" data-minimum-results-for-search="-1" data-paid="<?php echo $escape($remainingRefund); ?>">
                                        <option value="100">100%</option>
                                        <option value="75">75%</option>
                                        <option value="50">50%</option>
                                        <option value="25">25%</option>
                                        <option value="custom">Custom amount</option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="admin-field"><label class="form-label" for="refund-reply">Reply (optional, recorded internally)</label><textarea class="form-control" rows="5" name="refund_reply" id="refund-reply" maxlength="10000" placeholder="Add an optional reply with refund details."><?php echo $escape(
                                                                                                                                                                                                                                                                                                            isset($submitted["refund_reply"]) ? $submitted["refund_reply"] : "",
                                                                                                                                                                                                                                                                                                        ); ?></textarea></div>
                        <?php } else { ?>
                            <hr>
                            <div class="admin-field"><label class="form-label" for="cancel-reason">Cancellation reason (optional, recorded internally)</label><textarea class="form-control" rows="5" name="cancel_reason" id="cancel-reason" maxlength="10000" placeholder="Add an optional reason for cancelling this booking."><?php echo $escape(
                                                                                                                                                                                                                                                                                                                                        isset($submitted["cancel_reason"]) ? $submitted["cancel_reason"] : "",
                                                                                                                                                                                                                                                                                                                                    ); ?></textarea></div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <div class="modal-footer admin-form-actions"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" <?php echo ($modalId ===
                                                                                                                                                                                                                "assignGuide" &&
                                                                                                                                                                                                                (!$guides || !$editableGuide)) ||
                                                                                                                                                                                                                ($modalId === "addNote" && !$notes_ready) ||
                                                                                                                                                                                                                ($modalId === "refundBooking" &&
                                                                                                                                                                                                                    ($remainingRefund <= 0 || $record["book_status"] !== "Completed")) ||
                                                                                                                                                                                                                ($modalId === "cancelBooking" &&
                                                                                                                                                                                                                    $record["book_status"] !== "Pending")
                                                                                                                                                                                                                ? "disabled"
                                                                                                                                                                                                                : ""; ?>><?php echo $escape(
                                    $modal["button"],
                                ); ?></button></div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
<?php } ?>
<div class="modal fade" id="deleteNoteModal" tabindex="-1" aria-labelledby="deleteNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="deleteNoteModalLabel">Delete note</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">This internal note will be permanently deleted. This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="deleteNoteConfirm" class="btn btn-danger">Delete note</button>
            </div>
        </div>
    </div>
</div>
<?php if ($submitted) { ?>
    <div id="booking-reopen" data-modal="<?php echo isset($submitted["note"])
                                                ? "addNote"
                                                : (isset($submitted["refund_amount"])
                                                    ? "refundBooking"
                                                    : (isset($submitted["cancel_reason"])
                                                        ? "cancelBooking"
                                                        : "editBooking")); ?>" hidden></div>
<?php } ?>
