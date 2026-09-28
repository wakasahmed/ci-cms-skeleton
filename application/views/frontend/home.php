<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Home page. The sections (hero, services, gallery, artists, offers,
 * testimonials, location) are built on CMS data in Phase 6 of PROJECT_PLAN.md.
 */
?>
<main id="main">
    <h1 class="sr-only"><?php echo html_escape($site['name']); ?></h1>
    <div class="pt-20 lg:pt-24"></div>
    <?php $this->load->view('frontend/partials/cta_band'); ?>
</main>
