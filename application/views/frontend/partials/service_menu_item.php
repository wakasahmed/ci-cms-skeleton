<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * One service in the "full menu" lists: initial, name, price and duration,
 * and the one-sentence summary.
 *
 * $service   a row from Service_model (card columns)
 */
$meta = array_filter(array(
    frontend_service_price($service),
    trim((string) $service['service_duration_label']),
));
?>
<li>
    <a
        class="group flex h-full gap-4 rounded-lg border border-border-strong bg-background p-5 transition-colors hover:border-primary hover:bg-petal"
        href="<?php echo html_escape(base_url('services/'.rawurlencode($service['service_slug']))); ?>"
    >
        <span class="grid size-14 shrink-0 place-items-center rounded-lg bg-rose-100 font-display text-xl text-primary" aria-hidden="true"><?php echo html_escape(mb_substr($service['service_name'], 0, 1, 'UTF-8')); ?></span>
        <span class="min-w-0 flex-1">
            <span class="block font-display text-xl text-foreground"><?php echo html_escape($service['service_name']); ?></span>
            <?php if (!empty($meta)) { ?>
                <span class="mt-1 block text-sm text-primary-ink"><?php echo html_escape(implode(' · ', $meta)); ?></span>
            <?php } ?>
            <?php if (trim((string) $service['service_summary']) !== '') { ?>
                <span class="mt-2 block text-sm leading-relaxed text-muted-foreground"><?php echo html_escape($service['service_summary']); ?></span>
            <?php } ?>
        </span>
        <i class="fa-solid fa-arrow-up-right-from-square text-primary" aria-hidden="true"></i>
    </a>
</li>
