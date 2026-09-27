<?php
/**
 * Shared site footer + global scripts.
 *
 * The brand column uses images/alam/logo-white.png — regenerate it with
 * `php tools/prepare-logo.php <source.png>` if the brand artwork changes.
 *
 * The email address, the telephone number and the licence number are wrapped in
 * <bdi> or given dir="ltr": they are Latin-script, left-to-right values sitting
 * inside right-to-left copy, and without isolation the bidi algorithm moves
 * their punctuation to the wrong end (+966 57 979 0028 renders as 0028 966+).
 */


$biz = business();
$footer = footer_content();
?>
</main>

<footer class="relative overflow-hidden bg-alam-800 text-white">
  <div class="pattern-grid absolute inset-0 opacity-25" aria-hidden="true"></div>
  <div class="absolute -start-24 top-10 h-72 w-72 rounded-full bg-alam-500/25 blur-3xl" aria-hidden="true"></div>

  <div class="container relative">
    <div class="grid gap-10 py-14 sm:py-16 lg:grid-cols-[1.6fr_1fr_1fr_1.3fr] lg:gap-12">

      <!-- Brand -->
      <div>
        <img src="<?php echo e($biz['logo_white']) ?>" alt="<?php echo e($biz['name']) ?>"
             loading="lazy" class="h-20 w-auto">
        <h2 class="mt-5 text-eyebrow font-bold uppercase text-white/55">
          <?php echo e($footer['about_heading']) ?>
        </h2>
        <p class="mt-3 max-w-sm text-body-sm text-white/70">
          <?php echo nl2br($footer['about']) ?>
        </p>
        <?php if ($biz['social_links']): ?>
          <ul class="mt-6 flex flex-wrap gap-2.5">
          <?php foreach ($biz['social_links'] as $social): ?>
            <li>
              <a href="<?php echo e($social['url']) ?>" aria-label="<?php echo e(t($social['label'], ['name' => $biz['name']])) ?>"
                 class="grid h-10 w-10 place-items-center rounded-control border border-white/20 text-white/80 transition-colors hover:border-white/50 hover:text-white">
                <?php echo icon($social['icon'], 'icon-md') ?>
              </a>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <!-- Explore -->
      <nav aria-labelledby="footer-explore">
        <h2 id="footer-explore" class="text-eyebrow font-bold uppercase text-white/55"><?php echo e($footer['explore_heading']) ?></h2>
        <ul class="mt-5 space-y-3 text-meta">
          <?php foreach (footer_nav('one') as $item): ?>
            <li><a href="<?php echo e($item['href']) ?>" class="text-white/70 transition-colors hover:text-white"<?php echo htmx_nav_attr($item) ?>><?php echo e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <!-- Support -->
      <nav aria-labelledby="footer-support">
        <h2 id="footer-support" class="text-eyebrow font-bold uppercase text-white/55"><?php echo e($footer['support_heading']) ?></h2>
        <ul class="mt-5 space-y-3 text-meta">
          <?php foreach (footer_nav('two') as $item): ?>
            <li><a href="<?php echo e($item['href']) ?>" class="text-white/70 transition-colors hover:text-white"<?php echo htmx_nav_attr($item) ?>><?php echo e($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <!-- Contact -->
      <div>
        <h2 class="text-eyebrow font-bold uppercase text-white/55"><?php echo e($footer['contact_heading']) ?></h2>
        <?php if ($footer['contact_text'] !== ''): ?>
          <p class="mt-3 text-body-sm text-white/70"><?php echo e($footer['contact_text']) ?></p>
        <?php endif; ?>
        <ul class="mt-5 space-y-4 text-meta">
          <?php if ($footer['address'] !== ''): ?>
            <li class="flex items-start gap-3">
              <?php echo icon('map-pin', 'mt-0.5 icon-md shrink-0 text-alam-300') ?>
              <span class="text-white/70"><?php echo nl2br($footer['address']) ?></span>
            </li>
          <?php endif; ?>
          <?php if ($biz['phone'] !== ''): ?>
            <li class="flex items-start gap-3">
              <?php echo icon('phone', 'mt-0.5 icon-md shrink-0 text-alam-300') ?>
              <a href="tel:<?php echo e($biz['phone_href']) ?>" class="text-white/70 transition-colors hover:text-white"><bdi dir="ltr"><?php echo e($biz['phone']) ?></bdi></a>
            </li>
          <?php endif; ?>
          <?php if ($biz['email'] !== ''): ?>
          <li class="flex items-start gap-3">
            <?php echo icon('mail', 'mt-0.5 icon-md shrink-0 text-alam-300') ?>
            <a href="mailto:<?php echo e($biz['email']) ?>" class="break-all text-white/70 transition-colors hover:text-white"><bdi dir="ltr"><?php echo e($biz['email']) ?></bdi></a>
          </li>
          <?php endif; ?>
        </ul>
        <?php /* Payment title/icons from Website Settings replace the booking
                 button; the button stays as the fallback when neither is set. */ ?>
        <?php if ($footer['payment_title'] !== '' || $footer['payment_icons']): ?>
          <div class="mt-6">
            <?php if ($footer['payment_title'] !== ''): ?>
              <h3 class="text-eyebrow font-bold uppercase text-white/55"><?php echo e($footer['payment_title']) ?></h3>
            <?php endif; ?>
            <?php if ($footer['payment_icons']): ?>
              <img src="<?php echo e($footer['payment_icons']['src']) ?>"
                   srcset="<?php echo e($footer['payment_icons']['src']) ?> 1x, <?php echo e($footer['payment_icons']['src_2x']) ?> 2x"
                   alt="<?php echo e(t('footer.paymentMethods')) ?>"
                   loading="lazy" class="<?php echo $footer['payment_title'] !== '' ? 'mt-3 ' : '' ?>h-auto w-full max-w-xs">
            <?php endif; ?>
          </div>
        <?php else: ?>
          <button type="button" class="btn mt-6 w-full bg-white text-alam-800 hover:bg-alam-50" data-booking-picker-open aria-haspopup="dialog" aria-controls="booking-picker">
            <?php echo e(t('cta.bookTour')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="flex flex-col gap-3 border-t border-white/10 py-6 text-meta text-white/55 sm:flex-row sm:items-center sm:justify-between">
      <p><?php echo e(str_replace('[YEAR]', date('Y'), $footer['copyright'])) ?></p>
      <?php /* Label and number are separate so the licence number can carry its
               own direction isolation rather than being embedded in a sentence. */ ?>
      <p><?php echo e($footer['license']) ?></p>
    </div>
  </div>
</footer>

<?php require __DIR__ . '/partials/booking_picker.php'; ?>

<?php if (!empty($config['htmx'])): ?>
  <?php /* Partial-navigation chrome for js/htmx-init.js: the bar is HTMX's
           request indicator, and the notice (an Alpine component) appears
           only when a partial navigation cannot reach the server. Both sit
           outside <main>, so a swap never replaces them. */ ?>
  <div id="page-progress" class="page-progress" aria-hidden="true"<?php echo ENVIRONMENT === 'development' ? ' data-debug' : '' ?>></div>

  <div x-data="navigationNotice"
       x-on:alam-navigation-error.window="show($event.detail.url)"
       x-on:keydown.escape.window="dismiss()"
       aria-live="assertive">
    <div x-cloak
         x-show="open"
         class="fixed inset-x-4 bottom-4 z-[60] rounded-card border border-line-strong bg-white p-4 shadow-panel sm:inset-x-auto sm:end-4 sm:w-96">
      <p class="text-body-sm text-ink"><?php echo e(t('navigation.loadError')) ?></p>
      <div class="mt-3 flex items-center gap-3">
        <a x-bind:href="url" class="btn-primary btn-sm"><?php echo e(t('navigation.retry')) ?></a>
        <button type="button" class="btn-link" x-on:click="dismiss()"><?php echo e(t('navigation.dismiss')) ?></button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script src="<?php echo e(asset('js/main.js')) ?>" defer></script>
<script src="<?php echo e(asset('js/booking-picker.js')) ?>" defer></script>
<?php if (!empty($config['sweetalert2'])): ?>
<script src="<?php echo e(base_url('assets/admin/vendor/sweetalert2/sweetalert2.all.min.js')); ?>" defer></script>
<?php endif; ?>
<?php if (!empty($config['moyasar'])): ?>
<script src="https://cdn.moyasar.com/mpf/<?php echo rawurlencode(MOYASAR_FORM_VERSION); ?>/moyasar.js" defer></script>
<?php endif; ?>
<?php foreach (page_scripts($config) as $s): ?>
<script src="<?php echo e(asset($s)) ?>" defer></script>
<?php endforeach; ?>
<?php echo setting_value('script_before_body') ?>
</body>
</html>
