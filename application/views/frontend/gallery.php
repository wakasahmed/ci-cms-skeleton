<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Gallery (/gallery): image hero, category chips (?category= works without
 * JavaScript), a mosaic grid and a PhotoSwipe lightbox (js/gallery.js).
 *
 * $page        the Gallery Web Pages record (banner fields)
 * $sections    Web Page Sections of the Gallery page, keyed by section
 * $images      public images in gallery order, each with 'full' (url,
 *              width, height) for the lightbox
 * $categories  categories with image counts
 * $category    the category slug requested with ?category=, or 'all'
 */
$pageName = html_entity_decode((string) $page['page_name'], ENT_QUOTES, 'UTF-8');
$heading = trim((string) $page['banner_heading']) !== '' ? $page['banner_heading'] : $pageName;
$heroLink = isset($sections['hero_link']) ? $sections['hero_link'] : array();
$ctaSection = isset($sections['cta']) ? $sections['cta'] : array();
$heroImage = trim((string) $page['banner_background']);

// The reference's mosaic: six tiles that repeat down the page.
$tiles = array(
    array('class' => 'sm:col-span-2 lg:col-span-7 aspect-4/3', 'width' => 1200),
    array('class' => 'sm:col-span-1 lg:col-span-5 aspect-4/5', 'width' => 900),
    array('class' => 'sm:col-span-1 lg:col-span-4 aspect-square', 'width' => 800),
    array('class' => 'sm:col-span-1 lg:col-span-4 aspect-square', 'width' => 800),
    array('class' => 'sm:col-span-2 lg:col-span-4 aspect-4/5', 'width' => 800),
    array('class' => 'sm:col-span-2 lg:col-span-12 aspect-16/9', 'width' => 1600),
);
$chipBase = 'inline-flex min-h-11 shrink-0 snap-start cursor-pointer items-center gap-2 rounded-full px-5 text-[0.95rem] font-medium'
    .' transition-[background-color,color,border-color] duration-200 ease-[var(--ease-out-soft)]'
    .' focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$chipActive = 'bg-primary-cta text-primary-foreground shadow-[var(--shadow-card)]';
$chipInactive = 'border border-border-strong bg-background text-foreground-soft hover:border-primary hover:bg-petal hover:text-primary';
$countActive = 'text-primary-foreground/75';
$countInactive = 'text-muted-foreground';
$shown = 0;
foreach ($images as $image) {
    if ($category === 'all' || $image['category_slug'] === $category) {
        $shown++;
    }
}
?>
<main id="main">
    <section aria-labelledby="gallery-heading" class="relative isolate overflow-hidden">
        <div class="absolute inset-0 -z-10">
            <?php if ($heroImage !== '') { ?>
                <img
                    alt=""
                    decoding="async"
                    class="absolute inset-0 size-full object-cover object-[75%_center]"
                    src="<?php echo html_escape(upload_thumb('pages', $heroImage, 1920, 0, 'images/no_image.jpg')); ?>"
                >
            <?php } ?>
            <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-r from-plum-deeper/95 via-plum-deep/80 to-plum-deep/45"></div>
            <div aria-hidden="true" class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-plum-deeper/85 to-transparent"></div>
        </div>
        <div class="mx-auto w-full max-w-[86rem] px-5 pt-32 pb-16 sm:px-8 sm:pt-36 lg:px-12 lg:pt-40 lg:pb-20">
            <?php $this->load->view('frontend/partials/breadcrumb', array(
                'crumbs' => array(
                    array('label' => 'Home', 'url' => base_url()),
                    array('label' => $pageName),
                ),
                'light' => TRUE,
            )); ?>
            <?php if (trim((string) $page['banner_title']) !== '') { ?>
                <p class="section-label section-label--light"><?php echo html_escape($page['banner_title']); ?></p>
            <?php } ?>
            <h1 id="gallery-heading" class="mt-4 max-w-2xl text-[clamp(2.25rem,5.4vw,3.75rem)] text-background"><?php echo html_escape($heading); ?></h1>
            <?php if (trim((string) $page['banner_text']) !== '') { ?>
                <p class="mt-5 max-w-lg text-lg leading-relaxed text-rose-100/85"><?php echo html_escape($this->frontend_seo->plainText($page['banner_text'])); ?></p>
            <?php } ?>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a
                    class="<?php echo html_escape(frontend_button_class('light', 'h-13 px-8')); ?>"
                    href="<?php echo html_escape($site['book_url']); ?>"
                >Book an appointment</a>
                <?php if (!empty($heroLink['button_text']) && !empty($heroLink['button_url'])) { ?>
                    <a
                        class="<?php echo html_escape(frontend_button_class('outline-light', 'h-13 px-8')); ?>"
                        href="<?php echo html_escape(frontend_url($heroLink['button_url'])); ?>"
                    ><?php echo html_escape($heroLink['button_text']); ?></a>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-label="Gallery" data-gallery>
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <?php if (empty($images)) { ?>
                <p class="text-muted-foreground">New photos are on their way.</p>
            <?php } else { ?>
                <div role="radiogroup" aria-label="Filter gallery by category" class="-mx-5 flex snap-x gap-2 overflow-x-auto px-5 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0">
                    <?php
                    $chips = array(array('slug' => 'all', 'name' => 'All', 'count' => count($images)));
                    foreach ($categories as $group) {
                        $chips[] = array(
                            'slug' => $group['category_slug'],
                            'name' => $group['category_name'],
                            'count' => (int) $group['image_count'],
                        );
                    }
                    ?>
                    <?php foreach ($chips as $chip) { ?>
                        <?php $active = $category === $chip['slug']; ?>
                        <button
                            type="button"
                            role="radio"
                            aria-checked="<?php echo $active ? 'true' : 'false'; ?>"
                            data-gallery-filter="<?php echo html_escape($chip['slug']); ?>"
                            class="<?php echo $chipBase.' '.($active ? $chipActive : $chipInactive); ?>"
                        >
                            <?php echo html_escape($chip['name']); ?>
                            <span class="text-sm tabular-nums <?php echo $active ? $countActive : $countInactive; ?>" data-gallery-count><?php echo (int) $chip['count']; ?></span>
                        </button>
                    <?php } ?>
                </div>
                <p class="sr-only" role="status" data-gallery-status><?php echo (int) $shown; ?> photos shown</p>

                <ul class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:mt-12 lg:grid-cols-12 lg:gap-5">
                    <?php foreach ($images as $index => $image) { ?>
                        <?php
                        $tile = $tiles[$index % count($tiles)];
                        $caption = trim((string) $image['image_caption']);
                        $visible = $category === 'all' || $image['category_slug'] === $category;
                        ?>
                        <li
                            class="reveal group <?php echo $tile['class']; ?>"
                            data-gallery-category="<?php echo html_escape((string) $image['category_slug']); ?>"
                            <?php echo $index > 0 ? 'style="transition-delay:'.(($index % 6) * 60).'ms"' : ''; ?>
                            <?php echo $visible ? '' : 'hidden'; ?>
                        >
                            <a
                                href="<?php echo html_escape($image['full']['url']); ?>"
                                data-pswp-width="<?php echo (int) $image['full']['width']; ?>"
                                data-pswp-height="<?php echo (int) $image['full']['height']; ?>"
                                aria-label="View larger: <?php echo html_escape($caption !== '' ? $caption : 'photo '.($index + 1)); ?>"
                                class="relative block size-full cursor-pointer overflow-hidden rounded-xl bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                            >
                                <img
                                    alt="<?php echo html_escape($caption); ?>"
                                    loading="<?php echo $index < 2 ? 'eager' : 'lazy'; ?>"
                                    decoding="async"
                                    class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
                                    src="<?php echo html_escape(upload_thumb('gallery', $image['image_file'], $tile['width'], 0, 'images/no_image.jpg')); ?>"
                                >
                                <?php if ($caption !== '' || !empty($image['category_name'])) { ?>
                                    <span class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-gradient-to-t from-plum-deeper/75 to-transparent p-4 pt-10 text-left opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-focus-visible:opacity-100">
                                        <span class="font-display text-base text-background"><?php echo html_escape($caption); ?></span>
                                        <?php if (!empty($image['category_name'])) { ?>
                                            <span class="shrink-0 text-xs text-rose-100/85"><?php echo html_escape($image['category_name']); ?></span>
                                        <?php } ?>
                                    </span>
                                <?php } ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </section>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'variant' => 'inline',
        'heading' => !empty($ctaSection['heading']) ? $ctaSection['heading'] : 'Book your visit to Blossom',
        'text' => !empty($ctaSection['contents']) ? $ctaSection['contents'] : '',
    ))); ?>
</main>
