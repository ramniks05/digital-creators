-- Ensure App Development exists, uses the new banner, and sits after Website Development
UPDATE services
SET
  image = 'assets/service/AppDevelopment.png',
  title = 'App Development',
  is_active = 1,
  sort_order = 3
WHERE LOWER(TRIM(title)) IN ('app development', 'app developnet', 'mobile app development');

INSERT INTO services (icon, title, description, metric, image, tag, stack_json, sort_order, is_active)
SELECT
  'smartphone',
  'App Development',
  'Native and cross-platform iOS and Android apps with secure APIs and polished interfaces.',
  'iOS & Android',
  'assets/service/AppDevelopment.png',
  'Mobile Engineering',
  '["React Native","Flutter","iOS","Android"]',
  3,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM services WHERE LOWER(TRIM(title)) = 'app development'
);

UPDATE services SET sort_order = 1 WHERE LOWER(TRIM(title)) = 'custom software';
UPDATE services SET sort_order = 2 WHERE LOWER(TRIM(title)) = 'website development';
UPDATE services SET sort_order = 3 WHERE LOWER(TRIM(title)) = 'app development';
