-- ---------------------------------------------------------------------------
-- Phase 6 — Contact content (PROJECT_PLAN.md)
--
-- The Contact page (Web Pages 7) hero, sections (replacing the Alam form
-- sections) and meta from the /ci3/ contact page, and a Blossom version of
-- the visitor acknowledgement email (email template 1). The form subjects
-- and success message are already in form_settings (Phase 4).
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `pages` SET
    `banner_title` = 'Contact',
    `banner_heading` = 'Find us in Gorlice',
    `banner_text` = 'We''re in the centre of town, on the floor above the 5.10.15 children''s store. Call during opening hours, or send a message and we''ll come back to you.',
    `banner_background` = '',
    `meta_description` = 'Blossom Ewa Mazur, 2 Piekarska Street, 38-300 Gorlice — above the 5.10.15 store. Call +48 512 129 654 or send us a message.'
WHERE `page_id` = 7;

DELETE FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` NOT IN ('details', 'form');

INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`)
SELECT 7, 'details', 'Where to Find Us', 10, 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'details');
INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`)
SELECT 7, 'form', 'Contact Form', 20, 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'form');

INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'heading', 'Where to find us' FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'details'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'map_button_text', 'Open in Maps' FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'details'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'heading', 'Send us a message' FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'form'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'button_text', 'Send message' FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'form'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'contents', 'For anything that isn''t urgent — a question about a service, a nail design you have in mind, or checking whether something is possible.\n\nTo book, the [booking page] is quicker.' FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'form'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`)
SELECT `id`, 'en', 'note', 'We reply during opening hours. For a same-day answer, please call.' FROM `web_page_sections` WHERE `page_id` = 7 AND `section_key` = 'form'
ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

UPDATE `email_templates` SET
    `subject` = 'We’ve received your message',
    `heading` = 'Thank you for getting in touch',
    `contents` = '<p>Hi {{first_name}},</p>\n\n<p>Thank you for contacting Blossom Ewa Mazur. We’ve received your message and will reply during opening hours. For a same-day answer, please call the salon.</p>\n\n<p><strong>Your message:</strong><br />\n<strong>Subject: </strong>{{subject}}<br />\n{{message}}</p>\n\n<p>If you need to add anything, simply reply to this email.</p>\n\n<p>See you soon,<br />\n<strong>Blossom Ewa Mazur</strong></p>'
WHERE `id` = 1;
