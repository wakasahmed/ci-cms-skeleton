<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Journal (/blog): hero, the latest article, then the other articles with
 * category chips (?category= works without JavaScript; js/category-filter.js).
 *
 * $hero        page hero data (see partials/page_hero.php)
 * $sections    Web Page Sections of the Journal page, keyed by section
 * $lead        the latest published post, or NULL
 * $posts       the other published posts, newest first
 * $categories  chips: array('slug', 'name', 'count') for the categories in $posts
 * $category    the category slug requested with ?category=, or 'all'
 */
$articles = isset($sections['articles']) ? $sections['articles'] : array();
$ctaSection = isset($sections['cta']) ? $sections['cta'] : array();
$articlesHeading = isset($articles['heading']) && trim($articles['heading']) !== ''
    ? $articles['heading']
    : 'More from the journal';
$shown = 0;
foreach ($posts as $post) {
    if ($category === 'all' || $post['category_slug'] === $category) {
        $shown++;
    }
}
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <?php if ($lead === NULL) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-label="Articles">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <p class="text-muted-foreground">New articles are on their way.</p>
            </div>
        </section>
    <?php } else { ?>
        <?php $leadUrl = base_url('blog/'.rawurlencode($lead['blog_slug'])); ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="featured-post-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal">
                    <article class="group grid gap-8 lg:grid-cols-12 lg:items-center lg:gap-12">
                        <a class="block lg:col-span-7" href="<?php echo html_escape($leadUrl); ?>" tabindex="-1" aria-hidden="true">
                            <div class="relative aspect-16/10 overflow-hidden rounded-2xl bg-muted shadow-[var(--shadow-card)]">
                                <img
                                    alt=""
                                    decoding="async"
                                    class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.03]"
                                    src="<?php echo html_escape(upload_thumb('blogs', $lead['blog_image'], 1200, 0, 'images/no_image.jpg')); ?>"
                                >
                            </div>
                        </a>
                        <div class="lg:col-span-5">
                            <p class="section-label">Latest</p>
                            <?php $this->load->view('frontend/partials/post_meta', array('post' => $lead, 'readTime' => TRUE, 'class' => 'mt-4')); ?>
                            <h2 id="featured-post-heading" class="mt-3 text-[clamp(1.75rem,4vw,2.75rem)]">
                                <a class="link-underline" href="<?php echo html_escape($leadUrl); ?>"><?php echo html_escape($lead['blog_name']); ?></a>
                            </h2>
                            <p class="mt-4 text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($lead['blog_short_description']); ?></p>
                            <a class="<?php echo html_escape(frontend_button_class('outline', 'mt-7 h-12 px-7')); ?>" href="<?php echo html_escape($leadUrl); ?>">
                                Read the article<span class="sr-only">: <?php echo html_escape($lead['blog_name']); ?></span>
                                <i class="fa-solid fa-arrow-right size-4" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <?php if (!empty($posts)) { ?>
            <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="all-posts-heading" data-category-filter>
                <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                    <div class="reveal max-w-2xl">
                        <h2 id="all-posts-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($articlesHeading); ?></h2>
                    </div>
                    <div>
                        <?php if (count($categories) > 1) { ?>
                            <?php $this->load->view('frontend/partials/category_chips', array(
                                'chips' => array_merge(
                                    array(array('slug' => 'all', 'name' => 'All', 'count' => count($posts))),
                                    $categories
                                ),
                                'active' => $category,
                                'label' => 'Filter articles by category',
                                'noun' => 'articles',
                                'shown' => $shown,
                            )); ?>
                        <?php } ?>
                        <ul class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($posts as $index => $post) { ?>
                                <?php $visible = $category === 'all' || $post['category_slug'] === $category; ?>
                                <li
                                    class="reveal"
                                    data-filter-item="<?php echo html_escape($post['category_slug']); ?>"
                                    <?php echo $index % 3 > 0 ? 'style="transition-delay:'.(($index % 3) * 70).'ms"' : ''; ?>
                                    <?php echo $visible ? '' : 'hidden'; ?>
                                >
                                    <?php $this->load->view('frontend/partials/post_card', array('post' => $post, 'variant' => 'listing')); ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php if (isset($articles['note']) && trim($articles['note']) !== '') { ?>
                        <div class="reveal mt-12">
                            <p class="rounded-lg bg-background px-5 py-4 text-sm text-foreground-soft shadow-[var(--shadow-card)]"><?php echo html_escape($articles['note']); ?></p>
                        </div>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'variant' => 'inline',
        'heading' => isset($ctaSection['heading']) && trim($ctaSection['heading']) !== ''
            ? $ctaSection['heading']
            : 'Book your visit to Blossom',
        'text' => isset($ctaSection['contents']) ? $ctaSection['contents'] : '',
    ))); ?>
</main>
