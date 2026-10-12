CREATE DATABASE IF NOT EXISTS `digital_creators` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `digital_creators`;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(80) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `icon` VARCHAR(80) NOT NULL DEFAULT 'code-2',
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `metric` VARCHAR(100) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `tag` VARCHAR(100) DEFAULT NULL,
  `stack_json` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `team_members` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `role` VARCHAR(150) NOT NULL,
  `bio` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `specialty` VARCHAR(200) DEFAULT NULL,
  `member_type` ENUM('director','core') NOT NULL DEFAULT 'core',
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `education` VARCHAR(150) DEFAULT NULL,
  `icon` VARCHAR(80) DEFAULT 'user',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'webapp',
  `category_label` VARCHAR(100) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `metric` VARCHAR(100) DEFAULT NULL,
  `metric_desc` VARCHAR(200) DEFAULT NULL,
  `stack_json` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'engineering',
  `excerpt` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `read_time` VARCHAR(40) DEFAULT '5 Min Read',
  `published_at` DATE DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `role` VARCHAR(200) NOT NULL,
  `review` TEXT NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `service` VARCHAR(100) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `budget` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('new','read','archived') NOT NULL DEFAULT 'new',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `demo_products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'other',
  `category_label` VARCHAR(100) DEFAULT NULL,
  `summary` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `demo_url` VARCHAR(500) DEFAULT NULL,
  `guide_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('live','coming_soon') NOT NULL DEFAULT 'coming_soon',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `features_json` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `whatsapp_webhook_dedupe` (
  `dedupe_key` VARCHAR(128) NOT NULL PRIMARY KEY,
  `event_kind` VARCHAR(32) NOT NULL DEFAULT 'unknown',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `whatsapp_inbound_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wa_message_id` VARCHAR(128) NOT NULL,
  `waba_id` VARCHAR(64) DEFAULT NULL,
  `phone_number_id` VARCHAR(64) DEFAULT NULL,
  `from_wa_id` VARCHAR(32) NOT NULL,
  `contact_name` VARCHAR(120) DEFAULT NULL,
  `message_type` VARCHAR(32) NOT NULL DEFAULT 'unknown',
  `body_text` TEXT DEFAULT NULL,
  `media_json` JSON DEFAULT NULL,
  `wa_timestamp` BIGINT UNSIGNED DEFAULT NULL,
  `raw_message_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_wa_inbound_message_id` (`wa_message_id`),
  KEY `idx_inbound_from` (`from_wa_id`),
  KEY `idx_inbound_created` (`created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `whatsapp_message_status_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wa_message_id` VARCHAR(128) NOT NULL,
  `waba_id` VARCHAR(64) DEFAULT NULL,
  `phone_number_id` VARCHAR(64) DEFAULT NULL,
  `recipient_wa_id` VARCHAR(32) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL,
  `error_code` INT DEFAULT NULL,
  `error_title` VARCHAR(255) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `error_details_json` JSON DEFAULT NULL,
  `wa_timestamp` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_status_event` (`wa_message_id`, `status`, `wa_timestamp`),
  KEY `idx_status_wamid` (`wa_message_id`),
  KEY `idx_status_recipient` (`recipient_wa_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `whatsapp_outbound_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wa_message_id` VARCHAR(128) DEFAULT NULL,
  `phone_number_id` VARCHAR(64) DEFAULT NULL,
  `recipient_wa_id` VARCHAR(32) NOT NULL,
  `message_type` VARCHAR(32) NOT NULL DEFAULT 'text',
  `template_name` VARCHAR(128) DEFAULT NULL,
  `payload_json` JSON DEFAULT NULL,
  `delivery_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `last_error_code` INT DEFAULT NULL,
  `last_error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_outbound_wamid` (`wa_message_id`),
  KEY `idx_outbound_recipient` (`recipient_wa_id`),
  KEY `idx_outbound_status` (`delivery_status`)
) ENGINE=InnoDB;
