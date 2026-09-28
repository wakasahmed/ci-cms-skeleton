-- ---------------------------------------------------------------------------
-- Phase 6 — Services pages (PROJECT_PLAN.md)
--
-- Schema for the service detail page:
--   services.service_show_shapes  shows the "Shapes and finishes" block
--                                 (Miscellaneous Contents > Nail Shapes &
--                                 Finishes) on the service page.
--   service_related               the "Often booked with this" services,
--                                 chosen per service in Manage > Services.
--
-- Additive only and safe to run again.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS `phase6_add_column`;

DELIMITER $$

CREATE PROCEDURE `phase6_add_column`(
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
        SET @phase6_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE phase6_statement FROM @phase6_sql;
        EXECUTE phase6_statement;
        DEALLOCATE PREPARE phase6_statement;
    END IF;
END$$

DELIMITER ;

CALL phase6_add_column(
    'services',
    'service_show_shapes',
    'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `service_featured`'
);

DROP PROCEDURE IF EXISTS `phase6_add_column`;

CREATE TABLE IF NOT EXISTS `service_related` (
    `service_id` int unsigned NOT NULL,
    `related_service_id` int unsigned NOT NULL,
    PRIMARY KEY (`service_id`, `related_service_id`),
    KEY `service_related_related` (`related_service_id`),
    CONSTRAINT `service_related_service_fk` FOREIGN KEY (`service_id`)
        REFERENCES `services` (`service_id`) ON DELETE CASCADE,
    CONSTRAINT `service_related_related_fk` FOREIGN KEY (`related_service_id`)
        REFERENCES `services` (`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
