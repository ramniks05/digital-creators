<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();
require_once __DIR__ . '/layout.php';

crm_app_header('Leads', 'leads', [
    ['label' => 'Dashboard', 'href' => 'index.php'],
    ['label' => 'Leads'],
]);
?>
<div class="crm-panel crm-stub-page">
  <h2 class="crm-panel-title">Sales pipeline</h2>
  <p style="color:var(--crm-text-muted)">
    The Kanban lead pipeline (<code>crm_leads</code>, stages, drag-and-drop) is <strong>not implemented</strong> in the backend yet.
    This page intentionally shows no sample cards.
  </p>
  <ul>
    <li><a href="../leads.php">Website enquiry leads</a> — existing admin table for form submissions.</li>
    <li><a href="contacts/index.php">CRM contacts</a> — WhatsApp-aligned contact records.</li>
  </ul>
  <p style="margin-top:24px">
    <a class="crm-btn crm-btn-primary" href="live.php">Work leads from inbox</a>
  </p>
</div>
<?php crm_app_footer(); ?>
