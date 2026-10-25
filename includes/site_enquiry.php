<?php
declare(strict_types=1);

require_once __DIR__ . '/phone_utils.php';
require_once __DIR__ . '/external_messaging.php';
require_once __DIR__ . '/enquiry_catalog.php';

function site_enquiry_leads_columns_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT source, consent_at FROM leads LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * After website form saved (api/contact.php). Keeps DB lead; messaging goes to external platform only.
 *
 * @param array{name:string,email:string,phone:string,service:string,message:string,budget:string} $data
 */
function site_enquiry_after_website_form(int $leadId, array $data): void
{
    $phone = phone_normalize_digits((string) ($data['phone'] ?? ''));
    external_messaging_post_lead($leadId, [
        'name' => (string) ($data['name'] ?? ''),
        'email' => (string) ($data['email'] ?? ''),
        'phone' => (string) ($data['phone'] ?? ''),
        'phone_normalized' => $phone,
        'service' => (string) ($data['service'] ?? ''),
        'service_label' => enquiry_service_label((string) ($data['service'] ?? '')),
        'message' => (string) ($data['message'] ?? ''),
        'budget' => (string) ($data['budget'] ?? ''),
        'source' => 'website_form',
    ]);
}
