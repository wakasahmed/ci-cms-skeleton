<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Journal post meta line: category, publish date and (optionally) reading time.
 *
 * $post      a post row with category_name, blog_pdate, blog_added and
 *            blog_time_to_read
 * $readTime  TRUE to include the reading time
 * $class     extra classes, e.g. spacing (optional)
 */
$published = $post['blog_pdate'] !== NULL && $post['blog_pdate'] !== '' ? $post['blog_pdate'] : $post['blog_added'];
$timestamp = strtotime($published);
?>
<p class="<?php echo isset($class) && $class !== '' ? $class.' ' : ''; ?>flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
    <?php if ($post['category_name'] !== '') { ?>
        <span class="font-medium text-primary-ink"><?php echo html_escape($post['category_name']); ?></span>
    <?php } ?>
    <span class="text-muted-foreground"><time datetime="<?php echo date('Y-m-d', $timestamp); ?>"><?php echo date('j F Y', $timestamp); ?></time></span>
    <?php if (!empty($readTime)) { ?>
        <span class="inline-flex items-center gap-1.5 text-muted-foreground">
            <i class="fa-regular fa-clock size-3.5" aria-hidden="true"></i>
            <?php echo (int) $post['blog_time_to_read']; ?> min read
        </span>
    <?php } ?>
</p>
