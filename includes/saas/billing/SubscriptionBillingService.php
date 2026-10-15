<?php
declare(strict_types=1);

require_once __DIR__ . '/RazorpayGateway.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__, 2) . '/crm/tenant.php';

final class SaasSubscriptionBillingService
{
    private RazorpayGateway $razorpay;

    public function __construct(private PDO $pdo)
    {
        $this->razorpay = new RazorpayGateway();
    }

    /**
     * @return array{ok:bool, error?:string, checkout?:array}
     */
    public function createSubscriptionCheckout(int $tenantId, string $planSlug, string $interval = 'month'): array
    {
        if (!$this->razorpay->isConfigured()) {
            return ['ok' => false, 'error' => 'Razorpay is not configured. Add RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET.'];
        }

        $plan = $this->loadPlan($planSlug);
        if ($plan === null) {
            return ['ok' => false, 'error' => 'Plan not found.'];
        }

        $interval = $interval === 'year' ? 'year' : 'month';
        $rzPlanId = $interval === 'year'
            ? trim((string) ($plan['razorpay_plan_id_yearly'] ?? ''))
            : trim((string) ($plan['razorpay_plan_id_monthly'] ?? ''));

        if ($rzPlanId === '') {
            return [
                'ok' => false,
                'error' => 'Razorpay Plan ID is not set for this plan. Create a plan in Razorpay Dashboard and save razorpay_plan_id_' . $interval . ' in crm_subscription_plans.',
            ];
        }

        $customer = $this->ensureRazorpayCustomer($tenantId);
        if (!$customer['ok']) {
            return $customer;
        }
        $customerId = (string) $customer['customer_id'];

        $totalCount = $interval === 'year' ? 10 : 120;
        $result = $this->razorpay->apiPost('/subscriptions', [
            'plan_id' => $rzPlanId,
            'customer_id' => $customerId,
            'total_count' => $totalCount,
            'quantity' => 1,
            'customer_notify' => 1,
            'notes' => [
                'tenant_id' => (string) $tenantId,
                'plan_slug' => $planSlug,
                'crm_plan_id' => (string) ($plan['id'] ?? ''),
            ],
        ]);

        if (!$result['ok']) {
            return ['ok' => false, 'error' => (string) ($result['error'] ?? 'Could not create subscription')];
        }

        $sub = $result['data'];
        $rzSubId = (string) ($sub['id'] ?? '');
        $this->pdo->prepare(
            'UPDATE crm_subscriptions SET razorpay_subscription_id = ?, razorpay_customer_id = ?, updated_at = UTC_TIMESTAMP()
             WHERE tenant_id = ?'
        )->execute([$rzSubId, $customerId, $tenantId]);

        $this->recordTransaction($tenantId, [
            'provider_subscription_id' => $rzSubId,
            'amount_cents' => (int) ($plan['price_cents'] ?? 0),
            'currency' => (string) ($plan['currency'] ?? 'INR'),
            'status' => 'created',
            'meta_json' => ['type' => 'subscription_checkout', 'plan_slug' => $planSlug],
        ]);

        return [
            'ok' => true,
            'checkout' => [
                'key_id' => $this->razorpay->publicKeyId(),
                'subscription_id' => $rzSubId,
                'name' => 'Digital Creatorss WhatsApp CRM',
                'description' => (string) ($plan['name'] ?? $planSlug),
                'prefill' => ['email' => (string) ($customer['email'] ?? '')],
                'notes' => ['tenant_id' => (string) $tenantId],
            ],
        ];
    }

    /**
     * Server-side confirmation after Razorpay Checkout (subscription auth payment).
     *
     * @return array{ok:bool, error?:string}
     */
    public function confirmSubscriptionPayment(
        int $tenantId,
        string $razorpayPaymentId,
        string $razorpaySubscriptionId,
        string $signature
    ): array {
        if (!$this->razorpay->verifySubscriptionSignature($razorpayPaymentId, $razorpaySubscriptionId, $signature)) {
            return ['ok' => false, 'error' => 'Invalid payment signature.'];
        }

        return $this->activateFromProviderSubscription($tenantId, $razorpaySubscriptionId, $razorpayPaymentId, 'checkout_confirm');
    }

    /**
     * @return array{ok:bool, error?:string, duplicate?:bool}
     */
    public function handleWebhook(string $rawBody, ?string $signatureHeader): array
    {
        if (!$this->razorpay->verifyWebhookSignature($rawBody, $signatureHeader)) {
            return ['ok' => false, 'error' => 'Invalid webhook signature'];
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'error' => 'Invalid JSON'];
        }

        $headerEventId = isset($_SERVER['HTTP_X_RAZORPAY_EVENT_ID']) ? trim((string) $_SERVER['HTTP_X_RAZORPAY_EVENT_ID']) : '';
        $eventId = $headerEventId !== ''
            ? $headerEventId
            : ((string) ($payload['event'] ?? '') . ':' . ($payload['payload']['payment']['entity']['id'] ?? $payload['payload']['subscription']['entity']['id'] ?? hash('sha256', $rawBody)));
        if ($this->webhookAlreadyProcessed('razorpay', $eventId)) {
            return ['ok' => true, 'duplicate' => true];
        }

        $this->storeWebhook('razorpay', substr($eventId, 0, 64), (string) ($payload['event'] ?? 'unknown'), $payload);

        try {
            $event = (string) ($payload['event'] ?? '');
            if (str_starts_with($event, 'subscription.')) {
                $entity = $payload['payload']['subscription']['entity'] ?? [];
                if (!is_array($entity)) {
                    throw new RuntimeException('Missing subscription entity');
                }
                $this->processSubscriptionEntity($entity, $event);
            } elseif ($event === 'payment.captured') {
                $pay = $payload['payload']['payment']['entity'] ?? [];
                if (is_array($pay) && !empty($pay['subscription_id'])) {
                    $tenantId = $this->tenantIdFromNotes($pay['notes'] ?? []);
                    if ($tenantId === null) {
                        $tenantId = $this->tenantIdByRazorpaySubscription((string) $pay['subscription_id']);
                    }
                    if ($tenantId !== null) {
                        $this->activateFromProviderSubscription(
                            $tenantId,
                            (string) $pay['subscription_id'],
                            (string) ($pay['id'] ?? ''),
                            $event
                        );
                    }
                }
            }
            $this->markWebhookProcessed('razorpay', substr($eventId, 0, 64));
        } catch (Throwable $e) {
            $this->markWebhookFailed('razorpay', substr($eventId, 0, 64), $e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return ['ok' => true];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function billingHistory(int $tenantId, int $limit = 50): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM crm_payment_transactions WHERE tenant_id = ? ORDER BY id DESC LIMIT ' . max(1, min(100, $limit))
            );
            $stmt->execute([$tenantId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array{ok:bool, subscription?:array, error?:string}
     */
    public function getSubscriptionSummary(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, p.slug AS plan_slug, p.name AS plan_name, p.limits_json
             FROM crm_subscriptions s
             INNER JOIN crm_subscription_plans p ON p.id = s.plan_id
             WHERE s.tenant_id = ? LIMIT 1'
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['ok' => false, 'error' => 'No subscription'];
        }

        return ['ok' => true, 'subscription' => $row];
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function processSubscriptionEntity(array $entity, string $event): void
    {
        $rzSubId = (string) ($entity['id'] ?? '');
        $notes = $entity['notes'] ?? [];
        $tenantId = $this->tenantIdFromNotes(is_array($notes) ? $notes : []);
        if ($tenantId === null) {
            $tenantId = $this->tenantIdByRazorpaySubscription($rzSubId);
        }
        if ($tenantId === null) {
            throw new RuntimeException('Cannot resolve tenant for subscription ' . $rzSubId);
        }

        $status = (string) ($entity['status'] ?? '');
        if (in_array($event, ['subscription.activated', 'subscription.resumed', 'subscription.charged'], true)
            || $status === 'active') {
            $this->activateFromProviderSubscription($tenantId, $rzSubId, null, $event);
        } elseif (in_array($event, ['subscription.pending', 'subscription.halted'], true)) {
            $this->setSubscriptionStatus($tenantId, 'past_due');
        } elseif ($event === 'subscription.cancelled') {
            $this->setSubscriptionStatus($tenantId, 'cancelled');
        }
    }

    private function activateFromProviderSubscription(
        int $tenantId,
        string $razorpaySubscriptionId,
        ?string $paymentId,
        string $source
    ): array {
        $verify = $this->razorpay->apiGet('/subscriptions/' . rawurlencode($razorpaySubscriptionId));
        if (!$verify['ok']) {
            return ['ok' => false, 'error' => 'Could not verify subscription with Razorpay.'];
        }
        $entity = $verify['data'];
        $status = (string) ($entity['status'] ?? '');
        if (!in_array($status, ['active', 'authenticated', 'completed'], true)) {
            return ['ok' => false, 'error' => 'Subscription is not active on Razorpay (status: ' . $status . ').'];
        }

        $notes = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];
        $planSlug = (string) ($notes['plan_slug'] ?? '');
        $crmPlanId = (int) ($notes['crm_plan_id'] ?? 0);
        if ($crmPlanId <= 0 && $planSlug !== '') {
            $plan = $this->loadPlan($planSlug);
            $crmPlanId = $plan !== null ? (int) $plan['id'] : 0;
        }

        $periodEnd = null;
        if (!empty($entity['current_end'])) {
            $periodEnd = gmdate('Y-m-d H:i:s', (int) $entity['current_end']);
        }

        $this->pdo->prepare(
            'UPDATE crm_subscriptions SET
               status = \'active\',
               plan_id = IF(? > 0, ?, plan_id),
               razorpay_subscription_id = ?,
               current_period_start = UTC_TIMESTAMP(),
               current_period_end = ?,
               updated_at = UTC_TIMESTAMP()
             WHERE tenant_id = ?'
        )->execute([$crmPlanId, $crmPlanId, $razorpaySubscriptionId, $periodEnd, $tenantId]);

        $this->pdo->prepare(
            'UPDATE crm_tenants SET subscription_status = \'active\', plan_slug = COALESCE(NULLIF(?, \'\'), plan_slug), updated_at = UTC_TIMESTAMP() WHERE id = ?'
        )->execute([$planSlug, $tenantId]);

        if ($paymentId !== null && $paymentId !== '') {
            $this->recordTransaction($tenantId, [
                'provider_payment_id' => $paymentId,
                'provider_subscription_id' => $razorpaySubscriptionId,
                'status' => 'captured',
                'meta_json' => ['source' => $source],
            ]);
        }

        $this->logSubscriptionEvent($tenantId, 'activated', $source, ['razorpay_subscription_id' => $razorpaySubscriptionId]);

        return ['ok' => true];
    }

    private function setSubscriptionStatus(int $tenantId, string $status): void
    {
        $allowed = ['trialing', 'active', 'past_due', 'cancelled', 'suspended'];
        if (!in_array($status, $allowed, true)) {
            return;
        }
        $this->pdo->prepare('UPDATE crm_subscriptions SET status = ?, updated_at = UTC_TIMESTAMP() WHERE tenant_id = ?')
            ->execute([$status, $tenantId]);
        $this->pdo->prepare('UPDATE crm_tenants SET subscription_status = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([$status, $tenantId]);
        if ($status === 'suspended' || $status === 'cancelled') {
            $this->pdo->prepare('UPDATE crm_tenants SET status = \'suspended\' WHERE id = ?')->execute([$tenantId]);
        }
    }

    /**
     * @return array{ok:bool, customer_id?:string, email?:string, error?:string}
     */
    private function ensureRazorpayCustomer(int $tenantId): array
    {
        $existing = $this->pdo->prepare(
            'SELECT provider_customer_id FROM crm_payment_customers WHERE tenant_id = ? AND provider = \'razorpay\' LIMIT 1'
        );
        $existing->execute([$tenantId]);
        $cid = $existing->fetchColumn();
        if ($cid !== false) {
            $email = $this->pdo->prepare('SELECT business_email FROM crm_tenant_settings WHERE tenant_id = ?');
            $email->execute([$tenantId]);

            return ['ok' => true, 'customer_id' => (string) $cid, 'email' => (string) ($email->fetchColumn() ?: '')];
        }

        $settings = $this->pdo->prepare('SELECT business_email FROM crm_tenant_settings WHERE tenant_id = ?');
        $settings->execute([$tenantId]);
        $email = trim((string) ($settings->fetchColumn() ?: ''));
        if ($email === '') {
            return ['ok' => false, 'error' => 'Business email is required before checkout. Update tenant settings.'];
        }

        $tenantName = $this->pdo->prepare('SELECT name FROM crm_tenants WHERE id = ?');
        $tenantName->execute([$tenantId]);
        $name = (string) ($tenantName->fetchColumn() ?: 'CRM Customer');

        $created = $this->razorpay->apiPost('/customers', [
            'name' => substr($name, 0, 120),
            'email' => $email,
            'notes' => ['tenant_id' => (string) $tenantId],
        ]);
        if (!$created['ok']) {
            return ['ok' => false, 'error' => (string) ($created['error'] ?? 'Could not create Razorpay customer')];
        }
        $customerId = (string) ($created['data']['id'] ?? '');
        $this->pdo->prepare(
            'INSERT INTO crm_payment_customers (tenant_id, provider, provider_customer_id, email) VALUES (?,?,?,?)'
        )->execute([$tenantId, 'razorpay', $customerId, $email]);

        return ['ok' => true, 'customer_id' => $customerId, 'email' => $email];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadPlan(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM crm_subscription_plans WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([preg_replace('/[^a-z0-9_-]/', '', strtolower($slug))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function recordTransaction(int $tenantId, array $fields): void
    {
        try {
            $this->pdo->prepare(
                'INSERT INTO crm_payment_transactions
                 (tenant_id, provider, provider_payment_id, provider_order_id, provider_subscription_id, amount_cents, currency, status, meta_json)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([
                $tenantId,
                'razorpay',
                $fields['provider_payment_id'] ?? null,
                $fields['provider_order_id'] ?? null,
                $fields['provider_subscription_id'] ?? null,
                (int) ($fields['amount_cents'] ?? 0),
                (string) ($fields['currency'] ?? 'INR'),
                (string) ($fields['status'] ?? 'created'),
                isset($fields['meta_json']) ? json_encode($fields['meta_json'], JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $e) {
            error_log('crm_payment_transactions: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $notes
     */
    private function tenantIdFromNotes(array $notes): ?int
    {
        $tid = (int) ($notes['tenant_id'] ?? 0);

        return $tid > 0 ? $tid : null;
    }

    private function tenantIdByRazorpaySubscription(string $rzSubId): ?int
    {
        if ($rzSubId === '') {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT tenant_id FROM crm_subscriptions WHERE razorpay_subscription_id = ? LIMIT 1');
        $stmt->execute([$rzSubId]);
        $tid = $stmt->fetchColumn();

        return $tid !== false ? (int) $tid : null;
    }

    private function webhookAlreadyProcessed(string $provider, string $eventId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM crm_billing_webhook_events WHERE provider = ? AND event_id = ? AND processed_at IS NOT NULL LIMIT 1'
            );
            $stmt->execute([$provider, $eventId]);

            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function storeWebhook(string $provider, string $eventId, string $eventType, array $payload): void
    {
        $this->pdo->prepare(
            'INSERT IGNORE INTO crm_billing_webhook_events (provider, event_id, event_type, payload_json)
             VALUES (?,?,?,?)'
        )->execute([
            $provider,
            $eventId,
            substr($eventType, 0, 64),
            json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function markWebhookProcessed(string $provider, string $eventId): void
    {
        $this->pdo->prepare(
            'UPDATE crm_billing_webhook_events SET processed_at = UTC_TIMESTAMP(), process_error = NULL
             WHERE provider = ? AND event_id = ?'
        )->execute([$provider, $eventId]);
    }

    private function markWebhookFailed(string $provider, string $eventId, string $error): void
    {
        $this->pdo->prepare(
            'UPDATE crm_billing_webhook_events SET process_error = ? WHERE provider = ? AND event_id = ?'
        )->execute([substr($error, 0, 500), $provider, $eventId]);
    }

    private function logSubscriptionEvent(int $tenantId, string $type, string $providerEventId, ?array $meta = null): void
    {
        try {
            $this->pdo->prepare(
                'INSERT INTO crm_subscription_events (tenant_id, event_type, provider_event_id, meta_json)
                 VALUES (?,?,?,?)'
            )->execute([
                $tenantId,
                substr($type, 0, 64),
                substr($providerEventId, 0, 64),
                $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $e) {
            // ignore duplicate provider_event_id
        }
    }
}
