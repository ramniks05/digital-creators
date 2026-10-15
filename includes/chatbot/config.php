<?php
declare(strict_types=1);

function chatbot_enabled_raw(array $config): mixed
{
    $cb = $config['whatsapp']['chatbot'] ?? [];
    if (array_key_exists('enabled', $cb)) {
        return $cb['enabled'];
    }
    // Common mistake: chatbot nested under business_enquiry in private config
    $nested = $config['whatsapp']['business_enquiry']['chatbot']['enabled'] ?? null;
    if ($nested !== null) {
        return $nested;
    }

    return getenv('WHATSAPP_CHATBOT_ENABLED') ?: '0';
}

function chatbot_parse_enabled(mixed $raw): bool
{
    if (is_bool($raw)) {
        return $raw;
    }
    if (is_int($raw)) {
        return $raw === 1;
    }

    return filter_var((string) $raw, FILTER_VALIDATE_BOOLEAN);
}

function chatbot_enabled(): bool
{
    static $on = null;
    if ($on !== null) {
        return $on;
    }
    $config = require dirname(__DIR__) . '/config.php';
    $on = chatbot_parse_enabled(chatbot_enabled_raw($config));

    return $on;
}

/**
 * @return array{enabled:bool, source:string, private_config:?string, misplaced:bool}
 */
function chatbot_config_status(): array
{
    $candidates = [
        dirname(__DIR__, 2) . '/private/digital_creators_config.php',
        dirname(__DIR__) . '/../private/digital_creators_config.php',
        dirname(__DIR__) . '/private/digital_creators_config.php',
        dirname(__DIR__) . '/config.local.php',
    ];
    $private = null;
    foreach ($candidates as $path) {
        if (is_file($path) && is_readable($path)) {
            $private = $path;
            break;
        }
    }

    $config = require dirname(__DIR__) . '/config.php';
    $cb = $config['whatsapp']['chatbot'] ?? [];
    $misplaced = array_key_exists('enabled', $config['whatsapp']['business_enquiry']['chatbot'] ?? [])
        && !array_key_exists('enabled', $cb);
    $source = 'default/env';
    if (array_key_exists('enabled', $cb)) {
        $source = 'whatsapp.chatbot.enabled';
    } elseif ($misplaced) {
        $source = 'whatsapp.business_enquiry.chatbot.enabled (move to whatsapp.chatbot)';
    } elseif (getenv('WHATSAPP_CHATBOT_ENABLED')) {
        $source = 'WHATSAPP_CHATBOT_ENABLED env';
    }

    return [
        'enabled' => chatbot_parse_enabled(chatbot_enabled_raw($config)),
        'source' => $source,
        'private_config' => $private,
        'misplaced' => $misplaced,
    ];
}

function chatbot_tables_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM chatbot_sessions LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}
