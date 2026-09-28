<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Services listing (/services).
 *
 * $hero       page hero data (see partials/page_hero.php)
 * $sections   Web Page Sections of the Services page, keyed by section
 * $featured   featured services (cards)
 * $menu       categories, each with its services
 * $category   the category slug requested with ?category=, or 'all'
 */
$featuredSection = isset($sections['featured_services']) ? $sections['featured_services'] : array();
$menuSection = isset($sections['full_menu']) ? $sections['full_menu'] : array();
$ctaSection = isset($sections['cta']) ? $sections['cta'] : array();
$serviceCount = 0;
foreach ($menu as $group) {
    $serviceCount += count($group['services']);
}
$activeChip = 'bg-primary-cta text-primary-foreground';
$inactiveChip = 'border border-border-strong bg-background';
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => $hero)); ?>

    <?php if (!empty($featured) && !empty($featuredSection['heading'])) { ?>
        <section class="py-14 md:py-16 lg:py-20 bg-background text-foreground" aria-labelledby="featured-heading">
            <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
                <div class="reveal flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <?php if (!empty($featuredSection['pre_heading'])) { ?>
                            <p class="section-label"><?php echo html_escape($featuredSection['pre_heading']); ?></p>
                        <?php } ?>
                        <h2 id="featured-heading" class="mt-4 text-[clamp(1.75rem,4vw,2.5rem)]"><?php echo html_escape($featuredSection['heading']); ?></h2>
                    </div>
                </div>
                <ul class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4 lg:gap-6">
                    <?php foreach ($featured as $index => $service) { ?>
                        <li class="reveal"<?php echo $index > 0 ? ' style="transition-delay:'.($index * 60).'ms"' : ''; ?>>
                            <?php $this->load->view('frontend/partials/service_card', array(
                                'service' => $service,
                                'eager' => $index < 2,
                            )); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </section>
    <?php } ?>

    <section class="py-20 md:py-26 lg:py-32 bg-petal text-foreground" aria-labelledby="all-services-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="reveal max-w-2xl">
                <h2 id="all-services-heading" class="text-[clamp(1.75rem,4vw,2.5rem)]">
                    <?php echo html_escape(!empty($menuSection['heading']) ? $menuSection['heading'] : 'The full menu'); ?>
                </h2>
                <?php if (!empty($menuSection['contents'])) { ?>
                    <p class="mt-4 text-muted-foreground"><?php echo html_escape($menuSection['contents']); ?></p>
                <?php } ?>
            </div>

            <div class="mt-10" data-service-menu>
                <?php if (empty($menu)) { ?>
                    <p class="text-muted-foreground">Our service menu is being updated. Please call the salon for prices.</p>
                <?php } else { ?>
                    <div role="radiogroup" aria-label="Filter services by category" class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            role="radio"
                            data-service-group="all"
                            aria-checked="<?php echo $category === 'all' ? 'true' : 'false'; ?>"
                            class="min-h-11 rounded-full px-5 text-sm font-semibold <?php echo $category === 'all' ? $activeChip : $inactiveChip; ?>"
                        >All services <span class="opacity-70"><?php echo (int) $serviceCount; ?></span></button>
                        <?php foreach ($menu as $group) { ?>
                            <button
                                type="button"
                                role="radio"
                                data-service-group="<?php echo html_escape($group['category_slug']); ?>"
                                aria-checked="<?php echo $category === $group['category_slug'] ? 'true' : 'false'; ?>"
                                class="min-h-11 rounded-full px-5 text-sm font-semibold <?php echo $category === $group['category_slug'] ? $activeChip : $inactiveChip; ?>"
                            ><?php echo html_escape($group['category_name']); ?> <span class="opacity-70"><?php echo count($group['services']); ?></span></button>
                        <?php } ?>
                    </div>

                    <p class="sr-only" role="status" data-service-status>
                        <?php
                        $shown = $category === 'all' ? $serviceCount : 0;
                        foreach ($menu as $group) {
                            if ($group['category_slug'] === $category) {
                                $shown = count($group['services']);
                            }
                        }
                        echo (int) $shown;
                        ?> services shown
                    </p>

                    <?php foreach ($menu as $group) { ?>
                        <section
                            class="mt-12"
                            data-service-category="<?php echo html_escape($group['category_slug']); ?>"
                            data-service-count="<?php echo count($group['services']); ?>"
                            <?php echo $category !== 'all' && $category !== $group['category_slug'] ? 'hidden' : ''; ?>
                        >
                            <div class="border-b border-border pb-5">
                                <h2 class="text-3xl"><?php echo html_escape($group['category_name']); ?></h2>
                                <?php if (trim((string) $group['category_description']) !== '') { ?>
                                    <p class="mt-2 text-muted-foreground"><?php echo html_escape($group['category_description']); ?></p>
                                <?php } ?>
                            </div>
                            <ul class="mt-6 grid gap-4 lg:grid-cols-2">
                                <?php foreach ($group['services'] as $service) { ?>
                                    <?php $this->load->view('frontend/partials/service_menu_item', array('service' => $service)); ?>
                                <?php } ?>
                            </ul>
                        </section>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <?php $this->load->view('frontend/partials/cta_band', array('cta' => array(
        'variant' => 'inline',
        'heading' => !empty($ctaSection['heading']) ? $ctaSection['heading'] : 'Book your visit to Blossom',
        'text' => !empty($ctaSection['contents']) ? $ctaSection['contents'] : '',
    ))); ?>
</main>
