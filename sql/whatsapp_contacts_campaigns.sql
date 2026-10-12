-- WhatsApp contacts (consent/opt-out) and marketing campaigns (run after whatsapp_webhook.sql).

CREATE TABLE IF NOT EXISTS `whatsapp_contacts` (
  `wa_id` VARCHAR(32) NOT NULL PRIMARY KEY,
  `display_name` VARCHAR(120) DEFAULT NULL,
  `consent_status` ENUM('unknown','opted_in','opted_out') NOT NULL DEFAULT 'unknown',
  `consent_source` VARCHAR(64) DEFAULT NULL,
  `consent_at` TIMESTAMP NULL DEFAULT NULL,
  `opt_out_at` TIMESTAMP NULL DEFAULT NULL,
  `last_inbound_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_wa_contacts_consent` (`consent_status`),
  KEY `idx_wa_contacts_last_inbound` (`last_inbound_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `whatsapp_campaigns` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `template_name` VARCHAR(128) NOT NULL,
  `language_code` VARCHAR(16) NOT NULL DEFAULT 'en_US',
  `template_params_json` JSON DEFAULT NULL,
  `status` ENUM('draft','queued','sending','completed','cancelled') NOT NULL DEFAULT 'draft',
  `filter_consent` ENUM('opted_in_only','all_not_opted_out') NOT NULL DEFAULT 'opted_in_only',
  `stats_sent` INT UNSIGNED NOT NULL DEFAULT 0,
  `stats_failed` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` VARCHAR(64) DEFAULT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `whatsapp_campaign_recipients` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` BIGINT UNSIGNED NOT NULL,
  `recipient_wa_id` VARCHAR(32) NOT NULL,
  `delivery_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `outbound_message_id` BIGINT UNSIGNED DEFAULT NULL,
  `error_message` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_campaign_recipient` (`campaign_id`, `recipient_wa_id`),
  KEY `idx_campaign_status` (`campaign_id`, `delivery_status`),
  CONSTRAINT `fk_campaign_recipients_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `whatsapp_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
