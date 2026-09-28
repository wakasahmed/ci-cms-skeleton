-- ---------------------------------------------------------------------------
-- Phase 8 — Real-time booking (PROJECT_PLAN.md, decision D1 changed)
--
-- A booking is a run of back-to-back services, each done by its own artist.
-- appointment_services now records, per service:
--   service_artist_id    the artist who does it (cleared if the artist is
--                        deleted; the name stays in service_artist_name)
--   service_artist_name  the artist's name when booked
--   service_start_time   when that service starts
-- These rows are what Booking_availability reads as each artist's booked
-- time. Rows saved before Phase 8 keep NULL here.
--
-- Additive only and safe to run again.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS `phase8_booking_schema`;

DELIMITER $$

CREATE PROCEDURE `phase8_booking_schema`()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'appointment_services'
            AND COLUMN_NAME = 'service_artist_id'
    ) THEN
        ALTER TABLE `appointment_services`
            ADD COLUMN `service_artist_id` int unsigned NULL DEFAULT NULL AFTER `service_duration_minutes`,
            ADD COLUMN `service_artist_name` varchar(120) NULL DEFAULT NULL AFTER `service_artist_id`,
            ADD COLUMN `service_start_time` time NULL DEFAULT NULL AFTER `service_artist_name`,
            ADD KEY `appointment_services_artist` (`service_artist_id`),
            ADD CONSTRAINT `appointment_services_artist_fk` FOREIGN KEY (`service_artist_id`)
                REFERENCES `artists` (`artist_id`) ON DELETE SET NULL;
    END IF;
END$$

DELIMITER ;

CALL phase8_booking_schema();

DROP PROCEDURE IF EXISTS `phase8_booking_schema`;
