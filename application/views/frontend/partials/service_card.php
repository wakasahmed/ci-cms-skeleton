<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Service card with a portrait image, used in the featured services grids.
 *
 * $service   a row from Service_model (card columns)
 * $eager     TRUE for cards visible on first paint (loading="eager")
 */
$price = frontend_service_price($service);
$duration = trim((string) $service['service_duration_label']);
$image = upload_thumb(
    'services',
    $service['service_card_image'] !== '' && $service['service_card_image'] !== NULL
        ? $service['service_card_image']
        : $service['service_hero_image'],
    640,
    0,
    'images/no_image.jpg'
);
?>
<article class="group h-full">
    <a class="block" href="<?php echo html_escape(base_url('services/'.rawurlencode($service['service_slug']))); ?>">
        <div class="relative aspect-4/5 overflow-hidden rounded-xl bg-muted">
            <img
                alt="<?php echo html_escape($service['service_name']); ?>"
                loading="<?php echo !empty($eager) ? 'eager' : 'lazy'; ?>"
                decoding="async"
                class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.05]"
                src="<?php echo html_escape($image); ?>"
            >
        </div>
        <div class="mt-4 flex items-start justify-between gap-3">
            <h3 class="font-display text-lg text-foreground sm:text-xl"><?php echo html_escape($service['service_name']); ?></h3>
            <i class="fa-solid fa-arrow-up-right-from-square mt-1 size-4 shrink-0 text-primary transition-transform duration-300 ease-[var(--ease-out-soft)] group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true"></i>
        </div>
        <?php if ($price !== '' || $duration !== '') { ?>
            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 text-sm">
                <?php if ($price !== '') { ?>
                    <span class="font-medium text-foreground-soft"><?php echo html_escape($price); ?></span>
                <?php } ?>
                <?php if ($duration !== '') { ?>
                    <span class="inline-flex items-center gap-1 text-muted-foreground">
                        <i class="fa-regular fa-clock size-3.5" aria-hidden="true"></i>
                        <?php echo html_escape($duration); ?>
                    </span>
                <?php } ?>
            </p>
        <?php } ?>
    </a>
</article>
