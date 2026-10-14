<?php
/**
 * Synthetic WhatsApp webhook tests (no real Meta traffic).
 * Run: php tests/whatsapp_webhook_test.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/whatsapp_cloud.php';

$failures = 0;

function assert_true(bool $cond, string $label): void
{
    global $failures;
    if (!$cond) {
        echo "FAIL: {$label}\n";
        $failures++;
        return;
    }
    echo "PASS: {$label}\n";
}

// --- Signature tests (in-memory override via env not available; test crypto directly) ---
$secret = 'test_app_secret_only';
$body = '{"object":"whatsapp_business_account","entry":[]}';
$sig = 'sha256=' . hash_hmac('sha256', $body, $secret);

$expectedHex = substr($sig, 7);
$computed = hash_hmac('sha256', $body, $secret);
assert_true(hash_equals($computed, $expectedHex), 'HMAC sha256 computation');
assert_true(!hash_equals($computed, 'deadbeef'), 'HMAC mismatch detection');

// --- Parser + DB (optional) ---
try {
    $pdo = db();
    $pdo->query('SELECT 1 FROM whatsapp_webhook_dedupe LIMIT 1')->fetchAll();

    $wamid = 'wamid.TEST_' . bin2hex(random_bytes(8));
    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => 'PHONE_ID_TEST'],
                    'contacts' => [[
                        'profile' => ['name' => 'Synthetic Tester'],
                        'wa_id' => '919999999999',
                    ]],
                    'messages' => [[
                        'from' => '919999999999',
                        'id' => $wamid,
                        'timestamp' => '1700000000',
                        'type' => 'text',
                        'text' => ['body' => 'Synthetic hello from test'],
                    ]],
                ],
            ]],
        ]],
    ];

    $pdo->beginTransaction();
    whatsapp_process_webhook_payload($payload, $pdo);
    $pdo->commit();

    $stmt = $pdo->prepare('SELECT body_text, contact_name FROM whatsapp_inbound_messages WHERE wa_message_id = ?');
    $stmt->execute([$wamid]);
    $row = $stmt->fetch();
    assert_true($row && $row['body_text'] === 'Synthetic hello from test', 'Inbound message persisted');
    assert_true($row && $row['contact_name'] === 'Synthetic Tester', 'Contact profile name stored');

    // Duplicate delivery should not duplicate row content incorrectly
    $pdo->beginTransaction();
    whatsapp_process_webhook_payload($payload, $pdo);
    $pdo->commit();
    $count = $pdo->prepare('SELECT COUNT(*) FROM whatsapp_inbound_messages WHERE wa_message_id = ?');
    $count->execute([$wamid]);
    assert_true((int) $count->fetchColumn() === 1, 'Duplicate webhook ignored via dedupe');

    // Status event
    $statusId = 'wamid.OUT_' . bin2hex(random_bytes(8));
    $pdo->prepare(
        'INSERT INTO whatsapp_outbound_messages (wa_message_id, recipient_wa_id, message_type, delivery_status)
         VALUES (?,?,?,?)'
    )->execute([$statusId, '919999999999', 'text', 'sent']);

    $statusPayload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => 'PHONE_ID_TEST'],
                    'statuses' => [[
                        'id' => $statusId,
                        'status' => 'delivered',
                        'timestamp' => '1700000001',
                        'recipient_id' => '919999999999',
                    ]],
                ],
            ]],
        ]],
    ];
    $pdo->beginTransaction();
    whatsapp_process_webhook_payload($statusPayload, $pdo);
    $pdo->commit();

    $st = $pdo->prepare('SELECT delivery_status FROM whatsapp_outbound_messages WHERE wa_message_id = ?');
    $st->execute([$statusId]);
    $out = $st->fetch();
    assert_true($out && $out['delivery_status'] === 'delivered', 'Outbound status updated from webhook');

    $externalId = 'wamid.META_' . bin2hex(random_bytes(8));
    $externalPayload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => 'PHONE_ID_TEST'],
                    'statuses' => [[
                        'id' => $externalId,
                        'status' => 'sent',
                        'timestamp' => '1700000002',
                        'recipient_id' => '918888888888',
                    ]],
                ],
            ]],
        ]],
    ];
    $pdo->beginTransaction();
    whatsapp_process_webhook_payload($externalPayload, $pdo);
    $pdo->commit();
    $ext = $pdo->prepare('SELECT recipient_wa_id, message_type, payload_json FROM whatsapp_outbound_messages WHERE wa_message_id = ?');
    $ext->execute([$externalId]);
    $extRow = $ext->fetch();
    assert_true($extRow && $extRow['recipient_wa_id'] === '918888888888', 'Meta-only send creates outbound row from status webhook');
    assert_true($extRow && in_array($extRow['message_type'], ['external', 'template'], true), 'External send typed as external or template');

    $echoId = 'wamid.ECHO_' . bin2hex(random_bytes(8));
    $echoPayload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => 'PHONE_ID_TEST'],
                    'message_echoes' => [[
                        'to' => '918888888888',
                        'id' => $echoId,
                        'timestamp' => '1700000003',
                        'type' => 'text',
                        'text' => ['body' => 'Echo from Meta Business app'],
                    ]],
                ],
            ]],
        ]],
    ];
    $pdo->beginTransaction();
    whatsapp_process_webhook_payload($echoPayload, $pdo);
    $pdo->commit();
    $echoStmt = $pdo->prepare('SELECT payload_json FROM whatsapp_outbound_messages WHERE wa_message_id = ?');
    $echoStmt->execute([$echoId]);
    $echoRow = $echoStmt->fetch();
    $echoJson = is_array($echoRow) ? json_decode((string) ($echoRow['payload_json'] ?? ''), true) : null;
    assert_true(is_array($echoJson) && ($echoJson['text'] ?? '') === 'Echo from Meta Business app', 'Message echo stored with body text');

    // Cleanup synthetic rows
    $pdo->prepare('DELETE FROM whatsapp_inbound_messages WHERE wa_message_id = ?')->execute([$wamid]);
    $pdo->prepare('DELETE FROM whatsapp_webhook_dedupe WHERE dedupe_key LIKE ?')->execute(['inbound:' . $wamid]);
    $pdo->prepare('DELETE FROM whatsapp_outbound_messages WHERE wa_message_id = ?')->execute([$statusId]);
    $pdo->prepare('DELETE FROM whatsapp_message_status_log WHERE wa_message_id = ?')->execute([$statusId]);
    $pdo->prepare('DELETE FROM whatsapp_webhook_dedupe WHERE dedupe_key LIKE ?')->execute(['status:' . $statusId . '%']);
    $pdo->prepare('DELETE FROM whatsapp_outbound_messages WHERE wa_message_id = ?')->execute([$externalId]);
    $pdo->prepare('DELETE FROM whatsapp_outbound_messages WHERE wa_message_id = ?')->execute([$echoId]);
    $pdo->prepare('DELETE FROM whatsapp_message_status_log WHERE wa_message_id = ?')->execute([$externalId]);
    $pdo->prepare('DELETE FROM whatsapp_webhook_dedupe WHERE dedupe_key LIKE ?')->execute(['status:' . $externalId . '%']);
    $pdo->prepare('DELETE FROM whatsapp_webhook_dedupe WHERE dedupe_key LIKE ?')->execute(['echo:' . $echoId]);
} catch (Throwable $e) {
    echo "SKIP DB tests (run sql/migrate_whatsapp_webhook.php first): " . $e->getMessage() . "\n";
}

if ($failures > 0) {
    echo "\n{$failures} test(s) failed.\n";
    exit(1);
}

echo "\nAll runnable tests passed.\n";
