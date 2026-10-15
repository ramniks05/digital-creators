<?php
declare(strict_types=1);

require_once __DIR__ . '/chatbot/config.php';
require_once __DIR__ . '/chatbot/FlowController.php';
require_once __DIR__ . '/crm/tenant.php';

/**
 * @param list<array{from:string,wamid:string,body:?string,type:string,contact_name?:?string,phone_number_id?:?string,tenant_id?:int,interactive_id?:?string}> $newInbound
 */
function whatsapp_chatbot_after_inbound(PDO $pdo, array $newInbound): void
{
    if (!chatbot_enabled() || !chatbot_tables_ready($pdo) || $newInbound === []) {
        return;
    }

    foreach ($newInbound as $row) {
        $tenantId = (int) ($row['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            $phoneNumberId = isset($row['phone_number_id']) ? (string) $row['phone_number_id'] : null;
            $tenantId = crm_webhook_resolve_tenant($pdo, $phoneNumberId);
        }
        if ($tenantId <= 0) {
            $tenantId = crm_default_tenant_id();
        }

        try {
            $controller = new ChatbotFlowController($pdo, $tenantId);
            $controller->handleInbound($row);
        } catch (Throwable $e) {
            error_log('Chatbot inbound error: ' . $e->getMessage());
        }
    }
}
