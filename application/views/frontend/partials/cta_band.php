<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * "Book your visit" call to action near the end of a page.
 *
 * $cta keys:
 *   variant      'card' (centred gradient card, the default) or 'inline'
 *                (heading and buttons in a row on a light wash)
 *   heading      heading text
 *   text         supporting sentence (optional)
 *   book_url     booking link, for example base_url('book').'?service=manicure'
 *                (defaults to the booking page)
 *   book_label   booking button text (defaults to "Book appointment")
 *
 * The phone button uses the Website Settings phone ($site from Frontend_layout).
 */
$cta = array_merge(array(
    'variant' => 'card',
    'heading' => 'Book your visit to Blossom',
    'text' => '',
    'book_url' => $site['book_url'],
    'book_label' => 'Book appointment',
), isset($cta) ? $cta : array());
?>
<?php if ($cta['variant'] === 'inline') { ?>
    <section aria-labelledby="cta-heading" class="wash-fresh py-16 md:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="reveal flex flex-col items-start gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <h2 id="cta-heading" class="text-[clamp(1.75rem,3.6vw,2.5rem)] text-foreground"><?php echo html_escape($cta['heading']); ?></h2>
                    <?php if ($cta['text'] !== '') { ?>
                        <p class="mt-4 leading-relaxed text-muted-foreground"><?php echo html_escape($cta['text']); ?></p>
                    <?php } ?>
                </div>
                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    <a
                        class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>"
                        href="<?php echo html_escape($cta['book_url']); ?>"
                    ><?php echo html_escape($cta['book_label']); ?></a>
                    <?php if ($site['phone_href'] !== '') { ?>
                        <a
                            href="<?php echo html_escape($site['phone_href']); ?>"
                            class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>"
                        >
                            <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                            <?php echo html_escape($site['phone']); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php } else { ?>
    <section aria-labelledby="cta-heading" class="bg-background py-16 md:py-20 lg:py-24">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="reveal relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-strong via-plum to-plum-deep px-6 py-12 text-center text-background shadow-[var(--shadow-soft)] sm:px-10 md:py-16">
                <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-secondary/25 blur-2xl"></div>
                <div class="relative mx-auto max-w-2xl">
                    <h2 id="cta-heading" class="text-[clamp(1.9rem,4.4vw,3rem)] text-background"><?php echo html_escape($cta['heading']); ?></h2>
                    <?php if ($cta['text'] !== '') { ?>
                        <p class="mx-auto mt-5 max-w-xl leading-relaxed text-rose-100/85"><?php echo html_escape($cta['text']); ?></p>
                    <?php } ?>
                    <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                        <a
                            class="<?php echo html_escape(frontend_button_class('light', 'h-13 px-8')); ?>"
                            href="<?php echo html_escape($cta['book_url']); ?>"
                        ><?php echo html_escape($cta['book_label']); ?></a>
                        <?php if ($site['phone_href'] !== '') { ?>
                            <a
                                href="<?php echo html_escape($site['phone_href']); ?>"
                                class="<?php echo html_escape(frontend_button_class('outline-light', 'h-13 px-8')); ?>"
                            >
                                <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                                <?php echo html_escape($site['phone']); ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php } ?>
