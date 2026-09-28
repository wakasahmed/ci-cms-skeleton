<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Artists listing (/artists): the first artist as the lead profile, then
 * the rest of the team.
 *
 * $page       the Artists Web Pages record (banner heading and text)
 * $sections   Web Page Sections of the Artists page, keyed by section
 * $lead       the lead artist, or NULL
 * $team       the other artists
 */
$pageName = html_entity_decode((string) $page['page_name'], ENT_QUOTES, 'UTF-8');
$heading = trim((string) $page['banner_heading']) !== '' ? $page['banner_heading'] : $pageName;
$teamSection = isset($sections['team']) ? $sections['team'] : array();
$ctaSection = isset($sections['cta']) ? $sections['cta'] : array();
$hasPlaceholder = FALSE;
foreach ($team as $member) {
    $hasPlaceholder = $hasPlaceholder || (int) $member['artist_is_placeholder'] === 1;
}
if ($lead !== NULL) {
    $hasPlaceholder = $hasPlaceholder || (int) $lead['artist_is_placeholder'] === 1;
}
?>
<main id="main">
    <section
        <?php echo $lead !== NULL ? 'aria-labelledby="lead-artist-heading"' : 'aria-labelledby="page-hero-heading"'; ?>
        class="relative isolate overflow-hidden pt-28 pb-18 sm:pt-32 md:pb-22 lg:pt-36 lg:pb-26"
    >
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-petal via-lilac to-background"></div>
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="reveal max-w-2xl">
                <?php $this->load->view('frontend/partials/breadcrumb', array('crumbs' => array(
                    array('label' => 'Home', 'url' => base_url()),
                    array('label' => $pageName),
                ))); ?>
                <h1 id="page-hero-heading" class="text-[clamp(2.25rem,5.4vw,3.75rem)] text-foreground"><?php echo html_escape($heading); ?></h1>
                <?php if (trim((string) $page['banner_text']) !== '') { ?>
                    <p class="mt-5 text-lg leading-relaxed text-muted-foreground"><?php echo html_escape($this->frontend_seo->plainText($page['banner_text'])); ?></p>
                <?php } ?>
            </div>

            <?php if ($lead !== NULL) { ?>
                <?php
                $leadUrl = base_url('artists/'.rawurlencode($lead['artist_slug']));
                $leadFirstName = strtok($lead['artist_name'], ' ');
                ?>
                <div class="mt-14 grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-16">
                    <div class="reveal lg:col-span-5">
                        <div class="relative aspect-3/4 overflow-hidden rounded-2xl bg-muted shadow-[var(--shadow-soft)]">
                            <img
                                alt="Portrait of <?php echo html_escape($lead['artist_name']); ?>"
                                decoding="async"
                                class="absolute inset-0 size-full object-cover object-top"
                                src="<?php echo html_escape(upload_thumb('artists', $lead['artist_image'], 900, 0, 'images/no_image.jpg')); ?>"
                            >
                        </div>
                    </div>
                    <div class="reveal lg:col-span-7" style="transition-delay:80ms">
                        <?php if (trim((string) $lead['artist_role']) !== '') { ?>
                            <p class="section-label"><?php echo html_escape($lead['artist_role']); ?></p>
                        <?php } ?>
                        <h2 id="lead-artist-heading" class="mt-4 text-[clamp(2rem,4.6vw,3.25rem)]"><?php echo html_escape($lead['artist_name']); ?></h2>
                        <?php foreach (preg_split('/\R\s*\R/', trim((string) $lead['artist_bio'])) as $paragraph) { ?>
                            <?php if (trim($paragraph) !== '') { ?>
                                <p class="mt-5 text-lg leading-relaxed text-muted-foreground"><?php echo html_escape(trim($paragraph)); ?></p>
                            <?php } ?>
                        <?php } ?>
                        <?php $specialties = frontend_lines($lead['artist_specialties']); ?>
                        <?php if (!empty($specialties)) { ?>
                            <ul class="mt-7 flex flex-wrap gap-2">
                                <?php foreach ($specialties as $specialty) { ?>
                                    <li class="chip"><?php echo html_escape($specialty); ?></li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a
                                class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>"
                                href="<?php echo html_escape(base_url('book').'?artist='.rawurlencode($lead['artist_slug'])); ?>"
                            >Book with <?php echo html_escape($leadFirstName); ?></a>
                            <a
                                class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>"
                                href="<?php echo html_escape($leadUrl); ?>"
                            >View profile<span class="sr-only"> of <?php echo html_escape($lead['artist_name']); ?></span></a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <?php if (!empty($team)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="team-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-2xl">
                    <h2 id="team-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape(!empty($teamSection['heading']) ? $teamSection['heading'] : 'Also at the studio'); ?></h2>
                    <?php if (!empty($teamSection['contents'])) { ?>
                        <p class="mt-4 text-muted-foreground"><?php echo html_escape($teamSection['contents']); ?></p>
                    <?php } ?>
                </div>
                <ul class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2">
                    <?php foreach ($team as $index => $member) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 80).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/artist_card', array('artist' => $member, 'variant' => 'team')); ?>
                        </li>
                    <?php } ?>
                </ul>
                <?php if ($hasPlaceholder && !empty($teamSection['placeholder_note'])) { ?>
                    <div class="reveal mt-12">
                        <p class="rounded-lg bg-background px-5 py-4 text-sm text-foreground-soft shadow-[var(--shadow-card)]"><?php echo html_escape($teamSection['placeholder_note']); ?></p>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'variant' => 'inline',
        'heading' => !empty($ctaSection['heading']) ? $ctaSection['heading'] : 'Book your visit to Blossom',
        'text' => !empty($ctaSection['contents']) ? $ctaSection['contents'] : '',
    ))); ?>
</main>
