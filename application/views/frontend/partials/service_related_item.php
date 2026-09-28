<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * One row of an "Often booked with this" list: thumbnail, name, summary and
 * price.
 *
 * $service   a row from Service_model (card columns)
 */
$price = frontend_service_price($service);
$thumbnail = upload_thumb(
    'services',
    $service['service_card_image'] !== '' && $service['service_card_image'] !== NULL
        ? $service['service_card_image']
        : $service['service_hero_image'],
    112,
    112,
    'images/no_image.jpg'
);
?>
<li>
    <a
        class="group -mx-3 flex items-center gap-4 rounded-lg px-3 py-5 transition-colors duration-200 hover:bg-petal"
        href="<?php echo html_escape(base_url('services/'.rawurlencode($service['service_slug']))); ?>"
    >
        <span class="relative size-14 shrink-0 overflow-hidden rounded-lg bg-muted">
            <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape($thumbnail); ?>">
        </span>
        <span class="min-w-0 flex-1">
            <span class="block font-display text-lg text-foreground transition-colors duration-200 group-hover:text-primary"><?php echo html_escape($service['service_name']); ?></span>
            <?php if (trim((string) $service['service_summary']) !== '') { ?>
                <span class="mt-0.5 block truncate text-sm text-muted-foreground"><?php echo html_escape($service['service_summary']); ?></span>
            <?php } ?>
        </span>
        <?php if ($price !== '') { ?>
            <span class="hidden shrink-0 text-sm text-foreground-soft sm:block"><?php echo html_escape($price); ?></span>
        <?php } ?>
        <i class="fa-solid fa-arrow-up-right-from-square size-5 shrink-0 text-primary transition-transform duration-300 ease-[var(--ease-out-soft)] group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true"></i>
    </a>
</li>
