<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SessionRepository.php';
require_once __DIR__ . '/HandoffService.php';
require_once __DIR__ . '/LeadCaptureService.php';
require_once __DIR__ . '/WhatsAppMessageSender.php';
require_once dirname(__DIR__) . '/crm/conversations.php';
require_once dirname(__DIR__) . '/crm/messages.php';

final class ChatbotFlowController
{
    private ChatbotSessionRepository $sessions;
    private ChatbotHandoffService $handoffs;
    private ChatbotLeadCaptureService $leads;
    private ChatbotWhatsAppMessageSender $sender;

    public function __construct(private PDO $pdo, private int $tenantId)
    {
        $this->sessions = new ChatbotSessionRepository($pdo, $tenantId);
        $this->handoffs = new ChatbotHandoffService($pdo, $tenantId);
        $this->leads = new ChatbotLeadCaptureService($pdo, $tenantId);
        $this->sender = new ChatbotWhatsAppMessageSender($pdo, $tenantId);
    }

    /**
     * @param array{from:string,wamid:string,body:?string,type:string,contact_name?:?string,tenant_id?:int} $row
     */
    public function handleInbound(array $row): bool
    {
        if (!chatbot_enabled() || !chatbot_tables_ready($this->pdo)) {
            return false;
        }

        $from = (string) ($row['from'] ?? '');
        $wamid = (string) ($row['wamid'] ?? '');
        if ($from === '' || $wamid === '') {
            return false;
        }

        require_once dirname(__DIR__) . '/whatsapp_contacts.php';
        if (whatsapp_contact_is_opted_out($this->pdo, $from)) {
            return false;
        }

        $contactName = isset($row['contact_name']) ? (string) $row['contact_name'] : null;
        $contactId = crm_touch_contact($this->pdo, $this->tenantId, $from, $contactName);
        $convId = crm_conversation_id_for_peer($this->pdo, $this->tenantId, $from);

        $session = $this->sessions->getOrCreate($from);
        if (($session['last_inbound_wamid'] ?? '') === $wamid) {
            return true;
        }

        $session['peer_wa_id'] = $from;
        $session['contact_id'] = $contactId;
        $session['conversation_id'] = $convId;
        $session['last_inbound_wamid'] = $wamid;

        if (($session['mode'] ?? 'bot') === 'human') {
            $this->sessions->save($session);
            if (is_file(dirname(__DIR__) . '/whatsapp_webhook_diagnostics.php')) {
                require_once dirname(__DIR__) . '/whatsapp_webhook_diagnostics.php';
                whatsapp_log_event('chatbot_skipped', [
                    'reason' => 'human_mode',
                    'from_masked' => whatsapp_mask_wa_id($from),
                ]);
            }

            return true;
        }

        $action = $this->resolveAction($row);
        if ($action === 'TALK_TO_AGENT') {
            $this->doHandoff($session, $from, $wamid);

            return true;
        }
        if ($action === 'MAIN_MENU') {
            $session['current_step'] = 'MAIN_MENU';
            $this->sessions->save($session);
            $this->showMainMenu($from, $wamid);

            return true;
        }

        $step = (string) ($session['current_step'] ?? 'MAIN_MENU');

        if ($step === 'MAIN_MENU') {
            if ($action === 'GET_BROCHURE') {
                $this->sendBrochure($from, $wamid);
                $session['current_step'] = 'MAIN_MENU';
                $this->sessions->save($session);
                $this->showPostBrochureMenu($from, $wamid . '-menu');

                return true;
            }
            if ($action === 'EXPLORE_SERVICES') {
                $this->showServiceList($from, $wamid);
                $session['current_step'] = 'SELECT_SERVICE';
                $this->sessions->save($session);

                return true;
            }
            if ($action === null) {
                $this->sessions->save($session);
                $this->showMainMenu($from, $wamid);

                return true;
            }
        }

        if ($step === 'SELECT_SERVICE') {
            $serviceId = $this->parseServiceId($action);
            if ($serviceId === null) {
                $this->repeatStep($from, $wamid, 'Please pick a service from the list.', fn () => $this->showServiceList($from, $wamid . '-r'));

                return true;
            }
            $session['selected_service_id'] = $serviceId;
            $session['current_step'] = 'ASK_NAME';
            $this->sessions->save($session);
            $this->sender->sendText(
                $from,
                "Great choice! What is your name?",
                'cb-ask-name-' . $wamid
            );

            return true;
        }

        if ($step === 'ASK_NAME') {
            $name = $this->textAnswer($row, $action);
            if ($name === null) {
                $this->repeatStep($from, $wamid, 'Please send your name as a text message.');

                return true;
            }
            $session['customer_name'] = $name;
            $session['current_step'] = 'ASK_REQUIREMENT';
            $this->sessions->save($session);
            $this->sender->sendText(
                $from,
                'Thanks, ' . $name . ". Briefly describe your requirement:",
                'cb-ask-req-' . $wamid
            );

            return true;
        }

        if ($step === 'ASK_REQUIREMENT') {
            $req = $this->textAnswer($row, $action);
            if ($req === null) {
                $this->repeatStep($from, $wamid, 'Please describe what you need in a short message.');

                return true;
            }
            $session['requirement_text'] = $req;
            $session['current_step'] = 'ASK_BUDGET';
            $this->sessions->save($session);
            $this->showBudgetButtons($from, $wamid);

            return true;
        }

        if ($step === 'ASK_BUDGET') {
            $budget = $this->parseBudget($action, $row);
            if ($budget === null) {
                $this->repeatStep($from, $wamid, 'Please choose a budget option.', fn () => $this->showBudgetButtons($from, $wamid . '-r'));

                return true;
            }
            $session['budget_choice'] = $budget;
            $session['current_step'] = 'ASK_TIMELINE';
            $this->sessions->save($session);
            $this->sender->sendText(
                $from,
                'When do you want to start or launch? (e.g. "ASAP", "next month")',
                'cb-ask-time-' . $wamid
            );

            return true;
        }

        if ($step === 'ASK_TIMELINE') {
            $timeline = $this->textAnswer($row, $action);
            if ($timeline === null) {
                $this->repeatStep($from, $wamid, 'Please share your preferred timeline as text.');

                return true;
            }
            $session['timeline_text'] = $timeline;
            $serviceTitle = $this->serviceTitle((int) ($session['selected_service_id'] ?? 0));
            if (($session['lead_id'] ?? null) === null) {
                $leadId = $this->leads->createFromSession($session, $serviceTitle, $wamid);
                $session['lead_id'] = $leadId;
            }
            $session['current_step'] = 'DONE';
            $this->sessions->save($session);
            $this->sender->sendText(
                $from,
                "Thank you! We've saved your enquiry for *{$serviceTitle}*. Our team will follow up soon.\n\n"
                . "Reply *menu* anytime to explore more, or *agent* to talk to a person.",
                'cb-done-' . $wamid
            );

            return true;
        }

        if ($step === 'DONE') {
            $this->showMainMenu($from, $wamid);

            return true;
        }

        $session['current_step'] = 'MAIN_MENU';
        $this->sessions->save($session);
        $this->showMainMenu($from, $wamid);

        return true;
    }

    /**
     * @param array{from:string,wamid:string,body:?string,type:string,interactive_id?:?string} $row
     */
    private function resolveAction(array $row): ?string
    {
        $id = isset($row['interactive_id']) ? trim((string) $row['interactive_id']) : '';
        if ($id !== '') {
            return $id;
        }
        $body = strtolower(trim((string) ($row['body'] ?? '')));
        if ($body === '') {
            return null;
        }
        if (in_array($body, ['menu', 'main menu', 'start', 'hi', 'hello'], true)) {
            return 'MAIN_MENU';
        }
        if (str_contains($body, 'brochure') || $body === 'pdf') {
            return 'GET_BROCHURE';
        }
        if (str_contains($body, 'agent') || str_contains($body, 'human') || str_contains($body, 'talk to')) {
            return 'TALK_TO_AGENT';
        }
        if (str_contains($body, 'service') || $body === 'explore') {
            return 'EXPLORE_SERVICES';
        }

        return null;
    }

    private function textAnswer(array $row, ?string $action): ?string
    {
        if ($action !== null && in_array($action, ['MAIN_MENU', 'GET_BROCHURE', 'EXPLORE_SERVICES', 'TALK_TO_AGENT'], true)) {
            return null;
        }
        if ($action !== null && (str_starts_with($action, 'SVC_') || str_starts_with($action, 'BUDGET_'))) {
            return null;
        }
        $body = trim((string) ($row['body'] ?? ''));
        if ($body === '') {
            return null;
        }
        if (strlen($body) > 2000) {
            $body = substr($body, 0, 2000);
        }

        return $body;
    }

    private function parseServiceId(?string $action): ?int
    {
        if ($action === null || !str_starts_with($action, 'SVC_')) {
            return null;
        }
        $id = (int) substr($action, 4);
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id FROM chatbot_services WHERE tenant_id = ? AND id = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$this->tenantId, $id]);

        return $stmt->fetchColumn() !== false ? $id : null;
    }

    private function parseBudget(?string $action, array $row): ?string
    {
        $map = [
            'BUDGET_UNDER_50K' => 'Under ₹50,000',
            'BUDGET_50_100' => '₹50,000 – ₹1,00,000',
            'BUDGET_100_PLUS' => 'Above ₹1,00,000',
            'BUDGET_NOT_SURE' => 'Not sure yet',
        ];
        if ($action !== null && isset($map[$action])) {
            return $map[$action];
        }
        $body = strtolower(trim((string) ($row['body'] ?? '')));
        if ($body === 'not sure' || str_contains($body, 'not sure')) {
            return $map['BUDGET_NOT_SURE'];
        }

        return null;
    }

    private function serviceTitle(int $serviceId): string
    {
        if ($serviceId <= 0) {
            return 'General enquiry';
        }
        $stmt = $this->pdo->prepare(
            'SELECT title FROM chatbot_services WHERE tenant_id = ? AND id = ? LIMIT 1'
        );
        $stmt->execute([$this->tenantId, $serviceId]);
        $t = $stmt->fetchColumn();

        return is_string($t) && $t !== '' ? $t : 'General enquiry';
    }

    private function showMainMenu(string $phone, string $wamid): void
    {
        $ok = $this->sender->sendButtons(
            $phone,
            "Welcome to Digital Creatorss!\nHow can we help you today?",
            [
                ['id' => 'GET_BROCHURE', 'title' => 'Get brochure'],
                ['id' => 'EXPLORE_SERVICES', 'title' => 'Our services'],
                ['id' => 'TALK_TO_AGENT', 'title' => 'Talk to agent'],
            ],
            'cb-menu-' . $wamid
        );
        if (!$ok) {
            $this->sender->sendText(
                $phone,
                "Welcome to Digital Creatorss!\n\n"
                . "Reply with:\n"
                . "• *brochure* — company PDF\n"
                . "• *services* — our offerings\n"
                . "• *agent* — talk to our team\n"
                . "• *menu* — show this again",
                'cb-menu-text-' . $wamid
            );
        }
    }

    private function showPostBrochureMenu(string $phone, string $key): void
    {
        $this->sender->sendButtons(
            $phone,
            'Would you like to explore our services or speak with our team?',
            [
                ['id' => 'EXPLORE_SERVICES', 'title' => 'Our services'],
                ['id' => 'TALK_TO_AGENT', 'title' => 'Talk to agent'],
                ['id' => 'MAIN_MENU', 'title' => 'Main menu'],
            ],
            'cb-post-bro-' . $key
        );
    }

    private function showServiceList(string $phone, string $wamid): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, description FROM chatbot_services
             WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 10'
        );
        $stmt->execute([$this->tenantId]);
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $svc) {
            $rows[] = [
                'id' => 'SVC_' . (int) $svc['id'],
                'title' => (string) $svc['title'],
                'description' => (string) ($svc['description'] ?? ''),
            ];
        }
        if ($rows === []) {
            $this->sender->sendText($phone, 'Services are being updated. Please choose *Talk to agent* from the menu.', 'cb-no-svc-' . $wamid);

            return;
        }
        $this->sender->sendServiceList(
            $phone,
            'Pick the service that best matches your need:',
            $rows,
            'cb-svc-list-' . $wamid
        );
    }

    private function showBudgetButtons(string $phone, string $wamid): void
    {
        $this->sender->sendButtons(
            $phone,
            'What is your approximate budget?',
            [
                ['id' => 'BUDGET_UNDER_50K', 'title' => 'Under ₹50K'],
                ['id' => 'BUDGET_50_100', 'title' => '₹50K–₹1L'],
                ['id' => 'BUDGET_NOT_SURE', 'title' => 'Not sure'],
            ],
            'cb-budget-' . $wamid
        );
    }

    /**
     * @param callable():void|null $resend
     */
    private function repeatStep(string $phone, string $wamid, string $hint, ?callable $resend = null): void
    {
        $this->sender->sendText($phone, $hint . "\n\nReply *menu* for options or *agent* for our team.", 'cb-hint-' . $wamid);
        if ($resend !== null) {
            $resend();
        }
    }

    private function sendBrochure(string $phone, string $wamid): void
    {
        require_once __DIR__ . '/BrochureAssetService.php';
        $brochure = new ChatbotBrochureAssetService($this->pdo, $this->tenantId);
        $mediaId = $brochure->resolveMediaId();
        if ($mediaId === null) {
            $mediaId = $brochure->refreshUpload();
        }
        if ($mediaId === null) {
            $this->sender->sendText(
                $phone,
                'Our brochure is being prepared. Please add the PDF on the server (see assets/uploads/brochures/) or choose *Talk to agent*.',
                'cb-bro-miss-' . $wamid
            );

            return;
        }
        $filename = $brochure->filename();
        $ok = $this->sender->sendDocument($phone, $mediaId, $filename, 'Digital Creatorss — company brochure');
        if (!$ok) {
            $mediaId = $brochure->refreshUpload();
            if ($mediaId !== null) {
                $this->sender->sendDocument($phone, $mediaId, $filename, 'Digital Creatorss — company brochure');
            }
        }
    }

    /**
     * @param array<string, mixed> $session
     */
    private function doHandoff(array $session, string $phone, string $wamid): void
    {
        $convId = (int) ($session['conversation_id'] ?? 0);
        if ($convId <= 0) {
            $convId = (int) (crm_conversation_id_for_peer($this->pdo, $this->tenantId, $phone) ?? 0);
        }
        if ($convId > 0) {
            $this->handoffs->requestHandoff($convId);
            crm_update_conversation($this->pdo, $this->tenantId, $convId, 'pending', null);
        }
        $session['mode'] = 'human';
        $this->sessions->save($session);
        $this->sender->sendText(
            $phone,
            "Your conversation has been added to our team's queue. An agent will reply during business hours.",
            'cb-handoff-' . $wamid
        );
    }

    public function resumeBot(string $peerWaId): void
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $peerWaId = whatsapp_normalize_recipient($peerWaId);
        $this->sessions->setMode($peerWaId, 'bot');
    }

    public function pauseBot(string $peerWaId): void
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $peerWaId = whatsapp_normalize_recipient($peerWaId);
        $this->sessions->setMode($peerWaId, 'human');
    }

    public function sessionForConversation(int $conversationId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.* FROM chatbot_sessions s
             INNER JOIN crm_conversations c ON c.peer_wa_id = s.peer_wa_id AND c.tenant_id = s.tenant_id
             WHERE s.tenant_id = ? AND c.id = ? LIMIT 1'
        );
        $stmt->execute([$this->tenantId, $conversationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
