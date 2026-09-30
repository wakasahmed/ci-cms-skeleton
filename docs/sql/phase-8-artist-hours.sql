-- ---------------------------------------------------------------------------
-- Phase 8 — Artist working hours and time off
--
-- artist_hours      one row per weekday an artist works. hours_start and
--                   hours_end narrow that day to part of the salon's opening
--                   hours; NULL means "whenever the salon is open that day".
--                   An artist with no rows takes bookings whenever the salon
--                   is open (as an artist with no working days did before).
-- artist_time_off   days (or part of each day, when both times are set) an
--                   artist is away. Booking_availability treats it as booked
--                   time.
--
-- artists.artist_working_days stays and is kept in step with artist_hours by
-- Manage > Artists; the public artist page still reads it.
--
-- The migration gives every current working day a row with NULL times, so
-- availability does not change. Additive only and safe to run again.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `artist_hours` (
    `artist_id` int unsigned NOT NULL,
    `hours_day` enum('mon','tue','wed','thu','fri','sat','sun') COLLATE utf8mb4_unicode_ci NOT NULL,
    `hours_start` time DEFAULT NULL,
    `hours_end` time DEFAULT NULL,
    PRIMARY KEY (`artist_id`, `hours_day`),
    CONSTRAINT `artist_hours_artist_fk` FOREIGN KEY (`artist_id`)
        REFERENCES `artists` (`artist_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `artist_time_off` (
    `time_off_id` int unsigned NOT NULL AUTO_INCREMENT,
    `artist_id` int unsigned NOT NULL,
    `time_off_start_date` date NOT NULL,
    `time_off_end_date` date NOT NULL,
    `time_off_start_time` time DEFAULT NULL,
    `time_off_end_time` time DEFAULT NULL,
    `time_off_note` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `time_off_added` datetime NOT NULL,
    PRIMARY KEY (`time_off_id`),
    KEY `artist_time_off_dates` (`artist_id`, `time_off_start_date`, `time_off_end_date`),
    CONSTRAINT `artist_time_off_artist_fk` FOREIGN KEY (`artist_id`)
        REFERENCES `artists` (`artist_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Current working days become full days (NULL times).
INSERT IGNORE INTO `artist_hours` (`artist_id`, `hours_day`)
SELECT a.`artist_id`, d.`code`
FROM `artists` a
JOIN (
    SELECT 'mon' AS `code` UNION ALL SELECT 'tue' UNION ALL SELECT 'wed'
    UNION ALL SELECT 'thu' UNION ALL SELECT 'fri' UNION ALL SELECT 'sat'
    UNION ALL SELECT 'sun'
) d ON FIND_IN_SET(d.`code`, a.`artist_working_days`) > 0;
