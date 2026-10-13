<?php
/**
 * Safe webhook diagnostics (no tokens, no message bodies).
 */
declare(strict_types=1);

require_once __DIR__ . '/whatsapp_send.php';

function whatsapp_webhook_trace_file(): string
{
    $candidates = [
        dirname(__DIR__, 2) . '/private/wa_webhook_trace.jsonl',
        dirname(__DIR__) . '/../private/wa_webhook_trace.jsonl',
        dirname(__DIR__) . '/private/wa_webhook_trace.jsonl',
    ];
    foreach ($candidates as $path) {
        $dir = dirname($path);
        if (is_dir($dir) && is_writable($dir)) {
            return $path;
        }
    }

    return $candidates[0];
}

/**
 * @param array<string, scalar|null> $fields
 */
function whatsapp_mask_wa_id(string $waId): string
{
    $digits = preg_replace('/\D+/', '', $waId) ?: $waId;
    $len = strlen($digits);
    if ($len <= 4) {
        return '****';
    }

    return str_repeat('*', max(0, $len - 4)) . substr($digits, -4);
}

/**
 * Structured log line (no secrets, masked phone when provided as from_masked).
 *
 * @param array<string, scalar|null> $fields
 */
function whatsapp_log_event(string $eventType, array $fields = []): void
{
    $fields['event'] = $eventType;
    error_log('WhatsApp ' . $eventType . ': ' . json_encode($fields, JSON_UNESCAPED_UNICODE));
    whatsapp_webhook_trace($fields);
}

function whatsapp_webhook_trace(array $fields): void
{
    $fields['ts'] = gmdate('c');
    $line = json_encode($fields, JSON_UNESCAPED_UNICODE);
    if ($line === false) {
        return;
    }
    $path = whatsapp_webhook_trace_file();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        error_log('WhatsApp webhook trace: cannot write to ' . $path);

        return;
    }
    @file_put_contents($path, $line . "\n", FILE_APPEND | LOCK_EX);
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (is_array($lines) && count($lines) > 120) {
        $trim = array_slice($lines, -100);
        @file_put_contents($path, implode("\n", $trim) . "\n", LOCK_EX);
    }
}

/**
 * @return list<array<string, mixed>>
 */
function whatsapp_webhook_trace_read(int $limit = 25): array
{
    $path = whatsapp_webhook_trace_file();
    if (!is_readable($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }
    $lines = array_slice($lines, -$limit);
    $out = [];
    foreach ($lines as $line) {
        $row = json_decode($line, true);
        if (is_array($row)) {
            $out[] = $row;
        }
    }

    return $out;
}

/**
 * Count inbound vs status events in a Meta webhook JSON payload.
 *
 * @return array{object:string,fields:list<string>,messages:int,statuses:int,phone_number_id:?string}
 */
function whatsapp_webhook_payload_stats(array $payload): array
{
    $messages = 0;
    $statuses = 0;
    $fields = [];
    $phoneNumberId = null;
    $object = (string) ($payload['object'] ?? '');
    $entries = $payload['entry'] ?? [];
    if (!is_array($entries)) {
        return [
            'object' => $object,
            'fields' => [],
            'messages' => 0,
            'statuses' => 0,
            'phone_number_id' => null,
        ];
    }
    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $changes = $entry['changes'] ?? [];
        if (!is_array($changes)) {
            continue;
        }
        foreach ($changes as $change) {
            if (!is_array($change)) {
                continue;
            }
            $field = (string) ($change['field'] ?? '');
            if ($field !== '') {
                $fields[$field] = true;
            }
            if ($field !== 'messages') {
                continue;
            }
            $value = $change['value'] ?? [];
            if (!is_array($value)) {
                continue;
            }
            if ($phoneNumberId === null && !empty($value['metadata']['phone_number_id'])) {
                $phoneNumberId = (string) $value['metadata']['phone_number_id'];
            }
            if (is_array($value['messages'] ?? null)) {
                $messages += count($value['messages']);
            }
            if (is_array($value['statuses'] ?? null)) {
                $statuses += count($value['statuses']);
            }
        }
    }

    return [
        'object' => $object,
        'fields' => array_keys($fields),
        'messages' => $messages,
        'statuses' => $statuses,
        'phone_number_id' => $phoneNumberId,
    ];
}

/**
 * @return array{ok:bool, apps?:list<array<string,mixed>>, error?:string, waba_id?:string}
 */
function whatsapp_fetch_subscribed_apps(): array
{
    $waba = whatsapp_resolve_waba_id();
    if ($waba === '') {
        return ['ok' => false, 'error' => 'WABA ID could not be resolved.'];
    }
    $res = whatsapp_graph_get($waba . '/subscribed_apps');
    if (!$res['ok']) {
        return ['ok' => false, 'error' => (string) ($res['error'] ?? 'Graph request failed.'), 'waba_id' => $waba];
    }
    $data = $res['data']['data'] ?? [];
    if (!is_array($data)) {
        $data = [];
    }
    $apps = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $api = is_array($row['whatsapp_business_api_data'] ?? null) ? $row['whatsapp_business_api_data'] : [];
        $apps[] = [
            'id' => (string) ($api['id'] ?? $row['id'] ?? ''),
            'name' => (string) ($api['name'] ?? ''),
            'link' => (string) ($api['link'] ?? ''),
        ];
    }

    return ['ok' => true, 'apps' => $apps, 'waba_id' => $waba];
}
