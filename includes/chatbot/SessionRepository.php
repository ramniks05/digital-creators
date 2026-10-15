<?php
declare(strict_types=1);

final class ChatbotSessionRepository
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function getOrCreate(string $peerWaId): array
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $peerWaId = whatsapp_normalize_recipient($peerWaId);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM chatbot_sessions WHERE tenant_id = ? AND peer_wa_id = ? LIMIT 1'
        );
        $stmt->execute([$this->tenantId, $peerWaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
        $this->pdo->prepare(
            'INSERT INTO chatbot_sessions (tenant_id, peer_wa_id, current_step, mode)
             VALUES (?,?,\'MAIN_MENU\',\'bot\')'
        )->execute([$this->tenantId, $peerWaId]);
        $stmt->execute([$this->tenantId, $peerWaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: [
            'tenant_id' => $this->tenantId,
            'peer_wa_id' => $peerWaId,
            'current_step' => 'MAIN_MENU',
            'mode' => 'bot',
        ];
    }

    public function save(array $session): void
    {
        $this->pdo->prepare(
            'UPDATE chatbot_sessions SET
               contact_id = ?,
               conversation_id = ?,
               current_step = ?,
               mode = ?,
               selected_service_id = ?,
               customer_name = ?,
               requirement_text = ?,
               budget_choice = ?,
               timeline_text = ?,
               lead_id = ?,
               last_inbound_wamid = ?,
               state_json = ?,
               last_activity_at = UTC_TIMESTAMP()
             WHERE tenant_id = ? AND peer_wa_id = ?'
        )->execute([
            $session['contact_id'] ?? null,
            $session['conversation_id'] ?? null,
            $session['current_step'] ?? 'MAIN_MENU',
            $session['mode'] ?? 'bot',
            $session['selected_service_id'] ?? null,
            $session['customer_name'] ?? null,
            $session['requirement_text'] ?? null,
            $session['budget_choice'] ?? null,
            $session['timeline_text'] ?? null,
            $session['lead_id'] ?? null,
            $session['last_inbound_wamid'] ?? null,
            isset($session['state_json']) ? json_encode($session['state_json'], JSON_UNESCAPED_UNICODE) : null,
            $this->tenantId,
            $session['peer_wa_id'],
        ]);
    }

    public function setMode(string $peerWaId, string $mode): void
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $peerWaId = whatsapp_normalize_recipient($peerWaId);
        $this->pdo->prepare(
            'UPDATE chatbot_sessions SET mode = ?, last_activity_at = UTC_TIMESTAMP()
             WHERE tenant_id = ? AND peer_wa_id = ?'
        )->execute([$mode === 'human' ? 'human' : 'bot', $this->tenantId, $peerWaId]);
    }
}
