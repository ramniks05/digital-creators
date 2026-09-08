-- One-time: remove Ramesh Kumar director; keep Neekita as sole director
DELETE FROM team_members WHERE name LIKE '%Ramesh%' OR name LIKE '%Rohit%';

UPDATE team_members
SET
  role = 'Director',
  bio = 'Leads Digital Creatorss end-to-end — strategy, sales, delivery, client success, and day-to-day operations. The single point of leadership aligning technology solutions with business goals.',
  image = 'assets/images/Nikita_Maam.webp',
  education = 'MBA (IT)',
  icon = 'briefcase',
  sort_order = 0
WHERE name LIKE '%Neekita%';

UPDATE testimonials
SET review = REPLACE(review, 'Their CTO, Ramesh, architected', 'Their director Neekita and team architected')
WHERE review LIKE '%Ramesh%';
