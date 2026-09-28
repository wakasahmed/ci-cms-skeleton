-- ---------------------------------------------------------------------------
-- Phase 6 — Journal pages (PROJECT_PLAN.md)
--
-- Schema for the article page:
--   blogs.blog_service_id  the "Related service" card at the end of an
--                          article, chosen per post in Manage > Blogs.
--                          Cleared automatically when the service is
--                          deleted.
--
-- Additive only and safe to run again.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS `phase6_journal_schema`;

DELIMITER $$

CREATE PROCEDURE `phase6_journal_schema`()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'blogs'
            AND COLUMN_NAME = 'blog_service_id'
    ) THEN
        ALTER TABLE `blogs`
            ADD COLUMN `blog_service_id` int unsigned NULL DEFAULT NULL AFTER `blog_author`,
            ADD KEY `blogs_service` (`blog_service_id`),
            ADD CONSTRAINT `blogs_service_fk` FOREIGN KEY (`blog_service_id`)
                REFERENCES `services` (`service_id`) ON DELETE SET NULL;
    END IF;
END$$

DELIMITER ;

CALL phase6_journal_schema();

DROP PROCEDURE IF EXISTS `phase6_journal_schema`;
