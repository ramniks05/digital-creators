<?php
declare(strict_types=1);

final class ChatbotHandoffService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function requestHandoff(int $conversationId): int
    {
        $this->pdo->prepare(
            'INSERT INTO agent_handoffs (tenant_id, conversation_id, status) VALUES (?,?,\'waiting\')'
        )->execute([$this->tenantId, $conversationId]);

        return (int) $this->pdo->lastInsertId();
    }

    public function assign(int $conversationId, int $adminId): void
    {
        require_once dirname(__DIR__) . '/crm/conversations.php';
        crm_update_conversation($this->pdo, $this->tenantId, $conversationId, null, $adminId > 0 ? $adminId : null);
        $find = $this->pdo->prepare(
            'SELECT id FROM agent_handoffs WHERE tenant_id = ? AND conversation_id = ?
             AND status IN (\'waiting\',\'assigned\') ORDER BY id DESC LIMIT 1'
        );
        $find->execute([$this->tenantId, $conversationId]);
        $handoffId = $find->fetchColumn();
        if ($handoffId !== false) {
            $this->pdo->prepare(
                'UPDATE agent_handoffs SET status = \'assigned\', assigned_admin_id = ?, assigned_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$adminId > 0 ? $adminId : null, (int) $handoffId]);
        }
    }

    public function close(int $conversationId): void
    {
        $this->pdo->prepare(
            'UPDATE agent_handoffs SET status = \'closed\', closed_at = UTC_TIMESTAMP()
             WHERE tenant_id = ? AND conversation_id = ? AND status != \'closed\''
        )->execute([$this->tenantId, $conversationId]);
    }

    public function activeHandoff(int $conversationId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM agent_handoffs WHERE tenant_id = ? AND conversation_id = ?
             AND status IN (\'waiting\',\'assigned\') ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$this->tenantId, $conversationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
