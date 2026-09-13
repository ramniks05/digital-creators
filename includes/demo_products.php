<?php
/**
 * Demo / ready software products.
 * Loads from MySQL when available; falls back to hardcoded catalog.
 */
require_once __DIR__ . '/db.php';

function fallback_demo_products(): array
{
    return [
        [
            'id' => 'gym',
            'title' => 'Gym Management Software',
            'category' => 'gym',
            'category_label' => 'Fitness',
            'summary' => 'Memberships, attendance, trainers, billing, and class schedules — ready to demo and customize for your gym brand.',
            'image' => 'assets/images/products/gym.png',
            'demo_url' => 'https://example.com/gym-demo',
            'guide_url' => 'https://example.com/gym-user-guide',
            'status' => 'live',
            'featured' => true,
            'features' => [
                'Member registration & membership plans',
                'Trainer & class scheduling',
                'Attendance & access tracking',
                'Billing, invoices & renewals',
                'Admin dashboard & reports',
            ],
        ],
        [
            'id' => 'multivendor',
            'title' => 'Multivendor E-commerce',
            'category' => 'ecommerce',
            'category_label' => 'E-commerce',
            'summary' => 'Marketplace with vendor stores, commissions, product catalogs, and order workflows — brandable for your business.',
            'image' => 'assets/images/products/multivendor.png',
            'demo_url' => '',
            'guide_url' => '',
            'status' => 'coming_soon',
            'featured' => false,
            'features' => [],
        ],
        [
            'id' => 'news',
            'title' => 'News Portal',
            'category' => 'news',
            'category_label' => 'Publishing',
            'summary' => 'Editorial CMS, categories, breaking news layouts, and ad-ready pages for digital newsrooms.',
            'image' => 'assets/images/products/news.png',
            'demo_url' => '',
            'guide_url' => '',
            'status' => 'coming_soon',
            'featured' => false,
            'features' => [],
        ],
        [
            'id' => 'crm',
            'title' => 'CRM System',
            'category' => 'crm',
            'category_label' => 'CRM',
            'summary' => 'Leads, pipelines, follow-ups, and team activity tracking for sales-driven organizations.',
            'image' => 'assets/images/products/crm.png',
            'demo_url' => '',
            'guide_url' => '',
            'status' => 'coming_soon',
            'featured' => false,
            'features' => [],
        ],
        [
            'id' => 'school',
            'title' => 'School Management',
            'category' => 'school',
            'category_label' => 'Education',
            'summary' => 'Students, fees, attendance, staff, and academic operations in one school admin platform.',
            'image' => 'assets/images/products/school.png',
            'demo_url' => '',
            'guide_url' => '',
            'status' => 'coming_soon',
            'featured' => false,
            'features' => [],
        ],
        [
            'id' => 'tuition',
            'title' => 'Tuition Management',
            'category' => 'tuition',
            'category_label' => 'Education',
            'summary' => 'Batches, fees, student progress, and coaching-center operations built for tuition institutes.',
            'image' => 'assets/images/products/tuition.png',
            'demo_url' => '',
            'guide_url' => '',
            'status' => 'coming_soon',
            'featured' => false,
            'features' => [],
        ],
        [
            'id' => 'ecommerce-single',
            'title' => 'Single Vendor E-commerce',
            'category' => 'ecommerce',
            'category_label' => 'E-commerce',
            'summary' => 'Storefront, cart, checkout, inventory, and order management for a single brand shop.',
            'image' => 'assets/images/products/ecommerce.png',
            'demo_url' => '',
            'guide_url' => '',
            'status' => 'coming_soon',
            'featured' => false,
            'features' => [],
        ],
    ];
}

function normalize_demo_product(array $row): array
{
    $features = [];
    if (!empty($row['features_json'])) {
        $decoded = json_decode((string) $row['features_json'], true);
        $features = is_array($decoded) ? $decoded : [];
    } elseif (!empty($row['features']) && is_array($row['features'])) {
        $features = $row['features'];
    }

    $image = (string) ($row['image'] ?? '');

    return [
        'id' => $row['id'] ?? ($row['category'] ?? 'product'),
        'title' => $row['title'] ?? '',
        'category' => $row['category'] ?? 'other',
        'category_label' => $row['category_label'] ?? '',
        'summary' => $row['summary'] ?? '',
        'image' => $image,
        'image_src' => demo_product_image_src($image),
        'demo_url' => $row['demo_url'] ?? '',
        'guide_url' => $row['guide_url'] ?? '',
        'status' => $row['status'] ?? 'coming_soon',
        'featured' => !empty($row['is_featured']) || !empty($row['featured']),
        'features' => $features,
        'sort_order' => (int) ($row['sort_order'] ?? 0),
        'is_active' => isset($row['is_active']) ? (int) $row['is_active'] : 1,
    ];
}

/** Cache-bust product images so replaced files show after hard refresh / CDN. */
function demo_product_image_src(string $relativePath): string
{
    $relativePath = trim($relativePath);
    if ($relativePath === '') {
        return '';
    }
    $abs = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    $ver = is_file($abs) ? (string) filemtime($abs) : (string) time();
    return $relativePath . (str_contains($relativePath, '?') ? '&' : '?') . 'v=' . $ver;
}

function get_demo_products(bool $activeOnly = true): array
{
    // Prefer Admin/DB so live CMS edits show on the site. Set PRODUCTS_FROM_DB=0 for local hardcoded catalog.
    $useDatabase = filter_var(getenv('PRODUCTS_FROM_DB') ?: '1', FILTER_VALIDATE_BOOLEAN);

    if ($useDatabase) {
        try {
            $sql = 'SELECT * FROM demo_products';
            if ($activeOnly) {
                $sql .= ' WHERE is_active = 1';
            }
            $sql .= ' ORDER BY sort_order ASC, id ASC';
            $rows = db()->query($sql)->fetchAll();
            if ($rows) {
                return array_map('normalize_demo_product', $rows);
            }
        } catch (Throwable $e) {
            // Table missing or DB down — use fallback.
        }
    }

    return array_map('normalize_demo_product', fallback_demo_products());
}

function get_featured_demo_product(): ?array
{
    $products = get_demo_products();
    foreach ($products as $product) {
        if (!empty($product['featured']) && ($product['status'] ?? '') === 'live') {
            return $product;
        }
    }
    foreach ($products as $product) {
        if (!empty($product['featured'])) {
            return $product;
        }
    }
    return $products[0] ?? null;
}
