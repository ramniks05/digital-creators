-- WhatsApp CRM foundation (multi-tenant ready, single tenant id=1 for Digital Creatorss).
-- Run: php sql/migrate_crm_foundation.php

CREATE TABLE IF NOT EXISTS `crm_tenants` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(64) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `status` ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_tenant_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `slug` VARCHAR(32) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_role_tenant_slug` (`tenant_id`, `slug`),
  CONSTRAINT `fk_crm_roles_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_admin_tenant_roles` (
  `admin_id` INT UNSIGNED NOT NULL,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `tenant_id`),
  KEY `idx_crm_admin_tenant_role` (`tenant_id`, `role_id`),
  CONSTRAINT `fk_crm_admin_tenant_roles_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_admin_tenant_roles_role` FOREIGN KEY (`role_id`) REFERENCES `crm_roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_whatsapp_accounts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `waba_id` VARCHAR(64) DEFAULT NULL,
  `phone_number_id` VARCHAR(64) NOT NULL,
  `display_phone` VARCHAR(32) DEFAULT NULL,
  `label` VARCHAR(120) NOT NULL DEFAULT 'Primary',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_wa_phone_number` (`tenant_id`, `phone_number_id`),
  KEY `idx_crm_wa_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_wa_accounts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contacts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `wa_id` VARCHAR(32) DEFAULT NULL,
  `display_name` VARCHAR(160) DEFAULT NULL,
  `email` VARCHAR(160) DEFAULT NULL,
  `phone` VARCHAR(32) DEFAULT NULL,
  `lead_id` INT UNSIGNED DEFAULT NULL,
  `consent_status` ENUM('unknown','opted_in','opted_out') NOT NULL DEFAULT 'unknown',
  `tags_json` JSON DEFAULT NULL,
  `custom_fields_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_contact_wa` (`tenant_id`, `wa_id`),
  KEY `idx_crm_contacts_tenant_phone` (`tenant_id`, `phone`),
  KEY `idx_crm_contacts_lead` (`lead_id`),
  CONSTRAINT `fk_crm_contacts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_conversations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `channel` ENUM('whatsapp') NOT NULL DEFAULT 'whatsapp',
  `peer_wa_id` VARCHAR(32) NOT NULL,
  `contact_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('open','pending','resolved') NOT NULL DEFAULT 'open',
  `assigned_admin_id` INT UNSIGNED DEFAULT NULL,
  `last_message_at` TIMESTAMP NULL DEFAULT NULL,
  `last_message_preview` VARCHAR(255) DEFAULT NULL,
  `last_inbound_at` TIMESTAMP NULL DEFAULT NULL,
  `unread_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_conv_peer` (`tenant_id`, `channel`, `peer_wa_id`),
  KEY `idx_crm_conv_status` (`tenant_id`, `status`, `last_message_at`),
  KEY `idx_crm_conv_assignee` (`tenant_id`, `assigned_admin_id`),
  CONSTRAINT `fk_crm_conversations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_conversations_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_conversation_notes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_notes_conv` (`conversation_id`, `created_at`),
  CONSTRAINT `fk_crm_notes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_notes_conv` FOREIGN KEY (`conversation_id`) REFERENCES `crm_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_lead_stages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `slug` VARCHAR(32) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_won` TINYINT(1) NOT NULL DEFAULT 0,
  `is_lost` TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_crm_stage_slug` (`tenant_id`, `slug`),
  CONSTRAINT `fk_crm_stages_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_webhook_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  `event_sha256` CHAR(64) NOT NULL,
  `payload_json` JSON NOT NULL,
  `payload_bytes` INT UNSIGNED NOT NULL DEFAULT 0,
  `received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `process_error` VARCHAR(500) DEFAULT NULL,
  UNIQUE KEY `uq_crm_webhook_event_sha` (`event_sha256`),
  KEY `idx_crm_webhook_received` (`received_at`),
  KEY `idx_crm_webhook_processed` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(64) DEFAULT NULL,
  `entity_id` VARCHAR(64) DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_audit_tenant_time` (`tenant_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
