<?php
declare(strict_types=1);

function chatbot_enabled(): bool
{
    static $on = null;
    if ($on !== null) {
        return $on;
    }
    $config = require dirname(__DIR__) . '/config.php';
    $cb = $config['whatsapp']['chatbot'] ?? [];
    $on = filter_var($cb['enabled'] ?? getenv('WHATSAPP_CHATBOT_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN);

    return $on;
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
