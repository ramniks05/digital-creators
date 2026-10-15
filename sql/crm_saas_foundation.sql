-- Multi-tenant SaaS foundation (extends existing crm_* tables).
-- Run: php sql/migrate_crm_saas_foundation.php

CREATE TABLE IF NOT EXISTS `crm_users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(160) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(160) DEFAULT NULL,
  `status` ENUM('active','suspended','pending_verification') NOT NULL DEFAULT 'pending_verification',
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_tenant_users` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `tenant_id`),
  KEY `idx_crm_tenant_users_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_tu_user` FOREIGN KEY (`user_id`) REFERENCES `crm_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_tu_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_tu_role` FOREIGN KEY (`role_id`) REFERENCES `crm_roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_tenant_settings` (
  `tenant_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  `business_email` VARCHAR(160) DEFAULT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `company_profile_json` JSON DEFAULT NULL,
  `onboarding_completed_at` TIMESTAMP NULL DEFAULT NULL,
  `settings_json` JSON DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_crm_tsettings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_tenant_invitations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `invited_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `accepted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_invite_token` (`token_hash`),
  KEY `idx_crm_invite_tenant_email` (`tenant_id`, `email`),
  CONSTRAINT `fk_crm_invite_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_email_verification_tokens` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uq_crm_ev_token` (`token_hash`),
  KEY `idx_crm_ev_user` (`user_id`),
  CONSTRAINT `fk_crm_ev_user` FOREIGN KEY (`user_id`) REFERENCES `crm_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_password_reset_tokens` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uq_crm_pr_token` (`token_hash`),
  CONSTRAINT `fk_crm_pr_user` FOREIGN KEY (`user_id`) REFERENCES `crm_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_onboarding_progress` (
  `tenant_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  `steps_json` JSON NOT NULL,
  `current_step` VARCHAR(64) NOT NULL DEFAULT 'workspace',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_crm_onboard_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_subscription_plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(32) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `billing_interval` ENUM('month','year') NOT NULL DEFAULT 'month',
  `price_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `trial_days` INT UNSIGNED NOT NULL DEFAULT 14,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `limits_json` JSON NOT NULL,
  UNIQUE KEY `uq_crm_plan_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_subscriptions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('trialing','active','past_due','cancelled','suspended') NOT NULL DEFAULT 'trialing',
  `trial_ends_at` TIMESTAMP NULL DEFAULT NULL,
  `current_period_start` TIMESTAMP NULL DEFAULT NULL,
  `current_period_end` TIMESTAMP NULL DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_sub_tenant_active` (`tenant_id`),
  CONSTRAINT `fk_crm_sub_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `crm_subscription_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_meta_integrations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `waba_id` VARCHAR(64) DEFAULT NULL,
  `status` ENUM('pending','connected','disconnected','revoked','error') NOT NULL DEFAULT 'pending',
  `meta_business_id` VARCHAR(64) DEFAULT NULL,
  `connected_at` TIMESTAMP NULL DEFAULT NULL,
  `disconnected_at` TIMESTAMP NULL DEFAULT NULL,
  `last_error` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_crm_meta_tenant` (`tenant_id`),
  KEY `idx_crm_meta_waba` (`waba_id`),
  CONSTRAINT `fk_crm_meta_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_integration_credentials` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `integration_id` BIGINT UNSIGNED DEFAULT NULL,
  `credential_type` VARCHAR(32) NOT NULL DEFAULT 'wa_access_token',
  `encrypted_payload` TEXT NOT NULL,
  `key_version` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_crm_cred_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_cred_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_onboarding_sessions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `state_token_hash` CHAR(64) NOT NULL,
  `meta_session_data_json` JSON DEFAULT NULL,
  `status` ENUM('started','completed','failed','expired') NOT NULL DEFAULT 'started',
  `expires_at` TIMESTAMP NOT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_ob_state` (`state_token_hash`),
  KEY `idx_crm_ob_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_ob_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_whatsapp_numbers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `integration_id` BIGINT UNSIGNED DEFAULT NULL,
  `account_id` BIGINT UNSIGNED DEFAULT NULL,
  `waba_id` VARCHAR(64) DEFAULT NULL,
  `phone_number_id` VARCHAR(64) NOT NULL,
  `display_phone` VARCHAR(32) DEFAULT NULL,
  `verified_name` VARCHAR(160) DEFAULT NULL,
  `quality_rating` VARCHAR(32) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_phone_number_id_global` (`phone_number_id`),
  KEY `idx_crm_wa_num_tenant` (`tenant_id`),
  CONSTRAINT `fk_crm_wa_num_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_usage_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `idempotency_key` VARCHAR(64) DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_usage_idem` (`tenant_id`, `idempotency_key`),
  KEY `idx_crm_usage_tenant_time` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_usage_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_usage_counters` (
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `period_ym` CHAR(7) NOT NULL,
  `metric` VARCHAR(64) NOT NULL,
  `value` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tenant_id`, `period_ym`, `metric`),
  CONSTRAINT `fk_crm_uc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_platform_admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(160) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(160) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_platform_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_platform_audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `platform_admin_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL,
  `tenant_id` BIGINT UNSIGNED DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_plat_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_support_access_grants` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `platform_admin_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_crm_support_grant` (`tenant_id`, `expires_at`),
  CONSTRAINT `fk_crm_support_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_feature_flags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(64) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `default_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_crm_ff_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_tenant_feature_flags` (
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `flag_id` INT UNSIGNED NOT NULL,
  `enabled` TINYINT(1) NOT NULL,
  PRIMARY KEY (`tenant_id`, `flag_id`),
  CONSTRAINT `fk_crm_tff_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crm_tff_flag` FOREIGN KEY (`flag_id`) REFERENCES `crm_feature_flags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
