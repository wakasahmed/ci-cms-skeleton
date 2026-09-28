-- ---------------------------------------------------------------------------
-- Phase 5 — Frontend foundation content (PROJECT_PLAN.md)
--
-- Content only, no schema changes. Sets up the Web Pages records, the header
-- menu, the two footer menus and the Website Settings values that the new
-- layout (header, footer, CTA) reads, using the text of the /ci3/ design.
--
-- Run once against blossom_cms after phase-4-cleanup.sql. Re-running it is
-- safe: the new pages are only inserted when their slug does not exist yet,
-- and every other statement sets fixed values.
--
-- The logo files are uploads, not part of the repository. Before running
-- this on another environment, upload the Blossom logos through
-- Website Settings instead of relying on the file names below.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- 1. Web Pages: slugs that match the new URLs --------------------------------

UPDATE `pages`
SET `page_slug` = 'about', `page_name` = 'About', `menu_name` = 'About'
WHERE `page_id` = 2;

UPDATE `pages`
SET `page_slug` = 'faq', `page_name` = 'FAQ', `menu_name` = 'FAQ'
WHERE `page_id` = 6;

UPDATE `pages` SET `page_name` = 'Home', `menu_name` = 'Home' WHERE `page_id` = 1;
UPDATE `pages` SET `page_name` = 'Journal', `menu_name` = 'Journal' WHERE `page_id` = 8;

-- 2. Web Pages for the module listings and the terms page --------------------
-- Each listing page carries its own SEO fields and menu placement, the same
-- way the Blog page does for /blog. The IDs are fixed because
-- config/content_sections.php keys page sections by page ID.

INSERT INTO `pages` (
    `page_id`,
    `page_slug`, `page_name`, `menu_name`, `page_title`, `page_text`,
    `robots_index`, `robots_follow`, `show_top_banner`, `banner_overlay`,
    `page_added`, `page_updated`, `page_year`, `page_month`, `page_month_year`,
    `page_status`, `created_at`, `updated_at`, `page_slider`,
    `menu_order`, `menu_parent_id`, `menu_active`, `page_order`, `page_parent_id`
)
SELECT
    `new_pages`.`id`,
    `new_pages`.`slug`, `new_pages`.`name`, `new_pages`.`name`, '', '',
    1, 1, 0, 'No',
    NOW(), NOW(), YEAR(NOW()), MONTH(NOW()), DATE_FORMAT(NOW(), '%M %Y'),
    'Published', NOW(), NOW(), 0,
    0, 0, 0, 0, 0
FROM (
    SELECT 37 AS `id`, 'services' AS `slug`, 'Services' AS `name`
    UNION ALL SELECT 38, 'artists', 'Artists'
    UNION ALL SELECT 39, 'gallery', 'Gallery'
    UNION ALL SELECT 40, 'offers', 'Offers'
    UNION ALL SELECT 41, 'terms', 'Terms & Conditions'
) AS `new_pages`
WHERE NOT EXISTS (
    SELECT 1
    FROM `pages`
    WHERE `pages`.`page_slug` = `new_pages`.`slug`
        OR `pages`.`page_id` = `new_pages`.`id`
);

-- 3. Header menu (Manage > Menu) ---------------------------------------------
-- Services, Artists, Gallery, Offers, About, Journal, Contact. The logo links
-- home, so Home is not a menu item.

UPDATE `pages` SET `menu_active` = 0, `menu_parent_id` = 0;

UPDATE `pages`
SET
    `menu_active` = 1,
    `menu_order` = CASE `page_slug`
        WHEN 'services' THEN 1
        WHEN 'artists' THEN 2
        WHEN 'gallery' THEN 3
        WHEN 'offers' THEN 4
        WHEN 'about' THEN 5
        WHEN 'blog' THEN 6
        WHEN 'contact' THEN 7
    END
WHERE `page_slug` IN ('services', 'artists', 'gallery', 'offers', 'about', 'blog', 'contact');

-- 4. Footer menus (Manage > Foot) --------------------------------------------
-- One: the "Salon" column. Two: the legal links under the footer.
-- Three (email footer links) is left as it is.

UPDATE `pages`
SET
    `menu_active_one` = 0, `menu_parent_id_one` = 0,
    `menu_active_two` = 0, `menu_parent_id_two` = 0;

UPDATE `pages`
SET
    `menu_active_one` = 1,
    `menu_order_one` = CASE `page_slug`
        WHEN 'about' THEN 1
        WHEN 'artists' THEN 2
        WHEN 'gallery' THEN 3
        WHEN 'offers' THEN 4
        WHEN 'services' THEN 5
        WHEN 'blog' THEN 6
        WHEN 'faq' THEN 7
        WHEN 'contact' THEN 8
    END
WHERE `page_slug` IN ('about', 'artists', 'gallery', 'offers', 'services', 'blog', 'faq', 'contact');

UPDATE `pages`
SET
    `menu_active_two` = 1,
    `menu_order_two` = CASE `page_slug`
        WHEN 'privacy-policy' THEN 1
        WHEN 'terms' THEN 2
        WHEN 'cancellation-policy' THEN 3
    END
WHERE `page_slug` IN ('privacy-policy', 'terms', 'cancellation-policy');

-- 5. Website Settings --------------------------------------------------------
-- foot_col_1 heads the featured-services column, foot_col_2 the Foot menu one
-- column, foot_col_3 labels Foot menu two in the admin, foot_col_4 heads the
-- visit and contact column.

UPDATE `site_settings`
SET
    `logo` = '24a7c8d31d3b24449074d099ed6b604a.png',
    `logo_white` = 'ec6511af1744d67974fc8726315c3313.png',
    `logo_sticky` = NULL,
    `address` = CONCAT('2 Piekarska Street', '\n', '38-300 Gorlice, Poland'),
    `opening_hours` = CONCAT(
        'Monday – Friday | 9:00 AM – 5:00 PM', '\n',
        'Saturday | 9:00 AM – 2:00 PM', '\n',
        'Sunday | Closed'
    ),
    `website_intro` = 'Manicure, nail art and beauty treatments in the centre of Gorlice.',
    `foot_col_1` = 'Nails',
    `foot_col_2` = 'Salon',
    `foot_col_3` = 'Legal',
    `foot_col_4` = 'Visit & contact'
WHERE `id` = 1;
