<?php
/**
 * One-time: create demo_products table and seed catalog (safe if table already has rows).
 * Run: php sql/migrate_demo_products.php
 */
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();

$pdo->exec("
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
) ENGINE=InnoDB
");

echo "demo_products table ready.\n";

$count = (int) $pdo->query('SELECT COUNT(*) FROM demo_products')->fetchColumn();
if ($count > 0) {
    echo "Table already has {$count} row(s); skip seed.\n";
    exit(0);
}

$products = [
    ['Gym Management Software', 'gym', 'Fitness', 'Memberships, attendance, trainers, billing, and class schedules — ready to demo and customize for your gym brand.', 'assets/images/products/gym.webp', 'https://example.com/gym-demo', 'https://example.com/gym-user-guide', 'live', 1, ['Member registration & membership plans', 'Trainer & class scheduling', 'Attendance & access tracking', 'Billing, invoices & renewals', 'Admin dashboard & reports']],
    ['Multivendor E-commerce', 'ecommerce', 'E-commerce', 'Marketplace with vendor stores, commissions, product catalogs, and order workflows — brandable for your business.', 'assets/images/products/multivendor.webp', '', '', 'coming_soon', 0, []],
    ['News Portal', 'news', 'Publishing', 'Editorial CMS, categories, breaking news layouts, and ad-ready pages for digital newsrooms.', 'assets/images/products/news.webp', '', '', 'coming_soon', 0, []],
    ['CRM System', 'crm', 'CRM', 'Leads, pipelines, follow-ups, and team activity tracking for sales-driven organizations.', 'assets/images/products/crm.webp', '', '', 'coming_soon', 0, []],
    ['School Management', 'school', 'Education', 'Students, fees, attendance, staff, and academic operations in one school admin platform.', 'assets/images/products/school.webp', '', '', 'coming_soon', 0, []],
    ['Tuition Management', 'tuition', 'Education', 'Batches, fees, student progress, and coaching-center operations built for tuition institutes.', 'assets/images/products/tuition.webp', '', '', 'coming_soon', 0, []],
    ['Single Vendor E-commerce', 'ecommerce', 'E-commerce', 'Storefront, cart, checkout, inventory, and order management for a single brand shop.', 'assets/images/products/ecommerce.webp', '', '', 'coming_soon', 0, []],
];

$ins = $pdo->prepare('INSERT INTO demo_products (title, category, category_label, summary, image, demo_url, guide_url, status, is_featured, features_json, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,1)');
foreach ($products as $i => $p) {
    $ins->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], json_encode($p[9]), $i]);
}

echo count($products) . " demo products seeded.\n";
