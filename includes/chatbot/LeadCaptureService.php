<?php
declare(strict_types=1);

final class ChatbotLeadCaptureService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    /**
     * @param array<string, mixed> $session
     */
    public function createFromSession(array $session, string $serviceTitle, ?string $inboundWamid): int
    {
        $name = trim((string) ($session['customer_name'] ?? 'WhatsApp lead'));
        $phone = (string) ($session['peer_wa_id'] ?? '');
        $requirement = trim((string) ($session['requirement_text'] ?? ''));
        $budget = trim((string) ($session['budget_choice'] ?? ''));
        $timeline = trim((string) ($session['timeline_text'] ?? ''));
        $message = "Requirement: {$requirement}\nTimeline: {$timeline}";

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO leads (name, email, phone, service, budget, message, status, source, consent_at, wa_message_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, \'new\', \'whatsapp\', UTC_TIMESTAMP(), ?, UTC_TIMESTAMP())'
            );
            $stmt->execute([
                substr($name, 0, 120),
                '',
                $phone,
                substr($serviceTitle, 0, 150),
                substr($budget, 0, 100),
                $message,
                $inboundWamid !== null && $inboundWamid !== '' ? substr($inboundWamid, 0, 128) : null,
            ]);
        } catch (Throwable $e) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO leads (name, email, phone, service, budget, message, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, \'new\', UTC_TIMESTAMP())'
            );
            $stmt->execute([
                substr($name, 0, 120),
                '',
                $phone,
                substr($serviceTitle, 0, 150),
                substr($budget, 0, 100),
                $message,
            ]);
        }

        return (int) $this->pdo->lastInsertId();
    }
}
