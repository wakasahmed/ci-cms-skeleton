-- ---------------------------------------------------------------------------
-- Phase 4 — Database clean-up (PROJECT_PLAN.md)
--
-- Removes everything the code stopped using in Phases 2 and 3: the tour,
-- booking, payment, discount, referral, plan-your-visit, translation,
-- WhatsApp and Countries tables; every Arabic (_ar) column; tour-only
-- columns in the kept tables; the Arabic content-section rows; the Alam
-- tour pages and emails; and the Alam sample data. It then adds the Blossom
-- fields to site_settings.
--
-- DESTRUCTIVE. Run only against blossom_cms, and only after a backup:
--
--   mysqldump -uroot --routines --triggers blossom_cms > blossom_cms-before-phase-4.sql
--   mysql -uroot blossom_cms < docs/sql/phase-4-cleanup.sql
--
-- Never run it against ci_cms (the untouched Alam copy).
--
-- MySQL has no DROP COLUMN IF EXISTS, so column, index and foreign-key
-- changes go through the phase4_* helper procedures below. They check
-- information_schema first, which makes the whole script safe to run again
-- after a partial failure. The helpers are dropped at the end.
--
-- Emptying ci_sessions signs every administrator out.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Helpers --------------------------------------------------------------------

DROP PROCEDURE IF EXISTS `phase4_drop_column`;
DROP PROCEDURE IF EXISTS `phase4_drop_foreign_key`;
DROP PROCEDURE IF EXISTS `phase4_drop_index`;
DROP PROCEDURE IF EXISTS `phase4_add_column`;

DELIMITER $$

CREATE PROCEDURE `phase4_drop_column`(IN p_table VARCHAR(64), IN p_column VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = p_table
            AND COLUMN_NAME = p_column
    ) THEN
        SET @phase4_sql = CONCAT('ALTER TABLE `', p_table, '` DROP COLUMN `', p_column, '`');
        PREPARE phase4_statement FROM @phase4_sql;
        EXECUTE phase4_statement;
        DEALLOCATE PREPARE phase4_statement;
    END IF;
END$$

CREATE PROCEDURE `phase4_drop_foreign_key`(IN p_table VARCHAR(64), IN p_constraint VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = p_table
            AND CONSTRAINT_NAME = p_constraint
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @phase4_sql = CONCAT('ALTER TABLE `', p_table, '` DROP FOREIGN KEY `', p_constraint, '`');
        PREPARE phase4_statement FROM @phase4_sql;
        EXECUTE phase4_statement;
        DEALLOCATE PREPARE phase4_statement;
    END IF;
END$$

CREATE PROCEDURE `phase4_drop_index`(IN p_table VARCHAR(64), IN p_index VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = p_table
            AND INDEX_NAME = p_index
    ) THEN
        SET @phase4_sql = CONCAT('ALTER TABLE `', p_table, '` DROP INDEX `', p_index, '`');
        PREPARE phase4_statement FROM @phase4_sql;
        EXECUTE phase4_statement;
        DEALLOCATE PREPARE phase4_statement;
    END IF;
END$$

CREATE PROCEDURE `phase4_add_column`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = p_table
            AND COLUMN_NAME = p_column
    ) THEN
        SET @phase4_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE phase4_statement FROM @phase4_sql;
        EXECUTE phase4_statement;
        DEALLOCATE PREPARE phase4_statement;
    END IF;
END$$

DELIMITER ;

-- 1. Contact requests: country and website language (Phase 3) ---------------
-- The foreign key to countries goes first so the countries table can be dropped.

CALL phase4_drop_foreign_key('contact_requests', 'fk_country_contact_request');
CALL phase4_drop_index('contact_requests', 'contact_requests_country_idx');
CALL phase4_drop_column('contact_requests', 'country');
CALL phase4_drop_column('contact_requests', 'website');

-- 2. Retired tables ----------------------------------------------------------
-- The tour tables reference one another (and plan_your_visit references
-- countries), so foreign-key checks are off for this block only. No kept
-- table references any of them after step 1.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
    `tour_assigned_attractions`,
    `tour_assigned_categories`,
    `tour_assigned_slots`,
    `tour_assigned_vehicles`,
    `tour_booking_internal_notes`,
    `tour_booking_payments`,
    `tour_booking_refunds`,
    `tour_bookings`,
    `tour_categories`,
    `tour_guide_assigned_languages`,
    `tour_guide_assigned_tours`,
    `tour_guide_availability`,
    `tour_guides`,
    `tour_images`,
    `tour_itineraries`,
    `tour_languages`,
    `tour_reviews`,
    `tour_slots`,
    `tours`,
    `attractions`,
    `vehicles`,
    `discount_codes`,
    `referrals`,
    `plan_your_visit`,
    `translation_jobs`,
    `whatsapp_templates`,
    `ci_sessions_ci2_backup`,
    `zzz_migration_test`,
    `countries`;

SET FOREIGN_KEY_CHECKS = 1;

-- 3. Arabic (_ar) columns in the kept tables ---------------------------------

CALL phase4_drop_column('pages', 'page_slug_ar');
CALL phase4_drop_column('pages', 'page_name_ar');
CALL phase4_drop_column('pages', 'page_title_ar');
CALL phase4_drop_column('pages', 'meta_description_ar');
CALL phase4_drop_column('pages', 'meta_keywords_ar');
CALL phase4_drop_column('pages', 'og_title_ar');
CALL phase4_drop_column('pages', 'og_description_ar');
CALL phase4_drop_column('pages', 'og_image_ar');
CALL phase4_drop_column('pages', 'banner_title_ar');
CALL phase4_drop_column('pages', 'banner_heading_ar');
CALL phase4_drop_column('pages', 'banner_text_ar');
CALL phase4_drop_column('pages', 'banner_background_ar');
CALL phase4_drop_column('pages', 'menu_name_ar');
CALL phase4_drop_column('pages', 'page_text_ar');

CALL phase4_drop_column('blogs', 'blog_slug_ar');
CALL phase4_drop_column('blogs', 'blog_name_ar');
CALL phase4_drop_column('blogs', 'blog_short_description_ar');
CALL phase4_drop_column('blogs', 'blog_text_ar');
CALL phase4_drop_column('blogs', 'page_title_ar');
CALL phase4_drop_column('blogs', 'meta_description_ar');
CALL phase4_drop_column('blogs', 'meta_keywords_ar');
CALL phase4_drop_column('blogs', 'og_title_ar');
CALL phase4_drop_column('blogs', 'og_description_ar');
CALL phase4_drop_column('blogs', 'og_image_ar');
CALL phase4_drop_column('blogs', 'banner_title_ar');
CALL phase4_drop_column('blogs', 'banner_heading_ar');
CALL phase4_drop_column('blogs', 'banner_text_ar');
CALL phase4_drop_column('blogs', 'banner_background_ar');

CALL phase4_drop_column('blog_categories', 'cat_name_ar');
CALL phase4_drop_column('blog_categories', 'cat_slug_ar');
CALL phase4_drop_column('blog_categories', 'cat_desc_ar');
CALL phase4_drop_column('blog_categories', 'cat_contents_ar');
CALL phase4_drop_column('blog_categories', 'page_title_ar');
CALL phase4_drop_column('blog_categories', 'meta_description_ar');
CALL phase4_drop_column('blog_categories', 'meta_keywords_ar');
CALL phase4_drop_column('blog_categories', 'og_title_ar');
CALL phase4_drop_column('blog_categories', 'og_description_ar');
CALL phase4_drop_column('blog_categories', 'og_image_ar');
CALL phase4_drop_column('blog_categories', 'banner_title_ar');
CALL phase4_drop_column('blog_categories', 'banner_heading_ar');
CALL phase4_drop_column('blog_categories', 'banner_text_ar');
CALL phase4_drop_column('blog_categories', 'banner_background_ar');

CALL phase4_drop_column('faqs', 'faq_question_ar');
CALL phase4_drop_column('faqs', 'faq_answer_ar');

CALL phase4_drop_column('faqs_categories', 'cat_name_ar');
CALL phase4_drop_column('faqs_categories', 'cat_short_description_ar');

CALL phase4_drop_column('slider', 'pre_heading_ar');
CALL phase4_drop_column('slider', 'heading_ar');
CALL phase4_drop_column('slider', 'text_ar');
CALL phase4_drop_column('slider', 'button_1_icon_ar');
CALL phase4_drop_column('slider', 'button_1_text_ar');
CALL phase4_drop_column('slider', 'button_1_url_ar');
CALL phase4_drop_column('slider', 'button_1_target_ar');
CALL phase4_drop_column('slider', 'button_2_icon_ar');
CALL phase4_drop_column('slider', 'button_2_text_ar');
CALL phase4_drop_column('slider', 'button_2_url_ar');
CALL phase4_drop_column('slider', 'button_2_target_ar');
CALL phase4_drop_column('slider', 'image_ar');

CALL phase4_drop_column('customer_reviews', 'review_name_ar');
CALL phase4_drop_column('customer_reviews', 'review_desc_ar');

CALL phase4_drop_column('email_templates', 'subject_ar');
CALL phase4_drop_column('email_templates', 'heading_ar');
CALL phase4_drop_column('email_templates', 'contents_ar');

CALL phase4_drop_column('form_settings', 'contact_success_ar');
CALL phase4_drop_column('form_settings', 'plan_success_ar');
CALL phase4_drop_column('form_settings', 'tour_success_ar');
CALL phase4_drop_column('form_settings', 'experience_success_ar');
CALL phase4_drop_column('form_settings', 'contact_subject_ar');
CALL phase4_drop_column('form_settings', 'pyt_interests_ar');
CALL phase4_drop_column('form_settings', 'pyt_visit_time_ar');

CALL phase4_drop_column('site_settings', 'website_title_ar');
CALL phase4_drop_column('site_settings', 'currency_unit_ar');
CALL phase4_drop_column('site_settings', 'address_ar');
CALL phase4_drop_column('site_settings', 'default_bg_ar');
CALL phase4_drop_column('site_settings', 'sender_name_ar');
CALL phase4_drop_column('site_settings', 'website_intro_ar');
CALL phase4_drop_column('site_settings', 'foot_col_1_ar');
CALL phase4_drop_column('site_settings', 'foot_col_2_ar');
CALL phase4_drop_column('site_settings', 'foot_col_3_ar');
CALL phase4_drop_column('site_settings', 'foot_col_4_ar');
CALL phase4_drop_column('site_settings', 'copyright_text_ar');
CALL phase4_drop_column('site_settings', 'license_number_ar');
CALL phase4_drop_column('site_settings', 'contact_text_ar');
CALL phase4_drop_column('site_settings', 'payment_title_ar');

-- 4. Tour-only columns in the kept tables ------------------------------------

CALL phase4_drop_column('form_settings', 'plan_success');
CALL phase4_drop_column('form_settings', 'tour_success');
CALL phase4_drop_column('form_settings', 'experience_success');
CALL phase4_drop_column('form_settings', 'pyt_interests');
CALL phase4_drop_column('form_settings', 'pyt_visit_time');

CALL phase4_drop_column('site_settings', 'default_language');
CALL phase4_drop_column('site_settings', 'license_number');
CALL phase4_drop_column('site_settings', 'profit');
CALL phase4_drop_column('site_settings', 'tax');
CALL phase4_drop_column('site_settings', 'payment_title');
CALL phase4_drop_column('site_settings', 'payment_icons');

-- 5. Arabic content-section rows ---------------------------------------------

DELETE FROM `web_page_section_fields` WHERE `locale` = 'ar';
DELETE FROM `miscellaneous_content_section_fields` WHERE `locale` = 'ar';

-- 6. Alam pages and emails ---------------------------------------------------
-- Pages 3 (Tours), 4 (Experiences), 5 (Plan Your Visit) and 11 (Tour Guides).
-- Their web_page_sections and section fields are removed by ON DELETE CASCADE,
-- and their menu entries live on the pages rows themselves.

DELETE FROM `pages` WHERE `page_id` IN (3, 4, 5, 11);

-- Templates 2–15 are the plan-your-visit, booking, guide, discount, referral
-- and review emails. Template 1 (contact form) stays.
DELETE FROM `email_templates` WHERE `id` BETWEEN 2 AND 15;

-- 7. Sample data -------------------------------------------------------------

TRUNCATE TABLE `contact_requests`;
TRUNCATE TABLE `admin_login_attempts`;
TRUNCATE TABLE `admin_password_resets`;
TRUNCATE TABLE `admin_remember_tokens`;
TRUNCATE TABLE `ci_sessions`;

-- 8. Blossom fields in site_settings -----------------------------------------
-- Logos (logo, logo_sticky, logo_white) and the Instagram URL already exist.
-- opening_hours holds one "Days | Hours" line per day group.

CALL phase4_add_column('site_settings', 'address_note', 'VARCHAR(255) NULL DEFAULT NULL AFTER `address`');
CALL phase4_add_column('site_settings', 'opening_hours', 'TEXT NULL AFTER `address_note`');
CALL phase4_add_column('site_settings', 'map_url', 'VARCHAR(500) NULL DEFAULT NULL AFTER `opening_hours`');

UPDATE `site_settings`
SET
    `address_note` = COALESCE(NULLIF(`address_note`, ''), 'Above the 5.10.15 children''s store'),
    `opening_hours` = COALESCE(
        NULLIF(`opening_hours`, ''),
        CONCAT('Monday – Friday | 9:00 AM – 5:00 PM', '\n', 'Saturday | 9:00 AM – 2:00 PM')
    ),
    `map_url` = COALESCE(
        NULLIF(`map_url`, ''),
        'https://maps.google.com/?q=Piekarska+2,+38-300+Gorlice,+Poland'
    )
WHERE `id` = 1;

-- Clean up -------------------------------------------------------------------

DROP PROCEDURE IF EXISTS `phase4_drop_column`;
DROP PROCEDURE IF EXISTS `phase4_drop_foreign_key`;
DROP PROCEDURE IF EXISTS `phase4_drop_index`;
DROP PROCEDURE IF EXISTS `phase4_add_column`;
