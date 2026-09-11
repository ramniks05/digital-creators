<?php
/**
 * One-time seed: creates DB tables content from current site data.
 * Run: C:\xampp\php\php.exe sql/seed.php
 */
require_once dirname(__DIR__) . '/includes/db.php';

$config = require dirname(__DIR__) . '/includes/config.php';

// Create the database locally when permitted. Shared hosts normally provide
// an existing database and deny CREATE DATABASE, so continue in that case.
$hostDsn = sprintf(
    'mysql:host=%s;port=%s;charset=%s',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['charset']
);
$pdo = new PDO($hostDsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
try {
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $config['db']['name']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
} catch (PDOException $e) {
    // Expected on Hostinger/shared hosting where databases are created in hPanel.
}
$pdo->exec('USE `' . str_replace('`', '``', $config['db']['name']) . '`');

$schema = file_get_contents(__DIR__ . '/schema.sql');
// Strip CREATE DATABASE / USE so we run against selected DB
$schema = preg_replace('/CREATE DATABASE.*?;/is', '', $schema);
$schema = preg_replace('/USE\s+`?digital_creators`?\s*;/i', '', $schema);
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($stmt !== '') {
        $pdo->exec($stmt);
    }
}

echo "Schema ready.\n";

$pdo = db();

// Clear existing seedable data (keep leads)
$pdo->exec('DELETE FROM services');
$pdo->exec('DELETE FROM team_members');
$pdo->exec('DELETE FROM projects');
$pdo->exec('DELETE FROM blog_posts');
$pdo->exec('DELETE FROM testimonials');
$pdo->exec('DELETE FROM demo_products');
$pdo->exec('DELETE FROM settings');
$pdo->exec('DELETE FROM admins');

$adminUser = getenv('ADMIN_USER') ?: 'admin';
$adminPass = getenv('ADMIN_PASS') ?: bin2hex(random_bytes(8));
$hash = password_hash($adminPass, PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$adminUser, $hash]);
echo "Admin user: {$adminUser} / {$adminPass}\n";
echo "Save this password now; it is not stored in plain text.\n";

$settings = [
    'phone_1' => '+91-8851613806',
    'phone_2' => '7903152429',
    'email' => 'info@digitalcreatorss.com',
    'address' => 'C-84, C Block, Sec-2, Noida, Uttar Pardesh',
    'whatsapp' => '918851613806',
    'footer_blurb' => 'We engineer high-performance web applications, cloud hosting, and managed server infrastructure that keeps your business online and scalable.',
];
$insSet = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
foreach ($settings as $k => $v) {
    $insSet->execute([$k, $v]);
}

$services = [
    ['code-2', 'Custom Software', 'We engineer tailor-made software solutions designed to solve specific operational challenges, automate internal workflows, and scale alongside your growing business operations.', 'Enterprise Grade', 'assets/service/custom_software.png', 'Architecture Strategy', ['Laravel', 'Node.js', 'Python', 'AWS Cloud']],
    ['globe', 'Website Development', 'We build fast, secure, and mobile-friendly websites that establish your digital presence. Optimised for maximum user retention and seamless conversions.', 'High Speed CSS/JS', 'assets/service/webdevelopment.png', 'Frontend Performance', ['Next.js', 'TailwindCSS', 'GSAP', 'Vite']],
    ['smartphone', 'App Development', 'Native and cross-platform mobile apps for iOS and Android — intuitive UI, secure APIs, push notifications, offline support, and store-ready releases.', 'iOS & Android', 'assets/service/AppDevelopment.png', 'Mobile Engineering', ['React Native', 'Flutter', 'iOS', 'Android']],
    ['layers', 'Web Applications', 'From custom SaaS dashboards to intricate customer portals, we craft responsive web applications featuring secure databases and intuitive user dashboards.', 'Cloud Native SaaS', 'assets/service/web_applications.png', 'SaaS Engineering', ['React.js', 'PostgreSQL', 'Docker', 'GraphQL']],
    ['cloud', 'Cloud Hosting', 'Deploy on reliable cloud infrastructure with scalable resources, SSL, automated backups, and high availability for production workloads.', '99.9% Uptime', 'assets/service/cloud_hosting.png', 'Cloud Platforms', ['AWS', 'DigitalOcean', 'cPanel', 'SSL & CDN']],
    ['server', 'Server Management', 'Full server administration including monitoring, security patches, firewall hardening, performance tuning, and proactive incident response.', '24/7 Ops', 'assets/service/server_management.png', 'SysAdmin Care', ['Linux', 'Nginx', 'MySQL', 'Monitoring']],
    ['boxes', 'DevOps & Infrastructure', 'CI/CD pipelines, containers, and infrastructure-as-code so every release is repeatable, secure, and production-ready.', 'CI/CD Ready', 'assets/service/devops_infrastructure.png', 'Automation', ['Docker', 'GitHub Actions', 'Terraform', 'Kubernetes']],
    ['shield-check', 'Maintenance & Support', 'Ongoing application care: updates, uptime checks, database health, backup recovery, and priority support for live systems.', 'Always On', 'assets/service/maintenance_support.png', 'Lifecycle Care', ['Updates', 'Backups', 'Security', 'SLA Support']],
];
$insSvc = $pdo->prepare('INSERT INTO services (icon, title, description, metric, image, tag, stack_json, sort_order) VALUES (?,?,?,?,?,?,?,?)');
foreach ($services as $i => $s) {
    $insSvc->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], json_encode($s[6]), $i]);
}
echo count($services) . " services.\n";

$team = [
    ['Neekita Kumari', 'Director', 'Leads Digital Creatorss end-to-end — strategy, sales, delivery, client success, and day-to-day operations. The single point of leadership aligning technology solutions with business goals.', 'assets/images/director.jpg', null, 'director', 'info@digitalcreatorss.com', '+917903152429', 'MBA (IT)', 'briefcase'],
    ['Rahul Mehta', 'Senior Frontend Developer', 'Crafts beautiful interactive web panels, fluid micro-interactions, and maintains CSS standardization across projects.', 'assets/images/avatars/avatar-rahul.svg', 'TailwindCSS / Next.js / GSAP', 'core', null, null, null, 'layout'],
    ['Aman Gupta', 'Cloud & Hosting Engineer', 'Manages cloud deployments, SSL, DNS, and scalable hosting environments so production apps stay fast and available.', 'assets/images/avatars/avatar-aman.svg', 'AWS / Linux / CDN', 'core', null, null, null, 'cloud'],
    ['Priya Nair', 'Server Administrator', 'Handles server hardening, monitoring, backups, and performance tuning across client infrastructure.', 'assets/images/avatars/avatar-priya.svg', 'Linux / Nginx / Security', 'core', null, null, null, 'server'],
    ['Karan Joshi', 'DevOps Engineer', 'Builds deployment pipelines, container workflows, and observability so releases ship safely and repeatedly.', 'assets/images/avatars/avatar-karan.svg', 'CI/CD / Docker / Monitoring', 'core', null, null, null, 'boxes'],
];
$insTeam = $pdo->prepare('INSERT INTO team_members (name, role, bio, image, specialty, member_type, email, phone, education, icon, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
foreach ($team as $i => $t) {
    $insTeam->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $t[8], $t[9], $i]);
}
echo count($team) . " team members.\n";

$projects = [
    ['DFOHO — Highway Food App', 'webapp', 'Web Development', 'A full-stack highway food discovery & ordering platform. Users explore nearby dhabas on an in-built map, pre-order meals, book tables in advance, and earn rewards — smarter routes, faster meals, better journeys.', 'https://www.dfoho.com', 'assets/images/Dfoho.webp', 'Highway-First UX', 'Food ordering on the go', ['React Native', 'Node.js', 'MongoDB']],
    ['Social Impact Hub', 'nonprofit', 'Non-Profit Initiative', 'A centralized platform connecting volunteers with local community initiatives. Engineered with optimization, fast loading times, and accessible web standards.', 'https://www.sdftrust.org', 'assets/images/sdf.webp', '12K+ Volunteers', 'Connected globally', ['React', 'TailwindCSS', 'Node.js']],
    ['Tyre Marketplace', 'ecommerce', 'E-Commerce System', 'A complete high-performance marketplace featuring wholesale administration dashboards, fast shopping flows, and advanced inventory metrics.', 'https://www.autodeal4u.in', 'assets/images/tyre1.webp', '$1.4M+ GMV', 'Annual processing scale', ['Laravel', 'TailwindCSS', 'PostgreSQL']],
    ['Attendance Portal', 'webapp', 'Web Application', 'A high-performance cloud portal engineered for enterprise attendance tracking, active operation dashboards, and operational shift logging.', 'https://www.hrntechsolutions.com/attendance-system', 'assets/images/attend.webp', '40K+ Daily Logins', 'Active operations tracking', ['Vue.js', 'Express.js', 'MongoDB']],
    ['AURA Cosmetics Lab', 'hosting', 'Cloud & Hosting', 'A high-availability product catalog and storefront stack with managed hosting, SSL, CDN delivery, and secure checkout infrastructure.', 'https://www.digitalcreatorss.com', 'assets/images/branding_mockup.webp', '99.9% Uptime', 'Production hosting SLA', ['Nginx', 'SSL/CDN', 'PHP']],
    ['AIRA BRCS Legal Advisors', 'webapp', 'Web Application', 'A premium corporate law portal featuring practitioner profiles, legal consultation bookings, and practice area showcases.', 'https://hrntechsolutions.com/AIRA/', 'assets/images/aira.webp', '1500+ Matters', 'Successfully litigated', ['PHP', 'TailwindCSS', 'JavaScript']],
    ['Anhad Arts Foundation', 'nonprofit', 'Non-Profit Initiative', 'A community-focused non-profit platform offering inclusive arts education, grassroots workshops, and cultural campaigns.', 'https://hrntechsolutions.com/Anhadartsfoundation/', 'assets/images/anhad.webp', '100+ Art Workshops', 'Organized for marginalized youth', ['React', 'TailwindCSS', 'CSS3']],
    ['APSPACE Infratech', 'ecommerce', 'E-Commerce System', 'A commercial real estate discovery and booking engine for premium workspaces, coworking offices, and conference rooms in Noida.', 'https://hrntechsolutions.com/APSPACE/', 'assets/images/apspace.webp', 'Premium Locations', 'Fully furnished business centers', ['Vue.js', 'Express.js', 'MongoDB']],
    ['AZUL — Curated Blue Pigments', 'webapp', 'Website Development', 'A high-performance interactive portfolio website celebrating deep-blue chemical pigments and eco-conscious manufacturing.', 'https://hrntechsolutions.com/azul/', 'assets/images/azul.webp', 'Curated Palette', 'Eco-certified pigment systems', ['Next.js', 'TailwindCSS', 'GSAP']],
    ['Carelix Home Healthcare Portal', 'webapp', 'Web Application', 'A patient booking portal connecting users with verified home healthcare nurses, physiotherapists, and medical packages.', 'https://hrntechsolutions.com/carelix_health_care/aboutUs.php', 'assets/images/carelix.webp', '24/7 Specialist Care', 'Dedicated patient recovery', ['PHP', 'TailwindCSS', 'JavaScript']],
    ['CoreOn Fit — Custom Footwear Portal', 'webapp', 'Website Development', 'A comfort footwear and performance sportswear web portal detailing custom mesh sole designs and biomechanical support systems.', 'https://hrntechsolutions.com/coreonfit/about.php', 'assets/images/core.webp', 'Biomechanical Sole', 'Custom athletic footwear mesh', ['Next.js', 'TailwindCSS', 'GSAP']],
    ['Dental Boss — Smart Dental Care', 'webapp', 'Web Application', 'Expert dental care at your doorstep, offering 3D dental checkup scans, at-home dental care, video consultations, and clinic bookings.', 'https://hrntechsolutions.com/dentalboss/', 'assets/images/dentalboss.webp', '3D Dental Scans', 'At-home checkups & consultations', ['React', 'Node.js', 'TailwindCSS']],
    ['Dr. Shivam Khare — Gastroenterologist', 'webapp', 'Web Application', 'A medical specialist profile portal for Dr. Shivam Khare featuring clinical achievements and consultation options.', 'https://hrntechsolutions.com/Dr_Khare/about.php', 'assets/images/shivam.webp', '5K+ Patients Treated', 'Consultant Gastroenterologist', ['PHP', 'TailwindCSS', 'JavaScript']],
    ['Firetroops — Fire Equipment & Safety', 'ecommerce', 'E-Commerce System', 'A fire safety equipment directory and catalog showcasing commercial alarm systems, fire hydrants, and emergency response services.', 'https://hrntechsolutions.com/Fire_Equipment/index.php', 'assets/images/firetroops.webp', '24/7 Fire Safety', 'Emergency equipment provisioning', ['HTML5', 'CSS3', 'PHP']],
    ['Golden Nest 369 — Real Estate Consultants', 'webapp', 'Web Application', 'A premium real estate consultancy website connecting home buyers and commercial investors with verified, luxury developers.', 'https://hrntechsolutions.com/Golden_Nest_369_1/about.php', 'assets/images/goldnest.webp', 'Luxury Living', 'Verified premium properties', ['Vue.js', 'TailwindCSS', 'JavaScript']],
    ['Synergy Laundry — Doorstep Laundry Service', 'ecommerce', 'E-Commerce System', 'A premium doorstep laundry service portal featuring interactive pickup scheduling, price catalogs, and service descriptions.', 'https://hrntechsolutions.com/Laundry/', 'assets/images/freshfold.webp', 'Doorstep Care', 'Fast & reliable garment cleaning', ['HTML5', 'CSS3', 'JavaScript']],
    ['OmSai Security — Manpower Solutions', 'webapp', 'Web Application', 'A workforce recruitment and deployment portal providing trained corporate guards and hospitality staff.', 'https://hrntechsolutions.com/laxminarayan/#about', 'assets/images/omsai.webp', '15+ Years Service', 'Trained staff deployed', ['HTML5', 'CSS3', 'Bootstrap']],
    ['Legal Eyes — Premium Law Firm', 'webapp', 'Web Application', 'A modern corporate litigation portal offering legal advocacy, consultation requests, and case evaluation forms.', 'https://hrntechsolutions.com/legaleyes/index', 'assets/images/legaleyes.webp', 'Elite Advocacy', 'Advocating with vision and integrity', ['PHP', 'TailwindCSS', 'JavaScript']],
    ['Nilam Bharti Agro Solutions', 'ecommerce', 'E-Commerce System', 'A fertilizer product directory and agricultural consulting portal promoting eco-friendly farming solutions.', 'https://hrntechsolutions.com/nilam/about.php', 'assets/images/nilam.webp', 'Eco-Friendly Farming', 'Premium sustainable fertilizers', ['PHP', 'Bootstrap', 'MySQL']],
    ['Sukh Niwas PG — Co-Living Spaces', 'webapp', 'Web Application', 'A premium co-living property discovery and room booking system located in Faridabad.', 'https://hrntechsolutions.com/pg_landingpage/', 'assets/images/sukh.webp', 'CCTV & 24/7 Security', 'Fully furnished co-living', ['Vue.js', 'TailwindCSS', 'JavaScript']],
];
$insProj = $pdo->prepare('INSERT INTO projects (title, category, category_label, description, link, image, metric, metric_desc, stack_json, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)');
foreach ($projects as $i => $p) {
    $insProj->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], json_encode($p[8]), $i]);
}
echo count($projects) . " projects.\n";

$blog = [
    ['Architecting the Future: Why Custom Code Beats WordPress in 2026', 'engineering', 'Unpack the speed, page weight, security, and long-term maintainability advantages of building bespoke web engines over heavy database-driven drag-and-drop templates.', 'assets/images/software_engineering_mockup.webp', '5 Min Read', '2026-06-28'],
    ['Cloud Hosting Done Right: Scaling Apps Without Downtime', 'hosting', 'How to choose cloud providers, configure SSL and CDN, automate backups, and keep production workloads available under traffic spikes.', 'assets/images/project-admin.webp', '7 Min Read', '2026-06-24'],
    ['Visual Engineering: Aesthetic Psychology in Luxury Web Apps', 'uiux', 'Explore the mathematical principles and conversion psychology behind micro-animations, glassmorphism UI layouts, and interactive scroll triggers.', 'assets/images/web_design_showcase.webp', '4 Min Read', '2026-06-19'],
    ['Sub-Second Speed: Coding for 100/100 Google PageSpeed Marks', 'engineering', 'A granular checklist of asset compression, modern WebP/WebM packaging, critical path CSS integration, and DOM load execution orders.', 'assets/images/attend.webp', '6 Min Read', '2026-06-12'],
    ['Server Management Playbook: Patches, Firewalls & Monitoring', 'hosting', 'A practical guide to Linux hardening, uptime monitoring, backup recovery drills, and keeping production servers healthy year-round.', 'assets/images/software_engineering_mockup.webp', '8 Min Read', '2026-06-08'],
    ['Interaction Science: Mastering ScrollTimelines & GSAP Triggers', 'uiux', 'A design manual for building responsive, multi-stage scroll decks that anchor visitor attention without fracturing mobile accessibility.', 'assets/images/project-impact.webp', '5 Min Read', '2026-05-31'],
];
$insBlog = $pdo->prepare('INSERT INTO blog_posts (title, category, excerpt, image, read_time, published_at, sort_order) VALUES (?,?,?,?,?,?,?)');
foreach ($blog as $i => $b) {
    $insBlog->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $i]);
}
echo count($blog) . " blog posts.\n";

$testimonials = [
    ['Aarav Mehta', 'Founder & CEO, TechGro India', 'Digital Creatorss built our custom B2B web application. The development lifecycle was fast, clean, and highly collaborative. Their director Neekita and team architected a solution that scales perfectly as we expand.', 5],
    ['Priya Sharma', 'CTO, CloudNest Systems', 'Their cloud hosting and server management setup transformed our uptime. Migrations were smooth, monitoring is proactive, and support responds before issues escalate.', 5],
    ['Vikram Aditya', 'Co-Founder, EduVibe', 'We reached out to Digital Creatorss for web development, and they delivered an optimized, fast-loading educational portal. Our user engagement has doubled, thanks to the intuitive dashboard design.', 5],
];
$insT = $pdo->prepare('INSERT INTO testimonials (name, role, review, rating, sort_order) VALUES (?,?,?,?,?)');
foreach ($testimonials as $i => $t) {
    $insT->execute([$t[0], $t[1], $t[2], $t[3], $i]);
}
echo count($testimonials) . " testimonials.\n";

$demoProducts = [
    ['Gym Management Software', 'gym', 'Fitness', 'Memberships, attendance, trainers, billing, and class schedules — ready to demo and customize for your gym brand.', 'assets/images/products/gym.webp', 'https://example.com/gym-demo', 'https://example.com/gym-user-guide', 'live', 1, ['Member registration & membership plans', 'Trainer & class scheduling', 'Attendance & access tracking', 'Billing, invoices & renewals', 'Admin dashboard & reports']],
    ['Multivendor E-commerce', 'ecommerce', 'E-commerce', 'Marketplace with vendor stores, commissions, product catalogs, and order workflows — brandable for your business.', 'assets/images/products/multivendor.webp', '', '', 'coming_soon', 0, []],
    ['News Portal', 'news', 'Publishing', 'Editorial CMS, categories, breaking news layouts, and ad-ready pages for digital newsrooms.', 'assets/images/products/news.webp', '', '', 'coming_soon', 0, []],
    ['CRM System', 'crm', 'CRM', 'Leads, pipelines, follow-ups, and team activity tracking for sales-driven organizations.', 'assets/images/products/crm.webp', '', '', 'coming_soon', 0, []],
    ['School Management', 'school', 'Education', 'Students, fees, attendance, staff, and academic operations in one school admin platform.', 'assets/images/products/school.webp', '', '', 'coming_soon', 0, []],
    ['Tuition Management', 'tuition', 'Education', 'Batches, fees, student progress, and coaching-center operations built for tuition institutes.', 'assets/images/products/tuition.webp', '', '', 'coming_soon', 0, []],
    ['Single Vendor E-commerce', 'ecommerce', 'E-commerce', 'Storefront, cart, checkout, inventory, and order management for a single brand shop.', 'assets/images/products/ecommerce.webp', '', '', 'coming_soon', 0, []],
];
$insDemo = $pdo->prepare('INSERT INTO demo_products (title, category, category_label, summary, image, demo_url, guide_url, status, is_featured, features_json, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,1)');
foreach ($demoProducts as $i => $p) {
    $insDemo->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], json_encode($p[9]), $i]);
}
echo count($demoProducts) . " demo products.\n";

$uploadDir = $config['app']['upload_dir'];
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
file_put_contents($uploadDir . '/.gitkeep', '');

echo "Seed complete.\n";
