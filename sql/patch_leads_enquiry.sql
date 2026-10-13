-- Lead source + WhatsApp linkage (run via migrate_leads_enquiry.php)

ALTER TABLE `leads`
  ADD COLUMN IF NOT EXISTS `source` ENUM('website_form','whatsapp') NOT NULL DEFAULT 'website_form' AFTER `budget`,
  ADD COLUMN IF NOT EXISTS `consent_at` TIMESTAMP NULL DEFAULT NULL AFTER `source`,
  ADD COLUMN IF NOT EXISTS `wa_message_id` VARCHAR(128) DEFAULT NULL AFTER `consent_at`;

-- MySQL 8.0.12+ may not support IF NOT EXISTS on columns; migration script handles duplicates.

CREATE UNIQUE INDEX IF NOT EXISTS `idx_leads_wa_message_id` ON `leads` (`wa_message_id`);
