<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * About (/about).
 *
 * $hero       page hero data (see partials/page_hero.php)
 * $sections   Web Page Sections of the About page, keyed by section
 * $artists    enabled artists
 */
$section = function ($key) use ($sections) {
    return isset($sections[$key]) ? $sections[$key] : array();
};
$value = function (array $fields, $key, $default = '') {
    return isset($fields[$key]) && trim((string) $fields[$key]) !== ''
        ? $fields[$key]
        : $default;
};
$approach = $section('approach');
$nailsFirst = $section('nails_first');
$products = $section('products');
$team = $section('team');
$location = $section('location');
$cta = $section('cta');

// Approach items with a title, numbered in display order.
$approachItems = array();
for ($item = 1; $item <= 3; $item++) {
    if ($value($approach, 'item_'.$item.'_title') !== '') {
        $approachItems[] = array(
            'icon' => frontend_icon_class($value($approach, 'item_'.$item.'_icon')),
            'title' => $approach['item_'.$item.'_title'],
            'text' => $value($approach, 'item_'.$item.'_text'),
        );
    }
}
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <?php if ($value($approach, 'heading') !== '') { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="approach-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <?php if ($value($approach, 'pre_heading') !== '') { ?>
                        <p class="section-label"><?php echo html_escape($approach['pre_heading']); ?></p>
                    <?php } ?>
                    <h2 id="approach-heading" class="mt-4 text-[clamp(1.75rem,4vw,2.75rem)]"><?php echo html_escape($approach['heading']); ?></h2>
                    <?php if ($value($approach, 'contents') !== '') { ?>
                        <p class="mt-5 text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($approach['contents']); ?></p>
                    <?php } ?>
                </div>
                <ul class="mt-14 grid gap-10 sm:grid-cols-2 lg:mt-18 lg:grid-cols-3 lg:gap-0">
                    <?php foreach ($approachItems as $index => $approachItem) { ?>
                        <li class="reveal lg:border-l lg:border-border lg:px-10 lg:first:border-l-0 lg:first:pl-0 lg:last:pr-0"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 70).'ms"' : ''; ?>>
                            <span aria-hidden="true" class="grid size-12 place-items-center rounded-full bg-petal text-accent shadow-[var(--shadow-card)]">
                                <?php if ($approachItem['icon'] !== '') { ?>
                                    <i class="<?php echo $approachItem['icon']; ?> size-5" aria-hidden="true"></i>
                                <?php } ?>
                            </span>
                            <p class="mt-5 font-display text-sm text-primary-ink tabular-nums" aria-hidden="true"><?php echo sprintf('%02d', $index + 1); ?></p>
                            <h3 class="mt-1 text-xl"><?php echo html_escape($approachItem['title']); ?></h3>
                            <?php if ($approachItem['text'] !== '') { ?>
                                <p class="mt-2.5 leading-relaxed text-muted-foreground"><?php echo html_escape($approachItem['text']); ?></p>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php if ($value($nailsFirst, 'heading') !== '') { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="nails-first-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="grid gap-12 lg:grid-cols-12 lg:items-center lg:gap-16">
                    <div class="reveal lg:col-span-6">
                        <div class="grid grid-cols-5 gap-4">
                            <?php if ($value($nailsFirst, 'image_1') !== '') { ?>
                                <figure class="relative col-span-3 aspect-3/4 overflow-hidden rounded-lg bg-muted">
                                    <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape(upload_thumb('content-sections', $nailsFirst['image_1'], 800, 0, 'images/no_image.jpg')); ?>">
                                </figure>
                            <?php } ?>
                            <?php if ($value($nailsFirst, 'image_2') !== '') { ?>
                                <figure class="relative col-span-2 mt-12 aspect-2/3 overflow-hidden rounded-lg bg-muted">
                                    <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape(upload_thumb('content-sections', $nailsFirst['image_2'], 600, 0, 'images/no_image.jpg')); ?>">
                                </figure>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="reveal lg:col-span-6" style="transition-delay:80ms">
                        <?php if ($value($nailsFirst, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($nailsFirst['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="nails-first-heading" class="mt-4 text-[clamp(1.75rem,4vw,2.75rem)]"><?php echo html_escape($nailsFirst['heading']); ?></h2>
                        <?php foreach (preg_split('/\R\s*\R/', trim($value($nailsFirst, 'contents'))) as $index => $paragraph) { ?>
                            <?php if (trim($paragraph) !== '') { ?>
                                <p class="<?php echo $index === 0 ? 'mt-5' : 'mt-4'; ?> leading-relaxed text-muted-foreground"><?php echo html_escape(trim($paragraph)); ?></p>
                            <?php } ?>
                        <?php } ?>
                        <?php if ($value($nailsFirst, 'button_1_text') !== '' || $value($nailsFirst, 'button_2_text') !== '') { ?>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <?php if ($value($nailsFirst, 'button_1_text') !== '') { ?>
                                    <a
                                        class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>"
                                        href="<?php echo html_escape(frontend_url($value($nailsFirst, 'button_1_url', 'services'))); ?>"
                                    ><?php echo html_escape($nailsFirst['button_1_text']); ?></a>
                                <?php } ?>
                                <?php if ($value($nailsFirst, 'button_2_text') !== '') { ?>
                                    <a
                                        class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>"
                                        href="<?php echo html_escape(frontend_url($value($nailsFirst, 'button_2_url', 'gallery'))); ?>"
                                    ><?php echo html_escape($nailsFirst['button_2_text']); ?></a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if ($value($products, 'heading') !== '') { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="products-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-3xl">
                    <h2 id="products-heading" class="text-[clamp(1.5rem,3.2vw,2.25rem)]"><?php echo html_escape($products['heading']); ?></h2>
                    <?php if ($value($products, 'contents') !== '') { ?>
                        <p class="mt-4 leading-relaxed text-muted-foreground"><?php echo html_escape($products['contents']); ?></p>
                    <?php } ?>
                    <?php if ($value($products, 'note') !== '') { ?>
                        <p class="mt-4 text-sm text-muted-foreground"><?php echo html_escape($products['note']); ?></p>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($artists) && $value($team, 'heading') !== '') { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-lilac text-foreground" aria-labelledby="about-team-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-xl">
                        <h2 id="about-team-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($team['heading']); ?></h2>
                        <?php if ($value($team, 'contents') !== '') { ?>
                            <p class="mt-4 text-muted-foreground"><?php echo html_escape($team['contents']); ?></p>
                        <?php } ?>
                    </div>
                    <a class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>" href="<?php echo html_escape(base_url('artists')); ?>"><?php echo html_escape($value($team, 'button_text', 'Meet the team')); ?></a>
                </div>
                <ul class="mt-10 grid gap-6 sm:grid-cols-3">
                    <?php foreach ($artists as $index => $artist) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.(($index % 3) * 70).'ms"' : ''; ?>>
                            <a class="group block" href="<?php echo html_escape(base_url('artists/'.rawurlencode($artist['artist_slug']))); ?>">
                                <div class="relative aspect-4/5 overflow-hidden rounded-xl bg-muted">
                                    <img
                                        alt=""
                                        loading="lazy"
                                        decoding="async"
                                        class="absolute inset-0 size-full object-cover object-top transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
                                        src="<?php echo html_escape(upload_thumb('artists', $artist['artist_image'], 640, 0, 'images/no_image.jpg')); ?>"
                                    >
                                </div>
                                <h3 class="mt-4 font-display text-xl text-foreground"><?php echo html_escape($artist['artist_name']); ?></h3>
                                <?php if (trim((string) $artist['artist_role']) !== '') { ?>
                                    <p class="mt-1 text-sm text-foreground-soft"><?php echo html_escape($artist['artist_role']); ?></p>
                                <?php } ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="about-location-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                <div class="reveal lg:col-span-5">
                    <?php if ($value($location, 'pre_heading') !== '') { ?>
                        <p class="section-label"><?php echo html_escape($location['pre_heading']); ?></p>
                    <?php } ?>
                    <h2 id="about-location-heading" class="mt-4 text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($value($location, 'heading', 'Visit us')); ?></h2>
                    <?php $this->load->view('frontend/partials/visit_details', array('spacing' => 'mt-8')); ?>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>" href="<?php echo html_escape($site['book_url']); ?>">Book appointment</a>
                        <a class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>" href="<?php echo html_escape(base_url('contact')); ?>">Contact us</a>
                    </div>
                </div>
                <?php if ($value($location, 'image') !== '') { ?>
                    <div class="reveal lg:col-span-7" style="transition-delay:80ms">
                        <div class="relative h-full min-h-80 overflow-hidden rounded-xl bg-muted">
                            <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape(upload_thumb('content-sections', $location['image'], 1100, 0, 'images/no_image.jpg')); ?>">
                            <?php if (!empty($site['address_lines'])) { ?>
                                <p class="absolute bottom-4 left-4 flex items-center gap-2 rounded-full bg-background/95 px-4 py-2 text-sm font-medium text-foreground shadow-[var(--shadow-card)] backdrop-blur-sm">
                                    <i class="fa-solid fa-location-dot size-4 text-primary" aria-hidden="true"></i>
                                    <?php echo html_escape($site['address_lines'][0]); ?>
                                </p>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'heading' => $value($cta, 'heading', 'Book your visit to Blossom'),
        'text' => $value($cta, 'contents'),
    ))); ?>
</main>
