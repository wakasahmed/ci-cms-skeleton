<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Document head and opening <body> for every public page.
 * Data comes from Frontend_layout: $meta, $site, $styles, $vendor_scripts, $scripts.
 */
$image = $meta['og_image'];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title><?php echo html_escape($meta['title']); ?></title>
    <?php if ($meta['description'] !== '') { ?>
        <meta name="description" content="<?php echo html_escape($meta['description']); ?>">
    <?php } ?>
    <?php if ($meta['keywords'] !== '') { ?>
        <meta name="keywords" content="<?php echo html_escape($meta['keywords']); ?>">
    <?php } ?>
    <meta name="robots" content="<?php echo html_escape($meta['robots']); ?>">
    <?php if ($meta['canonical'] !== '') { ?>
        <link rel="canonical" href="<?php echo html_escape($meta['canonical']); ?>">
    <?php } ?>

    <meta property="og:title" content="<?php echo html_escape($meta['og_title']); ?>">
    <?php if ($meta['og_description'] !== '') { ?>
        <meta property="og:description" content="<?php echo html_escape($meta['og_description']); ?>">
    <?php } ?>
    <meta property="og:site_name" content="<?php echo html_escape($meta['site_name']); ?>">
    <meta property="og:locale" content="en">
    <meta property="og:type" content="<?php echo html_escape($meta['og_type']); ?>">
    <?php if ($meta['canonical'] !== '') { ?>
        <meta property="og:url" content="<?php echo html_escape($meta['canonical']); ?>">
    <?php } ?>
    <?php if (!empty($image['url'])) { ?>
        <meta property="og:image" content="<?php echo html_escape($image['url']); ?>">
        <?php if (!empty($image['width']) && !empty($image['height'])) { ?>
            <meta property="og:image:width" content="<?php echo (int) $image['width']; ?>">
            <meta property="og:image:height" content="<?php echo (int) $image['height']; ?>">
        <?php } ?>
        <meta property="og:image:alt" content="<?php echo html_escape($meta['og_image_alt']); ?>">
    <?php } ?>
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo html_escape($meta['og_title']); ?>">
    <?php if ($meta['og_description'] !== '') { ?>
        <meta name="twitter:description" content="<?php echo html_escape($meta['og_description']); ?>">
    <?php } ?>

    <link rel="stylesheet" href="<?php echo html_escape($this->frontend_layout->assetUrl('css/app.css')); ?>">
    <link rel="stylesheet" href="<?php echo html_escape($this->frontend_layout->assetUrl('vendor/fontawesome/css/all.min.css')); ?>">
    <?php foreach ($styles as $style) { ?>
        <link rel="stylesheet" href="<?php echo html_escape($style); ?>">
    <?php } ?>

    <?php foreach ($vendor_scripts as $script) { ?>
        <script defer src="<?php echo html_escape($script); ?>"></script>
    <?php } ?>
    <script defer src="<?php echo html_escape($this->frontend_layout->assetUrl('vendor/jquery/jquery-4.0.0.min.js')); ?>"></script>
    <script defer src="<?php echo html_escape($this->frontend_layout->assetUrl('js/site.js')); ?>"></script>
    <?php foreach ($scripts as $script) { ?>
        <script defer src="<?php echo html_escape($script); ?>"></script>
    <?php } ?>
</head>
<body>
