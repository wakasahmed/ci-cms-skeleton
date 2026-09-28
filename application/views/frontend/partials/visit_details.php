<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Salon address, landmark note, phone and opening hours, as used in the
 * "Visit us" sections. Everything comes from Website Settings ($site).
 */
?>
<address class="mt-8 not-italic">
    <?php if (!empty($site['address_lines'])) { ?>
        <p class="flex items-start gap-3 text-lg leading-relaxed text-foreground">
            <i class="fa-solid fa-location-dot mt-1.5 size-5 shrink-0 text-primary" aria-hidden="true"></i>
            <span><?php echo html_escape($site['name']); ?><br><?php echo implode('<br>', array_map('html_escape', $site['address_lines'])); ?></span>
        </p>
    <?php } ?>
    <?php if ($site['address_note'] !== '') { ?>
        <p class="mt-4 flex items-start gap-3 text-muted-foreground">
            <i class="fa-solid fa-store mt-1 size-5 shrink-0 text-primary" aria-hidden="true"></i>
            <?php echo html_escape($site['address_note']); ?>
        </p>
    <?php } ?>
    <?php if ($site['phone_href'] !== '') { ?>
        <a href="<?php echo html_escape($site['phone_href']); ?>" class="mt-4 inline-flex min-h-12 items-center gap-3 text-lg text-foreground transition-colors duration-200 hover:text-primary">
            <i class="fa-solid fa-phone size-5 text-primary" aria-hidden="true"></i>
            <?php echo html_escape($site['phone']); ?>
        </a>
    <?php } ?>
</address>
<?php if (!empty($site['opening_hours'])) { ?>
    <div class="mt-10">
        <h3 class="flex items-center gap-2 font-display text-xl text-foreground">
            <i class="fa-solid fa-calendar-days size-5 text-primary" aria-hidden="true"></i>
            Opening hours
        </h3>
        <dl class="mt-4 divide-y divide-border border-y border-border">
            <?php foreach ($site['opening_hours'] as $row) { ?>
                <div class="flex items-baseline justify-between gap-4 py-3.5">
                    <dt class="text-foreground-soft"><?php echo html_escape($row['days']); ?></dt>
                    <dd class="tabular-nums <?php echo $row['closed'] ? 'text-muted-foreground' : 'font-medium text-foreground'; ?>"><?php echo html_escape($row['hours']); ?></dd>
                </div>
            <?php } ?>
        </dl>
    </div>
<?php } ?>
