<?php

require_once __DIR__ . '/functions.php';

$hasOverview = trim((string) $tour['overview']) !== '';
$hasExperience = trim((string) $tour['experience']) !== '';
$hasHighlights = $hasExperience || !empty($attractions);
$hasItinerary = trim((string) $tour['itinerary']) !== '';
$hasJourney = !empty($journey) || $hasItinerary;
$hasPinfo = trim((string) $tour['pinfo']) !== '';
$hasPrepare = !empty($tour['prepare']) || $hasPinfo;
$hasGallery = !empty($gallery);
$hasGalleryIntro = trim((string) $tour['gallery']) !== '';
$hasVehicles = !empty($vehiclesForTour);
$hasVehicleInfo = trim((string) $tour['vehicle_info']) !== '';
$hasGuides = !empty($guidesForTour);
$hasGuideInfo = trim((string) $tour['guide_info']) !== '';
$hasFaqs = !empty($tour['faqs']);
$hasFaqsInfo = trim((string) $tour['faqs_info']) !== '';
$bookCtaLabel = $isExperience ? t('cta.bookThisExperience') : t('cta.bookThisTour');
$sectionNavigation = array_filter([
    'overview' => $hasOverview,
    'highlights' => $hasHighlights,
    'journey' => $hasJourney,
    'prepare' => $hasPrepare,
    'gallery' => $hasGallery,
    'vehicles' => $hasVehicles,
    'guides' => $hasGuides,
    'faqs' => $hasFaqs,
]);

require_once __DIR__ . '/header.php';
?>

<!-- ==================================================================
     Hero
     ============================================================== -->
<section class="relative isolate overflow-hidden bg-alam-900">
  <img src="<?php echo e(
      is_absolute_url($tour['background_image'])
          ? $tour['background_image']
          : site_base_url($tour['background_image'])
  ) ?>" alt="" aria-hidden="true"
       width="960" height="640" fetchpriority="high" decoding="async"
       class="absolute inset-0 -z-10 h-full w-full object-cover opacity-60">
  <div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/90 via-alam-900/70 to-alam-800/35" aria-hidden="true"></div>

  <div class="container">
    <div class="grid gap-10 py-12 sm:py-16 lg:grid-cols-[1.25fr_1fr] lg:gap-14 lg:py-20">

      <div class="max-w-2xl">
        <?php echo breadcrumb($breadcrumbItems) ?>

        <p class="eyebrow mt-6 text-white/70"><?php echo e($tour['category']) ?></p>
        <h1 class="mt-3.5 text-h1 text-white text-shadow-hero"><?php echo e($tour['title']) ?></h1>
        <p class="mt-4 max-w-xl text-body text-white/80"><?php echo e($tour['summary']) ?></p>

        <div class="mt-9 flex flex-wrap items-center gap-3">
          <a href="<?php echo e($bookUrl) ?>" class="btn-primary btn-lg">
            <?php echo e($bookCtaLabel) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
          </a>
          <?php if ($hasJourney): ?>
            <a href="#journey" class="btn-ghost-light btn-lg"><?php echo e(t('tour.seeJourney')) ?></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- At a glance: one row per fact — icon, label, then value -->
      <div class="self-center lg:w-full lg:max-w-sm lg:justify-self-end">
        <div class="rounded-feature border border-white/15 bg-alam-900/50 p-6 backdrop-blur-sm sm:p-7">
          <h2 class="text-h3 text-white"><?php echo e(t('tour.glance')) ?></h2>

          <dl class="mt-6 space-y-5">
            <?php foreach ([
                ['tag',      t('meta.startingFrom'), $tour['price'] > 0 ? price($tour['price']) : ''],
                ['clock',    t('meta.duration'),  $tour['duration']],
                ['users',    t('meta.groupSize'), $capacityLabel],
                ['language', t('meta.languages'), $tour['languages']],
            ] as $m): if ($m[2] === '') continue; ?>
              <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/15 bg-white/10 text-white">
                  <?php echo icon($m[0], 'icon-sm') ?>
                </span>
                <div class="min-w-0">
                  <dt class="text-eyebrow font-bold uppercase text-white/60"><?php echo e($m[1]) ?></dt>
                  <dd class="mt-1 text-body-sm font-semibold text-white"><?php echo e($m[2]) ?></dd>
                </div>
              </div>
            <?php endforeach; ?>
          </dl>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================================================================
     In-page navigation
     ============================================================== -->
<?php if (!empty($sectionNavigation)): ?>
  <nav class="sticky top-[var(--frontend-header-h)] z-30 border-b border-line bg-white/95 backdrop-blur" data-section-nav aria-label="<?php echo e(t('tour.sections.label')) ?>">
    <div class="container">
      <ul class="no-scrollbar relative flex items-center gap-1 overflow-x-auto py-2.5" data-section-nav-list>
        <?php /* The anchor ids are stable in both locales — they are what the
                 in-page navigation and the scroll spy key off. The pill itself
                 is one shared element that main.js slides under the active
                 link, rather than each link getting its own background. */ ?>
        <span class="section-nav-indicator" data-section-nav-indicator aria-hidden="true"></span>
        <?php foreach (array_keys($sectionNavigation) as $sectionId): ?>
          <li>
            <a href="#<?php echo e($sectionId) ?>"
               class="section-nav-link"
               data-section-nav-link>
              <?php echo e(isset($sectionNavigationLabels[$sectionId]) ? $sectionNavigationLabels[$sectionId] : t('tour.section.' . $sectionId)) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>
<?php endif; ?>

<div class="bg-white py-12 sm:py-14 lg:py-16">
  <div class="container">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_352px] lg:gap-12">

      <!-- ============ Main content ============ -->
      <div class="min-w-0">
       <?php if ($hasOverview): ?>
        <!-- Overview -->
        <section id="overview" class="scroll-mt-36">
          <div class="page-contents">
              <?php echo $tour['overview'] ?>
          </div>
        </section>

        <div class="hairline my-12"></div>
 <?php endif; ?>
        <!-- Places / highlights -->
        <?php if ($hasHighlights): ?>
          <section id="highlights" class="scroll-mt-36">
          <?php
          if($hasExperience):?>  
          <div class="page-contents">
              <?php echo $tour['experience'] ?>
              </div>
          <?php endif; ?>
            <?php if (!empty($attractions)): ?>
              <ul class="mt-6 grid gap-5 sm:grid-cols-2">
                <?php foreach ($attractions as $attraction): ?>
                  <li class="flex"><?php echo attraction_card($attraction) ?></li>
                <?php endforeach; ?>
              </ul>
              <?php echo attraction_data($attractions) ?>
            <?php endif; ?>
          </section>

          <div class="hairline my-12"></div>
        <?php endif; ?>

        <!-- Journey -->
        <?php if ($hasJourney): ?>
          <section id="journey" class="scroll-mt-36">
           <?php
          if($hasItinerary):?>  
          <div class="page-contents">
              <?php echo $tour['itinerary'] ?>
              </div>
          <?php endif; ?>

          <ol class="journey-timeline mt-8">
            <?php foreach ($journey as $i => $j): ?>
              <li class="journey-step">
                <?php /* An elapsed-time offset like 2:15 is a number pair, not
                         prose: dir="ltr" keeps the colon between the two halves
                         rather than letting the bidi algorithm move it. */ ?>
                <p class="journey-time" dir="ltr"><?php echo e($j['time']) ?></p>

                <div class="journey-rail" aria-hidden="true">
                  <span class="journey-dot"></span>
                </div>

                <div class="journey-body">
                  <div class="journey-copy">
                    <h3 class="journey-title"><?php echo e($j['title']) ?></h3>
                    <p class="mt-2 text-body-sm text-ink-muted"><?php echo e($j['text']) ?></p>
                  </div>

                  <?php if ($j['image'] !== ''): ?>
                    <img src="<?php echo e(is_absolute_url($j['image']) ? $j['image'] : site_base_url($j['image'])) ?>"
                         <?php if (!is_absolute_url($j['image'])): ?>srcset="<?php echo e(srcset($j['image'])) ?>"<?php endif; ?>
                         sizes="(min-width: 640px) 208px, 100vw"
                         alt="<?php echo e($j['alt']) ?>"
                         width="960" height="640" loading="lazy" decoding="async"
                         class="journey-thumb">
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
          </section>

          <div class="hairline my-12"></div>
        <?php endif; ?>

        <!-- Before your tour -->
        <?php if ($hasPrepare): ?>
          <section id="prepare" class="scroll-mt-36">
          <?php
          if ($hasPinfo): ?>
          <div class="page-contents">
              <?php echo $tour['pinfo'] ?>
              </div>
          <?php endif; ?>
          <dl class="mt-6 grid gap-x-8 gap-y-0 sm:grid-cols-2">
            <?php foreach ($tour['prepare'] as $p): ?>
              <div class="flex items-start justify-between gap-4 border-b border-line py-3.5">
                <dt class="text-meta font-semibold text-ink"><?php echo e($p['label']) ?></dt>
                <dd class="max-w-[62%] text-end text-body-sm text-ink-muted"><?php echo nl2br(e($p['value'])) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
          </section>

          <div class="hairline my-12"></div>
        <?php endif; ?>

        <!-- Gallery -->
        <?php if ($hasGallery): ?>
          <section id="gallery" class="scroll-mt-36">
          <?php
          if ($hasGalleryIntro): ?>
          <div class="page-contents">
              <?php echo $tour['gallery'] ?>
              </div>
          <?php endif; ?>

          <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3" data-gallery>
            <?php foreach ($gallery as $i => $g): ?>
              <li>
                <button type="button" class="group block w-full overflow-hidden rounded-card bg-alam-100" data-gallery-item
                        data-gallery-full="<?php echo e(is_absolute_url($g['full']) ? $g['full'] : site_base_url($g['full'])) ?>">
                  <span class="sr-only"><?php echo e(t('tour.gallery.open', ['alt' => $g['alt']])) ?></span>
                  <img src="<?php echo e(is_absolute_url($g['src']) ? $g['src'] : site_base_url($g['src'])) ?>"
                       <?php if (!is_absolute_url($g['src'])): ?>srcset="<?php echo e(srcset($g['src'])) ?>"<?php endif; ?>
                       sizes="(min-width: 640px) 30vw, 45vw"
                       alt="<?php echo e($g['alt']) ?>"
                       width="600" height="600" loading="lazy" decoding="async"
                       class="aspect-square w-full object-cover transition-transform duration-500 group-hover:scale-105">
                </button>
              </li>
            <?php endforeach; ?>
          </ul>
          </section>

          <div class="hairline my-12"></div>
        <?php endif; ?>

        <!-- Vehicles for this tour -->
        <?php if ($hasVehicles): ?>
          <section id="vehicles" class="scroll-mt-36">
            <?php
            if ($hasVehicleInfo): ?>
            <div class="page-contents">
                <?php echo $tour['vehicle_info'] ?>
                </div>
            <?php endif; ?>
            <ul class="mt-6 space-y-5">
              <?php foreach ($vehiclesForTour as $v): ?>
                <li class="card flex flex-col overflow-hidden sm:flex-row">
                  <?php if ($v['image'] !== ''): ?>
                    <div class="p-4 sm:w-1/4 sm:shrink-0 sm:p-3">
                      <img src="<?php echo e(is_absolute_url($v['image']) ? $v['image'] : site_base_url($v['image'])) ?>"
                           <?php if (!is_absolute_url($v['image'])): ?>srcset="<?php echo e(srcset($v['image'])) ?>"<?php endif; ?>
                           sizes="(min-width: 640px) 25vw, 100vw"
                           alt="<?php echo e($v['name']) ?>"
                           width="640" loading="lazy" decoding="async"
                           class="w-full h-auto rounded-card">
                    </div>
                  <?php endif; ?>
                  <div class="min-w-0 p-4 sm:p-5">
                    <h3 class="text-card-title font-bold text-ink"><?php echo e($v['name']) ?></h3>
                    <p class="mt-1 text-meta font-semibold text-alam-700"><?php echo e(tn('count.upTo', $v['capacity'])) ?></p>
                    <?php if (trim((string) $v['details']) !== ''): ?>
                      <p class="mt-1.5 text-body-sm text-ink-muted"><?php echo nl2br(e($v['details'])) ?></p>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>

          <div class="hairline my-12"></div>
        <?php endif; ?>

        <!-- Guides for this tour -->
        <?php if ($hasGuides): ?>
          <section id="guides" class="scroll-mt-36">
            <?php
            if ($hasGuideInfo): ?>
            <div class="page-contents">
                <?php echo $tour['guide_info'] ?>
                </div>
            <?php endif; ?>
            <ul class="mt-6 space-y-5">
              <?php foreach (array_slice($guidesForTour, 0, 4) as $g): ?>
                <li><?php echo guide_card($g, ['layout' => 'list']) ?></li>
              <?php endforeach; ?>
            </ul>
          </section>

          <div class="hairline my-12"></div>
        <?php endif; ?>

        <!-- FAQs -->
        <?php if ($hasFaqs): ?>
          <section id="faqs" class="scroll-mt-36">
          <?php
          if ($hasFaqsInfo): ?>
          <div class="page-contents">
              <?php echo $tour['faqs_info'] ?>
              </div>
          <?php endif; ?>
          <div class="card mt-6 px-5 sm:px-6">
            <?php echo faq_accordion($tour['faqs'], 'tour-faq', 0) ?>
          </div>
          <a href="<?php echo e(url('faqs.php')) ?>" class="btn-link mt-5"<?php echo htmx_boost_attr() ?>>
            <?php echo e(t('tour.faqs.cta')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
          </a>
          </section>
        <?php endif; ?>
      </div>

      <!-- ============ Sticky booking panel ============ -->
      <aside class="lg:sticky lg:top-[calc(var(--frontend-header-h)+64px)] lg:self-start" aria-labelledby="book-panel-title">
        <div class="card overflow-hidden">
          <div class="border-b border-line bg-alam-50 px-5 py-5">
            <p class="text-meta font-semibold text-ink-muted"><?php echo e(t('tour.panel.startingFrom')) ?></p>
            <p class="mt-1 flex items-baseline gap-2">
              <span class="text-2xl font-extrabold text-alam-800"><?php echo e(price($tour['price'])) ?></span>
            </p>
            <?php if (trim((string) $tour['price_details']) !== ''): ?>
            <p class="mt-1.5 text-body-sm text-ink-muted">
              <?php echo nl2br(e($tour['price_details'])) ?>
            </p>
            <?php endif; ?>
          </div>

          <div class="px-5 py-5">
            <h2 id="book-panel-title" class="text-body-sm font-bold text-ink"><?php echo e(t('tour.panel.title')) ?></h2>
            <dl class="mt-4 divide-y divide-line">
              <div class="summary-row"><dt class="summary-label"><?php echo e(t('meta.duration')) ?></dt><dd class="summary-value"><?php echo e($tour['duration']) ?></dd></div>
              <div class="summary-row"><dt class="summary-label"><?php echo e(t('meta.maxGroup')) ?></dt><dd class="summary-value"><?php echo e($capacityLabel) ?></dd></div>
              <?php if ($tour['languages'] !== ''): ?>
                <div class="summary-row"><dt class="summary-label"><?php echo e(t('meta.languages')) ?></dt><dd class="summary-value"><?php echo e($tour['languages']) ?></dd></div>
              <?php endif; ?>
              <div class="summary-row"><dt class="summary-label"><?php echo e(t('meta.walking')) ?></dt><dd class="summary-value"><?php echo e($tour['walking']) ?></dd></div>
              <div class="summary-row"><dt class="summary-label"><?php echo e(t('meta.pickup')) ?></dt><dd class="summary-value max-w-[60%]"><?php echo e($tour['pickup']) ?></dd></div>
            </dl>

            <a href="<?php echo e($bookUrl) ?>" class="btn-primary mt-5 w-full">
              <?php echo e($bookCtaLabel) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
            </a>
            <a href="<?php echo e(url('plan-your-trip.php')) ?>" class="btn-outline mt-2.5 w-full"><?php echo e(t('tour.panel.askFirst')) ?></a>
          </div>
        </div>

        <?php if ($helpCard !== null): ?>
          <?php echo help_card(array_merge($helpCard, ['class' => 'mt-4'])) ?>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</div>

<!-- Mobile sticky book bar -->
<div class="sticky bottom-0 z-40 border-t border-line bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
  <div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
      <p class="truncate text-meta text-ink-muted"><?php echo e(t('tour.panel.startingFrom')) ?></p>
      <p class="text-card-title font-extrabold text-alam-800"><?php echo e(price($tour['price'])) ?></p>
    </div>
    <a href="<?php echo e($bookUrl) ?>" class="btn-primary shrink-0"><?php echo e($bookCtaLabel) ?></a>
  </div>
</div>

<!-- Related tours -->
<section class="border-t border-line bg-surface-soft py-16 sm:py-20">
  <div class="container">
    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
      <?php echo section_head([
          'eyebrow' => t('tour.related.eyebrow'),
          'title'   => t('tour.related.title'),
      ]) ?>
      <a href="<?php echo e(url('tours.php')) ?>" class="btn-outline shrink-0"<?php echo htmx_boost_attr() ?>>
        <?php echo e(t('cta.viewAllTours')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
      </a>
    </div>
    <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
      <?php foreach ($related as $r): ?>
        <li><?php echo tour_card($r) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php /* TouristTrip structured data is part of the @graph printed by header.php. */ ?>

<?php require_once __DIR__ . '/footer.php'; ?>
