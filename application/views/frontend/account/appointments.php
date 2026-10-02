<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * My appointments (/account): a banner while the email address is not
 * confirmed, the upcoming appointments (move or cancel until
 * ACCOUNT_CHANGE_NOTICE_HOURS before; cancelling asks for confirmation in a
 * <details> panel, so it works without JavaScript) and the past ones.
 *
 * $customer   the signed-in customer
 * $upcoming   appointments to come, soonest first, each with 'services' and
 *             'blocker' (Booking_request::changeBlocker(): '' when changeable)
 * $past       earlier and cancelled appointments, latest first
 * $notice     flashed array('type', 'message') or NULL
 * $tokens     one-use tokens: 'appointment', 'resend', 'sign_out'
 */
$firstName = preg_split('/\s+/u', trim((string) $customer['customer_name']), 2)[0];
$statusClasses = array(
    'New' => 'bg-amber-100 text-amber-900',
    'Confirmed' => 'bg-petal text-primary-ink',
    'Completed' => 'bg-emerald-100 text-emerald-900',
    'Cancelled' => 'bg-muted text-muted-foreground',
);
$when = function (array $appointment) {
    $start = strtotime($appointment['appointment_date'].' '.$appointment['appointment_time']);

    return array(
        'iso' => date('c', $start),
        'date' => date('l, '.FRONTEND_DATE_FORMAT, $start),
        'time' => date('g:i A', $start),
    );
};
$serviceLine = function (array $service) {
    $parts = array();
    if ($service['service_start_time'] !== NULL) {
        $parts[] = date('g:i A', strtotime('2000-01-01 '.$service['service_start_time']));
    }
    $parts[] = $service['service_name'];
    if ($service['service_artist_name'] !== NULL && $service['service_artist_name'] !== '') {
        $parts[] = 'with '.$service['service_artist_name'];
    }

    return implode(' ', $parts);
};
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'My appointments'),
        ),
        'label' => 'Your account',
        'heading' => 'Hello, '.$firstName,
        'lead' => 'Your appointments with us. You can move or cancel one online until '.ACCOUNT_CHANGE_NOTICE_HOURS.' hours before it starts; after that, please call the salon.',
        'actions' => array(
            array('label' => 'Book an appointment', 'url' => base_url('book')),
        ),
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-labelledby="upcoming-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <?php $this->load->view('frontend/account/nav', array('current' => 'appointments', 'signOutToken' => $tokens['sign_out'])); ?>

            <?php $this->load->view('frontend/partials/form_status', array(
                'message' => is_array($notice) ? $notice['message'] : '',
                'success' => is_array($notice) && $notice['type'] === 'success',
            )); ?>

            <?php if ($customer['customer_email_verified_at'] === NULL) { ?>
                <div class="mb-10 flex flex-col gap-4 rounded-lg border border-border-strong bg-petal px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex items-start gap-3 text-sm leading-relaxed text-foreground-soft">
                        <i class="fa-regular fa-envelope mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true"></i>
                        <span>Please confirm your email address (<?php echo html_escape($customer['customer_email']); ?>) with the link we sent. Bookings you made earlier with it then appear here too.</span>
                    </p>
                    <form method="post" action="<?php echo html_escape(base_url('account/resend-verification')); ?>" class="shrink-0">
                        <input type="hidden" name="form_token" value="<?php echo html_escape($tokens['resend']); ?>">
                        <button type="submit" class="<?php echo html_escape(frontend_button_class('outline', 'h-11 px-5')); ?>">Send the link again</button>
                    </form>
                </div>
            <?php } ?>

            <h2 id="upcoming-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]">Coming up</h2>
            <?php if (empty($upcoming)) { ?>
                <div class="card-soft mt-6 p-6 sm:p-8">
                    <p class="text-foreground-soft">You have no upcoming appointments.</p>
                    <a class="<?php echo html_escape(frontend_button_class('primary', 'mt-5 h-12 px-7')); ?>" href="<?php echo html_escape(base_url('book')); ?>">Book an appointment</a>
                </div>
            <?php } else { ?>
                <ul class="mt-6 grid gap-5 lg:grid-cols-2" role="list">
                    <?php foreach ($upcoming as $appointment) {
                        $time = $when($appointment);
                        $reference = $appointment['appointment_reference'];
                        $base = base_url('account/appointments/'.rawurlencode($reference));
                        ?>
                        <li class="card-soft flex flex-col p-6 sm:p-8">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm text-muted-foreground">Reference <?php echo html_escape($reference); ?></p>
                                    <h3 class="mt-1 text-xl">
                                        <time datetime="<?php echo html_escape($time['iso']); ?>"><?php echo html_escape($time['date']); ?></time>
                                    </h3>
                                    <p class="mt-1 font-semibold text-primary-ink"><?php echo html_escape($time['time']); ?></p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo isset($statusClasses[$appointment['appointment_status']]) ? $statusClasses[$appointment['appointment_status']] : 'bg-muted text-muted-foreground'; ?>">
                                    <?php echo html_escape($appointment['appointment_status']); ?>
                                </span>
                            </div>
                            <ul class="mt-5 space-y-1 text-foreground-soft" role="list">
                                <?php foreach ($appointment['services'] as $service) { ?>
                                    <li><?php echo html_escape($serviceLine($service)); ?></li>
                                <?php } ?>
                            </ul>
                            <?php if ($appointment['appointment_total_price'] !== NULL) { ?>
                                <p class="mt-3 text-sm text-muted-foreground">Estimated total <?php echo html_escape(frontend_price($appointment['appointment_total_price'])); ?> · paid at the salon</p>
                            <?php } ?>

                            <div class="mt-6 border-t border-border pt-5">
                                <?php if ($appointment['blocker'] === '') { ?>
                                    <div class="flex flex-wrap items-start gap-3">
                                        <a class="<?php echo html_escape(frontend_button_class('outline', 'h-11 px-5')); ?>" href="<?php echo html_escape($base.'/reschedule'); ?>">
                                            <i class="fa-regular fa-calendar size-4" aria-hidden="true"></i> Move
                                        </a>
                                        <details class="group">
                                            <summary class="<?php echo html_escape(frontend_button_class('ghost', 'h-11 px-5 cursor-pointer list-none')); ?>">
                                                <i class="fa-regular fa-calendar-xmark size-4" aria-hidden="true"></i> Cancel
                                            </summary>
                                            <form method="post" action="<?php echo html_escape($base.'/cancel'); ?>" class="mt-3 rounded-lg bg-destructive/10 p-4">
                                                <input type="hidden" name="form_token" value="<?php echo html_escape($tokens['appointment']); ?>">
                                                <p class="text-sm text-destructive">Cancel appointment <?php echo html_escape($reference); ?>? Its time becomes free for others.</p>
                                                <button type="submit" class="mt-3 inline-flex min-h-11 items-center rounded-full bg-destructive px-5 text-sm font-semibold text-background hover:opacity-90">Yes, cancel it</button>
                                            </form>
                                        </details>
                                    </div>
                                <?php } else { ?>
                                    <p class="text-sm text-muted-foreground">
                                        <i class="fa-solid fa-phone mr-1 size-3.5" aria-hidden="true"></i>
                                        To change this appointment, please call the salon<?php echo $site['phone'] !== '' ? ' on '.html_escape($site['phone']) : ''; ?>.
                                    </p>
                                <?php } ?>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>

            <h2 class="mt-16 text-[clamp(1.5rem,3.2vw,2rem)]" id="past-heading">Past and cancelled</h2>
            <?php if (empty($past)) { ?>
                <p class="mt-4 text-foreground-soft">Nothing here yet.</p>
            <?php } else { ?>
                <ul class="mt-6 divide-y divide-border rounded-lg border border-border" role="list" aria-labelledby="past-heading">
                    <?php foreach ($past as $appointment) {
                        $time = $when($appointment);
                        ?>
                        <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-foreground">
                                    <time datetime="<?php echo html_escape($time['iso']); ?>"><?php echo html_escape($time['date'].', '.$time['time']); ?></time>
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    <?php echo html_escape(implode(', ', array_column($appointment['services'], 'service_name'))); ?>
                                    · <?php echo html_escape($appointment['appointment_reference']); ?>
                                </p>
                            </div>
                            <span class="self-start rounded-full px-3 py-1 text-xs font-semibold sm:self-center <?php echo isset($statusClasses[$appointment['appointment_status']]) ? $statusClasses[$appointment['appointment_status']] : 'bg-muted text-muted-foreground'; ?>">
                                <?php echo html_escape($appointment['appointment_status']); ?>
                            </span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </section>
</main>
