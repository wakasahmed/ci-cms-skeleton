<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Artist detail (/artists/{slug}).
 *
 * $artist     the artist row
 * $hero       page hero data (see partials/page_hero.php)
 * $services   the public services the artist takes
 * $days       working day names, in week order
 * $work       recent gallery images
 * $reviews    customer reviews
 * $labels     Miscellaneous Contents > Artist Page, with {name} replaced
 * $cta        call-to-action data (see partials/cta_band.php)
 * $book_url   booking link for this artist
 */
$label = function ($key, $default) use ($labels) {
    return isset($labels[$key]) && trim((string) $labels[$key]) !== '' ? $labels[$key] : $default;
};
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <?php if (!empty($services) || !empty($days)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="artist-services-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                    <div class="reveal lg:col-span-7">
                        <h2 id="artist-services-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($label('services_heading', 'Services')); ?></h2>
                        <?php if (!empty($services)) { ?>
                            <ul class="mt-8 divide-y divide-border border-y border-border">
                                <?php foreach ($services as $service) { ?>
                                    <?php $this->load->view('frontend/partials/service_related_item', array('service' => $service, 'variant' => 'artist')); ?>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>

                    <?php if (!empty($days)) { ?>
                        <div class="reveal lg:col-span-5" style="transition-delay:80ms">
                            <div class="rounded-xl bg-petal p-6 sm:p-8">
                                <h2 class="flex items-center gap-2 font-display text-xl text-foreground">
                                    <i class="fa-solid fa-calendar-days size-5 text-primary" aria-hidden="true"></i>
                                    <?php echo html_escape($label('days_heading', 'Usually in the studio')); ?>
                                </h2>
                                <ul class="mt-5 flex flex-wrap gap-2">
                                    <?php foreach ($days as $day) { ?>
                                        <li class="rounded-full bg-background px-4 py-2 text-sm font-medium text-foreground"><?php echo html_escape($day); ?></li>
                                    <?php } ?>
                                </ul>
                                <?php if ($label('days_note', '') !== '') { ?>
                                    <p class="mt-5 text-sm text-muted-foreground"><?php echo html_escape($label('days_note', '')); ?></p>
                                <?php } ?>
                                <a
                                    class="<?php echo html_escape(frontend_button_class('primary', 'h-12 px-7 mt-6 w-full')); ?>"
                                    href="<?php echo html_escape($book_url); ?>"
                                ><?php echo html_escape($label('days_button', 'Check availability')); ?></a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($work)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-lilac text-foreground" aria-labelledby="artist-work-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-2xl">
                    <h2 id="artist-work-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($label('work_heading', 'Recent work')); ?></h2>
                    <?php if ($label('work_text', '') !== '') { ?>
                        <p class="mt-4 text-muted-foreground"><?php echo html_escape($label('work_text', '')); ?></p>
                    <?php } ?>
                </div>
                <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                    <?php foreach ($work as $index => $image) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 60).'ms"' : ''; ?>>
                            <figure>
                                <div class="relative aspect-square overflow-hidden rounded-xl bg-muted">
                                    <img
                                        alt="<?php echo html_escape($image['image_caption']); ?>"
                                        loading="lazy"
                                        decoding="async"
                                        class="absolute inset-0 size-full object-cover"
                                        src="<?php echo html_escape(upload_thumb('gallery', $image['image_file'], 640, 640, 'images/no_image.jpg')); ?>"
                                    >
                                </div>
                                <?php if (trim((string) $image['image_caption']) !== '') { ?>
                                    <figcaption class="mt-2.5 text-sm text-muted-foreground"><?php echo html_escape($image['image_caption']); ?></figcaption>
                                <?php } ?>
                            </figure>
                        </li>
                    <?php } ?>
                </ul>
                <div class="reveal mt-10">
                    <a
                        class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>"
                        href="<?php echo html_escape(base_url('gallery')); ?>"
                    ><?php echo html_escape($label('work_button', 'View the full gallery')); ?></a>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($reviews)) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="artist-reviews-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-2xl">
                    <h2 id="artist-reviews-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]"><?php echo html_escape($label('reviews_heading', 'What clients say')); ?></h2>
                    <?php if ($label('reviews_note', '') !== '') { ?>
                        <p class="mt-3 text-sm text-muted-foreground"><?php echo html_escape($label('reviews_note', '')); ?></p>
                    <?php } ?>
                </div>
                <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($reviews as $index => $review) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 80).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/testimonial', array('review' => $review)); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => $cta)); ?>
</main>
