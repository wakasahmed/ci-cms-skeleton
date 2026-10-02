<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Account navigation: My appointments, Your details and Sign out (a POST
 * with its own form token, so another site cannot sign the customer out).
 *
 * $current        'appointments' or 'details'
 * $signOutToken   one-use token of the sign-out form
 */
$links = array(
    'appointments' => array('label' => 'My appointments', 'url' => base_url('account'), 'icon' => 'fa-regular fa-calendar'),
    'details' => array('label' => 'Your details', 'url' => base_url('account/details'), 'icon' => 'fa-regular fa-id-card'),
);
?>
<nav class="mb-10 flex flex-wrap items-center gap-2 border-b border-border pb-4" aria-label="Your account">
    <?php foreach ($links as $key => $link) { ?>
        <a
            href="<?php echo html_escape($link['url']); ?>"
            class="inline-flex min-h-11 items-center gap-2 rounded-full px-5 text-sm font-semibold transition-colors duration-200 <?php echo $current === $key ? 'bg-primary text-primary-foreground' : 'text-foreground-soft hover:bg-petal hover:text-primary'; ?>"
            <?php echo $current === $key ? 'aria-current="page"' : ''; ?>
        >
            <i class="<?php echo $link['icon']; ?> size-4" aria-hidden="true"></i>
            <?php echo html_escape($link['label']); ?>
        </a>
    <?php } ?>
    <form method="post" action="<?php echo html_escape(base_url('account/sign-out')); ?>" class="ml-auto">
        <input type="hidden" name="form_token" value="<?php echo html_escape($signOutToken); ?>">
        <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-full px-5 text-sm font-semibold text-foreground-soft transition-colors duration-200 hover:bg-petal hover:text-primary">
            <i class="fa-solid fa-arrow-right-from-bracket size-4" aria-hidden="true"></i>
            Sign out
        </button>
    </form>
</nav>
