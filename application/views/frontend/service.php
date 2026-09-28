<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Service detail (/services/{slug}).
 *
 * $service   the service row (with category_name)
 * $hero      page hero data (see partials/page_hero.php)
 * $addons    add-on rows
 * $gallery   gallery images linked to the service
 * $artists   artists who take the service
 * $related   "Often booked with this" services
 * $labels    Miscellaneous Contents > Service Page
 * $shapes    Miscellaneous Contents > Nail Shapes & Finishes, or NULL when not shown
 * $cta       call-to-action data (see partials/cta_band.php)
 * $schema    JSON-LD Service description
 */
$label = function ($key, $default) use ($labels) {
    return isset($labels[$key]) && trim((string) $labels[$key]) !== '' ? $labels[$key] : $default;
};
$included = frontend_lines($service['service_included']);
$before = frontend_lines($service['service_before_visit']);
$aftercare = frontend_lines($service['service_aftercare']);
$tipGroups = array_filter(array(
    array('heading' => $label('before_heading', 'Before your visit'), 'icon' => 'fa-circle-info', 'items' => $before),
    array('heading' => $label('aftercare_heading', 'Looking after it'), 'icon' => 'fa-wand-magic-sparkles', 'items' => $aftercare),
), function ($group) {
    return !empty($group['items']);
});
?>
<main id="main">
    <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <?php if (!empty($included) || !empty($tipGroups) || !empty($addons)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="included-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                    <div class="reveal lg:col-span-7">
                        <h2 id="included-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($label('included_heading', "What's included")); ?></h2>
                        <?php if (!empty($included)) { ?>
                            <ul class="mt-6 space-y-3">
                                <?php foreach ($included as $item) { ?>
                                    <li class="flex items-start gap-3">
                                        <i class="fa-solid fa-check mt-1 size-4 shrink-0 text-primary" aria-hidden="true"></i>
                                        <span class="text-foreground-soft"><?php echo html_escape($item); ?></span>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>

                        <?php if (!empty($tipGroups)) { ?>
                            <div class="mt-10 grid gap-8 sm:grid-cols-2">
                                <?php foreach ($tipGroups as $group) { ?>
                                    <div>
                                        <h3 class="flex items-center gap-2 font-display text-xl text-foreground">
                                            <i class="fa-solid <?php echo $group['icon']; ?> size-4 text-primary" aria-hidden="true"></i>
                                            <?php echo html_escape($group['heading']); ?>
                                        </h3>
                                        <ul class="mt-4 space-y-2 text-muted-foreground">
                                            <?php foreach ($group['items'] as $item) { ?>
                                                <li class="leading-relaxed"><?php echo html_escape($item); ?></li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php if (!empty($addons)) { ?>
                        <div class="reveal lg:col-span-5" style="transition-delay:80ms">
                            <div class="rounded-xl bg-petal p-6 sm:p-8">
                                <h2 class="flex items-center gap-2 font-display text-xl text-foreground">
                                    <i class="fa-solid fa-tag size-4 text-primary" aria-hidden="true"></i>
                                    <?php echo html_escape($label('addons_heading', 'Add to this appointment')); ?>
                                </h2>
                                <dl class="mt-5 divide-y divide-rose-200">
                                    <?php foreach ($addons as $addon) { ?>
                                        <div class="flex items-baseline justify-between gap-4 py-3">
                                            <dt class="text-foreground-soft"><?php echo html_escape($addon['addon_label']); ?></dt>
                                            <dd class="shrink-0 font-medium text-foreground tabular-nums">
                                                <?php $addonPrice = frontend_price($addon['addon_price']); ?>
                                                <?php echo html_escape($addonPrice !== '' ? '+'.$addonPrice : 'Free'); ?>
                                            </dd>
                                        </div>
                                    <?php } ?>
                                </dl>
                                <?php if ($label('addons_note', '') !== '') { ?>
                                    <p class="mt-4 text-sm text-muted-foreground"><?php echo html_escape($label('addons_note', '')); ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($shapes)) { ?>
        <?php
        $shapeRows = frontend_split_lines(isset($shapes['shapes']) ? $shapes['shapes'] : '');
        $finishRows = frontend_split_lines(isset($shapes['finishes']) ? $shapes['finishes'] : '');
        ?>
        <section class="py-18 md:py-22 lg:py-26 bg-lilac text-foreground" aria-labelledby="shapes-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-2xl">
                    <?php if (!empty($shapes['pre_heading'])) { ?>
                        <p class="section-label"><?php echo html_escape($shapes['pre_heading']); ?></p>
                    <?php } ?>
                    <h2 id="shapes-heading" class="mt-4 text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($shapes['heading']); ?></h2>
                    <?php if (!empty($shapes['contents'])) { ?>
                        <p class="mt-4 text-muted-foreground"><?php echo html_escape($shapes['contents']); ?></p>
                    <?php } ?>
                </div>
                <div class="mt-10 grid gap-10 lg:grid-cols-12 lg:gap-14">
                    <?php if (!empty($shapeRows)) { ?>
                        <div class="reveal lg:col-span-5">
                            <h3 class="font-display text-xl text-foreground"><?php echo html_escape(!empty($shapes['shapes_heading']) ? $shapes['shapes_heading'] : 'Shapes'); ?></h3>
                            <dl class="mt-4 divide-y divide-border border-y border-border">
                                <?php foreach ($shapeRows as $row) { ?>
                                    <div class="flex justify-between gap-4 py-3.5">
                                        <dt class="font-medium text-foreground"><?php echo html_escape($row[0]); ?></dt>
                                        <?php if (isset($row[1]) && $row[1] !== '') { ?>
                                            <dd class="text-right text-sm text-muted-foreground"><?php echo html_escape($row[1]); ?></dd>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </dl>
                        </div>
                    <?php } ?>
                    <?php if (!empty($finishRows)) { ?>
                        <div class="reveal lg:col-span-7" style="transition-delay:80ms">
                            <h3 class="font-display text-xl text-foreground"><?php echo html_escape(!empty($shapes['finishes_heading']) ? $shapes['finishes_heading'] : 'Finishes'); ?></h3>
                            <ul class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                                <?php foreach ($finishRows as $row) { ?>
                                    <?php $colour = isset($row[2]) && preg_match('/^#[0-9a-f]{3,8}$/i', $row[2]) ? $row[2] : ''; ?>
                                    <li class="flex items-start gap-3">
                                        <span
                                            aria-hidden="true"
                                            class="mt-1 size-5 shrink-0 rounded-full ring-1 ring-foreground/10 ring-offset-2 ring-offset-lilac"
                                            <?php echo $colour !== '' ? 'style="background-color:'.html_escape($colour).'"' : ''; ?>
                                        ></span>
                                        <span>
                                            <span class="block font-medium text-foreground"><?php echo html_escape($row[0]); ?></span>
                                            <?php if (isset($row[1]) && $row[1] !== '') { ?>
                                                <span class="mt-0.5 block text-sm text-muted-foreground"><?php echo html_escape($row[1]); ?></span>
                                            <?php } ?>
                                        </span>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($gallery)) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="service-gallery-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal">
                    <h2 id="service-gallery-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]"><?php echo html_escape($label('gallery_heading', 'Sets like this')); ?></h2>
                </div>
                <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    <?php foreach ($gallery as $index => $image) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 60).'ms"' : ''; ?>>
                            <div class="relative aspect-square overflow-hidden rounded-xl bg-muted">
                                <img
                                    alt="<?php echo html_escape($image['image_caption']); ?>"
                                    loading="lazy"
                                    decoding="async"
                                    class="absolute inset-0 size-full object-cover"
                                    src="<?php echo html_escape(upload_thumb('gallery', $image['image_file'], 600, 600, 'images/no_image.jpg')); ?>"
                                >
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($artists)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="service-artists-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-2xl">
                    <h2 id="service-artists-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($label('artists_heading', 'Who takes this appointment')); ?></h2>
                </div>
                <div class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($artists as $index => $artist) { ?>
                        <div class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 80).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/artist_card', array('artist' => $artist, 'variant' => 'compact')); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($related)) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="related-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal">
                    <h2 id="related-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]"><?php echo html_escape($label('related_heading', 'Often booked with this')); ?></h2>
                </div>
                <ul class="mt-6 divide-y divide-border border-y border-border">
                    <?php foreach ($related as $relatedService) { ?>
                        <?php $this->load->view('frontend/partials/service_related_item', array('service' => $relatedService, 'variant' => 'related')); ?>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => $cta)); ?>
</main>
