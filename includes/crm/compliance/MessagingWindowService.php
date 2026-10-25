<?php
declare(strict_types=1);

final class CrmMessagingWindowService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function isOpen(string $recipientWaId): bool
    {
        return false;
    }
}
