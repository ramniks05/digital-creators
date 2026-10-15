-- Stage 1: Advanced contact management (extends crm_contacts; non-destructive).
-- Run via sql/migrate_crm_stage1_contacts.php after foundation + phase3.

ALTER TABLE `crm_contacts`
  ADD COLUMN IF NOT EXISTS `source` VARCHAR(64) NOT NULL DEFAULT 'whatsapp_inbound' AFTER `consent_status`,
  ADD COLUMN IF NOT EXISTS `owner_admin_id` INT UNSIGNED DEFAULT NULL AFTER `source`,
  ADD COLUMN IF NOT EXISTS `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `owner_admin_id`,
  ADD COLUMN IF NOT EXISTS `merged_into_contact_id` BIGINT UNSIGNED DEFAULT NULL AFTER `archived_at`,
  ADD COLUMN IF NOT EXISTS `import_batch_id` VARCHAR(64) DEFAULT NULL AFTER `merged_into_contact_id`;

-- MySQL 8.0 may not support IF NOT EXISTS on ADD COLUMN; migration script handles duplicates.

CREATE TABLE IF NOT EXISTS `crm_contact_tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `slug` VARCHAR(64) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `color` VARCHAR(7) DEFAULT '#64748b',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_tag_slug` (`tenant_id`, `slug`),
  KEY `idx_crm_tags_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_contact_tags_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_tag_assignments` (
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by_admin_id` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`contact_id`, `tag_id`),
  KEY `idx_crm_cta_tag` (`tag_id`),
  CONSTRAINT `fk_crm_cta_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_cta_tag` FOREIGN KEY (`tag_id`) REFERENCES `crm_contact_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_custom_fields` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `field_key` VARCHAR(64) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `field_type` ENUM('text','number','date','boolean','select') NOT NULL DEFAULT 'text',
  `options_json` JSON DEFAULT NULL,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_cfield_key` (`tenant_id`, `field_key`),
  CONSTRAINT `fk_crm_cfields_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_custom_values` (
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `field_id` INT UNSIGNED NOT NULL,
  `value_text` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contact_id`, `field_id`),
  CONSTRAINT `fk_crm_cval_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_cval_field` FOREIGN KEY (`field_id`) REFERENCES `crm_contact_custom_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_activity` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `activity_type` VARCHAR(64) NOT NULL,
  `summary` VARCHAR(255) DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_activity_contact_time` (`contact_id`, `created_at`),
  KEY `idx_crm_activity_tenant_time` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_activity_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_activity_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_notes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_cnotes_contact` (`contact_id`, `created_at`),
  CONSTRAINT `fk_crm_cnotes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_cnotes_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_merge_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `source_contact_id` BIGINT UNSIGNED NOT NULL,
  `target_contact_id` BIGINT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `snapshot_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_merge_tenant` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_merge_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_segments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `filter_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_segments_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_segments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_segment_members` (
  `segment_id` INT UNSIGNED NOT NULL,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`segment_id`, `contact_id`),
  CONSTRAINT `fk_crm_sm_segment` FOREIGN KEY (`segment_id`) REFERENCES `crm_contact_segments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_sm_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS `idx_crm_contacts_archived` ON `crm_contacts` (`tenant_id`, `archived_at`);
CREATE INDEX IF NOT EXISTS `idx_crm_contacts_owner` ON `crm_contacts` (`tenant_id`, `owner_admin_id`);
CREATE INDEX IF NOT EXISTS `idx_crm_contacts_source` ON `crm_contacts` (`tenant_id`, `source`);
