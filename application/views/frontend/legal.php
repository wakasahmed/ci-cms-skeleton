<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Legal page (/privacy-policy, /cancellation-policy, /terms): hero, optional
 * notice, a numbered contents list, the page text split at its headings and
 * a "Questions about this?" box.
 *
 * $hero      page hero data (see partials/page_hero.php)
 * $title     page name (the section's accessible name)
 * $body      frontend_html_sections() of the page text
 * $sections  Web Page Sections of the page ('notice', 'help')
 */
$notice = isset($sections['notice']['contents']) ? trim($sections['notice']['contents']) : '';
$help = isset($sections['help']) ? $sections['help'] : array();
$helpHeading = isset($help['heading']) && trim($help['heading']) !== '' ? $help['heading'] : 'Questions about this?';
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-label="<?php echo html_escape($title); ?>">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <?php if ($notice !== '') { ?>
                <div class="reveal mb-10 flex items-start gap-3 rounded-lg border border-border-strong bg-petal px-5 py-4">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true"></i>
                    <p class="text-sm leading-relaxed text-foreground-soft"><?php echo html_escape($notice); ?></p>
                </div>
            <?php } ?>
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                <?php if (!empty($body['sections'])) { ?>
                    <div class="reveal lg:col-span-3">
                        <nav aria-label="Contents" class="lg:sticky lg:top-28">
                            <h2 class="font-display text-lg text-foreground">Contents</h2>
                            <ol class="mt-4 space-y-1">
                                <?php foreach ($body['sections'] as $index => $section) { ?>
                                    <li>
                                        <a
                                            href="#<?php echo html_escape($section['id']); ?>"
                                            class="link-underline inline-flex min-h-11 items-start gap-2 text-sm text-foreground-soft transition-colors duration-200 hover:text-primary"
                                        >
                                            <span class="tabular-nums text-muted-foreground"><?php echo $index + 1; ?>.</span>
                                            <?php echo html_escape($section['title']); ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ol>
                        </nav>
                    </div>
                <?php } ?>
                <div class="<?php echo !empty($body['sections']) ? 'lg:col-span-9' : 'lg:col-span-12'; ?>">
                    <div class="max-w-2xl space-y-12">
                        <?php /* Rich text written by administrators in the CMS editor. */ ?>
                        <?php if ($body['intro'] !== '') { ?>
                            <div class="legal-body"><?php echo $body['intro']; ?></div>
                        <?php } ?>
                        <?php foreach ($body['sections'] as $index => $section) { ?>
                            <section id="<?php echo html_escape($section['id']); ?>" class="scroll-mt-28">
                                <h2 class="text-[clamp(1.35rem,2.8vw,1.75rem)] text-foreground">
                                    <span class="text-muted-foreground tabular-nums"><?php echo $index + 1; ?>.</span>
                                    <?php echo html_escape($section['title']); ?>
                                </h2>
                                <div class="legal-body"><?php echo $section['html']; ?></div>
                            </section>
                        <?php } ?>
                    </div>
                    <div class="mt-14 rounded-xl bg-petal p-6 sm:p-8">
                        <h2 class="font-display text-xl text-foreground"><?php echo html_escape($helpHeading); ?></h2>
                        <?php if (isset($help['contents']) && trim($help['contents']) !== '') { ?>
                            <p class="mt-2 text-muted-foreground"><?php echo html_escape($help['contents']); ?></p>
                        <?php } ?>
                        <?php if (!empty($site['address_lines'])) { ?>
                            <address class="mt-4 text-sm text-foreground-soft not-italic">
                                <?php echo html_escape($site['name']); ?><br>
                                <?php echo html_escape(implode(', ', $site['address_lines'])); ?>
                            </address>
                        <?php } ?>
                        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                            <?php if ($site['phone_href'] !== '') { ?>
                                <a href="<?php echo html_escape($site['phone_href']); ?>" class="<?php echo html_escape(frontend_button_class('outline', 'h-12 px-7')); ?>">
                                    <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                                    <?php echo html_escape($site['phone']); ?>
                                </a>
                            <?php } ?>
                            <a class="<?php echo html_escape(frontend_button_class('ghost', 'h-12 px-7')); ?>" href="<?php echo html_escape(base_url('contact')); ?>">Contact page</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
