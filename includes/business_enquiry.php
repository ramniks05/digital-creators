<?php
/**
 * Core business enquiry automation — website forms + WhatsApp inbound.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/whatsapp_contacts.php';
require_once __DIR__ . '/whatsapp_webhook_diagnostics.php';

/**
 * @return list<array{slug:string,label:string,keywords:list<string>}>
 */
function business_enquiry_service_catalog(): array
{
    static $catalog = null;
    if (is_array($catalog)) {
        return $catalog;
    }

    $catalog = [
        ['slug' => 'custom-software', 'label' => 'Custom Software', 'keywords' => ['custom software', 'software', 'crm', 'erp', 'saas', 'lms', 'portal']],
        ['slug' => 'website-development', 'label' => 'Website Development', 'keywords' => ['website', 'web site', 'wordpress', 'landing page', 'corporate site']],
        ['slug' => 'app-development', 'label' => 'App Development', 'keywords' => ['app', 'mobile app', 'android', 'ios', 'flutter', 'react native']],
        ['slug' => 'web-applications', 'label' => 'Web Applications', 'keywords' => ['web app', 'web application', 'dashboard', 'admin panel']],
        ['slug' => 'cloud-hosting', 'label' => 'Cloud Hosting', 'keywords' => ['hosting', 'cloud', 'ssl', 'cdn', 'domain']],
        ['slug' => 'server-management', 'label' => 'Server Management', 'keywords' => ['server', 'vps', 'dedicated', 'linux server']],
        ['slug' => 'devops-infrastructure', 'label' => 'DevOps & Infrastructure', 'keywords' => ['devops', 'ci/cd', 'docker', 'kubernetes', 'pipeline']],
        ['slug' => 'maintenance-support', 'label' => 'Maintenance & Support', 'keywords' => ['maintenance', 'support', 'amc', 'bug fix', 'updates']],
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
            foreach ($catalog as &$row) {
                if ($row['slug'] === $slug) {
                    $row['label'] = $title;
                    $found = true;
                    break;
                }
            }
            unset($row);
            if (!$found) {
                $catalog[] = ['slug' => $slug, 'label' => $title, 'keywords' => [strtolower($title)]];
            }
        }
    } catch (Throwable $e) {
        // keep static catalog
    }

    return $catalog;
}

function business_enquiry_service_label(string $slug): string
{
    foreach (business_enquiry_service_catalog() as $row) {
        if ($row['slug'] === $slug) {
            return $row['label'];
        }
    }

    return str_replace('-', ' ', $slug);
}

/**
 * @return array{slug:?string,label:?string}
 */
function business_enquiry_detect_service(?string $text, ?string $hintSlug = null): array
{
    if ($hintSlug !== null && $hintSlug !== '') {
        return ['slug' => $hintSlug, 'label' => business_enquiry_service_label($hintSlug)];
    }
    if ($text === null || trim($text) === '') {
        return ['slug' => null, 'label' => null];
    }
    $norm = mb_strtolower($text, 'UTF-8');
    $bestSlug = null;
    $bestScore = 0;
    foreach (business_enquiry_service_catalog() as $row) {
        foreach ($row['keywords'] as $kw) {
            if ($kw !== '' && str_contains($norm, $kw)) {
                $score = strlen($kw);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestSlug = $row['slug'];
                }
            }
        }
    }
    if ($bestSlug === null) {
        return ['slug' => null, 'label' => null];
    }

    return ['slug' => $bestSlug, 'label' => business_enquiry_service_label($bestSlug)];
}

/**
 * @return array{
 *   enabled:bool,
 *   whatsapp_leads:bool,
 *   whatsapp_ack:bool,
 *   staff_notify:bool,
 *   staff_wa_ids:list<string>,
 *   services_menu_enabled:bool,
 *   form_welcome_enabled:bool,
 *   form_welcome_template:string,
 *   form_welcome_language:string,
 *   inbound_welcome_enabled:bool,
 *   inbound_welcome_message:string
 * }
 */
function business_enquiry_config(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $config = require __DIR__ . '/config.php';
    $wa = $config['whatsapp'] ?? [];
    $be = is_array($wa['business_enquiry'] ?? null) ? $wa['business_enquiry'] : [];

    $bool = static function (mixed $value, bool $default = false): bool {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    };

    $staffRaw = trim((string) ($be['staff_wa_ids'] ?? getenv('DC_ENQUIRY_STAFF_WA_IDS') ?: ''));
    $staff = [];
    if ($staffRaw !== '') {
        foreach (preg_split('/[\s,;]+/', $staffRaw) ?: [] as $part) {
            $part = preg_replace('/\D+/', '', (string) $part) ?: '';
            if ($part !== '') {
                $staff[] = $part;
            }
        }
        $staff = array_values(array_unique($staff));
    }

    $config = require __DIR__ . '/config.php';
    $waRoot = $config['whatsapp'] ?? [];
    $beRoot = is_array($waRoot['business_enquiry'] ?? null) ? $waRoot['business_enquiry'] : [];
    $defaultTpl = trim((string) ($waRoot['form_welcome_template'] ?? $beRoot['form_welcome_template'] ?? 'enquiry_thank_you'));
    $defaultLang = trim((string) ($waRoot['form_welcome_language'] ?? $beRoot['form_welcome_language'] ?? 'en'));

    $inboundWelcome = trim((string) ($be['inbound_welcome_message'] ?? getenv('DC_ENQUIRY_WELCOME_MESSAGE') ?: ''));
    if ($inboundWelcome === '') {
        $inboundWelcome = 'Hi! Thanks for messaging Digital Creatorss. We received your message and our team will reply within 24 hours. Reply STOP to opt out of marketing.';
    }

    $cached = [
        'enabled' => $bool($be['enabled'] ?? getenv('DC_ENQUIRY_AUTOMATION') ?: false, false),
        'whatsapp_leads' => $bool($be['whatsapp_leads'] ?? getenv('DC_ENQUIRY_WHATSAPP_LEADS') ?: true, true),
        'whatsapp_ack' => $bool($be['whatsapp_ack'] ?? getenv('DC_ENQUIRY_WHATSAPP_ACK') ?: true, true),
        'staff_notify' => $bool($be['staff_notify'] ?? getenv('DC_ENQUIRY_STAFF_NOTIFY') ?: false, false),
        'staff_wa_ids' => $staff,
        'services_menu_enabled' => $bool($be['services_menu_enabled'] ?? getenv('DC_ENQUIRY_SERVICES_MENU') ?: true, true),
        'form_welcome_enabled' => $bool($be['form_welcome_enabled'] ?? getenv('DC_ENQUIRY_FORM_WELCOME') ?: true, true),
        'form_welcome_template' => trim((string) ($be['form_welcome_template'] ?? getenv('DC_ENQUIRY_FORM_TEMPLATE') ?: $defaultTpl)),
        'form_welcome_language' => trim((string) ($be['form_welcome_language'] ?? getenv('DC_ENQUIRY_FORM_TEMPLATE_LANG') ?: $defaultLang)),
        'inbound_welcome_enabled' => $bool($be['inbound_welcome_enabled'] ?? getenv('DC_ENQUIRY_INBOUND_WELCOME') ?: true, true),
        'inbound_welcome_message' => $inboundWelcome,
    ];

    return $cached;
}

function business_enquiry_leads_columns_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT source, consent_at, wa_message_id FROM leads LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function business_enquiry_whatsapp_synthetic_email(string $waId): string
{
    $waId = preg_replace('/\D+/', '', $waId) ?: 'unknown';

    return 'wa.' . $waId . '@inbound.digitalcreatorss.local';
}

function business_enquiry_is_website_followup_message(?string $body): bool
{
    if ($body === null) {
        return false;
    }

    return stripos($body, 'submitted a query through the website') !== false
        || stripos($body, 'I have submitted a query') !== false;
}

function business_enquiry_recent_form_lead(PDO $pdo, string $phone, int $withinHours = 72): ?array
{
    $waId = whatsapp_normalize_recipient($phone);
    $variants = whatsapp_wa_id_variants($waId);
    if ($variants === []) {
        return null;
    }
    $placeholders = implode(',', array_fill(0, count($variants), '?'));
    $sql = "SELECT * FROM leads WHERE source = 'website_form' AND REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+','') IN ({$placeholders})
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR) ORDER BY id DESC LIMIT 1";
    $params = array_merge($variants, [$withinHours]);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * After website form saved (api/contact.php).
 *
 * @param array{name:string,email:string,phone:string,service:string,message:string,budget:string} $data
 */
function business_enquiry_after_website_form(int $leadId, array $data): void
{
    $cfg = business_enquiry_config();
    $phone = whatsapp_normalize_recipient((string) ($data['phone'] ?? ''));

    if ($phone !== '' && whatsapp_contacts_table_exists(db())) {
        whatsapp_contact_record_opt_in($phone, 'website_form');
    }

    whatsapp_log_event('lead_website_form', [
        'lead_id' => $leadId,
        'service' => (string) ($data['service'] ?? ''),
        'phone_masked' => $phone !== '' ? whatsapp_mask_wa_id($phone) : '',
    ]);

    if ($cfg['enabled'] && $cfg['staff_notify'] && $cfg['staff_wa_ids'] !== []) {
        $label = business_enquiry_service_label((string) ($data['service'] ?? ''));
        $snippet = mb_substr(preg_replace('/\s+/u', ' ', (string) ($data['message'] ?? '')) ?: '', 0, 120);
        $text = "New website enquiry #{$leadId}\n"
            . 'Name: ' . ($data['name'] ?? '') . "\n"
            . 'Service: ' . $label . "\n"
            . 'Phone: ' . ($data['phone'] ?? '') . "\n"
            . 'Email: ' . ($data['email'] ?? '') . "\n"
            . 'Message: ' . $snippet;

        business_enquiry_notify_staff($text, 'form:' . $leadId);
    }

    business_enquiry_send_form_welcome_whatsapp($leadId, $data);
}

/**
 * @param array{name:string,email:string,phone:string,service:string,message:string,budget:string} $data
 */
function business_enquiry_send_form_welcome_whatsapp(int $leadId, array $data): void
{
    $cfg = business_enquiry_config();
    if (!$cfg['form_welcome_enabled']) {
        whatsapp_log_event('form_welcome_skipped', ['lead_id' => $leadId, 'reason' => 'form_welcome_disabled']);

        return;
    }
    if (!$cfg['enabled']) {
        whatsapp_log_event('form_welcome_skipped', ['lead_id' => $leadId, 'reason' => 'business_enquiry_disabled']);

        return;
    }

    require_once __DIR__ . '/whatsapp_send.php';
    require_once __DIR__ . '/whatsapp_cloud.php';

    $waCfg = whatsapp_cloud_config();
    if (trim($waCfg['access_token']) === '' || trim($waCfg['phone_number_id']) === '') {
        whatsapp_log_event('form_welcome_skipped', ['lead_id' => $leadId, 'reason' => 'whatsapp_not_configured']);

        return;
    }

    $phone = whatsapp_normalize_recipient((string) ($data['phone'] ?? ''));
    if ($phone === '') {
        whatsapp_log_event('form_welcome_skipped', ['lead_id' => $leadId, 'reason' => 'invalid_phone']);

        return;
    }

    if (whatsapp_contacts_table_exists(db())) {
        $status = business_enquiry_contact_status($phone);
        if ($status === 'opted_out') {
            whatsapp_log_event('form_welcome_skipped', ['lead_id' => $leadId, 'reason' => 'opted_out']);

            return;
        }
    }

    $pdo = db();
    $dedupeKey = 'form_welcome:lead:' . $leadId;
    try {
        $pdo->beginTransaction();
        if (!whatsapp_register_dedupe($pdo, $dedupeKey, 'form_welcome')) {
            $pdo->commit();

            return;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return;
    }

    $template = $cfg['form_welcome_template'];
    $resolved = business_enquiry_resolve_form_welcome_template($template, $cfg['form_welcome_language']);
    if ($resolved === null) {
        whatsapp_log_event('form_welcome_failed', [
            'lead_id' => $leadId,
            'phone_masked' => whatsapp_mask_wa_id($phone),
            'template' => $template,
            'error' => 'Template not found on WABA (check name and language en vs en_US)',
        ]);

        return;
    }
    $lang = $resolved['language'];
    $params = business_enquiry_template_params_for_data($template, $lang, $data);

    $result = whatsapp_send_template_message($phone, $template, $lang, [], $params);
    if (!$result['ok']) {
        whatsapp_log_event('form_welcome_failed', [
            'lead_id' => $leadId,
            'phone_masked' => whatsapp_mask_wa_id($phone),
            'template' => $template,
            'language' => $lang,
            'param_count' => count($params),
            'error' => substr((string) ($result['error'] ?? ''), 0, 200),
        ]);

        return;
    }

    whatsapp_log_event('form_welcome_sent', [
        'lead_id' => $leadId,
        'phone_masked' => whatsapp_mask_wa_id($phone),
        'template' => $template,
        'wa_message_id' => (string) ($result['wa_message_id'] ?? ''),
    ]);
}

function business_enquiry_contact_status(string $waId): string
{
    $pdo = db();
    if (!whatsapp_contacts_table_exists($pdo)) {
        return 'unknown';
    }
    $waId = whatsapp_normalize_recipient($waId);
    $stmt = $pdo->prepare('SELECT consent_status FROM whatsapp_contacts WHERE wa_id = ? LIMIT 1');
    $stmt->execute([$waId]);
    $s = $stmt->fetchColumn();

    return is_string($s) && $s !== '' ? $s : 'unknown';
}

/**
 * Map lead/form data to template variable values (positional order).
 *
 * @param array{name?:string,service?:string,message?:string} $data
 * @return list<string>
 */
/**
 * @return array{language:string}|null
 */
function business_enquiry_resolve_form_welcome_template(string $templateName, string $languageCode): ?array
{
    require_once __DIR__ . '/whatsapp_send.php';
    $candidates = array_values(array_unique([
        whatsapp_normalize_template_language($languageCode),
        'en',
        'en_US',
        'en_GB',
    ]));
    foreach ($candidates as $lang) {
        $def = whatsapp_lookup_template_definition($templateName, $lang);
        if ($def !== null) {
            return ['language' => whatsapp_normalize_template_language((string) $def['language'])];
        }
    }

    return null;
}

function business_enquiry_template_params_for_data(string $templateName, string $languageCode, array $data): array
{
    require_once __DIR__ . '/whatsapp_send.php';
    $resolved = business_enquiry_resolve_form_welcome_template($templateName, $languageCode);
    $def = $resolved !== null
        ? whatsapp_lookup_template_definition($templateName, $resolved['language'])
        : null;
    if ($def === null) {
        return [];
    }
    $parameterFormat = strtolower((string) ($def['parameter_format'] ?? ''));
    $slots = $def['param_slots'] ?? whatsapp_template_param_slots($def['components'] ?? [], $parameterFormat);
    $needed = count($slots);
    if ($needed === 0) {
        return [];
    }

    $name = trim((string) ($data['name'] ?? 'Customer'));
    $first = trim(explode(' ', $name)[0] ?: 'Customer');
    $service = business_enquiry_service_label((string) ($data['service'] ?? 'our services'));
    $pool = [$first, $service, $name, 'Digital Creatorss'];

    return array_slice($pool, 0, $needed);
}

function business_enquiry_notify_staff(string $text, string $dedupeSuffix): void
{
    $cfg = business_enquiry_config();
    if (!$cfg['enabled'] || !$cfg['staff_notify'] || $cfg['staff_wa_ids'] === []) {
        return;
    }

    require_once __DIR__ . '/whatsapp_send.php';
    $pdo = db();
    foreach ($cfg['staff_wa_ids'] as $staffWa) {
        $dedupeKey = 'staff_notify:' . $dedupeSuffix . ':' . $staffWa;
        try {
            $pdo->beginTransaction();
            if (!whatsapp_register_dedupe($pdo, $dedupeKey, 'staff_notify')) {
                $pdo->commit();
                continue;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            continue;
        }
        $result = whatsapp_send_text_message($staffWa, $text, true);
        if (!$result['ok']) {
            whatsapp_log_event('staff_notify_failed', [
                'to_masked' => whatsapp_mask_wa_id($staffWa),
                'error' => substr((string) ($result['error'] ?? ''), 0, 160),
            ]);
        }
    }
}

function business_enquiry_services_menu_text(): string
{
    $lines = ["Digital Creatorss — our core services:"];
    $i = 1;
    foreach (business_enquiry_service_catalog() as $row) {
        $lines[] = $i . '. ' . $row['label'];
        $i++;
        if ($i > 8) {
            break;
        }
    }
    $lines[] = 'Reply with your requirement or project details and our team will assist you within 24 hours.';

    return implode("\n", $lines);
}

/**
 * @param array{from:string,wamid:string,body:?string,type:string,contact_name?:?string} $row
 * @return bool true if an automated reply was sent (skip welcome duplicate)
 */
function business_enquiry_process_whatsapp_inbound(PDO $pdo, array $row): bool
{
    $cfg = business_enquiry_config();
    $from = (string) ($row['from'] ?? '');
    $wamid = (string) ($row['wamid'] ?? '');
    $body = isset($row['body']) ? (string) $row['body'] : null;
    $type = (string) ($row['type'] ?? '');
    $contactName = isset($row['contact_name']) ? trim((string) $row['contact_name']) : '';

    if ($from === '' || $wamid === '') {
        return false;
    }

    if ($type !== 'text' || $body === null || trim($body) === '') {
        return false;
    }

    $sentReply = false;

    if ($cfg['enabled'] && $cfg['whatsapp_leads'] && business_enquiry_leads_columns_ready($pdo)) {
        if (!business_enquiry_is_website_followup_message($body)) {
            business_enquiry_create_whatsapp_lead($pdo, $from, $wamid, $body, $contactName);
        } else {
            $recent = business_enquiry_recent_form_lead($pdo, $from);
            if ($recent === null) {
                business_enquiry_create_whatsapp_lead($pdo, $from, $wamid, $body, $contactName);
            } else {
                whatsapp_log_event('lead_whatsapp_linked_form', [
                    'lead_id' => (int) $recent['id'],
                    'wamid' => $wamid,
                    'phone_masked' => whatsapp_mask_wa_id($from),
                ]);
            }
        }
    }

    if (!$cfg['enabled']) {
        return false;
    }

    require_once __DIR__ . '/whatsapp_automation.php';

    if (whatsapp_contact_consent_status($pdo, $from) === 'opted_out') {
        return false;
    }

    if (!whatsapp_service_window_open($pdo, $from)) {
        return false;
    }

    $norm = mb_strtolower(trim($body), 'UTF-8');
    if ($cfg['services_menu_enabled'] && in_array($norm, ['services', 'service', 'menu', 'options', 'list'], true)) {
        whatsapp_automation_send_once($pdo, $from, $wamid, 'services_menu', business_enquiry_services_menu_text());

        return true;
    }

    require_once __DIR__ . '/whatsapp_automation.php';
    $isShortOrGreeting = mb_strlen(trim($body), 'UTF-8') < 8
        || whatsapp_automation_is_welcome_trigger($body);

    if ($cfg['inbound_welcome_enabled'] && $isShortOrGreeting) {
        whatsapp_automation_send_once($pdo, $from, $wamid, 'inbound_welcome', $cfg['inbound_welcome_message']);
        $sentReply = true;
    } elseif ($cfg['whatsapp_ack']) {
        $detected = business_enquiry_detect_service($body);
        $serviceLabel = $detected['label'] ?? 'your project';
        $ack = "Thank you for contacting Digital Creatorss. We received your enquiry about {$serviceLabel}. "
            . 'Our team will respond within 24 hours on WhatsApp or phone.';
        if (mb_strlen(trim($body), 'UTF-8') >= 8) {
            whatsapp_automation_send_once($pdo, $from, $wamid, 'enquiry_ack', $ack);
            $sentReply = true;
        }
    }

    return $sentReply;
}

function business_enquiry_create_whatsapp_lead(
    PDO $pdo,
    string $fromWa,
    string $wamid,
    string $body,
    string $displayName = ''
): void {
    $stmt = $pdo->prepare('SELECT id FROM leads WHERE wa_message_id = ? LIMIT 1');
    $stmt->execute([$wamid]);
    if ($stmt->fetchColumn()) {
        return;
    }

    $detected = business_enquiry_detect_service($body);
    $service = $detected['slug'] ?? 'general-enquiry';
    $name = $displayName !== '' ? $displayName : 'WhatsApp Contact';
    $email = business_enquiry_whatsapp_synthetic_email($fromWa);
    $phone = '+' . whatsapp_normalize_recipient($fromWa);

    try {
        $ins = $pdo->prepare(
            'INSERT INTO leads (name, email, phone, service, message, budget, source, consent_at, wa_message_id, status)
             VALUES (?,?,?,?,?,NULL,\'whatsapp\',CURRENT_TIMESTAMP,?,\'new\')'
        );
        $ins->execute([$name, $email, $phone, $service, $body, $wamid]);
        $leadId = (int) $pdo->lastInsertId();
        whatsapp_log_event('lead_whatsapp_created', [
            'lead_id' => $leadId,
            'wamid' => $wamid,
            'service' => $service,
            'phone_masked' => whatsapp_mask_wa_id($fromWa),
        ]);

        if (business_enquiry_config()['staff_notify']) {
            $label = business_enquiry_service_label($service);
            business_enquiry_notify_staff(
                "New WhatsApp lead #{$leadId}\n{$name}\n{$phone}\nService: {$label}\n" . mb_substr($body, 0, 160),
                'lead:' . $wamid
            );
        }
    } catch (Throwable $e) {
        whatsapp_log_event('lead_whatsapp_failed', [
            'wamid' => $wamid,
            'error' => substr($e->getMessage(), 0, 120),
        ]);
    }
}
