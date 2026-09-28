<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Artist card: portrait, name, role, specialties and a short introduction,
 * linking to the artist's page.
 *
 * $artist    a row with artist_name, artist_slug, artist_role, artist_bio,
 *            artist_specialties and artist_image (plus service_count for 'team')
 * $variant   'compact' (service pages: specialties as one line) or 'team'
 *            (Artists page: specialty chips, service count, booking buttons)
 */
$variant = isset($variant) && $variant === 'team' ? 'team' : 'compact';
$url = base_url('artists/'.rawurlencode($artist['artist_slug']));
$specialties = frontend_lines($artist['artist_specialties']);
$intro = frontend_intro($artist['artist_bio']);
$portrait = upload_thumb('artists', $artist['artist_image'], 640, 0, 'images/no_image.jpg');
?>
<article class="group flex h-full flex-col">
    <a class="relative block aspect-4/5 overflow-hidden rounded-xl bg-muted" href="<?php echo html_escape($url); ?>" tabindex="-1" aria-hidden="true">
        <img
            alt=""
            loading="lazy"
            decoding="async"
            class="absolute inset-0 size-full object-cover object-top transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.04]"
            src="<?php echo html_escape($portrait); ?>"
        >
    </a>
    <div class="mt-5 flex flex-1 flex-col">
        <h3 class="text-2xl text-foreground">
            <a class="link-underline" href="<?php echo html_escape($url); ?>"><?php echo html_escape($artist['artist_name']); ?></a>
        </h3>
        <?php if (trim((string) $artist['artist_role']) !== '') { ?>
            <p class="<?php echo $variant === 'team' ? 'mt-1.5' : 'mt-2'; ?> text-sm text-foreground-soft"><?php echo html_escape($artist['artist_role']); ?></p>
        <?php } ?>

        <?php if (!empty($specialties)) { ?>
            <?php if ($variant === 'team') { ?>
                <ul class="mt-4 flex flex-wrap gap-2">
                    <?php foreach ($specialties as $specialty) { ?>
                        <li class="chip"><?php echo html_escape($specialty); ?></li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="mt-4 flex items-start gap-2.5 text-sm text-primary-ink">
                    <span aria-hidden="true" class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"></span>
                    <?php echo html_escape(implode(' · ', $specialties)); ?>
                </p>
            <?php } ?>
        <?php } ?>

        <?php if ($intro !== '') { ?>
            <p class="mt-4 leading-relaxed text-muted-foreground"><?php echo html_escape($intro); ?></p>
        <?php } ?>

        <?php if ($variant === 'team') { ?>
            <?php if (!empty($artist['service_count'])) { ?>
                <p class="mt-3 text-sm text-muted-foreground"><?php echo (int) $artist['service_count']; ?> <?php echo (int) $artist['service_count'] === 1 ? 'service' : 'services'; ?></p>
            <?php } ?>
            <div class="mt-6 flex flex-wrap gap-3">
                <a
                    class="<?php echo html_escape(frontend_button_class('soft', 'h-11 px-5')); ?>"
                    href="<?php echo html_escape(base_url('book').'?artist='.rawurlencode($artist['artist_slug'])); ?>"
                >Book with them<span class="sr-only"> (<?php echo html_escape($artist['artist_name']); ?>)</span></a>
                <a
                    class="<?php echo html_escape(frontend_button_class('ghost', 'h-11 px-5')); ?>"
                    href="<?php echo html_escape($url); ?>"
                >View profile<span class="sr-only"> of <?php echo html_escape($artist['artist_name']); ?></span></a>
            </div>
        <?php } else { ?>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a class="link-underline inline-flex min-h-11 items-center text-sm font-medium text-foreground" href="<?php echo html_escape($url); ?>">View profile<span class="sr-only"> of <?php echo html_escape($artist['artist_name']); ?></span></a>
            </div>
        <?php } ?>
    </div>
</article>
