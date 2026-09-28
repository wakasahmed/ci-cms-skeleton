-- ---------------------------------------------------------------------------
-- Phase 6 — Offers content (PROJECT_PLAN.md)
--
-- The six offers with their services, and the Offers page (Web Pages 40),
-- taken from the /ci3/ offers page and booking script. Run after
-- phase-6-services-content.sql.
--
-- It replaces all offers, so run it only on a database without real
-- offers. The image file names are uploads (not in the repository); on
-- another environment, upload the images through Manage > Offers instead.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

DELETE FROM `offers`;

INSERT INTO `offers` (`offer_id`, `offer_title`, `offer_slug`, `offer_label`, `offer_summary`, `offer_inclusions`, `offer_price`, `offer_old_price`, `offer_duration_label`, `offer_valid_from`, `offer_valid_to`, `offer_validity_note`, `offer_image`, `offer_featured`, `offer_order`, `offer_status`, `offer_added`, `offer_updated`) VALUES (1, 'Manicure + nail art', 'manicure-nail-art', 'Weekday mornings', 'Manicure, gel colour and hand-painted art on two nails', 'Full manicure with cuticle work and shaping\nGel colour in the shade of your choice\nHand-painted art on two accent nails\nShape and design planned with you first', '160', '190', 'about 90 min', NULL, NULL, 'Placeholder validity — confirm the running dates with the salon.', '7d6ed552ab1624305ee4213903d9c56c.jpg', 1, 1, 'Enable', NOW(), NOW());
INSERT INTO `offers` (`offer_id`, `offer_title`, `offer_slug`, `offer_label`, `offer_summary`, `offer_inclusions`, `offer_price`, `offer_old_price`, `offer_duration_label`, `offer_valid_from`, `offer_valid_to`, `offer_validity_note`, `offer_image`, `offer_featured`, `offer_order`, `offer_status`, `offer_added`, `offer_updated`) VALUES (2, 'Gel manicure', 'gel-manicure-package', 'Most booked', 'Manicure, gel colour and a long-wearing finish', 'Full preparation and cuticle care\nGel base, colour and top coat\nShaped to the length you prefer\nRemoval of existing gel included', '120', NULL, 'about 60 min', NULL, NULL, 'Placeholder validity — confirm the running dates with the salon.', 'cbf6baf591d08e9091f58bebbc0a494e.jpg', 1, 2, 'Enable', NOW(), NOW());
INSERT INTO `offers` (`offer_id`, `offer_title`, `offer_slug`, `offer_label`, `offer_summary`, `offer_inclusions`, `offer_price`, `offer_old_price`, `offer_duration_label`, `offer_valid_from`, `offer_valid_to`, `offer_validity_note`, `offer_image`, `offer_featured`, `offer_order`, `offer_status`, `offer_added`, `offer_updated`) VALUES (3, 'Nails + brows', 'nails-and-brows', 'Saturday mornings', 'Gel manicure with brow shaping in the same visit', 'Gel manicure with full preparation\nBrow shaping and tidy\nBoth done in one appointment', '175', '200', 'about 90 min', NULL, NULL, 'Placeholder validity — confirm the running dates with the salon.', '14802e2b21fba87a46ccbf0a2ea31d71.jpg', 0, 3, 'Enable', NOW(), NOW());
INSERT INTO `offers` (`offer_id`, `offer_title`, `offer_slug`, `offer_label`, `offer_summary`, `offer_inclusions`, `offer_price`, `offer_old_price`, `offer_duration_label`, `offer_valid_from`, `offer_valid_to`, `offer_validity_note`, `offer_image`, `offer_featured`, `offer_order`, `offer_status`, `offer_added`, `offer_updated`) VALUES (4, 'Seasonal nail design', 'seasonal-nail-design', 'Autumn and winter shades', 'Gel manicure with a seasonal design across all ten nails', 'Gel manicure with full preparation\nSeasonal design painted by hand\nChoice of matte, gloss or chrome finish\nColour direction chosen with you', '210', '240', 'about 120 min', NULL, NULL, 'Placeholder validity — confirm the running dates with the salon.', 'd37ce208410ce578158c3d1448190e22.jpg', 0, 4, 'Enable', NOW(), NOW());
INSERT INTO `offers` (`offer_id`, `offer_title`, `offer_slug`, `offer_label`, `offer_summary`, `offer_inclusions`, `offer_price`, `offer_old_price`, `offer_duration_label`, `offer_valid_from`, `offer_valid_to`, `offer_validity_note`, `offer_image`, `offer_featured`, `offer_order`, `offer_status`, `offer_added`, `offer_updated`) VALUES (5, 'Nails + hair for an event', 'nails-and-hair', 'Weddings and events', 'Gel manicure with hair styling, booked for the same day', 'Gel manicure with full preparation\nWash, blow-dry and styling\nPlanned around the time you need to leave\nColour and shape agreed beforehand', '185', '200', 'about 2 hr', NULL, NULL, 'Placeholder validity — confirm the running dates with the salon.', '7d10b3276332f614d1669f10310c759b.jpg', 0, 5, 'Enable', NOW(), NOW());
INSERT INTO `offers` (`offer_id`, `offer_title`, `offer_slug`, `offer_label`, `offer_summary`, `offer_inclusions`, `offer_price`, `offer_old_price`, `offer_duration_label`, `offer_valid_from`, `offer_valid_to`, `offer_validity_note`, `offer_image`, `offer_featured`, `offer_order`, `offer_status`, `offer_added`, `offer_updated`) VALUES (6, 'Beauty treatment package', 'beauty-treatment-package', 'Weekday afternoons', 'A facial with brow shaping, booked together', 'Consultation-led facial\nBrow shaping to suit your face\nAftercare advice for both', '165', '180', 'about 90 min', NULL, NULL, 'Placeholder validity — confirm the running dates with the salon.', '923fb0748a9110507b8ef983e90e8318.jpg', 0, 6, 'Enable', NOW(), NOW());

INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'nail-art';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 2, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'waxing';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 4, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 4, `service_id` FROM `services` WHERE `service_slug` = 'nail-art';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 5, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 5, `service_id` FROM `services` WHERE `service_slug` = 'hair-styling';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 6, `service_id` FROM `services` WHERE `service_slug` = 'facial-skin-care';
INSERT INTO `offer_services` (`offer_id`, `service_id`) SELECT 6, `service_id` FROM `services` WHERE `service_slug` = 'waxing';

-- Offers page (Web Pages > Offers) ---------------------------------------------

UPDATE `pages` SET
    `banner_title` = 'Current offers',
    `banner_heading` = 'Worth booking together',
    `banner_text` = 'A few combinations that work well in one visit, priced a little kinder than booking them separately. Everything else on the menu stays at its usual price.',
    `banner_background` = '',
    `meta_description` = 'Current service combinations at Blossom Ewa Mazur in Gorlice — manicure with nail art, gel manicure packages and beauty treatments booked together.'
WHERE `page_id` = 40;

INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`) SELECT 40, 'more_offers', 'More Offers', 10, 'Enable' WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'more_offers');
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'heading', 'More combinations' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'more_offers' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'contents', 'Mention the one you want when you book and we''ll set aside the right amount of time.' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'more_offers' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`) SELECT 40, 'services_link', 'Services Link', 20, 'Enable' WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'services_link');
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'heading', 'Looking for something specific?' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'services_link' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'contents', 'The full service menu has every treatment with its own price and duration.' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'services_link' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'button_text', 'View all services' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'services_link' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`) SELECT 40, 'cta', 'Call to Action', 30, 'Enable' WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'cta');
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'heading', 'Book your visit to Blossom' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'cta' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'contents', 'Pick a time that suits you and tell us what you have in mind. First visit? Let us know when you book and we''ll leave extra time to talk it through.' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'cta' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'note', 'Prices and running dates on this page are placeholders while the salon confirms its offers. Nothing here is a limited-time deal — ask us when you book and we''ll tell you what currently applies.' FROM `web_page_sections` WHERE `page_id` = 40 AND `section_key` = 'more_offers' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
