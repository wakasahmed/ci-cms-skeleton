<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Site footer and closing tags.
 *
 * Columns: featured services (Services module, heading foot_col_1), Manage >
 * Foot "one" (heading foot_col_2), and the visit/contact details from
 * Website Settings (heading foot_col_4). The legal links are Manage > Foot "two".
 */
$footerLinkClass = 'link-underline inline-flex min-h-9 items-center text-background/85 hover:text-background';
?>
<footer class="rounded-t-2xl bg-gradient-to-b from-primary-strong via-plum to-plum-deep text-background">
    <div class="mx-auto w-full max-w-[88rem] px-5 py-16 sm:px-8 md:py-20 lg:px-12">
        <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-4">
                <a
                    aria-label="<?php echo html_escape($site['name']); ?> — home"
                    class="inline-flex items-center py-1 transition-opacity duration-200 hover:opacity-80"
                    href="<?php echo html_escape(base_url()); ?>"
                >
                    <img
                        alt="<?php echo html_escape($site['name']); ?> salon"
                        loading="lazy"
                        width="<?php echo (int) $site['logo_light']['width']; ?>"
                        height="<?php echo (int) $site['logo_light']['height']; ?>"
                        decoding="async"
                        class="h-10 w-auto sm:h-12"
                        src="<?php echo html_escape($site['logo_light']['url']); ?>"
                    >
                </a>

                <?php if ($site['intro'] !== '') { ?>
                    <p class="mt-5 max-w-xs leading-relaxed text-background/85"><?php echo html_escape($site['intro']); ?></p>
                <?php } ?>

                <a
                    class="<?php echo html_escape(frontend_button_class('light', 'h-12 px-7 mt-7')); ?>"
                    href="<?php echo html_escape($site['book_url']); ?>"
                >Book appointment</a>

                <?php if (!empty($site['socials'])) { ?>
                    <ul class="mt-8 flex items-center gap-2">
                        <?php foreach ($site['socials'] as $social) { ?>
                            <li>
                                <a
                                    href="<?php echo html_escape($social['url']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="<?php echo html_escape($social['label']); ?>"
                                    class="inline-flex size-11 items-center justify-center rounded-full border border-background/25 transition-colors duration-200 hover:bg-background hover:text-plum"
                                >
                                    <i class="fa-brands <?php echo html_escape($social['icon']); ?> size-5" aria-hidden="true"></i>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>

            <?php if (!empty($footer['services'])) { ?>
                <nav aria-labelledby="footer-services" class="lg:col-span-2">
                    <h2 id="footer-services" class="font-sans text-sm font-medium text-background/75">
                        <?php echo html_escape($footer['services_heading'] !== '' ? $footer['services_heading'] : 'Services'); ?>
                    </h2>
                    <ul class="mt-5 space-y-1">
                        <?php foreach ($footer['services'] as $link) { ?>
                            <li>
                                <a class="<?php echo $footerLinkClass; ?>" href="<?php echo html_escape($link['url']); ?>"><?php echo html_escape($link['label']); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php } else { ?>
                <?php /* Keeps the other columns in their grid positions until services are featured. */ ?>
                <div class="hidden lg:col-span-2 lg:block" aria-hidden="true"></div>
            <?php } ?>

            <?php if (!empty($footer['salon'])) { ?>
                <nav aria-labelledby="footer-company" class="lg:col-span-2">
                    <h2 id="footer-company" class="font-sans text-sm font-medium text-background/75">
                        <?php echo html_escape($footer['salon_heading'] !== '' ? $footer['salon_heading'] : 'Salon'); ?>
                    </h2>
                    <ul class="mt-5 space-y-1">
                        <?php foreach ($footer['salon'] as $link) { ?>
                            <li>
                                <a
                                    class="<?php echo $footerLinkClass; ?>"
                                    href="<?php echo html_escape($link['url']); ?>"
                                    <?php echo $link['current'] ? 'aria-current="page"' : ''; ?>
                                ><?php echo html_escape($link['label']); ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php } ?>

            <div class="lg:col-span-4">
                <h2 class="font-sans text-sm font-medium text-background/75">
                    <?php echo html_escape($footer['contact_heading'] !== '' ? $footer['contact_heading'] : 'Visit & contact'); ?>
                </h2>
                <address class="mt-5 leading-relaxed text-background/85 not-italic">
                    <p class="font-display text-xl text-background"><?php echo html_escape($site['name']); ?></p>
                    <?php if (!empty($site['address_lines'])) { ?>
                        <p class="mt-2 flex items-start gap-2">
                            <i class="fa-solid fa-location-dot mt-1 size-4 shrink-0 text-secondary-light" aria-hidden="true"></i>
                            <span>
                                <?php echo implode('<br>', array_map('html_escape', $site['address_lines'])); ?>
                                <?php if ($site['address_note'] !== '') { ?>
                                    <br><span class="text-background/75"><?php echo html_escape($site['address_note']); ?></span>
                                <?php } ?>
                            </span>
                        </p>
                    <?php } ?>
                    <?php if ($site['phone_href'] !== '') { ?>
                        <a href="<?php echo html_escape($site['phone_href']); ?>" class="mt-3 inline-flex min-h-11 items-center gap-2 hover:text-background">
                            <i class="fa-solid fa-phone size-4 text-secondary-light" aria-hidden="true"></i>
                            <?php echo html_escape($site['phone']); ?>
                        </a>
                    <?php } ?>
                </address>

                <?php if (!empty($site['opening_hours'])) { ?>
                    <dl class="mt-6 border-t border-background/15 pt-4 text-sm">
                        <?php foreach ($site['opening_hours'] as $row) { ?>
                            <div class="flex items-baseline justify-between gap-4 py-1.5">
                                <dt class="text-background/70"><?php echo html_escape($row['days']); ?></dt>
                                <dd class="tabular-nums <?php echo $row['closed'] ? 'text-background/60' : 'text-background/85'; ?>"><?php echo html_escape($row['hours']); ?></dd>
                            </div>
                        <?php } ?>
                    </dl>
                <?php } ?>
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-background/15 pt-7 text-sm text-background/60 md:flex-row md:items-center md:justify-between">
            <p><?php echo html_escape($site['copyright'] !== '' ? $site['copyright'] : '© '.date('Y').' '.$site['name'].'. All rights reserved.'); ?></p>
            <?php if (!empty($footer['legal'])) { ?>
                <ul class="flex flex-wrap items-center gap-x-6 gap-y-1">
                    <?php foreach ($footer['legal'] as $link) { ?>
                        <li>
                            <a
                                class="link-underline inline-flex min-h-9 items-center hover:text-background"
                                href="<?php echo html_escape($link['url']); ?>"
                                <?php echo $link['current'] ? 'aria-current="page"' : ''; ?>
                            ><?php echo html_escape($link['label']); ?></a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</footer>
</body>
</html>
