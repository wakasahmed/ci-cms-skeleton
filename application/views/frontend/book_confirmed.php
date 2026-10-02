<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Book > confirmation (/book/confirmed): the booking just made on /book,
 * read from the session (never from a reference in the URL).
 *
 * $appointment   the appointments row with its 'services' rows
 * $confirmation  the Book page's Confirmation section
 */
$heading = isset($confirmation['heading']) && trim($confirmation['heading']) !== ''
    ? $confirmation['heading']
    : 'Your appointment is confirmed';

// The date, then one row per service with its start time and artist (a
// visit can be done by several artists), then the totals.
$rows = array(
    array('label' => 'Date', 'value' => date('l, j F Y', strtotime($appointment['appointment_date']))),
);
foreach ($appointment['services'] as $service) {
    $rows[] = array(
        'label' => $service['service_start_time'] !== NULL
            ? date('g:i A', strtotime($service['service_start_time']))
            : 'Service',
        'value' => $service['service_name']
            .($service['service_artist_name'] !== NULL ? ' with '.$service['service_artist_name'] : ''),
    );
}
$rows[] = array(
    'label' => 'Time needed',
    'value' => Booking_request::duration((int) $appointment['appointment_duration_minutes']),
);
$rows[] = array(
    'label' => 'Estimated total',
    'value' => $appointment['appointment_total_price'] !== NULL ? frontend_price($appointment['appointment_total_price']) : '',
);
if ($appointment['appointment_offer_title'] !== NULL) {
    $rows[] = array('label' => 'Offer', 'value' => $appointment['appointment_offer_title']);
}
?>
<main id="main">
    <div class="relative isolate">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-petal via-lilac to-background"></div>
        <div class="mx-auto w-full max-w-3xl px-5 pt-28 pb-18 sm:px-8 sm:pt-32 lg:pt-36 lg:pb-26">
            <div class="text-center">
                <span aria-hidden="true" class="mx-auto grid size-14 place-items-center rounded-full bg-primary text-2xl text-primary-foreground shadow-[var(--shadow-card)]">
                    <i class="fa-solid fa-check"></i>
                </span>
                <h1 class="mt-6 text-[clamp(2rem,4.6vw,3rem)]"><?php echo html_escape($heading); ?></h1>
                <p class="mx-auto mt-4 max-w-lg text-lg leading-relaxed text-muted-foreground">
                    <?php if (isset($confirmation['contents']) && trim($confirmation['contents']) !== '') { ?>
                        <?php echo html_escape($confirmation['contents']); ?>
                    <?php } ?>
                    A confirmation is on its way to <?php echo html_escape($appointment['customer_email']); ?>.
                </p>
            </div>

            <div class="mt-10 rounded-xl bg-petal p-6 shadow-[var(--shadow-card)] sm:p-8">
                <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-rose-200 pb-5">
                    <h2 class="font-display text-xl">Your appointment</h2>
                    <p class="text-sm text-muted-foreground">
                        Reference <span class="font-semibold text-primary-ink"><?php echo html_escape($appointment['appointment_reference']); ?></span>
                    </p>
                </div>
                <dl class="mt-5 space-y-4 text-[0.95rem]">
                    <?php foreach ($rows as $row) { ?>
                        <?php if ($row['value'] !== '') { ?>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground tabular-nums"><?php echo html_escape($row['label']); ?></dt>
                                <dd class="text-right font-medium"><?php echo html_escape($row['value']); ?></dd>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </dl>
            </div>

            <div class="mt-8 grid gap-8 sm:grid-cols-2">
                <div>
                    <h2 class="font-display text-xl">Where to come</h2>
                    <address class="mt-4 not-italic leading-relaxed">
                        <strong><?php echo html_escape($site['name']); ?></strong><br>
                        <?php echo implode('<br>', array_map('html_escape', $site['address_lines'])); ?>
                        <?php if ($site['address_note'] !== '') { ?>
                            <br><span class="text-muted-foreground"><?php echo html_escape($site['address_note']); ?></span>
                        <?php } ?>
                        <?php if ($site['phone_href'] !== '') { ?>
                            <br>
                            <a class="mt-3 inline-flex min-h-11 items-center text-primary-ink" href="<?php echo html_escape($site['phone_href']); ?>">
                                <i class="fa-solid fa-phone mr-2" aria-hidden="true"></i><?php echo html_escape($site['phone']); ?>
                            </a>
                        <?php } ?>
                    </address>
                </div>
                <?php if (!empty($site['opening_hours'])) { ?>
                    <div>
                        <h2 class="font-display text-xl">Opening hours</h2>
                        <dl class="mt-4 text-sm">
                            <?php foreach ($site['opening_hours'] as $row) { ?>
                                <div class="flex justify-between py-1.5<?php echo $row['closed'] ? ' text-muted-foreground' : ''; ?>">
                                    <dt><?php echo html_escape($row['days']); ?></dt>
                                    <dd><?php echo html_escape($row['hours']); ?></dd>
                                </div>
                            <?php } ?>
                        </dl>
                    </div>
                <?php } ?>
            </div>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a href="<?php echo html_escape(base_url()); ?>" class="<?php echo html_escape(frontend_button_class('primary', 'h-12 px-7')); ?>">Back to home</a>
                <?php if ($site['account']['signed_in']) { ?>
                    <a href="<?php echo html_escape(base_url('account')); ?>" class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>">My appointments</a>
                <?php } else { ?>
                    <a href="<?php echo html_escape(base_url('services')); ?>" class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>">Browse services</a>
                <?php } ?>
            </div>
            <?php if (!$site['account']['signed_in']) { ?>
                <p class="mt-6 text-sm text-foreground-soft">
                    Want to move or cancel bookings online?
                    <a class="link-underline font-semibold text-primary-ink" href="<?php echo html_escape(base_url('account/sign-up')); ?>">Create an account</a>
                    with <?php echo html_escape($appointment['customer_email']); ?> and confirm it — this booking will be there.
                </p>
            <?php } ?>
            <?php if (isset($confirmation['note']) && trim($confirmation['note']) !== '') { ?>
                <p class="mt-6 rounded-lg bg-lilac px-4 py-3 text-sm text-foreground-soft"><?php echo html_escape($confirmation['note']); ?></p>
            <?php } ?>
        </div>
    </div>
</main>
