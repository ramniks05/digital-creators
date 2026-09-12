<?php
/**
 * Site SEO helpers — canonical base URL, asset cache busting, head output.
 */

declare(strict_types=1);

if (!function_exists('site_base_url')) {
    function site_base_url(): string
    {
        $configured = getenv('SITE_URL') ?: '';
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '' && !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            return ($https ? 'https' : 'http') . '://' . $host;
        }

        return 'https://digitalcreatorss.com';
    }
}

if (!function_exists('seo_absolute_url')) {
    function seo_absolute_url(string $path = ''): string
    {
        $base = site_base_url();
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return $base . '/';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_version')) {
    function asset_version(string $relativePath): string
    {
        $abs = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        return is_file($abs) ? (string) filemtime($abs) : '1';
    }
}

if (!function_exists('seo_default_og_image')) {
    function seo_default_og_image(): string
    {
        $candidates = [
            'assets/service/custom_software.png',
            'assets/images/logo-white.webp',
            'assets/favicon.png',
        ];
        foreach ($candidates as $rel) {
            if (is_file(dirname(__DIR__) . '/' . $rel)) {
                return seo_absolute_url($rel);
            }
        }
        return seo_absolute_url('assets/favicon.png');
    }
}

/**
 * Echo a complete public <head> inner block.
 *
 * @param array{
 *   title: string,
 *   description: string,
 *   path?: string,
 *   og_type?: string,
 *   og_image?: string,
 *   robots?: string,
 *   json_ld?: array<int|string, mixed>|null,
 *   extra_head?: string
 * } $seo
 */
function render_seo_head(array $seo): void
{
    $title = trim((string) ($seo['title'] ?? 'Digital Creatorss'));
    $description = trim((string) ($seo['description'] ?? ''));
    $path = (string) ($seo['path'] ?? '');
    $canonical = seo_absolute_url($path);
    $ogType = (string) ($seo['og_type'] ?? 'website');
    $ogImage = trim((string) ($seo['og_image'] ?? ''));
    if ($ogImage === '') {
        $ogImage = seo_default_og_image();
    } elseif (!preg_match('#^https?://#i', $ogImage)) {
        $ogImage = seo_absolute_url($ogImage);
    }
    $robots = trim((string) ($seo['robots'] ?? 'index, follow, max-image-preview:large'));
    $cssVer = asset_version('css/style.css');
    $siteName = 'Digital Creatorss';

    $jsonLdBlocks = [];
    if (!empty($seo['json_ld']) && is_array($seo['json_ld'])) {
        $candidate = $seo['json_ld'];
        $isList = isset($candidate[0]) && is_array($candidate[0]);
        $jsonLdBlocks = $isList ? $candidate : [$candidate];
    }

    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    echo '<meta name="google-site-verification" content="RwrlSItmre1avNl5hczXswMsNttpa7nCUZXaI6DlZgo">' . "\n";
    echo '<meta name="robots" content="' . htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
    if ($description !== '') {
        echo '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    echo '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">' . "\n";

    echo '<meta property="og:type" content="' . htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<meta property="og:site_name" content="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if ($description !== '') {
        echo '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    echo '<meta property="og:url" content="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<meta property="og:locale" content="en_IN">' . "\n";

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if ($description !== '') {
        echo '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    echo '<meta name="twitter:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">' . "\n";

    echo '<meta name="theme-color" content="#fd7e14">' . "\n";

    require __DIR__ . '/head-fonts.php';

    echo '<link rel="stylesheet" href="css/style.css?v=' . htmlspecialchars($cssVer, ENT_QUOTES, 'UTF-8') . '">' . "\n";

    foreach ($jsonLdBlocks as $block) {
        if (!is_array($block) || $block === []) {
            continue;
        }
        $json = json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        if ($json === false) {
            continue;
        }
        echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }

    if (!empty($seo['extra_head'])) {
        echo $seo['extra_head'];
    }
}

if (!function_exists('seo_organization_graph')) {
    /**
     * @return array<string, mixed>
     */
    function seo_organization_graph(): array
    {
        $base = site_base_url();
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Digital Creatorss',
            'url' => $base . '/',
            'logo' => seo_absolute_url('assets/favicon.png'),
            'email' => 'info@digitalcreatorss.com',
            'telephone' => '+91-8851613806',
            'sameAs' => [
                'https://linkedin.com/in/digitalcreatorss',
                'https://www.youtube.com/@DigitalCreators-neekita',
                'https://www.instagram.com/digitalcreatorss_software/',
            ],
            'description' => 'Web and app development, custom software, cloud hosting, and server management.',
        ];
    }
}

if (!function_exists('seo_website_graph')) {
    /**
     * @return array<string, mixed>
     */
    function seo_website_graph(): array
    {
        $base = site_base_url();
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Digital Creatorss',
            'url' => $base . '/',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Digital Creatorss',
                'url' => $base . '/',
            ],
        ];
    }
}
