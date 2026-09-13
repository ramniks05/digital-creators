-- Remove Smart Attendance / Attendance Portal from the live site.
UPDATE projects SET is_active = 0 WHERE title LIKE '%Attendance%' OR image LIKE '%attend%';
UPDATE blog_posts SET image = 'assets/images/project-admin.webp' WHERE image LIKE '%attend%';
