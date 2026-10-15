<?php
declare(strict_types=1);

final class CrmMessagingWindowService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isOpen(string $recipientWaId): bool
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';

        return whatsapp_service_window_open($this->pdo, whatsapp_normalize_recipient($recipientWaId));
    }
}
