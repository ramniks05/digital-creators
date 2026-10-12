-- Run once on DBs created before contact_name column was added.
ALTER TABLE `whatsapp_inbound_messages`
  ADD COLUMN `contact_name` VARCHAR(120) DEFAULT NULL AFTER `from_wa_id`;
