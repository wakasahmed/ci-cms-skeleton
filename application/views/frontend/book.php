<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Book (/book): the booking wizard. The heading is rendered here;
 * js/booking.js renders the steps from the JSON configuration below, loads
 * the free times and posts the booking (libraries/Booking_request.php).
 *
 * $page      the Book Web Pages record (banner fields)
 * $crumbs    breadcrumb (see partials/breadcrumb.php)
 * $hasHours  FALSE when Website Settings has no readable opening hours
 * $config    catalogue, notes, URLs, form token and reCAPTCHA
 * $helpText  the phone prompt beside the steps
 */
$heading = trim((string) $page['banner_heading']) !== ''
    ? $page['banner_heading']
    : html_entity_decode((string) $page['page_name'], ENT_QUOTES, 'UTF-8');
$config['help'] = array(
    'text' => $helpText,
    'phone' => $site['phone'],
    'phoneHref' => $site['phone_href'],
);
?>
<main id="main">
    <div class="relative isolate">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-petal via-lilac to-background"></div>
        <div class="mx-auto w-full max-w-[86rem] px-5 pt-28 sm:px-8 sm:pt-32 lg:px-12 lg:pt-36">
            <?php $this->load->view('frontend/partials/breadcrumb', array('crumbs' => $crumbs, 'light' => FALSE)); ?>
        </div>
        <div class="mx-auto w-full max-w-[86rem] px-5 py-10 sm:px-8 lg:px-12">
            <?php if (trim((string) $page['banner_title']) !== '') { ?>
                <p class="section-label"><?php echo html_escape($page['banner_title']); ?></p>
            <?php } ?>
            <h1 class="mt-4 max-w-2xl text-[clamp(2rem,4.8vw,3.25rem)]"><?php echo html_escape($heading); ?></h1>
            <?php if (trim((string) $page['banner_text']) !== '') { ?>
                <p class="mt-5 max-w-xl text-lg text-muted-foreground"><?php echo html_escape($this->frontend_seo->plainText($page['banner_text'])); ?></p>
            <?php } ?>

            <?php if (!$hasHours) { ?>
                <p class="mt-10 max-w-2xl rounded-lg bg-lilac px-5 py-4 text-foreground-soft">
                    Online booking is not available right now.
                    <?php if ($site['phone_href'] !== '') { ?>
                        Please call the salon on
                        <a class="font-semibold text-primary-ink" href="<?php echo html_escape($site['phone_href']); ?>"><?php echo html_escape($site['phone']); ?></a>.
                    <?php } ?>
                </p>
            <?php } else { ?>
                <div data-booking-root>
                    <div aria-hidden="true" class="mt-10 animate-pulse rounded-lg bg-muted h-12 w-full"></div>
                    <div aria-hidden="true" class="mt-10 grid gap-10 lg:grid-cols-12">
                        <div class="space-y-4 lg:col-span-7">
                            <div class="animate-pulse rounded-lg bg-muted h-11 w-64"></div>
                            <div class="animate-pulse rounded-lg bg-muted h-28 w-full"></div>
                            <div class="animate-pulse rounded-lg bg-muted h-28 w-full"></div>
                        </div>
                        <div class="animate-pulse rounded-lg bg-muted h-80 lg:col-span-5"></div>
                    </div>
                    <noscript>
                        <p class="mt-10 max-w-2xl rounded-lg bg-lilac px-5 py-4 text-foreground-soft">
                            Booking online needs JavaScript.
                            <?php if ($site['phone_href'] !== '') { ?>
                                Please call the salon on
                                <a class="font-semibold text-primary-ink" href="<?php echo html_escape($site['phone_href']); ?>"><?php echo html_escape($site['phone']); ?></a>
                                instead.
                            <?php } ?>
                        </p>
                    </noscript>
                </div>
                <script type="application/json" id="booking-config"><?php echo json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
            <?php } ?>
        </div>
    </div>
</main>
<?php if ($hasHours && $config['recaptcha']['siteKey'] !== '') { ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($config['recaptcha']['siteKey']); ?>" defer></script>
<?php } ?>
