-- Hostinger / phpMyAdmin: create demo_products table (run once)
CREATE TABLE IF NOT EXISTS `demo_products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'other',
  `category_label` VARCHAR(100) DEFAULT NULL,
  `summary` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `demo_url` VARCHAR(500) DEFAULT NULL,
  `guide_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('live','coming_soon') NOT NULL DEFAULT 'coming_soon',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `features_json` TEXT DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
