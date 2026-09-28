<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Customer review card: star rating, quote, initial, name and caption.
 *
 * $review   a row from Review_model (review_name, review_desc,
 *           review_caption, review_rating)
 */
$rating = max(0, min(5, (int) $review['review_rating']));
$name = trim((string) $review['review_name']);
?>
<figure class="card-soft flex h-full flex-col p-7 transition-transform duration-300 ease-[var(--ease-out-soft)] hover:-translate-y-1 sm:p-8">
    <?php if ($rating > 0) { ?>
        <div class="flex items-center gap-1" role="img" aria-label="Rated <?php echo $rating; ?> out of 5">
            <?php for ($star = 0; $star < $rating; $star++) { ?>
                <i class="fa-regular fa-star size-4 fill-accent text-accent" aria-hidden="true"></i>
            <?php } ?>
        </div>
    <?php } ?>
    <blockquote class="mt-5 flex-1 text-lg leading-relaxed text-foreground">
        <p><?php echo nl2br(html_escape(trim(strip_tags((string) $review['review_desc'])))); ?></p>
    </blockquote>
    <figcaption class="mt-6 flex items-center gap-3">
        <span aria-hidden="true" class="grid size-10 shrink-0 place-items-center rounded-full bg-petal font-display text-lg font-medium text-primary ring-1 ring-rose-200"><?php echo html_escape(mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
        <span class="text-sm">
            <span class="block font-semibold text-foreground"><?php echo html_escape($name); ?></span>
            <?php if (trim((string) $review['review_caption']) !== '') { ?>
                <span class="block text-muted-foreground"><?php echo html_escape($review['review_caption']); ?></span>
            <?php } ?>
        </span>
    </figcaption>
</figure>
