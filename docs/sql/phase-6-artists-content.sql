-- ---------------------------------------------------------------------------
-- Phase 6 — Artists and gallery content (PROJECT_PLAN.md)
--
-- Gallery categories and images, the three artists and the services they
-- take, the placeholder customer reviews, the Artists page (Web Pages 38)
-- and the Artist Page labels, taken from the /ci3/ reference pages.
-- Run after phase-6-services-content.sql.
--
-- It replaces all gallery images, artists and customer reviews, so run it
-- only on a database without real data in those modules. The image file
-- names are uploads (not in the repository); on another environment,
-- upload the images through the admin instead.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Gallery ---------------------------------------------------------------------

DELETE FROM `gallery_images`;
DELETE FROM `gallery_categories`;

INSERT INTO `gallery_categories` (`category_id`, `category_name`, `category_slug`, `category_order`, `category_status`, `category_added`, `category_updated`) VALUES (1, 'Nail Art', 'nail-art', 2, 'Enable', NOW(), NOW());
INSERT INTO `gallery_categories` (`category_id`, `category_name`, `category_slug`, `category_order`, `category_status`, `category_added`, `category_updated`) VALUES (2, 'Manicure', 'manicure', 1, 'Enable', NOW(), NOW());
INSERT INTO `gallery_categories` (`category_id`, `category_name`, `category_slug`, `category_order`, `category_status`, `category_added`, `category_updated`) VALUES (3, 'Hair', 'hair', 3, 'Enable', NOW(), NOW());
INSERT INTO `gallery_categories` (`category_id`, `category_name`, `category_slug`, `category_order`, `category_status`, `category_added`, `category_updated`) VALUES (4, 'Makeup', 'makeup', 4, 'Enable', NOW(), NOW());
INSERT INTO `gallery_categories` (`category_id`, `category_name`, `category_slug`, `category_order`, `category_status`, `category_added`, `category_updated`) VALUES (5, 'Beauty', 'beauty', 5, 'Enable', NOW(), NOW());

INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (1, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure'), '6a79147a0e6f07a0247fb68ebe9cbc11.jpg', 'Soft pastels with a glitter accent', 1, 1, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (2, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure'), '6fd263bf5db79a49afe30c61456c9072.jpg', 'Almond shape in deep plum', 1, 2, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (1, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'nail-art'), '5b92278535ad0923dbecf2bf2b7b9c4b.jpg', 'Long pink set, hand-painted detail', 1, 3, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (2, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'manicure'), '2f4d94af6646e10644fc84a24479c502.jpg', 'Short nails, natural nude finish', 1, 4, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (1, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'nail-art'), '85f28b1e8243b24e507c225ac447ad16.jpg', 'Abstract lines across five nails', 0, 5, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (2, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'manicure'), '0fe390e000b510971da0325280bc9a55.jpg', 'Classic pink gel manicure', 0, 6, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (2, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'manicure'), '167942dc4ec7010e62d4075c6afef2a9.jpg', 'Shaping, mid-appointment', 0, 7, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (1, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'nail-styling'), 'cb6ad690038e9cc681f2b46ee2f4863d.jpg', 'The colour shelf', 0, 8, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (1, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure'), '6a79147a0e6f07a0247fb68ebe9cbc11.jpg', 'Mixed finish across ten nails', 0, 9, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (2, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'manicure'), '2f4d94af6646e10644fc84a24479c502.jpg', 'Bare nails, buffed and tidy', 0, 10, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (3, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'haircut'), '25fde5cd4a592a997325bba37c800f43.jpg', 'Curls, cut and shaped', 1, 11, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (3, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'haircut'), '1efa3b971931aef24b3f3b9264b18466.jpg', 'Blow-dry and finish', 0, 12, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (4, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'makeup'), 'a32f4f867624652292a58b44844c6774.jpg', 'Soft makeup for a special day', 0, 13, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (4, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'makeup'), 'ed9471e7e01068e2a70bb85ed9efcb0c.jpg', 'Shade matching in daylight', 1, 14, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (5, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'facial-skin-care'), '99070938e9b598222d402d40c6d92934.jpg', 'Treatment mask during a facial', 0, 15, 'Enable', NOW(), NOW());
INSERT INTO `gallery_images` (`image_category_id`, `image_service_id`, `image_file`, `image_caption`, `image_featured`, `image_order`, `image_status`, `image_added`, `image_updated`) VALUES (5, (SELECT `service_id` FROM `services` WHERE `service_slug` = 'facial-skin-care'), '2ae0c170d6898761e1cbd331248fcaf8.jpg', 'An unhurried hour', 0, 16, 'Enable', NOW(), NOW());

-- Artists ---------------------------------------------------------------------

DELETE FROM `artists`;

INSERT INTO `artists` (`artist_id`, `artist_name`, `artist_slug`, `artist_role`, `artist_bio`, `artist_specialties`, `artist_working_days`, `artist_image`, `artist_is_placeholder`, `artist_order`, `artist_status`, `artist_added`, `artist_updated`) VALUES (1, 'Ewa Mazur', 'ewa-mazur', 'Owner · Nail stylist', 'Ewa runs Blossom and takes most of the nail appointments herself. She works through the preparation carefully before any colour goes on, and will happily spend the first few minutes of an appointment talking through shape and length rather than getting straight to it.', 'Manicure & Nail Art\nGel Nails\nNail Styling', 'mon,tue,wed,thu,fri,sat', '7c5d0015173b3d5e1fadad7ce16a4672.jpg', 0, 1, 'Enable', NOW(), NOW());
INSERT INTO `artists` (`artist_id`, `artist_name`, `artist_slug`, `artist_role`, `artist_bio`, `artist_specialties`, `artist_working_days`, `artist_image`, `artist_is_placeholder`, `artist_order`, `artist_status`, `artist_added`, `artist_updated`) VALUES (2, 'Team member', 'nail-artist', 'Nail artist', 'Placeholder profile — this text, the name and the portrait all need replacing with the real stylist''s details before the site goes live.', 'Nail Art\nNail Styling\nNail Care', 'tue,wed,thu,fri', '5dd8906456d0ae20bb001e82158a473b.jpg', 1, 2, 'Enable', NOW(), NOW());
INSERT INTO `artists` (`artist_id`, `artist_name`, `artist_slug`, `artist_role`, `artist_bio`, `artist_specialties`, `artist_working_days`, `artist_image`, `artist_is_placeholder`, `artist_order`, `artist_status`, `artist_added`, `artist_updated`) VALUES (3, 'Team member', 'beauty-therapist', 'Beauty therapist', 'Placeholder profile — this text, the name and the portrait all need replacing with the real therapist''s details before the site goes live.', 'Beauty Treatments\nMakeup\nHair Styling', 'mon,wed,thu,fri,sat', '50268d87f47a97864c05aa4f935b1645.jpg', 1, 3, 'Enable', NOW(), NOW());

INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'manicure';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'nail-art';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'nail-extensions';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'nail-styling';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 1, `service_id` FROM `services` WHERE `service_slug` = 'nail-care';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 2, `service_id` FROM `services` WHERE `service_slug` = 'manicure';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 2, `service_id` FROM `services` WHERE `service_slug` = 'gel-manicure';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 2, `service_id` FROM `services` WHERE `service_slug` = 'nail-art';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 2, `service_id` FROM `services` WHERE `service_slug` = 'nail-extensions';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 2, `service_id` FROM `services` WHERE `service_slug` = 'nail-styling';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'nail-care';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'haircut';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'hair-styling';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'hair-color';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'hair-treatments';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'facial-skin-care';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'makeup';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'waxing';
INSERT INTO `artist_services` (`artist_id`, `service_id`) SELECT 3, `service_id` FROM `services` WHERE `service_slug` = 'beauty-treatments';

-- Customer reviews (placeholders from the design) ------------------------------

DELETE FROM `customer_reviews`;

INSERT INTO `customer_reviews` (`review_name`, `review_desc`, `review_caption`, `review_added`, `review_updated`, `review_image`, `review_status`, `review_order`, `review_rating`) VALUES ('Sample review', 'Placeholder review — replace with a real client review. My gel manicure lasted beautifully and still looked fresh weeks later.', 'Gel manicure', NOW(), NOW(), '', 'Enable', 1, 5);
INSERT INTO `customer_reviews` (`review_name`, `review_desc`, `review_caption`, `review_added`, `review_updated`, `review_image`, `review_status`, `review_order`, `review_rating`) VALUES ('Sample review', 'Placeholder review — replace with a real client review. I showed a photo I liked and left with exactly the design I wanted.', 'Nail art', NOW(), NOW(), '', 'Enable', 2, 5);
INSERT INTO `customer_reviews` (`review_name`, `review_desc`, `review_caption`, `review_added`, `review_updated`, `review_image`, `review_status`, `review_order`, `review_rating`) VALUES ('Sample review', 'Placeholder review — replace with a real client review. Lovely, calm little studio and very easy to find in the centre of town.', 'Manicure', NOW(), NOW(), '', 'Enable', 3, 5);

-- Artists page (Web Pages > Artists) --------------------------------------------

UPDATE `pages` SET
    `banner_title` = '',
    `banner_heading` = 'The people behind the work',
    `banner_text` = 'Ewa takes most nail appointments herself. Book with someone in particular, or let us find you the next free slot.',
    `meta_description` = 'Meet the team at Blossom Ewa Mazur in Gorlice — nail stylists and beauty therapists. Book with someone in particular or take the next free slot.'
WHERE `page_id` = 38;

INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`) SELECT 38, 'team', 'Team', 10, 'Enable' WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'team');
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'heading', 'Also at the studio' FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'team' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'contents', 'Between us we cover nails, hair, skin and makeup — so a visit can be one appointment or several.' FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'team' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'placeholder_note', 'Two of these profiles are placeholders while the team''s real names, photos and specialties are confirmed.' FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'team' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_sections` (`page_id`, `section_key`, `section_label`, `sort_order`, `status`) SELECT 38, 'cta', 'Call to Action', 20, 'Enable' WHERE NOT EXISTS (SELECT 1 FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'cta');
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'heading', 'No preference? That''s fine too.' FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'cta' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `web_page_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'contents', 'Choose ''any available artist'' when you book and we''ll match you with whoever is free at the time you want.' FROM `web_page_sections` WHERE `page_id` = 38 AND `section_key` = 'cta' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);

-- Artist Page labels (Manage > Miscellaneous Contents) --------------------------

INSERT INTO `miscellaneous_content_sections` (`section_key`, `section_label`, `status`)
SELECT 'artist_page', 'Artist Page', 'Enable'
WHERE NOT EXISTS (SELECT 1 FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page');

INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'services_heading', 'Services {name} offers' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'days_heading', 'Usually in the studio' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'days_note', 'Placeholder availability — the real diary is not connected yet. Exact times are confirmed when you book.' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'days_button', 'Check availability' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'work_heading', 'Recent work' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'work_text', 'A selection from the studio. Individual portfolios will replace this once each artist''s own photos are shot.' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'work_button', 'View the full gallery' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'reviews_heading', 'What clients say' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'reviews_note', 'Placeholder reviews — real, attributable reviews will replace these before launch.' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'cta_heading', 'Book with {name}' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
INSERT INTO `miscellaneous_content_section_fields` (`section_id`, `locale`, `field_key`, `field_value`) SELECT `id`, 'en', 'cta_text', 'Choose a service and a time that suits you, and we''ll confirm the details.' FROM `miscellaneous_content_sections` WHERE `section_key` = 'artist_page' ON DUPLICATE KEY UPDATE `field_value` = VALUES(`field_value`);
