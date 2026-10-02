-- ---------------------------------------------------------------------------
-- Phase 10 — Customer accounts (PROJECT_PLAN.md, decision D2 changed)
--
-- customers                  website accounts. Optional: guests still book.
-- customer_tokens            one-use links: 'verify' (confirm the email
--                            address) and 'reset' (choose a new password),
--                            stored as SHA-256 hashes.
-- customer_remember_tokens   "remember me" cookies (selector + hashed
--                            validator, rotated on use), as for admins.
-- customer_login_attempts    every sign-in attempt, used to slow down
--                            guessing by email address and by IP.
-- appointments.customer_id   the account a booking belongs to (NULL for a
--                            guest booking; cleared if the account goes).
--
-- Email templates 5 (confirm your email), 6 (reset your password) and
-- 7 (password changed) use the 'customer' short tags.
--
-- Additive; safe to run again (templates 5–7 are rewritten).
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `customers` (
    `customer_id` int unsigned NOT NULL AUTO_INCREMENT,
    `customer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    `customer_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
    `customer_phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `customer_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `customer_status` enum('Enable','Disable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Enable',
    `customer_email_verified_at` datetime DEFAULT NULL,
    `customer_last_login` datetime DEFAULT NULL,
    `customer_last_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `customer_added` datetime NOT NULL,
    `customer_updated` datetime NOT NULL,
    PRIMARY KEY (`customer_id`),
    UNIQUE KEY `customers_email` (`customer_email`),
    KEY `customers_listing` (`customer_status`, `customer_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_tokens` (
    `token_id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` int unsigned NOT NULL,
    `token_type` enum('verify','reset') COLLATE utf8mb4_unicode_ci NOT NULL,
    `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `expires_at` datetime NOT NULL,
    `used_at` datetime DEFAULT NULL,
    `requested_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`token_id`),
    UNIQUE KEY `customer_tokens_hash` (`token_hash`),
    KEY `customer_tokens_customer` (`customer_id`, `token_type`, `created_at`),
    CONSTRAINT `customer_tokens_customer_fk` FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_remember_tokens` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` int unsigned NOT NULL,
    `selector` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `last_used_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `customer_remember_tokens_selector` (`selector`),
    KEY `customer_remember_tokens_customer` (`customer_id`),
    KEY `customer_remember_tokens_expiry` (`expires_at`),
    CONSTRAINT `customer_remember_tokens_customer_fk` FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_login_attempts` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `customer_id` int unsigned DEFAULT NULL,
    `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `was_successful` tinyint(1) NOT NULL DEFAULT '0',
    `failure_reason` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
    `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `attempted_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `customer_login_attempts_email` (`email`, `attempted_at`),
    KEY `customer_login_attempts_ip` (`ip_address`, `attempted_at`),
    CONSTRAINT `customer_login_attempts_customer_fk` FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- appointments.customer_id (added once).
DROP PROCEDURE IF EXISTS `phase10_customers_schema`;

DELIMITER $$
CREATE PROCEDURE `phase10_customers_schema`()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'customer_id'
    ) THEN
        ALTER TABLE `appointments`
            ADD COLUMN `customer_id` int unsigned DEFAULT NULL AFTER `appointment_offer_title`,
            ADD KEY `appointments_customer` (`customer_id`, `appointment_date`),
            ADD CONSTRAINT `appointments_customer_fk` FOREIGN KEY (`customer_id`)
                REFERENCES `customers` (`customer_id`) ON DELETE SET NULL;
    END IF;
END$$
DELIMITER ;

CALL `phase10_customers_schema`();
DROP PROCEDURE `phase10_customers_schema`;

-- Account emails.
INSERT INTO `email_templates` (`id`, `name`, `subject`, `heading`, `contents`) VALUES
(5, 'Confirm Email Address (Customer Email)', 'Please confirm your email address', 'Confirm your email address',
'<p>Hi {{first_name}},</p>

<p>Thank you for creating an account with Blossom Ewa Mazur. Please confirm that this is your email address:</p>

<p><a href="{{link}}">Confirm my email address</a></p>

<p>The link works for {{expires}}. Once confirmed, bookings you made earlier with this address appear in your account too.</p>

<p>If you did not create an account, you can ignore this email.</p>

<p>See you soon,<br />
<strong>Blossom Ewa Mazur</strong></p>'),
(6, 'Reset Password (Customer Email)', 'Reset your Blossom Ewa Mazur password', 'Reset your password',
'<p>Hi {{first_name}},</p>

<p>We received a request to reset the password for your account. Choose a new one here:</p>

<p><a href="{{link}}">Choose a new password</a></p>

<p>The link works for {{expires}} and only once. If you did not ask for this, you can ignore this email; your password has not changed.</p>

<p>Kind regards,<br />
<strong>Blossom Ewa Mazur</strong></p>'),
(7, 'Password Changed (Customer Email)', 'Your Blossom Ewa Mazur password was changed', 'Your password was changed',
'<p>Hi {{first_name}},</p>

<p>The password for your account ({{customer_email}}) was just changed.</p>

<p>If this was you, there is nothing else to do. If it was not, please reset your password from the sign-in page and let the salon know.</p>

<p>Kind regards,<br />
<strong>Blossom Ewa Mazur</strong></p>')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `subject` = VALUES(`subject`),
    `heading` = VALUES(`heading`),
    `contents` = VALUES(`contents`);
