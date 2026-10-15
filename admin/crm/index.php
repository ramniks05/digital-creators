<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();

require_once dirname(__DIR__, 2) . '/includes/crm/conversations.php';
require_once __DIR__ . '/layout.php';

$pdo = db();
$tenantId = crm_current_tenant_id();
$ready = crm_tables_ready($pdo);

crm_app_header('Dashboard', 'dashboard');

if (!$ready): ?>
  <div class="crm-alert crm-alert-error">
    CRM tables are not installed. Run on server:
    <code>php sql/migrate_crm_foundation.php</code>
  </div>
  <?php crm_app_footer(); exit; endif;

$metrics = crm_dashboard_metrics($pdo, $tenantId);
$recent = crm_list_conversations($pdo, $tenantId, null, null, 10);
$waHealth = ['configured' => false, 'webhook' => false];
try {
    require_once dirname(__DIR__, 2) . '/includes/whatsapp_cloud.php';
    $cfg = whatsapp_cloud_config();
    $waHealth['configured'] = $cfg['access_token'] !== '' && $cfg['phone_number_id'] !== '';
    $waHealth['webhook'] = whatsapp_webhook_verify_token_configured() && whatsapp_app_secret_configured();
} catch (Throwable $e) {
    /* ignore */
}
?>
<?php if (!$waHealth['configured']): ?>
  <div class="crm-alert crm-alert-warning" role="alert">
    WhatsApp Cloud API is not fully configured. Outbound messaging and template sync may fail until credentials are set on the server.
  </div>
<?php elseif (!$waHealth['webhook']): ?>
  <div class="crm-alert crm-alert-warning" role="alert">
    Webhook verify token or app secret is missing. Inbound messages may not be verified correctly.
  </div>
<?php endif; ?>
<?php if ((int) ($metrics['conversations_unassigned'] ?? 0) > 0): ?>
  <div class="crm-alert crm-alert-warning" role="alert">
    <?= (int) $metrics['conversations_unassigned'] ?> open conversation(s) are unassigned.
    <a href="live.php">Review inbox</a>
  </div>
<?php endif; ?>
<?php if ((int) ($metrics['unread_total'] ?? 0) > 0): ?>
  <div class="crm-alert crm-alert-warning" role="alert">
    You have <?= (int) $metrics['unread_total'] ?> unread message(s) across conversations.
    <a href="live.php">Open inbox</a>
  </div>
<?php endif; ?>

<div class="crm-kpi-grid">
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">Open conversations</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['conversations_open'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">Unread messages</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['unread_total'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">Pending</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['conversations_pending'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">Unassigned</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['conversations_unassigned'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">CRM contacts</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['contacts'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">Website leads (new)</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['leads_new'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">WA inbound today</div>
    <div class="crm-kpi-value"><?= (int) ($metrics['inbound_today'] ?? 0) ?></div>
  </div>
  <div class="crm-kpi-card">
    <div class="crm-kpi-label">WhatsApp connection</div>
    <div class="crm-kpi-value" style="font-size:1.1rem;color:<?= $waHealth['configured'] ? 'var(--crm-success)' : 'var(--crm-warning)' ?>">
      <?= $waHealth['configured'] ? 'Configured' : 'Not configured' ?>
    </div>
  </div>
</div>

<div class="crm-panel" style="margin-bottom:var(--crm-space-4)">
  <h2 class="crm-panel-title">Recent conversations</h2>
  <?php if ($recent === []): ?>
    <div class="crm-empty">
      <p class="crm-empty-title">No conversations yet</p>
      <p>Inbound WhatsApp traffic will appear here after webhook activity.</p>
    </div>
  <?php else: ?>
  <div class="crm-table-wrap">
    <table class="crm-table">
      <thead>
        <tr>
          <th>Contact</th>
          <th>Preview</th>
          <th>Status</th>
          <th>Unread</th>
          <th>Last activity</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $row): ?>
          <?php
          $name = trim((string) ($row['contact_name'] ?? ''));
          $peer = (string) ($row['peer_wa_id'] ?? '');
          $title = $name !== '' ? $name : $peer;
          ?>
        <tr>
          <td><strong><?= e($title) ?></strong><br><span style="color:var(--crm-text-muted);font-size:var(--crm-text-xs)"><?= e($peer) ?></span></td>
          <td><?= e((string) ($row['last_message_preview'] ?? '—')) ?></td>
          <td><span class="crm-chip"><?= e((string) ($row['status'] ?? '')) ?></span></td>
          <td><?= (int) ($row['unread_count'] ?? 0) ?></td>
          <td><?= e((string) ($row['last_message_at'] ?? '')) ?></td>
          <td><a class="crm-btn crm-btn-sm crm-btn-secondary" href="live.php?conversation=<?= (int) ($row['id'] ?? 0) ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="crm-panel">
  <h2 class="crm-panel-title">Quick actions</h2>
  <p style="color:var(--crm-text-muted);margin-top:0">Shared team inbox, contacts, and legacy WhatsApp tools remain available.</p>
  <p style="display:flex;flex-wrap:wrap;gap:8px">
    <a class="crm-btn crm-btn-primary" href="live.php">Open inbox</a>
    <a class="crm-btn crm-btn-secondary" href="contacts/index.php">Contacts</a>
    <a class="crm-btn crm-btn-secondary" href="inbox.php">Classic team inbox</a>
    <a class="crm-btn crm-btn-secondary" href="../whatsapp.php?view=chats">Classic WhatsApp admin</a>
    <a class="crm-btn crm-btn-secondary" href="leads.php">Leads</a>
  </p>
</div>
<?php crm_app_footer(); ?>
