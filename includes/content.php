<?php
/**
 * Public content loaders (active records only).
 */
require_once __DIR__ . '/db.php';

function fallback_services(): array
{
    return [
        ['id' => 1, 'icon' => 'code-2', 'title' => 'Custom Software', 'description' => 'Tailor-made software that automates workflows and scales with your business.', 'metric' => 'Enterprise Grade', 'image' => 'assets/images/software_engineering_mockup.webp', 'tag' => 'Architecture Strategy', 'stack_json' => '["Laravel","Node.js","Python","AWS Cloud"]'],
        ['id' => 2, 'icon' => 'globe', 'title' => 'Website Development', 'description' => 'Fast, secure, mobile-friendly websites designed for retention and conversion.', 'metric' => 'High Speed CSS/JS', 'image' => 'assets/images/web_design_showcase.webp', 'tag' => 'Frontend Performance', 'stack_json' => '["Next.js","TailwindCSS","GSAP","Vite"]'],
        ['id' => 3, 'icon' => 'smartphone', 'title' => 'App Development', 'description' => 'Native and cross-platform iOS and Android apps with secure APIs and polished interfaces.', 'metric' => 'iOS & Android', 'image' => 'assets/images/software_engineering_mockup.webp', 'tag' => 'Mobile Engineering', 'stack_json' => '["React Native","Flutter","iOS","Android"]'],
        ['id' => 4, 'icon' => 'layers', 'title' => 'Web Applications', 'description' => 'Secure SaaS dashboards, customer portals, databases, and intuitive business tools.', 'metric' => 'Cloud Native SaaS', 'image' => 'assets/images/project-admin.webp', 'tag' => 'SaaS Engineering', 'stack_json' => '["React.js","PostgreSQL","Docker","GraphQL"]'],
        ['id' => 5, 'icon' => 'cloud', 'title' => 'Cloud Hosting', 'description' => 'Scalable cloud infrastructure with SSL, backups, CDN delivery, and high availability.', 'metric' => '99.9% Uptime', 'image' => 'assets/images/project-impact.webp', 'tag' => 'Cloud Platforms', 'stack_json' => '["AWS","DigitalOcean","cPanel","SSL & CDN"]'],
        ['id' => 6, 'icon' => 'server', 'title' => 'Server Management', 'description' => 'Monitoring, security patches, firewall hardening, backups, and performance tuning.', 'metric' => '24/7 Ops', 'image' => 'assets/images/project-tyre.webp', 'tag' => 'SysAdmin Care', 'stack_json' => '["Linux","Nginx","MySQL","Monitoring"]'],
        ['id' => 7, 'icon' => 'boxes', 'title' => 'DevOps & Infrastructure', 'description' => 'CI/CD pipelines, containers, and repeatable production-ready deployment workflows.', 'metric' => 'CI/CD Ready', 'image' => 'assets/images/project-admin.webp', 'tag' => 'Automation', 'stack_json' => '["Docker","GitHub Actions","Terraform","Kubernetes"]'],
        ['id' => 8, 'icon' => 'shield-check', 'title' => 'Maintenance & Support', 'description' => 'Ongoing updates, uptime checks, database health, recovery, and priority support.', 'metric' => 'Always On', 'image' => 'assets/images/software_engineering_mockup.webp', 'tag' => 'Lifecycle Care', 'stack_json' => '["Updates","Backups","Security","SLA Support"]'],
    ];
}

function fallback_team_members(): array
{
    return [
        ['id' => 1, 'name' => 'Neekita Kumari', 'role' => 'Director', 'bio' => 'Leads strategy, delivery, client success, and day-to-day operations at Digital Creatorss.', 'image' => 'assets/images/Nikita_Maam.webp', 'specialty' => null, 'member_type' => 'director', 'email' => 'director@digitalcreatorss.com', 'phone' => '+917903152429', 'education' => 'MBA (IT)', 'icon' => 'briefcase'],
        ['id' => 2, 'name' => 'Dharmendra Kumar', 'role' => 'Senior Frontend Developer', 'bio' => 'Builds interactive web interfaces and consistent responsive design systems.', 'image' => 'assets/images/DK.webp', 'specialty' => 'TailwindCSS / Next.js / GSAP', 'member_type' => 'core', 'email' => null, 'phone' => null, 'education' => null, 'icon' => 'layout'],
        ['id' => 3, 'name' => 'Mohit Chauhan', 'role' => 'Cloud & Hosting Engineer', 'bio' => 'Manages cloud deployment, SSL, DNS, and scalable hosting environments.', 'image' => 'assets/images/mohit.webp', 'specialty' => 'AWS / Linux / CDN', 'member_type' => 'core', 'email' => null, 'phone' => null, 'education' => null, 'icon' => 'cloud'],
    ];
}

function fallback_projects(): array
{
    return [
        ['id' => 1, 'title' => 'DFOHO — Highway Food App', 'category' => 'webapp', 'category_label' => 'Web Development', 'description' => 'A highway food discovery and ordering platform with maps, pre-orders, table booking, and rewards.', 'link' => 'https://www.dfoho.com', 'image' => 'assets/images/Dfoho.webp', 'metric' => 'Highway-First UX', 'metric_desc' => 'Food ordering on the go', 'stack_json' => '["React Native","Node.js","MongoDB"]'],
        ['id' => 2, 'title' => 'Social Impact Hub', 'category' => 'nonprofit', 'category_label' => 'Non-Profit Initiative', 'description' => 'A platform connecting volunteers with community initiatives using accessible web standards.', 'link' => 'https://www.sdftrust.org', 'image' => 'assets/images/sdf.webp', 'metric' => '12K+ Volunteers', 'metric_desc' => 'Connected globally', 'stack_json' => '["React","TailwindCSS","Node.js"]'],
        ['id' => 3, 'title' => 'Tyre Marketplace', 'category' => 'ecommerce', 'category_label' => 'E-Commerce System', 'description' => 'A high-performance marketplace with administration, shopping, and inventory workflows.', 'link' => 'https://www.autodeal4u.in', 'image' => 'assets/images/tyre1.webp', 'metric' => 'Wholesale Ready', 'metric_desc' => 'Inventory and order management', 'stack_json' => '["Laravel","TailwindCSS","PostgreSQL"]'],
        ['id' => 4, 'title' => 'Attendance Portal', 'category' => 'webapp', 'category_label' => 'Web Application', 'description' => 'A cloud portal for attendance tracking, dashboards, and operational shift logging.', 'link' => 'https://www.hrntechsolutions.com/attendance-system', 'image' => 'assets/images/attend.webp', 'metric' => '40K+ Daily Logins', 'metric_desc' => 'Active operations tracking', 'stack_json' => '["Vue.js","Express.js","MongoDB"]'],
        ['id' => 5, 'title' => 'AIRA BRCS Legal Advisors', 'category' => 'webapp', 'category_label' => 'Web Application', 'description' => 'A corporate law portal with practitioner profiles and consultation bookings.', 'link' => 'https://hrntechsolutions.com/AIRA/', 'image' => 'assets/images/aira.webp', 'metric' => '1500+ Matters', 'metric_desc' => 'Successfully litigated', 'stack_json' => '["PHP","TailwindCSS","JavaScript"]'],
    ];
}

function fallback_blog_posts(): array
{
    return [
        ['id' => 1, 'title' => 'Architecting the Future: Why Custom Code Wins', 'category' => 'engineering', 'excerpt' => 'Speed, security, and maintainability advantages of bespoke web systems.', 'image' => 'assets/images/software_engineering_mockup.webp', 'read_time' => '5 Min Read', 'published_at' => '2026-06-28'],
        ['id' => 2, 'title' => 'Cloud Hosting Done Right', 'category' => 'hosting', 'excerpt' => 'How to configure SSL, CDN, backups, and production availability.', 'image' => 'assets/images/project-admin.webp', 'read_time' => '7 Min Read', 'published_at' => '2026-06-24'],
        ['id' => 3, 'title' => 'Visual Engineering for Modern Web Apps', 'category' => 'uiux', 'excerpt' => 'Practical principles behind polished interfaces and micro-interactions.', 'image' => 'assets/images/web_design_showcase.webp', 'read_time' => '4 Min Read', 'published_at' => '2026-06-19'],
    ];
}

function fallback_testimonials(): array
{
    return [
        ['id' => 1, 'name' => 'Aarav Mehta', 'role' => 'Founder & CEO, TechGro India', 'review' => 'Digital Creatorss built our custom B2B web application with a fast, clean, and collaborative delivery process.', 'rating' => 5],
        ['id' => 2, 'name' => 'Priya Sharma', 'role' => 'CTO, CloudNest Systems', 'review' => 'Their cloud hosting and server management transformed our uptime. Migration was smooth and support is proactive.', 'rating' => 5],
        ['id' => 3, 'name' => 'Vikram Aditya', 'role' => 'Co-Founder, EduVibe', 'review' => 'They delivered an optimized educational portal with an intuitive dashboard that improved user engagement.', 'rating' => 5],
    ];
}

function get_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $s = get_settings();
    return isset($s[$key]) && $s[$key] !== '' ? $s[$key] : $default;
}

function get_services(bool $activeOnly = true): array
{
    try {
        $sql = 'SELECT * FROM services';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $rows = db()->query($sql)->fetchAll();
        if (!$rows) {
            $rows = fallback_services();
        }
    } catch (Throwable $e) {
        $rows = fallback_services();
    }

    foreach ($rows as &$row) {
        $row['stack'] = !empty($row['stack_json']) ? json_decode($row['stack_json'], true) : [];
        $row['desc'] = $row['description'];
        $row['spotlight'] = [
            'tag' => $row['tag'] ?? '',
            'stack' => $row['stack'] ?: [],
        ];
    }
    return $rows;
}

function get_team_members(?string $type = null, bool $activeOnly = true): array
{
    try {
        $sql = 'SELECT * FROM team_members WHERE 1=1';
        $params = [];
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        if ($type) {
            $sql .= ' AND member_type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if ($rows) {
            return $rows;
        }
    } catch (Throwable $e) {
    }

    $rows = fallback_team_members();
    return $type
        ? array_values(array_filter($rows, static fn(array $row): bool => $row['member_type'] === $type))
        : $rows;
}

function get_projects(bool $activeOnly = true): array
{
    try {
        $sql = 'SELECT * FROM projects';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $rows = db()->query($sql)->fetchAll();
        if (!$rows) {
            $rows = fallback_projects();
        }
    } catch (Throwable $e) {
        $rows = fallback_projects();
    }

    foreach ($rows as &$row) {
        $row['stack'] = !empty($row['stack_json']) ? json_decode($row['stack_json'], true) : [];
    }
    return $rows;
}

function get_blog_posts(bool $activeOnly = true): array
{
    try {
        $sql = 'SELECT * FROM blog_posts';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY published_at DESC, sort_order ASC, id DESC';
        $rows = db()->query($sql)->fetchAll();
        return $rows ?: fallback_blog_posts();
    } catch (Throwable $e) {
        return fallback_blog_posts();
    }
}

function get_testimonials(bool $activeOnly = true): array
{
    try {
        $sql = 'SELECT * FROM testimonials';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $rows = db()->query($sql)->fetchAll();
        return $rows ?: fallback_testimonials();
    } catch (Throwable $e) {
        return fallback_testimonials();
    }
}

function phones_display(): string
{
    $p1 = setting('phone_1', '+91-8851613806');
    $p2 = setting('phone_2', '7903152429');
    return trim($p1 . ($p2 ? ', ' . $p2 : ''));
}

function phone_tel(): string
{
    $raw = preg_replace('/[^\d+]/', '', setting('phone_1', '+918851613806'));
    if ($raw && $raw[0] !== '+') {
        $raw = '+' . ltrim($raw, '+');
    }
    return $raw ?: '+918851613806';
}

function whatsapp_number(): string
{
    return preg_replace('/\D+/', '', setting('whatsapp', '918851613806')) ?: '918851613806';
}
