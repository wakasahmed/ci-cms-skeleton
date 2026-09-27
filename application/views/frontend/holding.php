<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Temporary holding page for every public URL until the Blossom frontend is
 * built (PROJECT_PLAN.md, Phase 5). Standalone on purpose: it must not depend
 * on the retired Alam frontend or on the Tailwind build that does not exist
 * yet, so its few styles are inline. Delete this view in Phase 5.
 */
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$telephone = preg_replace('/[^\d+]/', '', $phone);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $is_not_found ? 'Page not found | ' : ''; ?><?php echo $escape($site_name); ?></title>
    <style>
        :root {
            color-scheme: light;
            --background: #fdf6fd;
            --foreground: #2b1f2a;
            --muted: #6f5f6d;
            --primary-ink: #a73a9b;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
            box-sizing: border-box;
            background: var(--background);
            color: var(--foreground);
            font-family: Georgia, "Times New Roman", serif;
            text-align: center;
        }

        main {
            max-width: 36rem;
        }

        h1 {
            margin: 0 0 1rem;
            font-size: clamp(2rem, 6vw, 3rem);
            font-weight: 500;
        }

        p {
            margin: 0 0 .75rem;
            color: var(--muted);
            font-family: system-ui, sans-serif;
            line-height: 1.6;
        }

        a {
            color: var(--primary-ink);
        }

        address {
            font-style: normal;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <main>
        <h1><?php echo $escape($site_name); ?></h1>
        <?php if ($is_not_found) { ?>
            <p>This page could not be found. Our new website is on its way.</p>
        <?php } else { ?>
            <p>Our new website is on its way.</p>
        <?php } ?>
        <?php if ($telephone !== '') { ?>
            <p>To book, call <a href="tel:<?php echo $escape($telephone); ?>"><?php echo $escape($phone); ?></a>.</p>
        <?php } ?>
        <?php if ($address !== '') { ?>
            <p><address><?php echo $escape($address); ?></address></p>
        <?php } ?>
    </main>
</body>
</html>
