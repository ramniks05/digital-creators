<?php
/**
 * Public content loaders (active records only).
 */
require_once __DIR__ . '/db.php';

function fallback_services(): array
{
    return [
        ['id' => 1, 'icon' => 'code-2', 'title' => 'Custom Software', 'description' => 'Tailor-made software that automates workflows and scales with your business.', 'metric' => 'Enterprise Grade', 'image' => 'assets/service/custom_software.png', 'tag' => 'Architecture Strategy', 'stack_json' => '["Laravel","Node.js","Python","AWS Cloud"]'],
        ['id' => 2, 'icon' => 'globe', 'title' => 'Website Development', 'description' => 'Fast, secure, mobile-friendly websites designed for retention and conversion.', 'metric' => 'High Speed CSS/JS', 'image' => 'assets/service/webdevelopment.png', 'tag' => 'Frontend Performance', 'stack_json' => '["Next.js","TailwindCSS","GSAP","Vite"]'],
        ['id' => 3, 'icon' => 'smartphone', 'title' => 'App Development', 'description' => 'Native and cross-platform iOS and Android apps with secure APIs and polished interfaces.', 'metric' => 'iOS & Android', 'image' => 'assets/service/AppDevelopment.png', 'tag' => 'Mobile Engineering', 'stack_json' => '["React Native","Flutter","iOS","Android"]'],
        ['id' => 4, 'icon' => 'layers', 'title' => 'Web Applications', 'description' => 'Secure SaaS dashboards, customer portals, databases, and intuitive business tools.', 'metric' => 'Cloud Native SaaS', 'image' => 'assets/service/web_applications.png', 'tag' => 'SaaS Engineering', 'stack_json' => '["React.js","PostgreSQL","Docker","GraphQL"]'],
        ['id' => 5, 'icon' => 'cloud', 'title' => 'Cloud Hosting', 'description' => 'Scalable cloud infrastructure with SSL, backups, CDN delivery, and high availability.', 'metric' => '99.9% Uptime', 'image' => 'assets/service/cloud_hosting.png', 'tag' => 'Cloud Platforms', 'stack_json' => '["AWS","DigitalOcean","cPanel","SSL & CDN"]'],
        ['id' => 6, 'icon' => 'server', 'title' => 'Server Management', 'description' => 'Monitoring, security patches, firewall hardening, backups, and performance tuning.', 'metric' => '24/7 Ops', 'image' => 'assets/service/server_management.png', 'tag' => 'SysAdmin Care', 'stack_json' => '["Linux","Nginx","MySQL","Monitoring"]'],
        ['id' => 7, 'icon' => 'boxes', 'title' => 'DevOps & Infrastructure', 'description' => 'CI/CD pipelines, containers, and repeatable production-ready deployment workflows.', 'metric' => 'CI/CD Ready', 'image' => 'assets/service/devops_infrastructure.png', 'tag' => 'Automation', 'stack_json' => '["Docker","GitHub Actions","Terraform","Kubernetes"]'],
        ['id' => 8, 'icon' => 'shield-check', 'title' => 'Maintenance & Support', 'description' => 'Ongoing updates, uptime checks, database health, recovery, and priority support.', 'metric' => 'Always On', 'image' => 'assets/service/maintenance_support.png', 'tag' => 'Lifecycle Care', 'stack_json' => '["Updates","Backups","Security","SLA Support"]'],
    ];
}

function fallback_team_members(): array
{
    // Placeholder names + avatars (update later). No employee photos.
    return [
        ['id' => 1, 'name' => 'Neekita Kumari', 'role' => 'Director', 'bio' => 'Leads strategy, delivery, client success, and day-to-day operations at Digital Creatorss.', 'image' => 'assets/images/director.jpg', 'specialty' => null, 'member_type' => 'director', 'email' => 'info@digitalcreatorss.com', 'phone' => '+917903152429', 'education' => 'MBA (IT)', 'icon' => 'briefcase'],
        ['id' => 2, 'name' => 'Rahul Mehta', 'role' => 'Senior Frontend Developer', 'bio' => 'Builds interactive web interfaces and consistent responsive design systems.', 'image' => 'assets/images/avatars/avatar-rahul.svg', 'specialty' => 'TailwindCSS / Next.js / GSAP', 'member_type' => 'core', 'email' => null, 'phone' => null, 'education' => null, 'icon' => 'layout'],
        ['id' => 3, 'name' => 'Aman Gupta', 'role' => 'Cloud & Hosting Engineer', 'bio' => 'Manages cloud deployment, SSL, DNS, and scalable hosting environments.', 'image' => 'assets/images/avatars/avatar-aman.svg', 'specialty' => 'AWS / Linux / CDN', 'member_type' => 'core', 'email' => null, 'phone' => null, 'education' => null, 'icon' => 'cloud'],
        ['id' => 4, 'name' => 'Priya Nair', 'role' => 'Server Administrator', 'bio' => 'Handles server hardening, monitoring, backups, and performance tuning across client infrastructure.', 'image' => 'assets/images/avatars/avatar-priya.svg', 'specialty' => 'Linux / Nginx / Security', 'member_type' => 'core', 'email' => null, 'phone' => null, 'education' => null, 'icon' => 'server'],
        ['id' => 5, 'name' => 'Karan Joshi', 'role' => 'DevOps Engineer', 'bio' => 'Builds deployment pipelines, container workflows, and observability so releases ship safely and repeatedly.', 'image' => 'assets/images/avatars/avatar-karan.svg', 'specialty' => 'CI/CD / Docker / Monitoring', 'member_type' => 'core', 'email' => null, 'phone' => null, 'education' => null, 'icon' => 'boxes'],
    ];
}

/** Resolve a team/public image to an existing local file (handles missing DB paths / wrong extensions). */
function resolve_asset_image(?string $path, array $fallbackCandidates = []): string
{
    $root = dirname(__DIR__);
    $candidates = [];

    if ($path) {
        $rel = ltrim(str_replace('\\', '/', (string) $path), '/');
        $candidates[] = $rel;
        $base = preg_replace('/\.(webp|png|jpe?g|svg)$/i', '', $rel);
        foreach (['.jpg', '.jpeg', '.png', '.webp', '.svg'] as $ext) {
            $candidates[] = $base . $ext;
        }
    }

    foreach ($fallbackCandidates as $candidate) {
        $candidates[] = ltrim(str_replace('\\', '/', (string) $candidate), '/');
    }

    $seen = [];
    foreach ($candidates as $candidate) {
        if ($candidate === '' || isset($seen[$candidate])) {
            continue;
        }
        $seen[$candidate] = true;
        $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        if (is_file($absolute)) {
            return $candidate;
        }
    }

    return $path ? ltrim(str_replace('\\', '/', (string) $path), '/') : '';
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

function service_brand_image_path(string $title): ?string
{
    $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $title)));
    $map = [
        'custom software' => [
            'assets/service/custom_software.png',
            'assets/service/custom_software.webp',
        ],
        'website development' => [
            'assets/service/webdevelopment.png',
            'assets/service/website_development.png',
        ],
        'app development' => [
            'assets/service/AppDevelopment.png',
            'assets/service/app_development.png',
            'assets/service/appdevelopment.png',
        ],
        'web applications' => ['assets/service/web_applications.png'],
        'cloud hosting' => ['assets/service/cloud_hosting.png'],
        'server management' => ['assets/service/server_management.png'],
        'devops & infrastructure' => ['assets/service/devops_infrastructure.png'],
        'maintenance & support' => ['assets/service/maintenance_support.png'],
    ];

    $candidates = $map[$normalized] ?? null;
    if (!$candidates) {
        return null;
    }

    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
    foreach ($candidates as $path) {
        $absolute = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($absolute)) {
            return $path;
        }
    }

    return null;
}

/** Keep homepage What We Do order: Custom Software → Website Development → App Development → rest */
function prioritize_homepage_services(array $rows): array
{
    $preferred = [
        'custom software',
        'website development',
        'app development',
    ];

    $picked = [];
    $rest = [];

    foreach ($preferred as $want) {
        foreach ($rows as $i => $row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            if ($title === $want) {
                $picked[] = $row;
                unset($rows[$i]);
                break;
            }
        }
    }

    foreach ($rows as $row) {
        $rest[] = $row;
    }

    return array_values(array_merge($picked, $rest));
}

function get_services(bool $activeOnly = true): array
{
    // Local directory first (assets/service + fallback_services). Flip to true later for Admin/DB.
    $useDatabase = filter_var(getenv('SERVICES_FROM_DB') ?: '0', FILTER_VALIDATE_BOOLEAN);

    $rows = fallback_services();

    if ($useDatabase) {
        try {
            $sql = 'SELECT * FROM services';
            if ($activeOnly) {
                $sql .= ' WHERE is_active = 1';
            }
            $sql .= ' ORDER BY sort_order ASC, id ASC';
            $dbRows = db()->query($sql)->fetchAll();
            if ($dbRows) {
                $rows = $dbRows;
            }
        } catch (Throwable $e) {
            // keep local fallback
        }
    }

    $rows = prioritize_homepage_services($rows);

    foreach ($rows as &$row) {
        $row['stack'] = !empty($row['stack_json']) ? json_decode($row['stack_json'], true) : [];
        $row['desc'] = $row['description'] ?? ($row['desc'] ?? '');
        $brandImage = service_brand_image_path((string) ($row['title'] ?? ''));
        if ($brandImage) {
            $row['image'] = $brandImage;
        }
        $row['spotlight'] = [
            'tag' => $row['tag'] ?? '',
            'stack' => $row['stack'] ?: [],
        ];
    }
    unset($row);
    return $rows;
}

function get_team_members(?string $type = null, bool $activeOnly = true): array
{
    // Local directory first. Flip later for Admin/DB with TEAM_FROM_DB=1.
    $useDatabase = filter_var(getenv('TEAM_FROM_DB') ?: '0', FILTER_VALIDATE_BOOLEAN);
    $rows = [];

    if ($useDatabase) {
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
        } catch (Throwable $e) {
            $rows = [];
        }
    }

    if (!$rows) {
        $rows = fallback_team_members();
        if ($type) {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => $row['member_type'] === $type));
        }
    }

    foreach ($rows as &$row) {
        $name = strtolower((string) ($row['name'] ?? ''));
        $role = strtolower((string) ($row['role'] ?? ''));
        $isDirector = (($row['member_type'] ?? '') === 'director')
            || str_contains($role, 'director')
            || str_contains($name, 'neekita');

        // Never expose employee photos for core team — force avatar placeholders.
        if (($row['member_type'] ?? '') === 'core') {
            $avatarMap = [
                'frontend' => 'assets/images/avatars/avatar-rahul.svg',
                'cloud' => 'assets/images/avatars/avatar-aman.svg',
                'server' => 'assets/images/avatars/avatar-priya.svg',
                'devops' => 'assets/images/avatars/avatar-karan.svg',
            ];
            $key = 'frontend';
            if (str_contains($role, 'cloud') || str_contains($role, 'hosting')) {
                $key = 'cloud';
            } elseif (str_contains($role, 'server') || str_contains($role, 'admin')) {
                $key = 'server';
            } elseif (str_contains($role, 'devops')) {
                $key = 'devops';
            }
            $row['image'] = resolve_asset_image($avatarMap[$key], array_values($avatarMap));
            continue;
        }

        $fallbacks = [];
        if ($isDirector) {
            $fallbacks = ['assets/images/director.jpg', 'assets/images/director.png', 'assets/images/Nikita_Maam.webp'];
        }

        $row['image'] = resolve_asset_image($row['image'] ?? '', $fallbacks);
    }
    unset($row);

    return $rows;
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
