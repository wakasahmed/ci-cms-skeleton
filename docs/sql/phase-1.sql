-- ---------------------------------------------------------------------------
-- Phase 1 — Salon modules (PROJECT_PLAN.md)
--
-- Creates the tables for Service Categories, Services (+ add-ons), Artists,
-- Gallery Categories, Gallery Images, Offers and Appointment requests.
-- English only: no _ar columns.
--
-- Additive only. Safe to run once against blossom_cms; every statement uses
-- CREATE TABLE IF NOT EXISTS, and nothing existing is altered or dropped.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- Service categories ("Nails", "Hair", "Beauty") -----------------------------

CREATE TABLE IF NOT EXISTS `service_categories` (
    `category_id` int unsigned NOT NULL AUTO_INCREMENT,
    `category_name` varchar(150) NOT NULL,
    `category_slug` varchar(160) NOT NULL,
    `category_heading` varchar(255) DEFAULT NULL,
    `category_description` text,
    `category_order` int unsigned NOT NULL DEFAULT '0',
    `category_status` enum('Enable','Disable') NOT NULL DEFAULT 'Enable',
    `category_added` datetime NOT NULL,
    `category_updated` datetime NOT NULL,
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `service_categories_slug` (`category_slug`),
    KEY `service_categories_listing` (`category_status`, `category_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services -------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `services` (
    `service_id` int unsigned NOT NULL AUTO_INCREMENT,
    `service_category_id` int unsigned NOT NULL,
    `service_name` varchar(150) NOT NULL,
    `service_slug` varchar(160) NOT NULL,
    `service_summary` varchar(500) DEFAULT NULL,
    `service_description` mediumtext,
    `service_price_from` decimal(8,2) unsigned DEFAULT NULL,
    `service_price_suffix` varchar(40) DEFAULT NULL,
    `service_duration_label` varchar(60) DEFAULT NULL,
    `service_duration_minutes` smallint unsigned DEFAULT NULL,
    `service_card_image` varchar(255) DEFAULT NULL,
    `service_hero_image` varchar(255) DEFAULT NULL,
    `service_included` text,
    `service_before_visit` text,
    `service_aftercare` text,
    `service_featured` tinyint(1) unsigned NOT NULL DEFAULT '0',
    `page_title` varchar(255) DEFAULT NULL,
    `meta_description` varchar(500) DEFAULT NULL,
    `robots_index` tinyint(1) unsigned NOT NULL DEFAULT '1',
    `robots_follow` tinyint(1) unsigned NOT NULL DEFAULT '1',
    `og_title` varchar(255) DEFAULT NULL,
    `og_description` varchar(500) DEFAULT NULL,
    `og_image` varchar(255) DEFAULT NULL,
    `service_order` int unsigned NOT NULL DEFAULT '0',
    `service_status` enum('Enable','Disable') NOT NULL DEFAULT 'Enable',
    `service_added` datetime NOT NULL,
    `service_updated` datetime NOT NULL,
    PRIMARY KEY (`service_id`),
    UNIQUE KEY `services_slug` (`service_slug`),
    KEY `services_category` (`service_category_id`),
    KEY `services_listing` (`service_status`, `service_order`),
    CONSTRAINT `services_category_fk` FOREIGN KEY (`service_category_id`)
        REFERENCES `service_categories` (`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_addons` (
    `addon_id` int unsigned NOT NULL AUTO_INCREMENT,
    `addon_service_id` int unsigned NOT NULL,
    `addon_label` varchar(150) NOT NULL,
    `addon_price` decimal(8,2) unsigned DEFAULT NULL,
    `addon_order` int unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`addon_id`),
    KEY `service_addons_service` (`addon_service_id`, `addon_order`),
    CONSTRAINT `service_addons_service_fk` FOREIGN KEY (`addon_service_id`)
        REFERENCES `services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Artists (team) -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `artists` (
    `artist_id` int unsigned NOT NULL AUTO_INCREMENT,
    `artist_name` varchar(120) NOT NULL,
    `artist_slug` varchar(130) NOT NULL,
    `artist_role` varchar(120) DEFAULT NULL,
    `artist_bio` text,
    `artist_specialties` text,
    `artist_working_days` varchar(40) DEFAULT NULL,
    `artist_image` varchar(255) DEFAULT NULL,
    `artist_is_placeholder` tinyint(1) unsigned NOT NULL DEFAULT '0',
    `artist_order` int unsigned NOT NULL DEFAULT '0',
    `artist_status` enum('Enable','Disable') NOT NULL DEFAULT 'Enable',
    `artist_added` datetime NOT NULL,
    `artist_updated` datetime NOT NULL,
    PRIMARY KEY (`artist_id`),
    UNIQUE KEY `artists_slug` (`artist_slug`),
    KEY `artists_listing` (`artist_status`, `artist_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `artist_services` (
    `artist_id` int unsigned NOT NULL,
    `service_id` int unsigned NOT NULL,
    PRIMARY KEY (`artist_id`, `service_id`),
    KEY `artist_services_service` (`service_id`),
    CONSTRAINT `artist_services_artist_fk` FOREIGN KEY (`artist_id`)
        REFERENCES `artists` (`artist_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `artist_services_service_fk` FOREIGN KEY (`service_id`)
        REFERENCES `services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery --------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `gallery_categories` (
    `category_id` int unsigned NOT NULL AUTO_INCREMENT,
    `category_name` varchar(100) NOT NULL,
    `category_slug` varchar(110) NOT NULL,
    `category_order` int unsigned NOT NULL DEFAULT '0',
    `category_status` enum('Enable','Disable') NOT NULL DEFAULT 'Enable',
    `category_added` datetime NOT NULL,
    `category_updated` datetime NOT NULL,
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `gallery_categories_slug` (`category_slug`),
    KEY `gallery_categories_listing` (`category_status`, `category_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_images` (
    `image_id` int unsigned NOT NULL AUTO_INCREMENT,
    `image_category_id` int unsigned DEFAULT NULL,
    `image_service_id` int unsigned DEFAULT NULL,
    `image_file` varchar(255) NOT NULL,
    `image_caption` varchar(255) NOT NULL,
    `image_featured` tinyint(1) unsigned NOT NULL DEFAULT '0',
    `image_order` int unsigned NOT NULL DEFAULT '0',
    `image_status` enum('Enable','Disable') NOT NULL DEFAULT 'Enable',
    `image_added` datetime NOT NULL,
    `image_updated` datetime NOT NULL,
    PRIMARY KEY (`image_id`),
    KEY `gallery_images_category` (`image_category_id`),
    KEY `gallery_images_service` (`image_service_id`),
    KEY `gallery_images_listing` (`image_status`, `image_order`),
    CONSTRAINT `gallery_images_category_fk` FOREIGN KEY (`image_category_id`)
        REFERENCES `gallery_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `gallery_images_service_fk` FOREIGN KEY (`image_service_id`)
        REFERENCES `services` (`service_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `offers` (
    `offer_id` int unsigned NOT NULL AUTO_INCREMENT,
    `offer_title` varchar(150) NOT NULL,
    `offer_slug` varchar(160) NOT NULL,
    `offer_label` varchar(80) DEFAULT NULL,
    `offer_summary` varchar(500) DEFAULT NULL,
    `offer_inclusions` text,
    `offer_price` decimal(8,2) unsigned NOT NULL,
    `offer_old_price` decimal(8,2) unsigned DEFAULT NULL,
    `offer_duration_label` varchar(60) DEFAULT NULL,
    `offer_valid_from` date DEFAULT NULL,
    `offer_valid_to` date DEFAULT NULL,
    `offer_validity_note` varchar(255) DEFAULT NULL,
    `offer_image` varchar(255) DEFAULT NULL,
    `offer_featured` tinyint(1) unsigned NOT NULL DEFAULT '0',
    `offer_order` int unsigned NOT NULL DEFAULT '0',
    `offer_status` enum('Enable','Disable') NOT NULL DEFAULT 'Enable',
    `offer_added` datetime NOT NULL,
    `offer_updated` datetime NOT NULL,
    PRIMARY KEY (`offer_id`),
    UNIQUE KEY `offers_slug` (`offer_slug`),
    KEY `offers_listing` (`offer_status`, `offer_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `offer_services` (
    `offer_id` int unsigned NOT NULL,
    `service_id` int unsigned NOT NULL,
    PRIMARY KEY (`offer_id`, `service_id`),
    KEY `offer_services_service` (`service_id`),
    CONSTRAINT `offer_services_offer_fk` FOREIGN KEY (`offer_id`)
        REFERENCES `offers` (`offer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `offer_services_service_fk` FOREIGN KEY (`service_id`)
        REFERENCES `services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointment requests ---------------------------------------------------------
-- Rows are created by the public booking-request form (Phase 6). Service,
-- artist and offer details are copied onto the request so later edits to
-- the catalogue never change what the client asked for.

CREATE TABLE IF NOT EXISTS `appointments` (
    `appointment_id` int unsigned NOT NULL AUTO_INCREMENT,
    `appointment_reference` varchar(20) NOT NULL,
    `appointment_status` enum('New','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'New',
    `appointment_date` date NOT NULL,
    `appointment_time` time NOT NULL,
    `appointment_duration_minutes` smallint unsigned DEFAULT NULL,
    `appointment_total_price` decimal(8,2) unsigned DEFAULT NULL,
    `appointment_artist_id` int unsigned DEFAULT NULL,
    `appointment_artist_name` varchar(120) DEFAULT NULL,
    `appointment_offer_id` int unsigned DEFAULT NULL,
    `appointment_offer_title` varchar(150) DEFAULT NULL,
    `customer_name` varchar(150) NOT NULL,
    `customer_email` varchar(190) NOT NULL,
    `customer_phone` varchar(40) DEFAULT NULL,
    `customer_contact_preference` enum('Email','Phone') NOT NULL DEFAULT 'Email',
    `customer_notes` text,
    `ip` varchar(45) DEFAULT NULL,
    `user_agent` varchar(500) DEFAULT NULL,
    `appointment_added` datetime NOT NULL,
    `appointment_updated` datetime NOT NULL,
    PRIMARY KEY (`appointment_id`),
    UNIQUE KEY `appointments_reference` (`appointment_reference`),
    KEY `appointments_status_date` (`appointment_status`, `appointment_date`, `appointment_time`),
    KEY `appointments_artist` (`appointment_artist_id`),
    KEY `appointments_offer` (`appointment_offer_id`),
    CONSTRAINT `appointments_artist_fk` FOREIGN KEY (`appointment_artist_id`)
        REFERENCES `artists` (`artist_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `appointments_offer_fk` FOREIGN KEY (`appointment_offer_id`)
        REFERENCES `offers` (`offer_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointment_services` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `appointment_id` int unsigned NOT NULL,
    `service_id` int unsigned DEFAULT NULL,
    `service_name` varchar(150) NOT NULL,
    `service_price` decimal(8,2) unsigned DEFAULT NULL,
    `service_duration_minutes` smallint unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `appointment_services_appointment` (`appointment_id`),
    KEY `appointment_services_service` (`service_id`),
    CONSTRAINT `appointment_services_appointment_fk` FOREIGN KEY (`appointment_id`)
        REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `appointment_services_service_fk` FOREIGN KEY (`service_id`)
        REFERENCES `services` (`service_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointment_notes` (
    `note_id` int unsigned NOT NULL AUTO_INCREMENT,
    `appointment_id` int unsigned NOT NULL,
    `author_id` int unsigned NOT NULL,
    `note` text NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`note_id`),
    KEY `appointment_notes_appointment` (`appointment_id`, `note_id`),
    CONSTRAINT `appointment_notes_appointment_fk` FOREIGN KEY (`appointment_id`)
        REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
