<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * FAQ (/faq): hero, a sticky "On this page" list with a phone box, the
 * question groups as <details> accordions (one open per group), a links band
 * and the call to action.
 *
 * $hero      page hero data (see partials/page_hero.php)
 * $sections  Web Page Sections of the FAQ page, keyed by section
 * $groups    FAQ groups from Faq_model::get_faq_groups()
 */
$sidebar = isset($sections['sidebar']) ? $sections['sidebar'] : array();
$questions = isset($sections['questions']) ? $sections['questions'] : array();
$links = isset($sections['links']) ? $sections['links'] : array();
$ctaSection = isset($sections['cta']) ? $sections['cta'] : array();
$value = function (array $fields, $key, $default = '') {
    return isset($fields[$key]) && trim((string) $fields[$key]) !== ''
        ? $fields[$key]
        : $default;
};

// Anchor ids for the "On this page" links, from the category names.
$anchors = array();
foreach ($groups as $group) {
    $anchor = url_title($group['title'], '-', TRUE);
    $anchors[$group['id']] = $anchor !== '' && !in_array($anchor, $anchors, TRUE)
        ? $anchor
        : 'faq-group-'.(int) $group['id'];
}
?>
<main id="main">

    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-label="Frequently asked questions">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <?php if (empty($groups)) { ?>
                <p class="text-muted-foreground">Questions and answers are on their way. In the meantime, give us a call.</p>
            <?php } else { ?>
                <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                    <div class="reveal lg:col-span-3">
                        <nav aria-label="FAQ sections" class="lg:sticky lg:top-28">
                            <h2 class="font-display text-lg text-foreground"><?php echo html_escape($value($sidebar, 'heading', 'On this page')); ?></h2>
                            <ul class="mt-4 space-y-1">
                                <?php foreach ($groups as $group) { ?>
                                    <li>
                                        <a
                                            href="#<?php echo html_escape($anchors[$group['id']]); ?>"
                                            class="link-underline inline-flex min-h-11 items-center text-foreground-soft transition-colors duration-200 hover:text-primary"
                                        ><?php echo html_escape($group['title']); ?></a>
                                    </li>
                                <?php } ?>
                            </ul>
                            <?php if ($value($sidebar, 'help_heading') !== '' && $site['phone_href'] !== '') { ?>
                                <div class="mt-8 rounded-lg bg-petal p-5">
                                    <p class="font-display text-lg text-foreground"><?php echo html_escape($sidebar['help_heading']); ?></p>
                                    <?php if ($value($sidebar, 'help_text') !== '') { ?>
                                        <p class="mt-2 text-sm text-muted-foreground"><?php echo html_escape($sidebar['help_text']); ?></p>
                                    <?php } ?>
                                    <a href="<?php echo html_escape($site['phone_href']); ?>" class="<?php echo html_escape(frontend_button_class('outline', 'mt-4 h-11 px-5')); ?>">
                                        <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                                        <?php echo html_escape($site['phone']); ?>
                                    </a>
                                </div>
                            <?php } ?>
                        </nav>
                    </div>
                    <div class="space-y-14 lg:col-span-9">
                        <?php foreach ($groups as $group) { ?>
                            <?php $anchor = $anchors[$group['id']]; ?>
                            <section class="reveal" id="<?php echo html_escape($anchor); ?>" aria-labelledby="<?php echo html_escape($anchor); ?>-heading">
                                <h2 id="<?php echo html_escape($anchor); ?>-heading" class="text-[clamp(1.5rem,3.2vw,2rem)] text-foreground"><?php echo html_escape($group['title']); ?></h2>
                                <?php if (trim((string) $group['description']) !== '') { ?>
                                    <p class="mt-2 text-muted-foreground"><?php echo html_escape($group['description']); ?></p>
                                <?php } ?>
                                <div class="mt-6 divide-y divide-border border-y border-border">
                                    <?php foreach ($group['items'] as $item) { ?>
                                        <details name="faq-<?php echo html_escape($anchor); ?>" class="group">
                                            <summary class="flex min-h-14 cursor-pointer list-none items-start justify-between gap-5 py-5 text-left [&amp;::-webkit-details-marker]:hidden focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                <span class="font-display text-lg text-foreground transition-colors duration-200 group-hover:text-primary sm:text-xl"><?php echo html_escape($item['question']); ?></span>
                                                <span aria-hidden="true" class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-petal text-primary transition-transform duration-300 ease-[var(--ease-out-soft)] group-open:rotate-45">
                                                    <i class="fa-solid fa-plus size-4" aria-hidden="true"></i>
                                                </span>
                                            </summary>
                                            <div class="pb-6 text-muted-foreground">
                                                <div class="max-w-2xl leading-relaxed"><?php echo nl2br(html_escape($item['answer'])); ?></div>
                                            </div>
                                        </details>
                                    <?php } ?>
                                </div>
                            </section>
                        <?php } ?>
                        <?php if ($value($questions, 'note') !== '') { ?>
                            <div class="reveal">
                                <p class="rounded-lg bg-lilac px-5 py-4 text-sm text-foreground-soft"><?php echo html_escape($questions['note']); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <?php if ($value($links, 'heading') !== '') { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-petal text-foreground" aria-labelledby="faq-links-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-xl">
                        <h2 id="faq-links-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]"><?php echo html_escape($links['heading']); ?></h2>
                        <?php if ($value($links, 'contents') !== '') { ?>
                            <p class="mt-3 text-muted-foreground"><?php echo html_escape($links['contents']); ?></p>
                        <?php } ?>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <?php if ($value($links, 'button_1_text') !== '') { ?>
                            <a
                                class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>"
                                href="<?php echo html_escape(frontend_url($value($links, 'button_1_url', 'services'))); ?>"
                            ><?php echo html_escape($links['button_1_text']); ?></a>
                        <?php } ?>
                        <?php if ($value($links, 'button_2_text') !== '') { ?>
                            <a
                                class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>"
                                href="<?php echo html_escape(frontend_url($value($links, 'button_2_url', 'cancellation-policy'))); ?>"
                            ><?php echo html_escape($links['button_2_text']); ?></a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'variant' => 'inline',
        'heading' => $value($ctaSection, 'heading', 'Book your visit to Blossom'),
        'text' => $value($ctaSection, 'contents'),
    ))); ?>
</main>
