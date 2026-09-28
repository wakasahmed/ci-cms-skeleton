<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Shown to visitors while Website Settings > Website Under Construction is
 * "Yes". Signed-in administrators see the real site instead. Uses the shared
 * head partial, then closes the document itself (no site header or footer).
 */
?>
<main id="main" class="wash-fresh flex min-h-screen items-center justify-center px-5 py-16 text-center sm:px-8">
    <div class="max-w-xl">
        <img
            alt="<?php echo html_escape($site['name']); ?> salon"
            width="<?php echo (int) $site['logo']['width']; ?>"
            height="<?php echo (int) $site['logo']['height']; ?>"
            decoding="async"
            class="mx-auto h-14 w-auto"
            src="<?php echo html_escape($site['logo']['url']); ?>"
        >
        <h1 class="mt-10 text-[clamp(2rem,5vw,3rem)] text-foreground">Our new website is on its way</h1>

        <?php if ($site['phone_href'] !== '') { ?>
            <p class="mt-5 text-lg leading-relaxed text-muted-foreground">
                To book an appointment, call us on
                <a class="font-semibold text-primary-ink link-underline" href="<?php echo html_escape($site['phone_href']); ?>"><?php echo html_escape($site['phone']); ?></a>.
            </p>
        <?php } ?>

        <?php if (!empty($site['address_lines'])) { ?>
            <address class="mt-6 leading-relaxed text-muted-foreground not-italic">
                <?php echo implode('<br>', array_map('html_escape', $site['address_lines'])); ?>
                <?php if ($site['address_note'] !== '') { ?>
                    <br><?php echo html_escape($site['address_note']); ?>
                <?php } ?>
            </address>
        <?php } ?>
    </div>
</main>
</body>
</html>
