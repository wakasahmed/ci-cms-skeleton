<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Offer card: image, label, title, summary, inclusions, price (with the old
 * price struck through), duration, validity and a booking button.
 *
 * $offer   a row from Offer_model
 * $size    'featured' (wide image, larger title) or 'regular'
 */
$featured = isset($size) && $size === 'featured';
$inclusions = frontend_lines($offer['offer_inclusions']);
$price = frontend_price($offer['offer_price']);
$oldPrice = frontend_price($offer['offer_old_price']);
$duration = trim((string) $offer['offer_duration_label']);

$validity = trim((string) $offer['offer_validity_note']);
if ($validity === '' && !empty($offer['offer_valid_to'])) {
    $validity = 'Valid until '.date('j F Y', strtotime($offer['offer_valid_to'])).'.';
}
?>
<article class="group flex flex-col overflow-hidden rounded-xl bg-background shadow-[var(--shadow-card)] transition-transform duration-300 ease-[var(--ease-out-soft)] hover:-translate-y-1 h-full">
    <div class="relative overflow-hidden bg-muted <?php echo $featured ? 'aspect-16/10' : 'aspect-4/3'; ?>">
        <img
            alt=""
            loading="lazy"
            decoding="async"
            class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
            src="<?php echo html_escape(upload_thumb('offers', $offer['offer_image'], $featured ? 1100 : 700, 0, 'images/no_image.jpg')); ?>"
        >
    </div>
    <div class="flex flex-1 flex-col p-6 sm:p-7">
        <?php if (trim((string) $offer['offer_label']) !== '') { ?>
            <p class="text-sm font-medium text-primary-ink"><?php echo html_escape($offer['offer_label']); ?></p>
        <?php } ?>
        <h3 class="mt-2 text-foreground <?php echo $featured ? 'text-2xl sm:text-3xl' : 'text-2xl'; ?>"><?php echo html_escape($offer['offer_title']); ?></h3>
        <?php if (trim((string) $offer['offer_summary']) !== '') { ?>
            <p class="mt-3 leading-relaxed text-muted-foreground"><?php echo html_escape($offer['offer_summary']); ?></p>
        <?php } ?>
        <?php if (!empty($inclusions)) { ?>
            <ul class="mt-5 space-y-2 text-sm">
                <?php foreach ($inclusions as $inclusion) { ?>
                    <li class="flex items-start gap-2.5">
                        <i class="fa-solid fa-check mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true"></i>
                        <span class="text-foreground-soft"><?php echo html_escape($inclusion); ?></span>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>
        <div class="mt-auto pt-6">
            <p class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <?php if ($price !== '') { ?>
                    <span class="font-display text-3xl text-foreground"><?php echo html_escape($price); ?></span>
                <?php } ?>
                <?php if ($oldPrice !== '') { ?>
                    <span class="text-muted-foreground line-through"><span class="sr-only">Usually </span><?php echo html_escape($oldPrice); ?></span>
                <?php } ?>
                <?php if ($duration !== '') { ?>
                    <span class="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                        <i class="fa-regular fa-clock size-3.5" aria-hidden="true"></i>
                        <?php echo html_escape($duration); ?>
                    </span>
                <?php } ?>
            </p>
            <?php if ($validity !== '') { ?>
                <p class="mt-2 text-sm text-muted-foreground"><?php echo html_escape($validity); ?></p>
            <?php } ?>
            <a
                class="<?php echo html_escape(frontend_button_class('primary', 'h-12 px-7 mt-5 w-full sm:w-auto')); ?>"
                href="<?php echo html_escape(base_url('book').'?offer='.rawurlencode($offer['offer_slug'])); ?>"
            >Book this offer<span class="sr-only">: <?php echo html_escape($offer['offer_title']); ?></span></a>
        </div>
    </div>
</article>
