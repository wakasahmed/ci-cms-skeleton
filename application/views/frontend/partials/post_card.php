<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Journal post card.
 *
 * $post     a post row from Blog_model (card columns and category)
 * $variant  'listing' (journal grid: meta, excerpt, reading time) or
 *           'related' (article page: category and title only)
 */
$url = base_url('blog/'.rawurlencode($post['blog_slug']));
$isListing = $variant === 'listing';
?>
<article class="group<?php echo $isListing ? ' flex h-full flex-col' : ''; ?>">
    <a class="block" href="<?php echo html_escape($url); ?>" tabindex="-1" aria-hidden="true">
        <div class="relative aspect-4/3 overflow-hidden rounded-xl bg-muted">
            <img
                alt=""
                loading="lazy"
                decoding="async"
                class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
                src="<?php echo html_escape(upload_thumb('blogs', $post['blog_image'], 640, 0, 'images/no_image.jpg')); ?>"
            >
        </div>
    </a>
    <?php if ($isListing) { ?>
        <?php $this->load->view('frontend/partials/post_meta', array('post' => $post, 'readTime' => FALSE, 'class' => 'mt-4')); ?>
        <h3 class="mt-2 text-xl text-foreground">
            <a class="link-underline" href="<?php echo html_escape($url); ?>"><?php echo html_escape($post['blog_name']); ?></a>
        </h3>
        <p class="mt-3 flex-1 leading-relaxed text-muted-foreground"><?php echo html_escape($post['blog_short_description']); ?></p>
        <p class="mt-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground">
            <i class="fa-regular fa-clock size-3.5" aria-hidden="true"></i>
            <?php echo (int) $post['blog_time_to_read']; ?> min read
        </p>
    <?php } else { ?>
        <?php if ($post['category_name'] !== '') { ?>
            <p class="mt-3 text-sm font-medium text-primary-ink"><?php echo html_escape($post['category_name']); ?></p>
        <?php } ?>
        <h3 class="mt-1.5 text-lg text-foreground">
            <a class="link-underline" href="<?php echo html_escape($url); ?>"><?php echo html_escape($post['blog_name']); ?></a>
        </h3>
    <?php } ?>
</article>
