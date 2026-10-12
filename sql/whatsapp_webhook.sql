-- Meta WhatsApp Cloud API webhook storage (run once on existing DB).
USE `digital_creators`;

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
