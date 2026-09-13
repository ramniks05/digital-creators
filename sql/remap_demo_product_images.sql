-- Point demo products at Git-tracked banners (survives Hostinger Git deploy).
-- Run in phpMyAdmin after this deploy.

UPDATE demo_products SET image = 'assets/images/products/gym.png' WHERE category = 'gym';
UPDATE demo_products SET image = 'assets/images/products/news.png' WHERE category = 'news';
UPDATE demo_products SET image = 'assets/images/products/crm.png' WHERE category = 'crm';
UPDATE demo_products SET image = 'assets/images/products/school.png' WHERE category = 'school';
UPDATE demo_products SET image = 'assets/images/products/tuition.png' WHERE category = 'tuition';
UPDATE demo_products SET image = 'assets/images/products/multivendor.png'
WHERE category = 'ecommerce' AND (title LIKE '%Multi%' OR title LIKE '%Multivendor%');
UPDATE demo_products SET image = 'assets/images/products/ecommerce.png'
WHERE category = 'ecommerce' AND title NOT LIKE '%Multi%' AND title NOT LIKE '%Multivendor%';

-- Drop old WebP thumbs if any remain
UPDATE demo_products SET image = REPLACE(image, '.webp', '.png')
WHERE image LIKE 'assets/images/products/%.webp';
