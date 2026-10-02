<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Skip link and site header. Navigation comes from Manage > Menu ($navigation);
 * site.js builds the mobile menu panel from the primary navigation.
 *
 * With $header_overlay (pages that open with a dark image hero) the header
 * starts light: light logo and text. Once the page scrolls, the header gets
 * its white background and the rules for header[data-header-overlay] in
 * tailwind.css switch it back to the default colours.
 */
$overlay = !empty($header_overlay);
?>
<a
    href="#main"
    class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:bg-foreground focus:px-5 focus:py-3 focus:text-background"
>Skip to content</a>

<header class="fixed inset-x-0 top-0 z-40 transition-colors duration-300 ease-[var(--ease-out-soft)] border-b border-transparent"<?php echo $overlay ? ' data-header-overlay' : ''; ?>>
    <div class="mx-auto flex h-20 max-w-[88rem] items-center justify-between gap-6 px-5 sm:px-8 lg:h-24 lg:px-12">
        <a
            aria-label="<?php echo html_escape($site['name']); ?> — home"
            class="inline-flex items-center py-1 transition-opacity duration-200 hover:opacity-80"
            href="<?php echo html_escape(base_url()); ?>"
        >
            <?php if ($overlay) { ?>
                <img
                    alt="<?php echo html_escape($site['name']); ?> salon"
                    width="<?php echo (int) $site['logo_light']['width']; ?>"
                    height="<?php echo (int) $site['logo_light']['height']; ?>"
                    decoding="async"
                    class="header-logo-light h-10 w-auto sm:h-12"
                    src="<?php echo html_escape($site['logo_light']['url']); ?>"
                >
            <?php } ?>
            <img
                alt="<?php echo $overlay ? '' : html_escape($site['name']).' salon'; ?>"
                width="<?php echo (int) $site['logo']['width']; ?>"
                height="<?php echo (int) $site['logo']['height']; ?>"
                decoding="async"
                class="header-logo-dark h-10 w-auto sm:h-12"
                src="<?php echo html_escape($site['logo']['url']); ?>"
            >
        </a>

        <?php if (!empty($navigation)) { ?>
            <nav aria-label="Primary" class="hidden lg:block">
                <ul class="relative flex items-center gap-8">
                    <?php foreach ($navigation as $item) { ?>
                        <li>
                            <?php if ($item['current'] && $overlay) { ?>
                                <a
                                    aria-current="page"
                                    data-header-link
                                    class="block py-2 text-[1.0625rem] transition-colors duration-200 font-semibold text-background"
                                    href="<?php echo html_escape($item['url']); ?>"
                                ><?php echo html_escape($item['label']); ?></a>
                            <?php } elseif ($item['current']) { ?>
                                <a
                                    aria-current="page"
                                    data-header-link
                                    class="block py-2 text-[1.0625rem] transition-colors duration-200 font-semibold text-primary-ink"
                                    href="<?php echo html_escape($item['url']); ?>"
                                ><?php echo html_escape($item['label']); ?></a>
                            <?php } elseif ($overlay) { ?>
                                <a
                                    data-header-link
                                    class="block py-2 text-[1.0625rem] transition-colors duration-200 font-medium text-rose-100/85 hover:text-background"
                                    href="<?php echo html_escape($item['url']); ?>"
                                ><?php echo html_escape($item['label']); ?></a>
                            <?php } else { ?>
                                <a
                                    data-header-link
                                    class="block py-2 text-[1.0625rem] transition-colors duration-200 font-medium text-foreground-soft hover:text-primary-ink"
                                    href="<?php echo html_escape($item['url']); ?>"
                                ><?php echo html_escape($item['label']); ?></a>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>

        <div class="flex items-center gap-2 sm:gap-3">
            <a
                aria-label="Search the site"
                data-header-tool
                class="hidden size-11 items-center justify-center rounded-full transition-colors duration-200 sm:inline-flex <?php echo $overlay ? 'text-rose-100/85 hover:bg-background/15 hover:text-background' : 'text-foreground-soft hover:bg-petal hover:text-primary'; ?>"
                href="<?php echo html_escape($site['search_url']); ?>"
            >
                <i class="fa-solid fa-magnifying-glass size-5" aria-hidden="true"></i>
            </a>

            <a
                aria-label="<?php echo html_escape($site['account']['label']); ?>"
                title="<?php echo html_escape($site['account']['label']); ?>"
                data-header-tool
                class="inline-flex size-11 items-center justify-center rounded-full transition-colors duration-200 <?php echo $overlay ? 'text-rose-100/85 hover:bg-background/15 hover:text-background' : 'text-foreground-soft hover:bg-petal hover:text-primary'; ?>"
                href="<?php echo html_escape($site['account']['url']); ?>"
            >
                <i class="<?php echo $site['account']['signed_in'] ? 'fa-solid fa-circle-user' : 'fa-regular fa-user'; ?> size-5" aria-hidden="true"></i>
            </a>

            <?php if ($site['phone_href'] !== '') { ?>
                <a
                    href="<?php echo html_escape($site['phone_href']); ?>"
                    data-header-tool
                    class="hidden items-center gap-2 px-2 py-3 text-[0.95rem] font-medium transition-colors duration-200 lg:inline-flex <?php echo $overlay ? 'text-rose-100/85 hover:text-background' : 'text-foreground-soft hover:text-primary-ink'; ?>"
                >
                    <i class="fa-solid fa-phone size-4" aria-hidden="true"></i>
                    <span><?php echo html_escape($site['phone']); ?></span>
                </a>
            <?php } ?>

            <a
                class="<?php echo html_escape(frontend_button_class('primary', 'h-11 px-4 sm:px-6')); ?>"
                href="<?php echo html_escape($site['book_url']); ?>"
            >Book<span class="hidden sm:inline"> appointment</span></a>

            <?php if (!empty($navigation)) { ?>
                <button
                    class="<?php echo html_escape(frontend_button_class($overlay ? 'outline-light' : 'outline', 'h-11 w-11 lg:hidden')); ?>"
                    aria-label="Open menu"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    data-menu-toggle
                >
                    <i class="fa-solid fa-bars size-5" aria-hidden="true"></i>
                </button>
            <?php } ?>
        </div>
    </div>
</header>
