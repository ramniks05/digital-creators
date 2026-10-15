<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_admin();
require_once dirname(__DIR__, 2) . '/includes/crm/conversations.php';
require_once __DIR__ . '/../layout.php';

$pdo = db();
$tenantId = crm_current_tenant_id();
$ready = crm_tables_ready($pdo);

admin_header('CRM Dashboard', 'crm/index.php');

if (!$ready): ?>
  <div class="alert alert-error">
    CRM tables are not installed. Run on server:
    <code>php sql/migrate_crm_foundation.php</code>
  </div>
  <?php admin_footer(); exit; endif;

$metrics = crm_dashboard_metrics($pdo, $tenantId);
?>
<div class="cards">
  <div class="card"><div class="label">CRM contacts</div><div class="value"><?= (int) ($metrics['contacts'] ?? 0) ?></div></div>
  <div class="card"><div class="label">Open chats</div><div class="value"><?= (int) ($metrics['conversations_open'] ?? 0) ?></div></div>
  <div class="card"><div class="label">Pending</div><div class="value"><?= (int) ($metrics['conversations_pending'] ?? 0) ?></div></div>
  <div class="card"><div class="label">Unassigned</div><div class="value"><?= (int) ($metrics['conversations_unassigned'] ?? 0) ?></div></div>
  <div class="card"><div class="label">Unread</div><div class="value"><?= (int) ($metrics['unread_total'] ?? 0) ?></div></div>
  <div class="card"><div class="label">New leads</div><div class="value"><?= (int) ($metrics['leads_new'] ?? 0) ?></div></div>
  <div class="card"><div class="label">WA inbound today</div><div class="value"><?= (int) ($metrics['inbound_today'] ?? 0) ?></div></div>
</div>

<div class="panel" style="margin-top:16px">
  <h2 style="margin-top:0">WhatsApp CRM</h2>
  <p class="muted">Shared team inbox with assignment, status, and contact sync. Existing webhook and <code>admin/whatsapp.php</code> chat are preserved.</p>
  <p>
    <a class="btn" href="inbox.php">Open team inbox</a>
    <a class="btn btn-secondary" href="../whatsapp.php?view=chats">Classic WhatsApp admin</a>
    <a class="btn btn-secondary" href="../leads.php">Leads</a>
  </p>
</div>
<?php admin_footer(); ?>
