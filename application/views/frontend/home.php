<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Home page (/).
 *
 * $slides      hero slides (Manage > Sliders): label, title, highlight, text,
 *              image, primary/secondary buttons
 * $sections    Web Page Sections of the home page, keyed by section
 * $services    featured services
 * $finishes    nail finishes (Miscellaneous Contents > Nail Shapes & Finishes):
 *              rows of array(name, description, colour)
 * $gallery     featured gallery images
 * $lead        the lead artist, or NULL; $team the other artists
 * $offers      public offers, featured first
 * $reviews     customer reviews
 */
$section = function ($key) use ($sections) {
    return isset($sections[$key]) ? $sections[$key] : array();
};
$value = function (array $fields, $key, $default = '') {
    return isset($fields[$key]) && trim((string) $fields[$key]) !== '' ? $fields[$key] : $default;
};
$image = function ($file, $width) {
    return upload_thumb('content-sections', $file, $width, 0, 'images/no_image.jpg');
};
$finishColours = array_values(array_filter(array_map(function ($row) {
    return isset($row[2]) && preg_match('/^#[0-9a-f]{3,8}$/i', $row[2]) ? $row[2] : '';
}, $finishes)));
$locationLine = implode(', ', $site['address_lines']).($site['address_note'] !== '' ? ' — '.lcfirst($site['address_note']) : '');

$hero = $section('hero');
$servicesIntro = $section('services_intro');
$otherServices = $section('other_services');
$nailStyles = $section('nail_styles');
$gallerySection = $section('gallery');
$about = $section('about');
$artistsSection = $section('artists');
$why = $section('why');
$booking = $section('booking');
$offersSection = $section('offers');
$testimonials = $section('testimonials');
$location = $section('location');
$instagram = $section('instagram');
$finalCta = $section('final_cta');
?>
<main id="main">
    <?php if (!empty($slides)) { ?>
        <section
            aria-roledescription="carousel"
            aria-label="Featured services"
            class="relative isolate overflow-hidden pt-24 pb-12 sm:pt-28 lg:pt-32 lg:pb-16"
            data-hero
        >
            <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-[80%] bg-gradient-to-b from-petal via-lilac to-background lg:h-[92%]"></div>
            <div aria-hidden="true" class="absolute -top-28 -right-32 -z-10 hidden size-[30rem] rounded-full bg-rose-100/70 blur-[2px] lg:block"></div>
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="grid items-center gap-9 lg:grid-cols-12 lg:gap-12">
                    <div class="order-2 min-w-0 lg:order-1 lg:col-span-5">
                        <?php foreach ($slides as $index => $slide) { ?>
                            <div class="animate-[fade-up_600ms_var(--ease-out-soft)_both]" data-hero-copy<?php echo $index > 0 ? ' hidden' : ''; ?>>
                                <?php if ($slide['label'] !== '') { ?>
                                    <p class="section-label"><?php echo html_escape($slide['label']); ?></p>
                                <?php } ?>
                                <?php $headingTag = $index === 0 ? 'h1' : 'h2'; ?>
                                <<?php echo $headingTag; ?> class="mt-5 text-[clamp(2.5rem,6.4vw,4.25rem)] leading-[1.05] text-foreground">
                                    <?php echo html_escape($slide['title']); ?>
                                    <?php if ($slide['highlight'] !== '') { ?>
                                        <span class="italic text-primary"><?php echo html_escape($slide['highlight']); ?></span>
                                    <?php } ?>
                                </<?php echo $headingTag; ?>>
                                <?php if ($slide['text'] !== '') { ?>
                                    <p class="mt-5 max-w-md text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($slide['text']); ?></p>
                                <?php } ?>
                                <?php if (!empty($slide['primary']) || !empty($slide['secondary'])) { ?>
                                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                        <?php if (!empty($slide['primary'])) { ?>
                                            <a
                                                class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>"
                                                href="<?php echo html_escape($slide['primary']['url']); ?>"
                                                <?php echo $slide['primary']['target'] === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                                            ><?php echo html_escape($slide['primary']['text']); ?></a>
                                        <?php } ?>
                                        <?php if (!empty($slide['secondary'])) { ?>
                                            <a
                                                class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>"
                                                href="<?php echo html_escape($slide['secondary']['url']); ?>"
                                                <?php echo $slide['secondary']['target'] === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                                            >
                                                <?php echo html_escape($slide['secondary']['text']); ?>
                                                <?php if ($slide['secondary']['icon'] !== '') { ?>
                                                    <i class="<?php echo html_escape($slide['secondary']['icon']); ?> size-4" aria-hidden="true"></i>
                                                <?php } ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <?php if (count($slides) > 1) { ?>
                            <div class="mt-8 flex items-center gap-5">
                                <div class="flex items-center gap-2">
                                    <?php foreach ($slides as $index => $slide) { ?>
                                        <button
                                            type="button"
                                            aria-label="Show slide <?php echo $index + 1; ?>: <?php echo html_escape($slide['label'] !== '' ? $slide['label'] : $slide['title']); ?>"
                                            aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                            class="group grid h-11 w-8 cursor-pointer place-items-center"
                                            data-hero-dot
                                        >
                                            <span class="block h-1.5 rounded-full transition-all duration-300 ease-[var(--ease-out-soft)] <?php echo $index === 0 ? 'w-8 bg-accent' : 'w-3 bg-rose-300 group-hover:bg-secondary'; ?>"></span>
                                        </button>
                                    <?php } ?>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" aria-label="Previous slide" data-hero-prev class="grid size-11 cursor-pointer place-items-center rounded-full bg-background text-foreground shadow-[var(--shadow-card)] ring-1 ring-border transition-colors duration-200 hover:text-primary hover:ring-primary">
                                        <i class="fa-solid fa-chevron-left size-5" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" aria-label="Next slide" data-hero-next class="grid size-11 cursor-pointer place-items-center rounded-full bg-background text-foreground shadow-[var(--shadow-card)] ring-1 ring-border transition-colors duration-200 hover:text-primary hover:ring-primary">
                                        <i class="fa-solid fa-chevron-right size-5" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="relative order-1 min-w-0 lg:order-2 lg:col-span-7">
                        <div class="relative aspect-4/3 overflow-hidden rounded-2xl bg-muted shadow-[var(--shadow-soft)] sm:aspect-16/11 lg:aspect-4/3" data-hero-frame>
                            <?php foreach ($slides as $index => $slide) { ?>
                                <img
                                    alt="<?php echo html_escape($slide['title'].' '.$slide['highlight']); ?>"
                                    aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"
                                    <?php echo $index > 0 ? 'loading="lazy"' : ''; ?>
                                    decoding="async"
                                    class="slide absolute inset-0 size-full object-cover <?php echo $index === 0 ? 'opacity-100' : 'scale-[1.04] opacity-0'; ?>"
                                    src="<?php echo html_escape($slide['image']); ?>"
                                >
                            <?php } ?>
                        </div>
                        <?php if (!empty($finishColours) && $value($hero, 'finishes_note') !== '') { ?>
                            <div class="absolute -bottom-6 left-4 z-10 hidden items-center gap-3 rounded-full bg-background/95 px-5 py-3 shadow-[var(--shadow-soft)] ring-1 ring-border backdrop-blur-sm sm:flex lg:left-8">
                                <ul class="flex items-center gap-2" aria-hidden="true">
                                    <?php foreach ($finishColours as $colour) { ?>
                                        <li class="size-6 rounded-full ring-1 ring-foreground/10" style="background-color:<?php echo html_escape($colour); ?>"></li>
                                    <?php } ?>
                                </ul>
                                <p class="text-sm font-medium text-foreground-soft"><?php echo html_escape($value($hero, 'finishes_note')); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php if ($locationLine !== '') { ?>
                    <p class="mt-10 flex items-center gap-2 text-sm text-muted-foreground lg:mt-12">
                        <i class="fa-solid fa-location-dot size-4 text-accent" aria-hidden="true"></i>
                        <?php echo html_escape($locationLine); ?>
                    </p>
                <?php } ?>
            </div>
        </section>
    <?php } else { ?>
        <h1 class="sr-only"><?php echo html_escape($site['name']); ?></h1>
        <div class="pt-20 lg:pt-24"></div>
    <?php } ?>

    <?php if (!empty($services) || $value($servicesIntro, 'heading') !== '') { ?>
        <?php
        $serviceTiles = array(
            array('wrap' => 'lg:col-span-7', 'aspect' => 'aspect-4/3', 'title' => 'text-[clamp(2rem,4vw,3rem)]', 'text' => 'text-muted-foreground max-w-lg text-lg', 'delay' => 0, 'width' => 1100),
            array('wrap' => 'lg:col-span-5', 'aspect' => 'aspect-4/5', 'title' => 'text-2xl', 'text' => 'leading-relaxed text-muted-foreground max-w-sm', 'delay' => 90, 'width' => 800),
            array('wrap' => 'lg:col-span-5 lg:col-start-2', 'aspect' => 'aspect-square', 'title' => 'text-2xl', 'text' => 'leading-relaxed text-muted-foreground max-w-sm', 'delay' => 60, 'width' => 800),
            array('wrap' => 'lg:col-span-5', 'aspect' => 'aspect-square', 'title' => 'text-2xl', 'text' => 'leading-relaxed text-muted-foreground max-w-sm', 'delay' => 120, 'width' => 800),
        );
        ?>
        <section class="py-20 md:py-26 lg:py-32 bg-background text-foreground" id="services" aria-labelledby="services-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl">
                        <?php if ($value($servicesIntro, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($servicesIntro['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="services-heading" class="mt-5 text-[clamp(2.5rem,6.5vw,4.5rem)] text-foreground"><?php echo nl2br(html_escape($value($servicesIntro, 'heading', 'Our services')), FALSE); ?></h2>
                        <?php if ($value($servicesIntro, 'contents') !== '') { ?>
                            <p class="mt-5 leading-relaxed text-muted-foreground"><?php echo html_escape($servicesIntro['contents']); ?></p>
                        <?php } ?>
                    </div>
                    <?php if ($value($servicesIntro, 'button_text') !== '') { ?>
                        <div class="shrink-0">
                            <a
                                class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>"
                                href="<?php echo html_escape(frontend_url($value($servicesIntro, 'button_url', 'services'))); ?>"
                            >
                                <?php echo html_escape($servicesIntro['button_text']); ?>
                                <i class="fa-solid fa-arrow-right size-4" aria-hidden="true"></i>
                            </a>
                        </div>
                    <?php } ?>
                </div>

                <?php if (!empty($services)) { ?>
                    <div class="mt-14 grid gap-x-8 gap-y-14 lg:mt-20 lg:grid-cols-12">
                        <?php foreach ($services as $index => $service) { ?>
                            <?php
                            $tile = $serviceTiles[$index % count($serviceTiles)];
                            $amount = frontend_price($service['service_price_from']);
                            $suffix = trim((string) $service['service_price_suffix']);
                            ?>
                            <div class="reveal <?php echo $tile['wrap']; ?>"<?php echo $tile['delay'] > 0 ? ' style="transition-delay:'.$tile['delay'].'ms"' : ''; ?>>
                                <article class="group">
                                    <a class="block" href="<?php echo html_escape(base_url('services/'.rawurlencode($service['service_slug']))); ?>">
                                        <div class="relative overflow-hidden rounded-xl bg-muted <?php echo $tile['aspect']; ?>">
                                            <img
                                                alt="<?php echo html_escape($service['service_name']); ?>"
                                                loading="lazy"
                                                decoding="async"
                                                class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
                                                src="<?php echo html_escape(upload_thumb('services', $service['service_card_image'] ?: $service['service_hero_image'], $tile['width'], 0, 'images/no_image.jpg')); ?>"
                                            >
                                        </div>
                                        <div class="mt-6 flex items-start justify-between gap-4">
                                            <h3 class="text-foreground <?php echo $tile['title']; ?>"><?php echo html_escape($service['service_name']); ?></h3>
                                            <i class="fa-solid fa-arrow-up-right-from-square mt-2 size-5 shrink-0 text-primary transition-transform duration-300 ease-[var(--ease-out-soft)] group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true"></i>
                                        </div>
                                        <?php if (trim((string) $service['service_summary']) !== '') { ?>
                                            <p class="mt-3 <?php echo $tile['text']; ?>"><?php echo html_escape($service['service_summary']); ?></p>
                                        <?php } ?>
                                        <p class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-foreground-soft">
                                            <?php if ($amount !== '') { ?>
                                                <span>from <span class="font-medium"><?php echo html_escape(trim($amount.' '.$suffix)); ?></span></span>
                                            <?php } ?>
                                            <?php if (trim((string) $service['service_duration_label']) !== '') { ?>
                                                <span class="text-muted-foreground"><?php echo html_escape($service['service_duration_label']); ?></span>
                                            <?php } ?>
                                        </p>
                                    </a>
                                </article>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($value($otherServices, 'heading') !== '') { ?>
                    <div class="reveal mt-20 lg:mt-28">
                        <div class="grid gap-8 lg:grid-cols-12 lg:gap-12">
                            <div class="min-w-0 lg:col-span-4">
                                <div class="rule mb-6 w-full max-w-40" aria-hidden="true"></div>
                                <h3 class="text-2xl text-foreground sm:text-3xl"><?php echo html_escape($otherServices['heading']); ?></h3>
                                <?php if ($value($otherServices, 'contents') !== '') { ?>
                                    <p class="mt-4 text-muted-foreground"><?php echo html_escape($otherServices['contents']); ?></p>
                                <?php } ?>
                                <a class="link-underline mt-6 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-foreground" href="<?php echo html_escape(base_url('services')); ?>">
                                    <?php echo html_escape($value($otherServices, 'link_text', 'View all services')); ?>
                                    <i class="fa-solid fa-arrow-right size-4 text-primary" aria-hidden="true"></i>
                                </a>
                            </div>
                            <ul class="min-w-0 lg:col-span-8">
                                <?php for ($row = 1; $row <= 3; $row++) { ?>
                                    <?php if ($value($otherServices, 'row_'.$row.'_title') === '') { continue; } ?>
                                    <li>
                                        <article class="group">
                                            <a
                                                class="-mx-3 flex min-w-0 items-center gap-4 rounded-lg border-b border-border px-3 py-5 transition-colors duration-200 hover:border-transparent hover:bg-petal sm:gap-5"
                                                href="<?php echo html_escape(frontend_url($value($otherServices, 'row_'.$row.'_url', 'services'))); ?>"
                                            >
                                                <span class="relative size-16 shrink-0 overflow-hidden rounded-lg bg-muted sm:size-20">
                                                    <?php if ($value($otherServices, 'row_'.$row.'_image') !== '') { ?>
                                                        <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape(upload_thumb('content-sections', $otherServices['row_'.$row.'_image'], 160, 160, 'images/no_image.jpg')); ?>">
                                                    <?php } ?>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block font-display text-xl text-foreground"><?php echo html_escape($otherServices['row_'.$row.'_title']); ?></span>
                                                    <?php if ($value($otherServices, 'row_'.$row.'_text') !== '') { ?>
                                                        <span class="mt-1 block truncate text-sm text-muted-foreground"><?php echo html_escape($otherServices['row_'.$row.'_text']); ?></span>
                                                    <?php } ?>
                                                </span>
                                                <?php if ($value($otherServices, 'row_'.$row.'_price') !== '') { ?>
                                                    <span class="hidden shrink-0 text-sm text-foreground-soft sm:block"><?php echo html_escape($otherServices['row_'.$row.'_price']); ?></span>
                                                <?php } ?>
                                                <i class="fa-solid fa-arrow-up-right-from-square size-5 shrink-0 text-primary transition-transform duration-300 ease-[var(--ease-out-soft)] group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true"></i>
                                            </a>
                                        </article>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($finishes) && $value($nailStyles, 'heading') !== '') { ?>
        <section aria-labelledby="nail-styles-heading" class="wash-fresh overflow-hidden py-20 md:py-24 lg:py-28">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal grid gap-7 lg:grid-cols-12 lg:items-end lg:gap-12">
                    <div class="lg:col-span-7">
                        <?php if ($value($nailStyles, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($nailStyles['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="nail-styles-heading" class="mt-5 text-[clamp(2.25rem,5.4vw,3.75rem)] text-foreground"><?php echo html_escape($nailStyles['heading']); ?></h2>
                    </div>
                    <div class="lg:col-span-5">
                        <?php if ($value($nailStyles, 'contents') !== '') { ?>
                            <p class="text-muted-foreground"><?php echo html_escape($nailStyles['contents']); ?></p>
                        <?php } ?>
                        <?php if ($value($nailStyles, 'button_text') !== '') { ?>
                            <a
                                class="<?php echo html_escape(frontend_button_class('primary', 'h-12 px-7 mt-6')); ?>"
                                href="<?php echo html_escape(frontend_url($value($nailStyles, 'button_url', 'services'))); ?>"
                            >
                                <?php echo html_escape($nailStyles['button_text']); ?>
                                <i class="fa-solid fa-arrow-right size-4" aria-hidden="true"></i>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <ul class="mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-5 pb-4 sm:px-8 lg:mt-16 lg:px-12 lg:mx-auto lg:grid lg:max-w-[86rem] lg:grid-cols-6 lg:gap-6 lg:overflow-visible lg:pb-0">
                <?php foreach ($finishes as $index => $finish) { ?>
                    <?php $styleImage = $value($nailStyles, 'image_'.($index + 1)); ?>
                    <li class="w-[62vw] shrink-0 snap-start sm:w-[38vw] lg:w-auto<?php echo $index % 2 === 1 ? ' lg:mt-12' : ''; ?>">
                        <figure class="group">
                            <div class="nail-arch relative aspect-3/4 overflow-hidden bg-rose-100 shadow-[var(--shadow-card)] transition-transform duration-500 ease-[var(--ease-out-soft)] group-hover:-translate-y-1.5">
                                <?php if ($styleImage !== '') { ?>
                                    <img
                                        alt="<?php echo html_escape($finish[0]); ?>"
                                        loading="lazy"
                                        decoding="async"
                                        class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.05]"
                                        src="<?php echo html_escape($image($styleImage, 500)); ?>"
                                    >
                                <?php } ?>
                            </div>
                            <figcaption class="mt-4 flex items-start gap-3">
                                <span
                                    aria-hidden="true"
                                    class="mt-1 size-4 shrink-0 rounded-full ring-1 ring-foreground/12 ring-offset-2 ring-offset-petal"
                                    <?php echo isset($finish[2]) && preg_match('/^#[0-9a-f]{3,8}$/i', $finish[2]) ? 'style="background-color:'.html_escape($finish[2]).'"' : ''; ?>
                                ></span>
                                <span>
                                    <span class="block font-display text-xl font-medium text-foreground"><?php echo html_escape($finish[0]); ?></span>
                                    <?php if (!empty($finish[1])) { ?>
                                        <span class="mt-1 block text-sm text-muted-foreground"><?php echo html_escape($finish[1]); ?></span>
                                    <?php } ?>
                                </span>
                            </figcaption>
                        </figure>
                    </li>
                <?php } ?>
            </ul>
        </section>
    <?php } ?>

    <?php if (!empty($gallery)) { ?>
        <?php
        $galleryTiles = array(
            array('class' => 'sm:col-span-2 lg:col-span-7', 'aspect' => 'aspect-4/3', 'width' => 1100),
            array('class' => 'sm:col-span-1 lg:col-span-5', 'aspect' => 'aspect-4/3', 'width' => 800),
            array('class' => 'sm:col-span-1 lg:col-span-5', 'aspect' => 'aspect-4/5', 'width' => 800),
            array('class' => 'sm:col-span-2 lg:col-span-7', 'aspect' => 'aspect-16/10', 'width' => 1100),
            array('class' => 'sm:col-span-1 lg:col-span-6', 'aspect' => 'aspect-4/5', 'width' => 900),
            array('class' => 'sm:col-span-1 lg:col-span-6', 'aspect' => 'aspect-4/5', 'width' => 900),
        );
        ?>
        <section class="py-20 md:py-26 lg:py-32 bg-background text-foreground" id="gallery" aria-labelledby="gallery-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl">
                        <?php if ($value($gallerySection, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($gallerySection['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="gallery-heading" class="mt-5 text-[clamp(2rem,5vw,3.25rem)] text-foreground"><?php echo html_escape($value($gallerySection, 'heading', 'Gallery')); ?></h2>
                        <?php if ($value($gallerySection, 'contents') !== '') { ?>
                            <p class="mt-5 leading-relaxed text-muted-foreground"><?php echo html_escape($gallerySection['contents']); ?></p>
                        <?php } ?>
                    </div>
                    <div class="shrink-0">
                        <a class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>" href="<?php echo html_escape(base_url('gallery')); ?>">
                            <?php echo html_escape($value($gallerySection, 'button_text', 'View gallery')); ?>
                            <i class="fa-solid fa-arrow-right size-4" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
                <ul class="mt-14 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:mt-18 lg:grid-cols-12 lg:gap-6">
                    <?php foreach ($gallery as $index => $photo) { ?>
                        <?php $tile = $galleryTiles[$index % count($galleryTiles)]; ?>
                        <li class="reveal group <?php echo $tile['class']; ?>"<?php echo $index % 3 > 0 ? ' style="transition-delay:'.(($index % 3) * 70).'ms"' : ''; ?>>
                            <a class="block" href="<?php echo html_escape(base_url('gallery')); ?>">
                                <figure>
                                    <div class="relative overflow-hidden rounded-xl bg-muted <?php echo $tile['aspect']; ?>">
                                        <img
                                            alt="<?php echo html_escape($photo['image_caption']); ?>"
                                            loading="lazy"
                                            decoding="async"
                                            class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
                                            src="<?php echo html_escape(upload_thumb('gallery', $photo['image_file'], $tile['width'], 0, 'images/no_image.jpg')); ?>"
                                        >
                                    </div>
                                    <figcaption class="mt-3 flex items-baseline justify-between gap-3">
                                        <span class="font-display text-lg text-foreground"><?php echo html_escape($photo['image_caption']); ?></span>
                                        <?php if (!empty($photo['category_name'])) { ?>
                                            <span class="shrink-0 text-sm <?php echo $index < 4 ? 'text-primary-ink' : 'text-muted-foreground'; ?>"><?php echo html_escape($photo['category_name']); ?></span>
                                        <?php } ?>
                                    </figcaption>
                                </figure>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php if ($value($about, 'heading') !== '') { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" id="about" aria-labelledby="about-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="grid gap-14 lg:grid-cols-12 lg:gap-16">
                    <div class="reveal lg:col-span-6">
                        <?php if ($value($about, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($about['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="about-heading" class="mt-5 text-[clamp(2rem,5vw,3.25rem)]"><?php echo html_escape($about['heading']); ?></h2>
                        <?php if ($value($about, 'contents') !== '') { ?>
                            <p class="mt-6 text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($about['contents']); ?></p>
                        <?php } ?>
                        <?php if ($value($about, 'point_1_title') !== '') { ?>
                            <dl class="mt-10 divide-y divide-border border-y border-border">
                                <?php for ($point = 1; $point <= 3; $point++) { ?>
                                    <?php if ($value($about, 'point_'.$point.'_title') === '') { continue; } ?>
                                    <div class="py-6">
                                        <dt class="font-display text-xl text-foreground"><?php echo html_escape($about['point_'.$point.'_title']); ?></dt>
                                        <dd class="mt-2 leading-relaxed text-muted-foreground"><?php echo html_escape($value($about, 'point_'.$point.'_text')); ?></dd>
                                    </div>
                                <?php } ?>
                            </dl>
                        <?php } ?>
                        <a class="link-underline mt-9 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-foreground" href="<?php echo html_escape(base_url('about')); ?>">
                            <?php echo html_escape($value($about, 'link_text', 'More about us')); ?>
                            <i class="fa-solid fa-arrow-right size-4 text-primary" aria-hidden="true"></i>
                        </a>
                    </div>
                    <?php if ($value($about, 'image_1') !== '' || $value($about, 'image_2') !== '') { ?>
                        <div class="reveal lg:col-span-6" style="transition-delay:80ms">
                            <div class="grid grid-cols-5 gap-4">
                                <?php if ($value($about, 'image_1') !== '') { ?>
                                    <figure class="relative col-span-3 aspect-3/4 overflow-hidden rounded-lg bg-muted">
                                        <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape($image($about['image_1'], 800)); ?>">
                                    </figure>
                                <?php } ?>
                                <?php if ($value($about, 'image_2') !== '') { ?>
                                    <figure class="relative col-span-2 mt-12 aspect-2/3 overflow-hidden rounded-lg bg-muted">
                                        <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape($image($about['image_2'], 600)); ?>">
                                    </figure>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if ($lead !== NULL) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-lilac text-foreground" id="artists" aria-labelledby="artists-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl">
                        <?php if ($value($artistsSection, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($artistsSection['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="artists-heading" class="mt-5 text-[clamp(2rem,5vw,3.25rem)] text-foreground"><?php echo html_escape($value($artistsSection, 'heading', 'Our artists')); ?></h2>
                        <?php if ($value($artistsSection, 'contents') !== '') { ?>
                            <p class="mt-5 leading-relaxed text-muted-foreground"><?php echo html_escape($artistsSection['contents']); ?></p>
                        <?php } ?>
                    </div>
                </div>
                <div class="mt-14 grid gap-x-10 gap-y-12 lg:mt-18 lg:grid-cols-12">
                    <div class="reveal lg:col-span-5">
                        <?php $this->load->view('frontend/partials/artist_card', array('artist' => $lead, 'variant' => 'lead')); ?>
                    </div>
                    <?php if (!empty($team)) { ?>
                        <div class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:col-span-6 lg:col-start-7 lg:pt-14">
                            <?php foreach (array_slice($team, 0, 2) as $index => $member) { ?>
                                <div class="reveal" style="transition-delay:<?php echo ($index + 1) * 90; ?>ms">
                                    <?php $this->load->view('frontend/partials/artist_card', array('artist' => $member, 'variant' => 'compact')); ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="reveal mt-14">
                    <a class="link-underline inline-flex min-h-11 items-center gap-2 text-sm font-medium text-foreground" href="<?php echo html_escape(base_url('artists')); ?>">
                        <?php echo html_escape($value($artistsSection, 'link_text', 'View all artists')); ?>
                        <i class="fa-solid fa-arrow-right size-4 text-primary" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if ($value($why, 'heading') !== '') { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="why-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-16">
                    <div class="reveal lg:col-span-4">
                        <?php if ($value($why, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($why['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="why-heading" class="mt-5 text-[clamp(1.9rem,4vw,2.75rem)]"><?php echo html_escape($why['heading']); ?></h2>
                    </div>
                    <ul class="grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:col-span-8">
                        <?php for ($item = 1, $shown = 0; $item <= 4; $item++) { ?>
                            <?php if ($value($why, 'item_'.$item.'_title') === '') { continue; } ?>
                            <li class="reveal"<?php echo $shown > 0 ? ' style="transition-delay:'.($shown * 60).'ms"' : ''; ?>>
                                <div class="flex gap-4">
                                    <span aria-hidden="true" class="grid size-12 shrink-0 place-items-center rounded-full bg-petal text-accent shadow-[var(--shadow-card)]">
                                        <?php if (frontend_icon_class($value($why, 'item_'.$item.'_icon')) !== '') { ?>
                                            <i class="<?php echo frontend_icon_class($why['item_'.$item.'_icon']); ?> size-5" aria-hidden="true"></i>
                                        <?php } ?>
                                    </span>
                                    <div>
                                        <h3 class="text-xl"><?php echo html_escape($why['item_'.$item.'_title']); ?></h3>
                                        <?php if ($value($why, 'item_'.$item.'_text') !== '') { ?>
                                            <p class="mt-1.5 text-muted-foreground"><?php echo html_escape($why['item_'.$item.'_text']); ?></p>
                                        <?php } ?>
                                    </div>
                                </div>
                            </li>
                            <?php $shown++; ?>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if ($value($booking, 'heading') !== '') { ?>
        <section aria-labelledby="booking-heading" class="bg-background py-16 md:py-20 lg:py-24">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-strong via-plum to-plum-deep px-6 py-12 text-background shadow-[var(--shadow-soft)] sm:px-10 md:py-16 lg:px-16">
                    <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-secondary/25 blur-2xl"></div>
                    <div class="relative grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-14">
                        <div class="lg:col-span-5">
                            <?php if ($value($booking, 'pre_heading') !== '') { ?>
                                <p class="section-label section-label--light"><?php echo html_escape($booking['pre_heading']); ?></p>
                            <?php } ?>
                            <h2 id="booking-heading" class="mt-5 text-[clamp(2rem,4.6vw,3.25rem)] text-background"><?php echo html_escape($booking['heading']); ?></h2>
                            <?php if ($value($booking, 'contents') !== '') { ?>
                                <p class="mt-5 max-w-md leading-relaxed text-rose-100/85"><?php echo html_escape($booking['contents']); ?></p>
                            <?php } ?>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a class="<?php echo html_escape(frontend_button_class('light', 'h-13 px-8')); ?>" href="<?php echo html_escape($site['book_url']); ?>">Book an appointment</a>
                                <?php if ($site['phone_href'] !== '') { ?>
                                    <a href="<?php echo html_escape($site['phone_href']); ?>" class="<?php echo html_escape(frontend_button_class('outline-light', 'h-13 px-8')); ?>">
                                        <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                                        <?php echo html_escape($site['phone']); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <ol class="grid gap-3 sm:grid-cols-2 lg:col-span-6 lg:col-start-7">
                            <?php for ($step = 1, $number = 1; $step <= 4; $step++) { ?>
                                <?php if ($value($booking, 'step_'.$step.'_title') === '') { continue; } ?>
                                <li class="rounded-lg bg-background/10 p-5 ring-1 ring-background/15 transition-colors duration-300 hover:bg-background/16">
                                    <span aria-hidden="true" class="grid size-8 place-items-center rounded-full bg-background/15 font-display text-sm font-semibold text-background"><?php echo $number++; ?></span>
                                    <span class="mt-4 block font-display text-lg font-medium text-background"><?php echo html_escape($booking['step_'.$step.'_title']); ?></span>
                                    <?php if ($value($booking, 'step_'.$step.'_text') !== '') { ?>
                                        <span class="mt-1 block text-sm text-rose-100/75"><?php echo html_escape($booking['step_'.$step.'_text']); ?></span>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($offers)) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" id="offers" aria-labelledby="offers-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <?php if ($value($offersSection, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($offersSection['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="offers-heading" class="mt-5 text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($value($offersSection, 'heading', 'Current offers')); ?></h2>
                    </div>
                    <a class="link-underline inline-flex min-h-11 items-center gap-2 text-sm font-medium text-foreground" href="<?php echo html_escape(base_url('offers')); ?>">
                        <?php echo html_escape($value($offersSection, 'link_text', 'View all offers')); ?>
                        <i class="fa-solid fa-arrow-right size-4 text-primary" aria-hidden="true"></i>
                    </a>
                </div>
                <ul class="mt-10">
                    <?php foreach ($offers as $index => $offer) { ?>
                        <?php
                        $offerPrice = frontend_price($offer['offer_price']);
                        $offerOldPrice = frontend_price($offer['offer_old_price']);
                        ?>
                        <li class="reveal border-t border-border last:border-b"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 70).'ms"' : ''; ?>>
                            <a
                                class="group -mx-4 grid grid-cols-1 items-baseline gap-x-8 gap-y-2 rounded-lg px-4 py-7 transition-colors duration-200 hover:bg-petal sm:grid-cols-12"
                                href="<?php echo html_escape(base_url('book').'?offer='.rawurlencode($offer['offer_slug'])); ?>"
                            >
                                <span class="sm:col-span-5">
                                    <span class="block font-display text-foreground transition-colors duration-200 group-hover:text-primary <?php echo $index === 0 ? 'text-2xl sm:text-3xl' : 'text-xl sm:text-2xl'; ?>"><?php echo html_escape($offer['offer_title']); ?></span>
                                    <?php if (trim((string) $offer['offer_label']) !== '') { ?>
                                        <span class="mt-1 block text-sm text-primary-ink"><?php echo html_escape($offer['offer_label']); ?></span>
                                    <?php } ?>
                                </span>
                                <span class="sm:col-span-5">
                                    <span class="block text-muted-foreground"><?php echo html_escape($offer['offer_summary']); ?></span>
                                    <?php if (trim((string) $offer['offer_duration_label']) !== '') { ?>
                                        <span class="mt-1 block text-sm text-foreground-soft"><?php echo html_escape($offer['offer_duration_label']); ?></span>
                                    <?php } ?>
                                </span>
                                <span class="flex items-baseline gap-3 sm:col-span-2 sm:justify-end">
                                    <?php if ($offerPrice !== '') { ?>
                                        <span class="font-display text-2xl text-foreground"><?php echo html_escape($offerPrice); ?></span>
                                    <?php } ?>
                                    <?php if ($offerOldPrice !== '') { ?>
                                        <span class="text-sm text-muted-foreground line-through"><span class="sr-only">Usually </span><?php echo html_escape($offerOldPrice); ?></span>
                                    <?php } ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($reviews)) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-lilac text-foreground relative overflow-hidden" aria-labelledby="testimonials-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div aria-hidden="true" class="pointer-events-none absolute -top-28 -right-24 -z-10 size-96 rounded-full bg-petal blur-[1px]"></div>
                <div class="reveal flex flex-col gap-6 md:justify-between md:flex-col md:items-center md:text-center">
                    <div class="max-w-2xl mx-auto">
                        <?php if ($value($testimonials, 'pre_heading') !== '') { ?>
                            <p class="section-label"><?php echo html_escape($testimonials['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="testimonials-heading" class="mt-5 text-[clamp(2rem,5vw,3.25rem)] text-foreground"><?php echo html_escape($value($testimonials, 'heading', 'What our clients say')); ?></h2>
                    </div>
                </div>
                <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3">
                    <?php foreach ($reviews as $index => $review) { ?>
                        <li class="reveal sm:last:col-span-2 lg:last:col-span-1"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 80).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/testimonial', array('review' => $review)); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" id="location" aria-labelledby="location-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                <div class="reveal lg:col-span-5">
                    <?php if ($value($location, 'pre_heading') !== '') { ?>
                        <p class="section-label"><?php echo html_escape($location['pre_heading']); ?></p>
                    <?php } ?>
                    <h2 id="location-heading" class="mt-5 text-[clamp(2rem,5vw,3rem)]"><?php echo html_escape($value($location, 'heading', 'Visit us')); ?></h2>
                    <?php $this->load->view('frontend/partials/visit_details'); ?>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>" href="<?php echo html_escape($site['book_url']); ?>">Book appointment</a>
                        <?php if ($site['phone_href'] !== '') { ?>
                            <a href="<?php echo html_escape($site['phone_href']); ?>" class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>">
                                <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                                Call us
                            </a>
                        <?php } ?>
                        <?php if ($site['map_url'] !== '') { ?>
                            <a href="<?php echo html_escape($site['map_url']); ?>" target="_blank" rel="noopener noreferrer" class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>">
                                <i class="fa-solid fa-location-arrow size-4" aria-hidden="true"></i>
                                Get directions
                            </a>
                        <?php } ?>
                    </div>
                </div>
                <div class="reveal lg:col-span-7" style="transition-delay:80ms">
                    <div class="card-soft relative h-full min-h-72 overflow-hidden">
                        <div aria-hidden="true" class="absolute inset-0 opacity-70 [background-image:linear-gradient(to_right,var(--color-border)_1px,transparent_1px),linear-gradient(to_bottom,var(--color-border)_1px,transparent_1px)] [background-size:44px_44px]"></div>
                        <div class="relative flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
                            <i class="fa-solid fa-location-dot size-8 text-primary" aria-hidden="true"></i>
                            <?php if (!empty($site['address_lines'])) { ?>
                                <p class="font-display text-2xl text-foreground"><?php echo html_escape($site['address_lines'][0]); ?></p>
                            <?php } ?>
                            <?php if (count($site['address_lines']) > 1 || $site['address_note'] !== '') { ?>
                                <p class="max-w-xs text-muted-foreground"><?php echo html_escape(trim(implode(', ', array_slice($site['address_lines'], 1)).($site['address_note'] !== '' ? ' — '.lcfirst($site['address_note']) : ''), ' —,')); ?></p>
                            <?php } ?>
                            <?php if ($site['map_url'] !== '') { ?>
                                <a href="<?php echo html_escape($site['map_url']); ?>" target="_blank" rel="noopener noreferrer" class="link-underline mt-2 inline-flex min-h-11 items-center text-sm font-medium text-foreground">Open in Maps</a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    $instagramUrl = '';
    foreach ($site['socials'] as $social) {
        if ($social['icon'] === 'fa-instagram') {
            $instagramUrl = $social['url'];
        }
    }
    $instagramImages = array();
    for ($photo = 1; $photo <= 6; $photo++) {
        if ($value($instagram, 'image_'.$photo) !== '') {
            $instagramImages[] = $instagram['image_'.$photo];
        }
    }
    ?>
    <?php if (!empty($instagramImages) && $value($instagram, 'heading') !== '') { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="instagram-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-3 sm:flex-row sm:items-baseline sm:justify-between">
                    <h2 id="instagram-heading" class="text-xl sm:text-2xl"><?php echo html_escape($instagram['heading']); ?></h2>
                    <?php if ($instagramUrl !== '' && $value($instagram, 'link_text') !== '') { ?>
                        <a href="<?php echo html_escape($instagramUrl); ?>" target="_blank" rel="noopener noreferrer" class="link-underline inline-flex min-h-11 items-center gap-2 self-start text-sm font-medium text-foreground">
                            <i class="fa-brands fa-instagram size-4 text-primary" aria-hidden="true"></i>
                            <?php echo html_escape($instagram['link_text']); ?>
                        </a>
                    <?php } ?>
                </div>
                <ul class="mt-6 grid grid-cols-3 gap-2 sm:gap-3 lg:grid-cols-6">
                    <?php foreach ($instagramImages as $file) { ?>
                        <li>
                            <?php if ($instagramUrl !== '') { ?>
                                <a href="<?php echo html_escape($instagramUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="Open Instagram" class="group relative block aspect-square overflow-hidden rounded-lg bg-muted">
                                    <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition-transform duration-500 ease-[var(--ease-out-soft)] group-hover:scale-[1.05]" src="<?php echo html_escape($image($file, 400)); ?>">
                                </a>
                            <?php } else { ?>
                                <div class="group relative block aspect-square overflow-hidden rounded-lg bg-muted">
                                    <img alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape($image($file, 400)); ?>">
                                </div>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="final-cta-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="reveal mx-auto max-w-3xl text-center">
                <?php if (!empty($finishColours)) { ?>
                    <ul class="flex items-center justify-center gap-2" aria-hidden="true">
                        <?php foreach ($finishColours as $colour) { ?>
                            <li class="size-3.5 rounded-full ring-1 ring-foreground/10" style="background-color:<?php echo html_escape($colour); ?>"></li>
                        <?php } ?>
                    </ul>
                <?php } ?>
                <h2 id="final-cta-heading" class="mt-8 text-[clamp(2.25rem,6vw,4rem)]"><?php echo html_escape($value($finalCta, 'heading', 'Book your visit to Blossom')); ?></h2>
                <?php if ($value($finalCta, 'contents') !== '') { ?>
                    <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($finalCta['contents']); ?></p>
                <?php } ?>
                <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                    <a class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>" href="<?php echo html_escape($site['book_url']); ?>">Book appointment</a>
                    <?php if ($site['phone_href'] !== '') { ?>
                        <a href="<?php echo html_escape($site['phone_href']); ?>" class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>">
                            <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                            <?php echo html_escape($site['phone']); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
</main>
