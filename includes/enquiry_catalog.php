<?php
declare(strict_types=1);

/**
 * Service labels for contact form + external messaging (no WhatsApp API).
 */
function enquiry_service_catalog(): array
{
    static $catalog = null;
    if (is_array($catalog)) {
        return $catalog;
    }

    $catalog = [
        ['slug' => 'custom-software', 'label' => 'Custom Software'],
        ['slug' => 'website-development', 'label' => 'Website Development'],
        ['slug' => 'app-development', 'label' => 'App Development'],
        ['slug' => 'web-applications', 'label' => 'Web Applications'],
        ['slug' => 'cloud-hosting', 'label' => 'Cloud Hosting'],
        ['slug' => 'server-management', 'label' => 'Server Management'],
        ['slug' => 'devops-infrastructure', 'label' => 'DevOps & Infrastructure'],
        ['slug' => 'maintenance-support', 'label' => 'Maintenance & Support'],
    ];

    try {
        if (!function_exists('get_services')) {
            require_once __DIR__ . '/content.php';
        }
        foreach (get_services() as $svc) {
            $title = trim((string) ($svc['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '');
            $slug = trim($slug, '-');
            $found = false;
            foreach ($catalog as $row) {
                if ($row['slug'] === $slug) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $catalog[] = ['slug' => $slug, 'label' => $title];
            }
        }
    } catch (Throwable $e) {
        // keep static catalog
    }

    return $catalog;
}

function enquiry_service_label(string $slug): string
{
    foreach (enquiry_service_catalog() as $row) {
        if ($row['slug'] === $slug) {
            return $row['label'];
        }
    }

    return str_replace('-', ' ', $slug);
}

function business_enquiry_service_label(string $slug): string
{
    return enquiry_service_label($slug);
}

function business_enquiry_leads_columns_ready(PDO $pdo): bool
{
    return site_enquiry_leads_columns_ready($pdo);
}

function business_enquiry_after_website_form(int $leadId, array $data): void
{
    site_enquiry_after_website_form($leadId, $data);
}
