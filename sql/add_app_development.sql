-- Add App Development service if missing (run once on existing databases)
INSERT INTO services (icon, title, description, metric, image, tag, stack_json, sort_order, is_active)
SELECT
  'smartphone',
  'App Development',
  'Native and cross-platform mobile apps for iOS and Android — intuitive UI, secure APIs, push notifications, offline support, and store-ready releases.',
  'iOS & Android',
  'assets/images/software_engineering_mockup.webp',
  'Mobile Engineering',
  '["React Native","Flutter","iOS","Android"]',
  2,
  1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM services WHERE title = 'App Development');
