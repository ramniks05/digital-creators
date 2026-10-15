-- Razorpay billing tables (extends SaaS foundation).

CREATE TABLE IF NOT EXISTS `crm_payment_customers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'razorpay',
  `provider_customer_id` VARCHAR(64) NOT NULL,
  `email` VARCHAR(160) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_pay_cust_tenant_provider` (`tenant_id`, `provider`),
  UNIQUE KEY `uq_crm_pay_cust_provider_id` (`provider`, `provider_customer_id`),
  CONSTRAINT `fk_crm_pay_cust_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_payment_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'razorpay',
  `provider_payment_id` VARCHAR(64) DEFAULT NULL,
  `provider_order_id` VARCHAR(64) DEFAULT NULL,
  `provider_subscription_id` VARCHAR(64) DEFAULT NULL,
  `amount_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `status` VARCHAR(32) NOT NULL DEFAULT 'created',
  `idempotency_key` VARCHAR(64) DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_pay_txn_provider_payment` (`provider`, `provider_payment_id`),
  KEY `idx_crm_pay_txn_tenant` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_pay_txn_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_subscription_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `subscription_id` BIGINT UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `provider_event_id` VARCHAR(64) DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_sub_evt_provider` (`provider_event_id`),
  KEY `idx_crm_sub_evt_tenant` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_sub_evt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_billing_webhook_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider` VARCHAR(32) NOT NULL DEFAULT 'razorpay',
  `event_id` VARCHAR(64) NOT NULL,
  `event_type` VARCHAR(64) NOT NULL,
  `payload_json` JSON NOT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `process_error` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_billing_wh_event` (`provider`, `event_id`),
  KEY `idx_crm_billing_wh_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_invoices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(32) NOT NULL,
  `amount_cents` INT UNSIGNED NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'INR',
  `tax_cents` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft','issued','paid','void') NOT NULL DEFAULT 'draft',
  `provider_invoice_id` VARCHAR(64) DEFAULT NULL,
  `issued_at` TIMESTAMP NULL DEFAULT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_crm_invoice_number` (`invoice_number`),
  KEY `idx_crm_invoice_tenant` (`tenant_id`, `created_at`),
  CONSTRAINT `fk_crm_invoice_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `crm_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
