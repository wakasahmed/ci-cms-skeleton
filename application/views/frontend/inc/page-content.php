<?php

$pageContent = isset($pageContent) ? trim((string) $pageContent) : '';

if ($pageContent === '') {
    return;
}
?>
<section class="bg-white py-14 sm:py-20">
  <div class="container">
    <div class="prose prose-lg page-contents mx-auto">
      <?php echo $pageContent ?>
    </div>
  </div>
</section>
