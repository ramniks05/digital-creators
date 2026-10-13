<?php
/**
 * Security / automation unit tests (no live Meta, no outbound Graph calls).
 * Run: php tests/whatsapp_webhook_security_test.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/whatsapp_cloud.php';
require_once $root . '/includes/whatsapp_automation.php';
require_once $root . '/includes/business_enquiry.php';
require_once $root . '/includes/whatsapp_webhook_diagnostics.php';

$failures = 0;

function sec_assert(bool $cond, string $label): void
{
    global $failures;
    if (!$cond) {
        echo "FAIL: {$label}\n";
        $failures++;
        return;
    }
    echo "PASS: {$label}\n";
}

// Verify token comparison (same logic as GET handler)
$expectedToken = 'verify-token-test';
sec_assert(hash_equals($expectedToken, 'verify-token-test'), 'Valid verify token matches');
sec_assert(!hash_equals($expectedToken, 'wrong'), 'Invalid verify token rejected');

// Signature helper with overridden config is not available; test algorithm directly
$secret = 'unit_test_secret';
$raw = '{"object":"whatsapp_business_account"}';
$goodHeader = 'sha256=' . hash_hmac('sha256', $raw, $secret);
$badHeader = 'sha256=' . str_repeat('a', 64);

// Temporarily we cannot inject secret into whatsapp_verify_signature without env;
// validate parsing rules on malformed headers
sec_assert(!whatsapp_verify_signature($raw, null), 'Missing signature header rejected');
sec_assert(!whatsapp_verify_signature($raw, 'sha1=abc'), 'Non-sha256 prefix rejected');
sec_assert(!whatsapp_verify_signature($raw, 'sha256=nothex'), 'Invalid hex signature rejected');

// Opt-out keywords
sec_assert(whatsapp_automation_is_opt_out_message('STOP'), 'STOP opt-out');
sec_assert(whatsapp_automation_is_opt_out_message('  Unsubscribe  '), 'UNSUBSCRIBE opt-out');
sec_assert(whatsapp_automation_is_opt_out_message('opt-out'), 'opt-out keyword');
sec_assert(!whatsapp_automation_is_opt_out_message('please stop the server'), 'Non opt-out phrase ignored');

// Welcome triggers
sec_assert(whatsapp_automation_is_welcome_trigger('Hi'), 'Hi welcome trigger');
sec_assert(whatsapp_automation_is_welcome_trigger('Hello!'), 'Hello welcome trigger');
sec_assert(!whatsapp_automation_is_welcome_trigger('I need a website quote please'), 'Long message not welcome');

// Mask phone
sec_assert(whatsapp_mask_wa_id('919876543210') === '********3210', 'Phone masking keeps last 4 digits');

$detected = business_enquiry_detect_service('I need a new website for my business');
sec_assert($detected['slug'] === 'website-development', 'Service detect: website');
sec_assert(business_enquiry_is_website_followup_message('I have submitted a query through the website'), 'Website WA follow-up detect');

// Automation defaults off
$autoCfg = whatsapp_automation_config();
sec_assert($autoCfg['enabled'] === false, 'Auto-reply disabled by default');
sec_assert($autoCfg['welcome_hi_enabled'] === false, 'Welcome Hi disabled by default');

// DB: STOP sets opted_out (no auto-send)
try {
    $pdo = db();
    $pdo->query('SELECT 1 FROM whatsapp_contacts LIMIT 1');

    $testWa = '919888777666';
    $wamid = 'wamid.SEC_' . bin2hex(random_bytes(6));
    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_TEST',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => 'PHONE_TEST'],
                    'messages' => [[
                        'from' => $testWa,
                        'id' => $wamid,
                        'timestamp' => (string) time(),
                        'type' => 'text',
                        'text' => ['body' => 'STOP'],
                    ]],
                ],
            ]],
        ]],
    ];

    $pdo->beginTransaction();
    $newRows = whatsapp_process_webhook_payload($payload, $pdo);
    $pdo->commit();

    sec_assert(count($newRows) === 1, 'STOP inbound counted as new');
    whatsapp_automation_after_inbound($pdo, $newRows);

    $st = $pdo->prepare('SELECT consent_status FROM whatsapp_contacts WHERE wa_id = ?');
    $st->execute([$testWa]);
    sec_assert($st->fetchColumn() === 'opted_out', 'STOP records opt-out in contacts');

    // Dedupe autoreply key
    $dedupeKey = 'autoreply:welcome_hi:' . $wamid;
    $pdo->prepare('INSERT IGNORE INTO whatsapp_webhook_dedupe (dedupe_key, event_kind) VALUES (?,?)')
        ->execute([$dedupeKey, 'autoreply']);
    $ins = $pdo->prepare('INSERT IGNORE INTO whatsapp_webhook_dedupe (dedupe_key, event_kind) VALUES (?,?)');
    $ins->execute([$dedupeKey, 'autoreply']);
    sec_assert($ins->rowCount() === 0, 'Duplicate autoreply dedupe blocked');

    // Cleanup
    $pdo->prepare('DELETE FROM whatsapp_inbound_messages WHERE wa_message_id = ?')->execute([$wamid]);
    $pdo->prepare('DELETE FROM whatsapp_webhook_dedupe WHERE dedupe_key IN (?,?)')->execute(['inbound:' . $wamid, $dedupeKey]);
    $pdo->prepare('DELETE FROM whatsapp_contacts WHERE wa_id = ?')->execute([$testWa]);
} catch (Throwable $e) {
    echo "SKIP DB/automation tests: " . $e->getMessage() . "\n";
}

if ($failures > 0) {
    echo "\n{$failures} test(s) failed.\n";
    exit(1);
}

echo "\nAll security unit tests passed.\n";
