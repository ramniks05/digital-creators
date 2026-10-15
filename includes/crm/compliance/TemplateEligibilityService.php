<?php
declare(strict_types=1);

final class CrmTemplateEligibilityService
{
    public function isApproved(string $templateName, ?string $language = null): bool
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';
        $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
        if ($templateName === '') {
            return false;
        }
        $lang = $language !== null && $language !== ''
            ? $language
            : whatsapp_default_template_language();
        $def = whatsapp_lookup_template_definition($templateName, $lang);

        return $def !== null && strtoupper((string) ($def['status'] ?? '')) === 'APPROVED';
    }
}
