-- Rule-based WhatsApp chatbot (CRM integration). Run migrate_crm_chatbot.php after CRM foundation.

CREATE TABLE IF NOT EXISTS `chatbot_services` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `slug` VARCHAR(64) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_chatbot_service_slug` (`tenant_id`, `slug`),
  KEY `idx_chatbot_services_tenant` (`tenant_id`),
  CONSTRAINT `fk_chatbot_services_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chatbot_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `peer_wa_id` VARCHAR(32) NOT NULL,
  `contact_id` BIGINT UNSIGNED DEFAULT NULL,
  `conversation_id` BIGINT UNSIGNED DEFAULT NULL,
  `current_step` VARCHAR(64) NOT NULL DEFAULT 'MAIN_MENU',
  `mode` ENUM('bot','human') NOT NULL DEFAULT 'bot',
  `selected_service_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(160) DEFAULT NULL,
  `requirement_text` TEXT DEFAULT NULL,
  `budget_choice` VARCHAR(64) DEFAULT NULL,
  `timeline_text` VARCHAR(255) DEFAULT NULL,
  `lead_id` INT UNSIGNED DEFAULT NULL,
  `last_inbound_wamid` VARCHAR(128) DEFAULT NULL,
  `state_json` JSON DEFAULT NULL,
  `last_activity_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_chatbot_session_peer` (`tenant_id`, `peer_wa_id`),
  KEY `idx_chatbot_session_conv` (`conversation_id`),
  CONSTRAINT `fk_chatbot_session_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chatbot_assets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `asset_key` VARCHAR(64) NOT NULL,
  `filename` VARCHAR(255) DEFAULT NULL,
  `meta_media_id` VARCHAR(128) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_chatbot_asset` (`tenant_id`, `asset_key`),
  CONSTRAINT `fk_chatbot_assets_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `agent_handoffs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('waiting','assigned','closed') NOT NULL DEFAULT 'waiting',
  `assigned_admin_id` INT UNSIGNED DEFAULT NULL,
  `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  KEY `idx_handoff_tenant_status` (`tenant_id`, `status`),
  KEY `idx_handoff_conv` (`conversation_id`),
  CONSTRAINT `fk_handoff_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
