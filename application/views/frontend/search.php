<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Search (/search?q=): the search form, suggestions before a search, and
 * results grouped by type (Services, Offers, Artists, Journal, Questions).
 *
 * $query        the search text as entered (trimmed)
 * $searched     TRUE when $query had at least one usable word
 * $groups       array of array('title', 'items' => list of array('title',
 *               'url', 'meta', 'text'))
 * $total        number of results
 * $suggestions  service names offered as example searches
 */
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'Search'),
        ),
        'heading' => 'Search',
        'lead' => 'Services, offers, the team, the journal and the questions we’re asked most.',
    ))); ?>

    <section class="py-18 md:py-22 lg:py-26 bg-background text-foreground" aria-label="Search results">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="max-w-4xl">
                <form role="search" method="get" action="<?php echo html_escape(base_url('search')); ?>" class="flex flex-col gap-3 sm:flex-row">
                    <label for="site-search" class="sr-only">Search the site</label>
                    <input
                        id="site-search"
                        name="q"
                        type="search"
                        maxlength="100"
                        value="<?php echo html_escape($query); ?>"
                        placeholder="Try gel, nail art or Ewa"
                        class="min-h-12 flex-1 rounded-lg border border-border-strong bg-background px-4 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/15"
                    >
                    <button type="submit" class="min-h-12 cursor-pointer rounded-full bg-primary-cta px-7 font-semibold text-primary-foreground transition-colors hover:bg-primary-strong">Search</button>
                </form>

                <?php if (!$searched) { ?>
                    <p class="mt-8 text-muted-foreground">
                        <?php echo $query !== '' ? 'Please type a longer word to search.' : 'Search across services, offers, the team and the journal.'; ?>
                    </p>
                    <?php if (!empty($suggestions)) { ?>
                        <ul class="mt-5 flex flex-wrap gap-2" aria-label="Suggested searches">
                            <?php foreach ($suggestions as $suggestion) { ?>
                                <li>
                                    <a class="chip min-h-11" href="<?php echo html_escape(base_url('search').'?q='.rawurlencode($suggestion)); ?>"><?php echo html_escape($suggestion); ?></a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                <?php } elseif ($total === 0) { ?>
                    <div class="mt-10 rounded-xl bg-petal p-8 text-center" role="status">
                        <h2 class="text-2xl">Nothing matched that</h2>
                        <p class="mt-3 text-muted-foreground">
                            Try a shorter word or browse the
                            <a class="link-underline text-primary-ink" href="<?php echo html_escape(base_url('services')); ?>">service menu</a>.
                        </p>
                    </div>
                <?php } else { ?>
                    <p class="mt-8 text-muted-foreground" role="status">
                        <?php echo (int) $total; ?> result<?php echo $total === 1 ? '' : 's'; ?> for “<?php echo html_escape($query); ?>”
                    </p>
                    <?php foreach ($groups as $index => $group) { ?>
                        <section class="mt-10" aria-labelledby="search-group-<?php echo (int) $index; ?>">
                            <div class="flex justify-between border-b border-border pb-3">
                                <h2 id="search-group-<?php echo (int) $index; ?>" class="text-2xl"><?php echo html_escape($group['title']); ?></h2>
                                <span class="text-muted-foreground"><?php echo count($group['items']); ?></span>
                            </div>
                            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                                <?php foreach ($group['items'] as $item) { ?>
                                    <li>
                                        <a href="<?php echo html_escape($item['url']); ?>" class="block h-full rounded-lg border border-border p-4 transition-colors hover:border-primary hover:bg-petal">
                                            <span class="font-display text-lg"><?php echo html_escape($item['title']); ?></span>
                                            <?php if ($item['meta'] !== '') { ?>
                                                <span class="mt-1 block text-sm text-primary-ink"><?php echo html_escape($item['meta']); ?></span>
                                            <?php } ?>
                                            <?php if ($item['text'] !== '') { ?>
                                                <span class="mt-2 block text-sm text-muted-foreground"><?php echo html_escape($item['text']); ?></span>
                                            <?php } ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </section>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>
</main>
