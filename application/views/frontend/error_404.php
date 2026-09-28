<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Page not found (404). The reference has no 404 design, so this combines
 * its page hero with the dashed "nothing found" card from its appointment
 * pages: a site search and the header menu's pages (Manage > Menu).
 */
?>
<main id="main">
    <section class="relative isolate overflow-hidden" aria-labelledby="page-hero-heading">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-full bg-gradient-to-b from-petal via-lilac to-background"></div>
        <div aria-hidden="true" class="absolute -top-32 -right-28 -z-10 hidden size-[26rem] rounded-full bg-rose-100/60 blur-[2px] lg:block"></div>
        <div class="mx-auto w-full max-w-[86rem] px-5 pt-28 pb-12 sm:px-8 sm:pt-32 lg:px-12 lg:pt-36 lg:pb-16">
            <div class="reveal max-w-2xl">
                <?php $this->load->view('frontend/partials/breadcrumb', array(
                    'crumbs' => array(
                        array('label' => 'Home', 'url' => base_url()),
                        array('label' => 'Page not found'),
                    ),
                    'light' => FALSE,
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

    <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="not-found-help-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="reveal mx-auto max-w-4xl rounded-lg border border-dashed border-border-strong bg-petal/50 px-6 py-12 text-center">
                <span aria-hidden="true" class="mx-auto mb-4 grid size-12 place-items-center rounded-full bg-background text-primary shadow-[var(--shadow-card)]">
                    <i class="fa-solid fa-magnifying-glass-minus size-5" aria-hidden="true"></i>
                </span>
                <h2 id="not-found-help-heading" class="font-display text-xl text-foreground">Looking for something?</h2>
                <p class="mx-auto mt-2 max-w-sm text-muted-foreground">Search the site, or pick up from one of these pages.</p>
                <form role="search" method="get" action="<?php echo html_escape($site['search_url']); ?>" class="mx-auto mt-6 flex max-w-xl flex-col gap-3 sm:flex-row">
                    <label for="not-found-search" class="sr-only">Search the site</label>
                    <input
                        id="not-found-search"
                        name="q"
                        type="search"
                        maxlength="100"
                        placeholder="Try gel, nail art or Ewa"
                        class="min-h-12 flex-1 rounded-lg border border-border-strong bg-background px-4 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/15"
                    >
                    <button type="submit" class="min-h-12 cursor-pointer rounded-full bg-primary-cta px-7 font-semibold text-primary-foreground transition-colors hover:bg-primary-strong">Search</button>
                </form>
                <?php if (!empty($navigation)) { ?>
                    <nav aria-label="Popular pages" class="mt-8">
                        <ul class="flex flex-wrap justify-center gap-2">
                            <?php foreach ($navigation as $item) { ?>
                                <li>
                                    <a class="chip min-h-11" href="<?php echo html_escape($item['url']); ?>"><?php echo html_escape($item['label']); ?></a>
                                </li>
                            <?php } ?>
                        </ul>
                    </nav>
                <?php } ?>
            </div>
        </div>
    </section>
</main>
