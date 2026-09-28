<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Journal article (/blog/{slug}).
 *
 * $post       the post, with its primary category and related service
 *             (service_slug is NULL when there is none or it is disabled)
 * $crumbs     breadcrumb (see partials/breadcrumb.php)
 * $image      article image URL
 * $published  publish date (Y-m-d H:i:s)
 * $more       other recent posts
 * $cta        the Journal page's Call to Action section
 */
$hasService = !empty($post['service_slug']);
?>
<main id="main">
    <article>
        <header class="relative isolate overflow-hidden">
            <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-full bg-gradient-to-b from-petal via-lilac to-background"></div>
            <div class="mx-auto w-full max-w-3xl px-5 pt-28 pb-10 sm:px-8 sm:pt-32 lg:pt-36">
                <?php $this->load->view('frontend/partials/breadcrumb', array('crumbs' => $crumbs, 'light' => FALSE)); ?>
                <?php $this->load->view('frontend/partials/post_meta', array('post' => $post, 'readTime' => TRUE, 'class' => '')); ?>
                <h1 class="mt-4 text-[clamp(2rem,4.8vw,3.25rem)] text-foreground"><?php echo html_escape($post['blog_name']); ?></h1>
                <?php if (trim((string) $post['blog_short_description']) !== '') { ?>
                    <p class="mt-5 text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($post['blog_short_description']); ?></p>
                <?php } ?>
            </div>
            <div class="mx-auto w-full max-w-4xl px-5 sm:px-8">
                <div class="relative aspect-16/9 overflow-hidden rounded-2xl bg-muted shadow-[var(--shadow-soft)]">
                    <img alt="" decoding="async" class="absolute inset-0 size-full object-cover" src="<?php echo html_escape($image); ?>">
                </div>
            </div>
        </header>
        <div class="mx-auto w-full max-w-3xl px-5 py-14 sm:px-8 lg:py-18">
            <?php /* Rich text written by administrators in the CMS editor. */ ?>
            <div class="article-body"><?php echo $post['blog_text']; ?></div>

            <?php if ($hasService) { ?>
                <?php $price = frontend_service_price($post); ?>
                <aside class="mt-14 rounded-xl bg-petal p-6 sm:p-8" aria-labelledby="related-service-heading">
                    <p class="section-label">Related service</p>
                    <h2 id="related-service-heading" class="mt-4 font-display text-2xl text-foreground"><?php echo html_escape($post['service_name']); ?></h2>
                    <?php if (trim((string) $post['service_summary']) !== '') { ?>
                        <p class="mt-3 leading-relaxed text-muted-foreground"><?php echo html_escape($this->frontend_seo->plainText($post['service_summary'])); ?></p>
                    <?php } ?>
                    <?php
                    $facts = array_filter(array($price, trim((string) $post['service_duration_label'])));
                    ?>
                    <?php if (!empty($facts)) { ?>
                        <p class="mt-4 text-sm text-foreground-soft"><?php echo html_escape(implode(' · ', $facts)); ?></p>
                    <?php } ?>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <a
                            class="<?php echo html_escape(frontend_button_class('primary', 'h-12 px-7')); ?>"
                            href="<?php echo html_escape(base_url('book').'?service='.rawurlencode($post['service_slug'])); ?>"
                        >Book <?php echo html_escape(mb_strtolower($post['service_name'], 'UTF-8')); ?></a>
                        <a
                            class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>"
                            href="<?php echo html_escape(base_url('services/'.rawurlencode($post['service_slug']))); ?>"
                        >Service details<span class="sr-only">: <?php echo html_escape($post['service_name']); ?></span></a>
                    </div>
                </aside>
            <?php } ?>
        </div>
    </article>

    <?php if (!empty($more)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="related-posts-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <h2 id="related-posts-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]">More from the journal</h2>
                    <a class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>" href="<?php echo html_escape(base_url('blog')); ?>">
                        All articles
                        <i class="fa-solid fa-arrow-right size-4" aria-hidden="true"></i>
                    </a>
                </div>
                <ul class="mt-8 grid gap-x-8 gap-y-10 sm:grid-cols-3">
                    <?php foreach ($more as $index => $morePost) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 70).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/post_card', array('post' => $morePost, 'variant' => 'related')); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'variant' => 'inline',
        'heading' => isset($cta['heading']) && trim($cta['heading']) !== '' ? $cta['heading'] : 'Book your visit to Blossom',
        'text' => isset($cta['contents']) ? $cta['contents'] : '',
    ))); ?>
</main>
