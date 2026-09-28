<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Offers (/offers): featured offers as large cards, the rest under
 * "More combinations", a link to the full service menu, and the CTA.
 *
 * $hero       page hero data (see partials/page_hero.php)
 * $sections   Web Page Sections of the Offers page, keyed by section
 * $featured   featured offers
 * $others     the other public offers
 */
$moreSection = isset($sections['more_offers']) ? $sections['more_offers'] : array();
$linkSection = isset($sections['services_link']) ? $sections['services_link'] : array();
$ctaSection = isset($sections['cta']) ? $sections['cta'] : array();
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <?php if (empty($featured) && empty($others)) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <p class="text-muted-foreground">There are no offers running at the moment. Every service is still available at its usual price.</p>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($featured)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-labelledby="featured-offer-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <h2 id="featured-offer-heading" class="sr-only">Featured offers</h2>
                <ul class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                    <?php foreach ($featured as $index => $offer) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 80).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/offer_card', array('offer' => $offer, 'size' => 'featured')); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($others)) { ?>
        <section class="py-18 md:py-22 lg:py-26 bg-petal text-foreground" aria-labelledby="more-offers-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal max-w-2xl">
                    <h2 id="more-offers-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape(!empty($moreSection['heading']) ? $moreSection['heading'] : 'More offers'); ?></h2>
                    <?php if (!empty($moreSection['contents'])) { ?>
                        <p class="mt-4 text-muted-foreground"><?php echo html_escape($moreSection['contents']); ?></p>
                    <?php } ?>
                </div>
                <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">
                    <?php foreach ($others as $index => $offer) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 80).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/offer_card', array('offer' => $offer, 'size' => 'regular')); ?>
                        </li>
                    <?php } ?>
                </ul>
                <?php if (!empty($moreSection['note'])) { ?>
                    <div class="reveal mt-12">
                        <div class="rounded-lg bg-background px-5 py-4 text-sm text-foreground-soft shadow-[var(--shadow-card)]"><?php echo html_escape($moreSection['note']); ?></div>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>

    <?php if (!empty($linkSection['heading'])) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="full-menu-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col items-start gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-xl">
                        <h2 id="full-menu-heading" class="text-[clamp(1.5rem,3.2vw,2rem)]"><?php echo html_escape($linkSection['heading']); ?></h2>
                        <?php if (!empty($linkSection['contents'])) { ?>
                            <p class="mt-3 text-muted-foreground"><?php echo html_escape($linkSection['contents']); ?></p>
                        <?php } ?>
                    </div>
                    <a
                        class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>"
                        href="<?php echo html_escape(base_url('services')); ?>"
                    ><?php echo html_escape(!empty($linkSection['button_text']) ? $linkSection['button_text'] : 'View all services'); ?></a>
                </div>
            </div>
        </section>
    <?php } ?>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'heading' => !empty($ctaSection['heading']) ? $ctaSection['heading'] : 'Book your visit to Blossom',
        'text' => !empty($ctaSection['contents']) ? $ctaSection['contents'] : '',
    ))); ?>
</main>
