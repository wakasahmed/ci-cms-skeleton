<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Breadcrumb trail shown at the top of a page hero.
 *
 * $crumbs: array of array('label' => ..., 'url' => ...). The last crumb is the
 * current page and is rendered without a link.
 */
$lastIndex = count($crumbs) - 1;
?>
<nav aria-label="Breadcrumb" class="mb-6">
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm">
        <?php foreach ($crumbs as $index => $crumb) { ?>
            <li class="flex items-center gap-1.5">
                <?php if ($index < $lastIndex && !empty($crumb['url'])) { ?>
                    <a
                        class="link-underline inline-flex min-h-8 items-center transition-colors duration-200 text-muted-foreground hover:text-primary"
                        href="<?php echo html_escape($crumb['url']); ?>"
                    ><?php echo html_escape($crumb['label']); ?></a>
                    <i class="fa-solid fa-chevron-right size-3.5 shrink-0 text-border-strong" aria-hidden="true"></i>
                <?php } else { ?>
                    <span aria-current="page" class="inline-flex min-h-8 items-center font-medium text-foreground"><?php echo html_escape($crumb['label']); ?></span>
                <?php } ?>
            </li>
        <?php } ?>
    </ol>
</nav>
