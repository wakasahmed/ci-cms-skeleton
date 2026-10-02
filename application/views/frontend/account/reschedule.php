<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Move an appointment (/account/appointments/{reference}/reschedule): the
 * free times for the same services (and the same artist when one artist did
 * the whole visit), from Booking_request::rescheduleDays(), as radio buttons
 * grouped by day. Works without JavaScript; the server checks the chosen
 * time again under the booking lock.
 *
 * $appointment   the customer's appointment, with 'services'
 * $days          Y-m-d => list of "HH:MM", or NULL when a service can no
 *                longer be booked online
 * $formToken     one-use token
 * $status        result of the last attempt ('taken', 'invalid', 'same', …)
 * $action        the form's URL
 */
$messages = array(
    'taken' => 'Sorry, that time was just booked. Please choose another.',
    'invalid' => 'Please choose one of the times.',
    'same' => 'That is the time you already have. Please choose another.',
    'expired' => 'That took a little long. Please try again.',
    'unavailable' => 'This appointment can no longer be moved online. Please call the salon.',
    'blocked' => 'Appointments can be changed online until '.ACCOUNT_CHANGE_NOTICE_HOURS.' hours before they start. Please call the salon.',
    'error' => 'The appointment could not be moved. Please try again, or call the salon.',
);
$message = isset($messages[$status]) ? $messages[$status] : '';
$current = $appointment['appointment_date'].' '.substr($appointment['appointment_time'], 0, 5);
$start = strtotime($current);
$freeDays = array_filter((array) $days);
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'My appointments', 'url' => base_url('account')),
            array('label' => 'Move '.$appointment['appointment_reference']),
        ),
        'label' => 'Your account',
        'heading' => 'Move your appointment',
        'lead' => implode(', ', array_column($appointment['services'], 'service_name'))
            .' — now '.date('l, '.FRONTEND_DATE_FORMAT, $start).' at '.date('g:i A', $start).'. Choose a new time below.',
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-label="Choose a new time">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <?php $this->load->view('frontend/partials/form_status', array('message' => $message, 'success' => FALSE)); ?>

            <?php if ($days === NULL || empty($freeDays)) { ?>
                <div class="card-soft max-w-2xl p-6 sm:p-8">
                    <p class="text-foreground-soft">
                        <?php echo $days === NULL
                            ? 'This appointment can no longer be moved online.'
                            : 'There are no free times for this appointment in the next three weeks.'; ?>
                        Please call the salon<?php echo $site['phone'] !== '' ? ' on '.html_escape($site['phone']) : ''; ?>.
                    </p>
                    <a class="<?php echo html_escape(frontend_button_class('outline', 'mt-5 h-12 px-7')); ?>" href="<?php echo html_escape(base_url('account')); ?>">Back to my appointments</a>
                </div>
            <?php } else { ?>
                <form method="post" action="<?php echo html_escape($action); ?>" class="grid gap-8">
                    <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($freeDays as $date => $times) { ?>
                            <fieldset class="card-soft p-5">
                                <legend class="sr-only"><?php echo html_escape(date('l, '.FRONTEND_DATE_FORMAT, strtotime($date))); ?></legend>
                                <p class="font-display text-lg text-foreground" aria-hidden="true"><?php echo html_escape(date('l, j F', strtotime($date))); ?></p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php foreach ($times as $time) {
                                        $value = $date.' '.$time;
                                        $id = 'slot-'.str_replace(array('-', ':', ' '), '', $value);
                                        $isCurrent = $value === $current;
                                        ?>
                                        <div>
                                            <input class="peer sr-only" type="radio" name="slot" id="<?php echo html_escape($id); ?>" value="<?php echo html_escape($value); ?>" <?php echo $isCurrent ? 'disabled' : ''; ?> required>
                                            <label
                                                for="<?php echo html_escape($id); ?>"
                                                class="inline-flex min-h-11 cursor-pointer items-center rounded-full border border-border-strong px-4 text-sm font-semibold text-foreground-soft transition-colors duration-200 hover:border-primary hover:text-primary peer-checked:border-primary peer-checked:bg-primary peer-checked:text-primary-foreground peer-focus-visible:ring-4 peer-focus-visible:ring-primary/25 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"
                                            ><?php echo html_escape(date('g:i A', strtotime('2000-01-01 '.$time))); ?><?php echo $isCurrent ? ' (current)' : ''; ?></label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </fieldset>
                        <?php } ?>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>" type="submit">Move to this time</button>
                        <a class="<?php echo html_escape(frontend_button_class('ghost', 'h-13 px-6')); ?>" href="<?php echo html_escape(base_url('account')); ?>">Keep my current time</a>
                    </div>
                </form>
            <?php } ?>
        </div>
    </section>
</main>
