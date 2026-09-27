<?php
/**
 * Homepage hero.
 *
 * The background is a Swiper fade slider (see js/hero-slider.js). The eyebrow,
 * heading, paragraph and buttons all cross-fade in step with it, each pulled
 * from that slide's own DB record (`slider` table); the feature strip and
 * availability form (slider_tour_search.php) stay fixed across every slide.
 *
 * Each slide's purple overlay is admin-controlled (Manage > Sliders,
 * `overlay` = Yes/No) and rendered as part of that slide's own swiper-slide,
 * so it cross-fades with its photo instead of applying to the whole hero.
 *
 * SEO/a11y note: only the FIRST panel's heading is an <h1> — the page keeps one
 * canonical heading. Panels 2 and 3 repeat the same visual treatment with <p>,
 * and every inactive panel is aria-hidden, so assistive tech only ever reads the
 * message currently on screen. All copy is server-rendered, so it is crawlable
 * and slide 1 still reads correctly with JavaScript disabled.
 *
 * Stacking order inside the isolated section:
 *   -z-30  slide images (each with its own optional overlay)
 *   -z-10  geometric pattern
 *    auto  content + availability form
 */


$slides = [];
$buttonClasses = ['btn-primary btn-lg', 'btn-ghost-light btn-lg'];

foreach (($slideRows ?? []) as $row) {
    if (!is_array($row)) {
        continue;
    }

    $buttons = [];

    foreach ([1, 2] as $buttonNumber) {
        $button = button_link(
            row_text($row, 'button_' . $buttonNumber . '_text'),
            $row['button_' . $buttonNumber . '_url'] ?? null,
            $row['button_' . $buttonNumber . '_icon'] ?? null,
            'Right',
            $buttonClasses[$buttonNumber - 1],
            'icon-sm icon-flip',
            $row['button_' . $buttonNumber . '_target'] ?? null
        );

        if ($button !== '') {
            $buttons[] = $button;
        }
    }

    $slides[] = [
        'image' => upload_thumb(
            'slider',
            $row['image'] ?? null,
            1920,
            1080,
            'images/alam/hero/prophets-mosque-plaza.webp'
        ),
        'alt' => row_text($row, 'heading'),
        'eyebrow' => row_text($row, 'pre_heading'),
        'heading' => row_text($row, 'heading'),
        'text' => row_text($row, 'text'),
        'overlay' => trim((string) ($row['overlay'] ?? '')) === 'Yes',
        'eyebrow_style' => text_gradient_style(
            $row['banner_pre_heading_color_1'] ?? null,
            $row['banner_pre_heading_color_2'] ?? null
        ),
        'heading_style' => text_gradient_style(
            $row['banner_heading_color_1'] ?? null,
            $row['banner_heading_color_2'] ?? null
        ),
        'text_style' => text_gradient_style(
            $row['banner_text_color_1'] ?? null,
            $row['banner_text_color_2'] ?? null
        ),
        'buttons' => $buttons,
    ];
}
?>
<?php if (!empty($slides)): ?>
<section class="relative isolate overflow-hidden bg-alam-900">

  <!-- Layer 1: rotating background photography (Swiper, fade only) -->
  <div class="hero-slider absolute inset-0 -z-30" data-hero-slider>
    <div class="swiper h-full w-full">
      <div class="swiper-wrapper">
        <?php foreach ($slides as $i => $slide): ?>
          <div class="swiper-slide">
            <img src="<?php echo e($slide['image']) ?>"
                 alt="<?php echo e($slide['alt']) ?>"
                 width="1920" height="1080"
                 <?php echo $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                 decoding="async"
                 class="h-full w-full object-cover object-[60%_center]">
            <?php if ($slide['overlay']): ?>
              <!-- Brand purple overlay — dense behind the copy, easing off towards
                   the far edge so the photography stays readable. The gradient follows
                   the reading direction: left-to-right in English, right-to-left in
                   Arabic, so the dense end is always the end the text starts at. The
                   photograph itself is NOT mirrored — flipping architecture would
                   misrepresent the place. Admin-controlled per slide (Manage > Sliders). -->
              <div class="absolute inset-0 bg-gradient-to-r from-alam-900/85 via-alam-900/60 to-alam-800/20 rtl:bg-gradient-to-l" aria-hidden="true"></div>
              <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-alam-900/70 to-transparent" aria-hidden="true"></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Layer 2: geometric motif, kept faint so it reads under the photography -->
  <div class="pattern-grid absolute inset-0 -z-10 opacity-15" aria-hidden="true"></div>

  <div class="container">
    <div class="grid items-center gap-10 py-16 sm:py-20 lg:grid-cols-[1.15fr_1fr] lg:gap-14 lg:py-24">

      <div class="max-w-xl">

        <!-- Rotating copy. Panels share one grid cell so the hero is as tall as
             the longest message and nothing below it shifts between slides. -->
        <div class="grid" data-hero-copy>
          <?php foreach ($slides as $i => $slide): ?>
            <div class="col-start-1 row-start-1 transition-[opacity,transform] duration-700 ease-out<?php echo $i === 0 ? '' : ' pointer-events-none opacity-0' ?>"
                 data-hero-panel="<?php echo $i ?>"
                 <?php echo $i === 0 ? '' : 'aria-hidden="true"' ?>>
              <?php if (!empty($slide['eyebrow'])): ?>
                <p class="eyebrow<?php echo $slide['eyebrow_style'] === '' ? ' text-white/70' : '' ?>"<?php echo $slide['eyebrow_style'] !== '' ? ' style="' . e($slide['eyebrow_style']) . '"' : '' ?>><?php echo e($slide['eyebrow']) ?></p>
              <?php endif; ?>
              <?php if (!empty($slide['heading'])): ?>
                <?php if ($i === 0): ?>
                  <h1 class="mt-4 text-display text-shadow-hero<?php echo $slide['heading_style'] === '' ? ' text-white' : '' ?>"<?php echo $slide['heading_style'] !== '' ? ' style="' . e($slide['heading_style']) . '"' : '' ?>><?php echo e($slide['heading']) ?></h1>
                <?php else: ?>
                  <p class="mt-4 text-display font-semibold text-shadow-hero<?php echo $slide['heading_style'] === '' ? ' text-white' : '' ?>"<?php echo $slide['heading_style'] !== '' ? ' style="' . e($slide['heading_style']) . '"' : '' ?>><?php echo e($slide['heading']) ?></p>
                <?php endif; ?>
              <?php endif; ?>
              <?php if (!empty($slide['text'])): ?>
                <p class="mt-5 max-w-lg text-body sm:text-lg<?php echo $slide['text_style'] === '' ? ' text-white/80' : '' ?>"<?php echo $slide['text_style'] !== '' ? ' style="' . e($slide['text_style']) . '"' : '' ?>>
                  <?php echo e($slide['text']) ?>
                </p>
              <?php endif; ?>
              <?php if (!empty($slide['buttons'])): ?>
                <div class="mt-8 flex flex-wrap gap-3">
                  <?php foreach ($slide['buttons'] as $buttonHtml): ?>
                    <?php echo $buttonHtml ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Subtle slide indicators; Swiper fills this in and hides it if unused -->
        <div class="hero-pagination mt-8 hidden" data-hero-pagination></div>
      </div>

      <?php require_once __DIR__ . '/slider_tour_search.php'; ?>
    </div>
  </div>
</section>
<?php endif; ?>
