<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Page hero: breadcrumb, section label, heading, lead text, actions, optional
 * key facts, and an optional image on the right.
 *
 * $hero keys:
 *   crumbs    breadcrumb (see partials/breadcrumb.php)
 *   label     section label above the heading (optional)
 *   heading   the page <h1>
 *   lead      lead paragraph (optional)
 *   actions   array of array('label', 'url', 'variant' => 'primary'|'outline')
 *   facts     array of array('label', 'value', 'icon' => Font Awesome class or '')
 *   chips     short tags shown under the actions (optional)
 *   image     array('url', 'alt') (optional)
 */
$hero = array_merge(array(
    'crumbs' => array(),
    'label' => '',
    'heading' => '',
    'lead' => '',
    'actions' => array(),
    'facts' => array(),
    'chips' => array(),
    'image' => array(),
), $hero);
$hasImage = !empty($hero['image']['url']);
?>
<section class="relative isolate overflow-hidden" aria-labelledby="page-hero-heading">
    <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-full bg-gradient-to-b from-petal via-lilac to-background"></div>
    <div aria-hidden="true" class="absolute -top-32 -right-28 -z-10 hidden size-[26rem] rounded-full bg-rose-100/60 blur-[2px] lg:block"></div>
    <div class="mx-auto w-full max-w-[86rem] px-5 pt-28 pb-12 sm:px-8 sm:pt-32 lg:px-12 lg:pt-36 lg:pb-16">
        <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-14">
            <div class="reveal <?php echo $hasImage ? 'lg:col-span-6' : 'lg:col-span-8'; ?>">
                <?php if (!empty($hero['crumbs'])) { ?>
                    <?php $this->load->view('frontend/partials/breadcrumb', array('crumbs' => $hero['crumbs'])); ?>
                <?php } ?>

                <?php if ($hero['label'] !== '') { ?>
                    <p class="section-label"><?php echo html_escape($hero['label']); ?></p>
                <?php } ?>
                <h1 id="page-hero-heading" class="text-foreground mt-4 text-[clamp(2.25rem,5.4vw,3.75rem)]"><?php echo html_escape($hero['heading']); ?></h1>
                <?php if ($hero['lead'] !== '') { ?>
                    <p class="mt-5 text-lg leading-relaxed text-muted-foreground max-w-2xl"><?php echo html_escape($hero['lead']); ?></p>
                <?php } ?>

                <?php if (!empty($hero['actions'])) { ?>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <?php foreach ($hero['actions'] as $action) { ?>
                            <a
                                class="<?php echo html_escape(frontend_button_class(isset($action['variant']) ? $action['variant'] : 'primary', 'h-13 px-8')); ?>"
                                href="<?php echo html_escape($action['url']); ?>"
                            ><?php echo html_escape($action['label']); ?></a>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if (!empty($hero['chips'])) { ?>
                    <ul class="mt-8 flex flex-wrap gap-2">
                        <?php foreach ($hero['chips'] as $chip) { ?>
                            <li class="chip"><?php echo html_escape($chip); ?></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <?php if (!empty($hero['facts'])) { ?>
                    <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-4">
                        <?php foreach ($hero['facts'] as $fact) { ?>
                            <div>
                                <dt class="text-sm text-muted-foreground"><?php echo html_escape($fact['label']); ?></dt>
                                <?php if (!empty($fact['icon'])) { ?>
                                    <dd class="mt-1 flex items-center gap-2 font-display text-2xl text-foreground">
                                        <i class="<?php echo html_escape($fact['icon']); ?> size-5 text-primary" aria-hidden="true"></i>
                                        <?php echo html_escape($fact['value']); ?>
                                    </dd>
                                <?php } else { ?>
                                    <dd class="mt-1 font-display text-2xl text-foreground"><?php echo html_escape($fact['value']); ?></dd>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </dl>
                <?php } ?>
            </div>

            <?php if ($hasImage) { ?>
                <div class="reveal lg:col-span-6" style="transition-delay:80ms">
                    <div class="relative aspect-4/3 overflow-hidden rounded-2xl bg-muted shadow-[var(--shadow-soft)]">
                        <img
                            alt="<?php echo html_escape($hero['image']['alt']); ?>"
                            decoding="async"
                            class="absolute inset-0 size-full object-cover"
                            src="<?php echo html_escape($hero['image']['url']); ?>"
                        >
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
