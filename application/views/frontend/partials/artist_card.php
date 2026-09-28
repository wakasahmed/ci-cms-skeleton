<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Artist card: portrait, name, role, specialties and the first paragraph of
 * the bio, linking to the artist's page.
 *
 * $artist   a row with artist_name, artist_slug, artist_role, artist_bio,
 *           artist_specialties and artist_image
 */
$url = base_url('artists/'.rawurlencode($artist['artist_slug']));
$specialties = frontend_lines($artist['artist_specialties']);
$bioParagraphs = preg_split('/\R\s*\R/', trim(strip_tags((string) $artist['artist_bio'])));
$intro = trim((string) $bioParagraphs[0]);
$portrait = upload_thumb('artists', $artist['artist_image'], 640, 0, 'images/no_image.jpg');
?>
<article class="group flex h-full flex-col">
    <a class="relative block overflow-hidden rounded-xl bg-muted aspect-4/5" href="<?php echo html_escape($url); ?>" tabindex="-1" aria-hidden="true">
        <img
            alt=""
            loading="lazy"
            decoding="async"
            class="absolute inset-0 size-full object-cover object-top transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
            src="<?php echo html_escape($portrait); ?>"
        >
    </a>
    <div class="mt-5 flex flex-1 flex-col">
        <h3 class="text-foreground text-2xl">
            <a class="link-underline" href="<?php echo html_escape($url); ?>"><?php echo html_escape($artist['artist_name']); ?></a>
        </h3>
        <?php if (trim((string) $artist['artist_role']) !== '') { ?>
            <p class="mt-2 text-sm text-foreground-soft"><?php echo html_escape($artist['artist_role']); ?></p>
        <?php } ?>
        <?php if (!empty($specialties)) { ?>
            <p class="mt-4 flex items-start gap-2.5 text-sm text-primary-ink">
                <span aria-hidden="true" class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"></span>
                <?php echo html_escape(implode(' · ', $specialties)); ?>
            </p>
        <?php } ?>
        <?php if ($intro !== '') { ?>
            <p class="mt-4 leading-relaxed text-muted-foreground"><?php echo html_escape($intro); ?></p>
        <?php } ?>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a class="link-underline inline-flex min-h-11 items-center text-sm font-medium text-foreground" href="<?php echo html_escape($url); ?>">View profile<span class="sr-only"> of <?php echo html_escape($artist['artist_name']); ?></span></a>
        </div>
    </div>
</article>
