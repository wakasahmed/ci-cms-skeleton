-- ---------------------------------------------------------------------------
-- Phase 6 — Booking request content (PROJECT_PLAN.md, decision D1)
--
-- 1. The Book page (Web Pages 42, /book): heading, lead and meta from the
--    /ci3/ booking page, and its page sections (wizard notes and the
--    confirmation screen). It is not in any menu; the header's "Book
--    appointment" button links to it.
-- 2. Email template 2: the client's "request received" email, with the
--    appointment short tags (config/short_tags.php).
--
-- Safe to run again.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `pages` (
    `page_id`,
    `page_slug`, `page_name`, `menu_name`, `page_title`, `page_text`,
    `robots_index`, `robots_follow`, `show_top_banner`, `banner_overlay`,
    `page_added`, `page_updated`, `page_year`, `page_month`, `page_month_year`,
    `page_status`, `created_at`, `updated_at`, `page_slider`,
    `menu_order`, `menu_parent_id`, `menu_active`, `page_order`, `page_parent_id`
)
SELECT
    42,
    'book', 'Book', 'Book', '', '',
    1, 1, 0, 'No',
    NOW(), NOW(), YEAR(NOW()), MONTH(NOW()), DATE_FORMAT(NOW(), '%M %Y'),
    'Published', NOW(), NOW(), 0,
    0, 0, 0, 0, 0
WHERE NOT EXISTS (
    SELECT 1 FROM `pages` WHERE `page_slug` = 'book' OR `page_id` = 42
);

UPDATE `pages` SET
    `banner_title` = 'Booking',
    `banner_heading` = 'Your next appointment is four simple steps away',
    `banner_text` = 'Choose what you would like, who you would like to see and when. Not sure yet? Book a manicure and we’ll decide together at the table.',
    `banner_background` = '',
    `meta_description` = 'Book a manicure, gel nails, nail art or a beauty treatment at Blossom Ewa Mazur in Gorlice. Choose your service, artist and time.'
WHERE `page_id` = 42;

INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`)
SELECT 42, 'wizard', 'Booking Steps', 10, 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 42 AND `section_key` = 'wizard');
INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`)
SELECT 42, 'confirmation', 'Confirmation', 20, 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 42 AND `section_key` = 'confirmation');

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', `fields`.`field_key`, `fields`.`field_value`
FROM `web_page_sections`
JOIN (
    SELECT 'service_note' AS `field_key`, 'You can choose more than one — the time and total update as you go.' AS `field_value`
    UNION ALL SELECT 'schedule_note', 'These times follow the salon’s opening hours rather than a live diary. Choose what suits you best and we’ll confirm your slot by phone or message.'
    UNION ALL SELECT 'pricing_note', 'Nail art is priced per nail, so the final total may differ once the design is agreed at the table.'
    UNION ALL SELECT 'review_note', 'Nail art is priced per nail, so the final amount is agreed with you at the table. Payment is taken at the salon — nothing is charged online.'
    UNION ALL SELECT 'help_text', 'Prefer to talk it through? Call the salon during opening hours.'
) AS `fields`
WHERE `page_id` = 42 AND `section_key` = 'wizard'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', `fields`.`field_key`, `fields`.`field_value`
FROM `web_page_sections`
JOIN (
    SELECT 'heading' AS `field_key`, 'Thank you — your request is in' AS `field_value`
    UNION ALL SELECT 'contents', 'We’ll check the diary and confirm your appointment with you during opening hours.'
    UNION ALL SELECT 'note', 'Need to change something? Call the salon and quote your reference.'
) AS `fields`
WHERE `page_id` = 42 AND `section_key` = 'confirmation'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

INSERT INTO `email_templates` (`id`, `name`, `subject`, `heading`, `contents`)
SELECT
    2,
    'Appointment Request (Customer Email)',
    'We’ve received your appointment request {{reference}}',
    'Thank you for your appointment request',
    '<p>Hi {{first_name}},</p>\n\n<p>Thank you for choosing Blossom Ewa Mazur. We’ve received your appointment request and will confirm it with you by {{contact_preference}} during opening hours. Your appointment is not booked until we confirm it.</p>\n\n<p><strong>Reference:</strong> {{reference}}<br />\n<strong>Services:</strong> {{services}}<br />\n<strong>Artist:</strong> {{artist}}<br />\n<strong>Requested time:</strong> {{date}} at {{time}}<br />\n<strong>Time needed:</strong> {{duration}}<br />\n<strong>Estimated total:</strong> {{estimated_total}}</p>\n\n<p>Need to change something? Reply to this email or call the salon and quote your reference.</p>\n\n<p>See you soon,<br />\n<strong>Blossom Ewa Mazur</strong></p>'
WHERE NOT EXISTS (SELECT 1 FROM `email_templates` WHERE `id` = 2);
