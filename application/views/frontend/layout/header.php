<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Skip link and site header. Navigation comes from Manage > Menu ($navigation);
 * site.js builds the mobile menu panel from the primary navigation.
 */
?>
<a
    href="#main"
    class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:bg-foreground focus:px-5 focus:py-3 focus:text-background"
>Skip to content</a>

<header class="fixed inset-x-0 top-0 z-40 transition-colors duration-300 ease-[var(--ease-out-soft)] border-b border-transparent">
    <div class="mx-auto flex h-20 max-w-[88rem] items-center justify-between gap-6 px-5 sm:px-8 lg:h-24 lg:px-12">
        <a
            aria-label="<?php echo html_escape($site['name']); ?> — home"
            class="inline-flex items-center py-1 transition-opacity duration-200 hover:opacity-80"
            href="<?php echo html_escape(base_url()); ?>"
        >
            <img
                alt="<?php echo html_escape($site['name']); ?> salon"
                width="<?php echo (int) $site['logo']['width']; ?>"
                height="<?php echo (int) $site['logo']['height']; ?>"
                decoding="async"
                class="h-10 w-auto sm:h-12"
                src="<?php echo html_escape($site['logo']['url']); ?>"
            >
        </a>

        <?php if (!empty($navigation)) { ?>
            <nav aria-label="Primary" class="hidden lg:block">
                <ul class="relative flex items-center gap-8">
                    <?php foreach ($navigation as $item) { ?>
                        <li>
                            <a
                                class="block py-2 text-[1.0625rem] transition-colors duration-200 font-medium text-foreground-soft hover:text-primary-ink"
                                href="<?php echo html_escape($item['url']); ?>"
                                <?php echo $item['current'] ? 'aria-current="page"' : ''; ?>
                            ><?php echo html_escape($item['label']); ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>

        <div class="flex items-center gap-2 sm:gap-3">
            <a
                aria-label="Search the site"
                class="hidden size-11 items-center justify-center rounded-full transition-colors duration-200 sm:inline-flex text-foreground-soft hover:bg-petal hover:text-primary"
                href="<?php echo html_escape($site['search_url']); ?>"
            >
                <i class="fa-solid fa-magnifying-glass size-5" aria-hidden="true"></i>
            </a>

            <?php if ($site['phone_href'] !== '') { ?>
                <a
                    href="<?php echo html_escape($site['phone_href']); ?>"
                    class="hidden items-center gap-2 px-2 py-3 text-[0.95rem] font-medium transition-colors duration-200 lg:inline-flex text-foreground-soft hover:text-primary-ink"
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
                    class="<?php echo html_escape(frontend_button_class('outline', 'h-11 w-11 lg:hidden')); ?>"
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
