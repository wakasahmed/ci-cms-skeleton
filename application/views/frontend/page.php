<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
}
?>
<section class="py-14 sm:py-20">
  <div class="container">
    <div class="prose prose-lg page-contents mx-auto">
      <?php echo $pageContent ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
