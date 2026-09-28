<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Page not found. Uses the reference design's page hero; the full Blossom
 * 404 design is part of Phase 6 of PROJECT_PLAN.md.
 */
?>
<main id="main">
    <section class="relative isolate overflow-hidden" aria-labelledby="page-hero-heading">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-full bg-gradient-to-b from-petal via-lilac to-background"></div>
        <div aria-hidden="true" class="absolute -top-32 -right-28 -z-10 hidden size-[26rem] rounded-full bg-rose-100/60 blur-[2px] lg:block"></div>
        <div class="mx-auto w-full max-w-[86rem] px-5 pt-28 pb-16 sm:px-8 sm:pt-32 lg:px-12 lg:pt-36 lg:pb-24">
            <div class="reveal max-w-2xl">
                <?php $this->load->view('frontend/partials/breadcrumb', array(
                    'crumbs' => array(
                        array('label' => 'Home', 'url' => base_url()),
                        array('label' => 'Page not found'),
                    ),
                )); ?>
                <p class="section-label">Error 404</p>
                <h1 id="page-hero-heading" class="text-foreground mt-4 text-[clamp(2.25rem,5.4vw,3.75rem)]">We couldn't find that page</h1>
                <p class="mt-5 text-lg leading-relaxed text-muted-foreground max-w-2xl">
                    The link may be out of date, or the page may have moved. Head back to the home page, or book your next visit.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a
                        class="<?php echo html_escape(frontend_button_class('primary', 'h-13 px-8')); ?>"
                        href="<?php echo html_escape(base_url()); ?>"
                    >Back to home</a>
                    <a
                        class="<?php echo html_escape(frontend_button_class('outline', 'h-13 px-8')); ?>"
                        href="<?php echo html_escape($site['book_url']); ?>"
                    >Book appointment</a>
                </div>
            </div>
        </div>
    </section>
</main>
