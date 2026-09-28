-- ---------------------------------------------------------------------------
-- Phase 6 — Gallery page content (PROJECT_PLAN.md)
--
-- The Gallery page (Web Pages 39): hero text and image, the secondary hero
-- button and the call to action, taken from the /ci3/ gallery page. The
-- gallery images themselves are in phase-6-artists-content.sql.
--
-- Safe to run again. The banner image file name is an upload in
-- assets/frontend/images/pages/ (not in the repository); on another
-- environment, upload it through Manage > Web Pages instead.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `pages`
SET
    `banner_title` = 'Our work',
    `banner_heading` = 'Sets, colours and finishes from the studio',
    `banner_text` = 'Mostly nails, because that is mostly what we do — with a little hair, makeup and skin work alongside.',
    `banner_background` = 'a9e94b933b50fbca467ae791e2af122b.jpg',
    `meta_description` = 'Nail art, manicure and gel work from Blossom Ewa Mazur in Gorlice, alongside hair, makeup and beauty treatments.'
WHERE `page_id` = 39;

INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`)
SELECT 39, 'hero_link', 'Hero Button', 10, 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 39 AND `section_key` = 'hero_link');

INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`)
SELECT 39, 'cta', 'Call to Action', 20, 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 39 AND `section_key` = 'cta');

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'button_text', 'See nail services' FROM `web_page_sections` WHERE `page_id` = 39 AND `section_key` = 'hero_link'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'button_url', 'services?category=nails' FROM `web_page_sections` WHERE `page_id` = 39 AND `section_key` = 'hero_link'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'heading', 'Seen a set you''d like?' FROM `web_page_sections` WHERE `page_id` = 39 AND `section_key` = 'cta'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'contents', 'Bring the photo to your appointment — it''s far easier to work from a picture than a description.' FROM `web_page_sections` WHERE `page_id` = 39 AND `section_key` = 'cta'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
