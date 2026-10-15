<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();

$pdo = db();
$assignAdmins = [];
try {
    $assignAdmins = $pdo->query('SELECT id, username FROM admins ORDER BY username ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $assignAdmins = [];
}

require_once __DIR__ . '/layout.php';

crm_app_header('Inbox', 'inbox', [], [
    'flush' => true,
    'extra_css' => ['crm-inbox.css'],
    'extra_js' => ['crm-inbox.js'],
]);
?>
<div class="crm-inbox-app" role="application" aria-label="WhatsApp shared inbox">
  <div class="crm-inbox-status-bar" id="crm-wa-status-bar" aria-live="polite">
    <span class="crm-wa-status-dot" id="crm-wa-status-dot" aria-hidden="true"></span>
    <span id="crm-wa-status-text">Checking WhatsApp connection…</span>
  </div>
  <div class="crm-inbox-mobile-bar">
    <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" id="crm-inbox-mobile-back" hidden aria-label="Back to conversations">← Back</button>
    <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" id="crm-inbox-mobile-details" hidden aria-label="Customer details">Details</button>
    <span id="crm-inbox-mobile-title" class="crm-conv-name">Inbox</span>
  </div>
  <div id="crm-inbox-error" class="crm-alert crm-alert-error" hidden role="alert" style="margin:12px"></div>
  <div class="crm-inbox-shell">
    <div class="crm-inbox-col crm-inbox-list is-mobile-visible">
      <div class="crm-inbox-list-head">
        <h2>Conversations</h2>
        <div class="crm-inbox-toolbar">
          <input type="search" id="crm-search" placeholder="Search name or phone…" autocomplete="off" aria-label="Search conversations">
          <select id="crm-filter-status" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="open">Open</option>
            <option value="pending">Pending</option>
            <option value="resolved">Resolved</option>
          </select>
          <select id="crm-filter-assigned" aria-label="Filter by assignment">
            <option value="">All assignments</option>
            <option value="me">Assigned to me</option>
            <option value="unassigned">Unassigned</option>
          </select>
        </div>
      </div>
      <div class="crm-conv-list" id="crm-conv-list" aria-live="polite">
        <div class="crm-inbox-empty"><div class="crm-skeleton" style="height:48px;margin:8px"></div><div class="crm-skeleton" style="height:48px;margin:8px"></div></div>
      </div>
    </div>
    <div class="crm-inbox-col crm-inbox-chat is-mobile-visible">
      <div class="crm-thread-wrap">
        <div class="crm-thread-header" id="crm-thread-header">
          <h2>Select a conversation</h2>
        </div>
        <div class="crm-thread" id="crm-thread" aria-live="polite">
          <div class="crm-inbox-empty">
            <p class="crm-empty-title">No conversation selected</p>
            <p>Choose a thread from the list to view messages and reply.</p>
          </div>
        </div>
        <div class="crm-compose-wrap" id="crm-compose-wrap">
          <div class="crm-compose-tabs" role="tablist" aria-label="Composer mode">
            <button type="button" class="crm-compose-tab is-active" data-compose-mode="reply" role="tab" aria-selected="true">Reply to customer</button>
            <button type="button" class="crm-compose-tab" data-compose-mode="note" role="tab" aria-selected="false">Internal note</button>
          </div>
          <p class="crm-compose-note-hint" role="note">Internal notes are saved on the contact record and are never sent to WhatsApp.</p>
          <p class="crm-compose-window-hint" id="crm-compose-window-hint" hidden role="status"></p>
          <form class="crm-compose" id="crm-compose-form">
            <?= csrf_field() ?>
            <textarea id="crm-compose-text" placeholder="Reply to customer…" rows="3" aria-label="Message text" disabled></textarea>
            <div class="crm-compose-tools">
              <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" id="crm-template-btn" disabled>Use template</button>
            </div>
            <div class="crm-compose-actions">
              <button class="crm-btn crm-btn-primary" type="submit" id="crm-compose-send" disabled>Send</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="crm-inbox-col crm-inbox-detail" id="crm-inbox-detail-col">
      <div class="crm-side-panel" id="crm-side-panel">
        <div class="crm-inbox-empty">
          <p>Customer details appear when you open a conversation.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="crm-modal-backdrop" id="crm-template-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="crm-template-modal-title">
  <div class="crm-modal crm-modal-wide">
    <div class="crm-modal-header">
      <h2 id="crm-template-modal-title" style="margin:0;font-size:var(--crm-text-lg)">Send approved template</h2>
      <button type="button" class="crm-icon-btn" data-crm-modal-close aria-label="Close">×</button>
    </div>
    <p class="crm-field-hint">Use when the 24-hour customer service window is closed or for structured outbound messages.</p>
    <div id="crm-template-list" class="crm-template-list"></div>
    <div class="crm-compose-actions" style="margin-top:16px">
      <button type="button" class="crm-btn crm-btn-secondary" data-crm-modal-close>Cancel</button>
    </div>
  </div>
</div>

<script>
  document.body.dataset.crmApiBase = '../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
  window.CRM_INBOX = {
    meAdminId: <?= (int) ($_SESSION['admin_id'] ?? 0) ?>,
    admins: <?= json_encode($assignAdmins, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>
  };
</script>
<?php crm_app_footer(); ?>
