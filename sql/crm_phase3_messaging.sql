-- CRM Phase 3: permissions, unified message index, outbound queue, consent/suppression.
-- Non-destructive. Run after crm_foundation.sql via migrate_crm_phase3.php

CREATE TABLE IF NOT EXISTS `crm_permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  UNIQUE KEY `uq_crm_permission_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_crm_rp_role` FOREIGN KEY (`role_id`) REFERENCES `crm_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `crm_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `direction` ENUM('inbound','outbound') NOT NULL,
  `wa_message_id` VARCHAR(128) DEFAULT NULL,
  `message_type` VARCHAR(32) NOT NULL DEFAULT 'text',
  `body_text` TEXT DEFAULT NULL,
  `media_json` JSON DEFAULT NULL,
  `delivery_status` VARCHAR(32) DEFAULT NULL,
  `source_inbound_id` BIGINT UNSIGNED DEFAULT NULL,
  `source_outbound_id` BIGINT UNSIGNED DEFAULT NULL,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_msg_wamid` (`tenant_id`, `wa_message_id`),
  KEY `idx_crm_msg_conv_time` (`conversation_id`, `created_at`),
  KEY `idx_crm_msg_tenant` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_messages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `crm_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_message_status_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `wa_message_id` VARCHAR(128) NOT NULL,
  `status` VARCHAR(32) NOT NULL,
  `recipient_wa_id` VARCHAR(32) DEFAULT NULL,
  `error_message` VARCHAR(500) DEFAULT NULL,
  `wa_timestamp` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_status_evt` (`tenant_id`, `wa_message_id`, `status`, `wa_timestamp`),
  KEY `idx_crm_status_wamid` (`wa_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_outbound_jobs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `conversation_id` BIGINT UNSIGNED DEFAULT NULL,
  `recipient_wa_id` VARCHAR(32) NOT NULL,
  `job_type` ENUM('text','template','image','document') NOT NULL DEFAULT 'text',
  `payload_json` JSON NOT NULL,
  `status` ENUM('pending','processing','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `next_attempt_at` TIMESTAMP NULL DEFAULT NULL,
  `wa_message_id` VARCHAR(128) DEFAULT NULL,
  `last_error` VARCHAR(500) DEFAULT NULL,
  `requested_by_admin_id` INT UNSIGNED DEFAULT NULL,
  `idempotency_key` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_outbound_idempotency` (`tenant_id`, `idempotency_key`),
  KEY `idx_crm_jobs_queue` (`status`, `next_attempt_at`),
  KEY `idx_crm_jobs_tenant` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_jobs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_consents` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `purpose` VARCHAR(64) NOT NULL DEFAULT 'customer_service',
  `source` VARCHAR(64) DEFAULT NULL,
  `evidence` VARCHAR(255) DEFAULT NULL,
  `consented_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_consent_contact` (`contact_id`, `purpose`),
  CONSTRAINT `fk_crm_consent_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_consent_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_suppressions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `wa_id` VARCHAR(32) NOT NULL,
  `reason` VARCHAR(64) NOT NULL DEFAULT 'opt_out',
  `source` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_suppress_wa` (`tenant_id`, `wa_id`),
  CONSTRAINT `fk_crm_suppress_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_login_attempts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(80) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_login_user_time` (`username`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_messaging_pause` (
  `tenant_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  `paused` TINYINT(1) NOT NULL DEFAULT 0,
  `reason` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_crm_pause_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
