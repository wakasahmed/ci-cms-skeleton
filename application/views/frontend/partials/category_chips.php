<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Category filter chips with counts, plus the screen-reader status line.
 * js/category-filter.js makes them filter the [data-filter-item] elements
 * inside the same [data-category-filter] container; without JavaScript the
 * page honours ?category= on the server.
 *
 * $chips    array of array('slug', 'name', 'count'); the first is usually
 *           array('slug' => 'all', ...)
 * $active   the selected slug
 * $label    accessible name of the chip group, e.g. "Filter gallery by category"
 * $noun     plural noun for the status line, e.g. "photos"
 * $shown    number of items shown for the selected slug
 */
$chipBase = 'inline-flex min-h-11 shrink-0 snap-start cursor-pointer items-center gap-2 rounded-full px-5 text-[0.95rem] font-medium'
    .' transition-[background-color,color,border-color] duration-200 ease-[var(--ease-out-soft)]'
    .' focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$chipActive = 'bg-primary-cta text-primary-foreground shadow-[var(--shadow-card)]';
$chipInactive = 'border border-border-strong bg-background text-foreground-soft hover:border-primary hover:bg-petal hover:text-primary';
$countActive = 'text-primary-foreground/75';
$countInactive = 'text-muted-foreground';
?>
<div role="radiogroup" aria-label="<?php echo html_escape($label); ?>" class="-mx-5 flex snap-x gap-2 overflow-x-auto px-5 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0">
    <?php foreach ($chips as $chip) { ?>
        <?php $isActive = $active === $chip['slug']; ?>
        <button
            type="button"
            role="radio"
            aria-checked="<?php echo $isActive ? 'true' : 'false'; ?>"
            data-filter-chip="<?php echo html_escape($chip['slug']); ?>"
            class="<?php echo $chipBase.' '.($isActive ? $chipActive : $chipInactive); ?>"
        >
            <?php echo html_escape($chip['name']); ?>
            <span class="text-sm tabular-nums <?php echo $isActive ? $countActive : $countInactive; ?>" data-filter-count><?php echo (int) $chip['count']; ?></span>
        </button>
    <?php } ?>
</div>
<p class="sr-only" role="status" data-filter-status data-filter-noun="<?php echo html_escape($noun); ?>"><?php echo (int) $shown; ?> <?php echo html_escape($noun); ?> shown</p>
