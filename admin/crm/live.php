<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();

$pdo = db();

require_once __DIR__ . '/../layout.php';

admin_header('CRM Live Inbox', 'crm/live.php');
?>
<link rel="stylesheet" href="../assets/crm-inbox.css">
<div id="crm-inbox-error" class="alert alert-error" hidden></div>
<div class="crm-inbox-shell">
  <div class="crm-inbox-col">
    <div class="crm-inbox-toolbar">
      <input type="search" id="crm-search" placeholder="Search name or phone…" autocomplete="off">
      <select id="crm-filter-status">
        <option value="">All statuses</option>
        <option value="open">Open</option>
        <option value="pending">Pending</option>
        <option value="resolved">Resolved</option>
      </select>
      <select id="crm-filter-assigned">
        <option value="">All assignments</option>
        <option value="me">Assigned to me</option>
        <option value="unassigned">Unassigned</option>
      </select>
    </div>
    <div class="crm-conv-list" id="crm-conv-list"></div>
  </div>
  <div class="crm-inbox-col">
    <div class="crm-thread" id="crm-thread">
      <p class="muted" style="padding:12px">Select a conversation to view messages.</p>
    </div>
    <form class="crm-compose" id="crm-compose-form">
      <?= csrf_field() ?>
      <textarea id="crm-compose-text" placeholder="Reply within 24-hour window…" rows="2" required></textarea>
      <button class="btn" type="submit">Send</button>
    </form>
  </div>
  <div class="crm-inbox-col side">
    <div class="crm-side-panel" id="crm-side-panel">
      <p class="muted">Customer details appear here.</p>
    </div>
  </div>
</div>
<script>
  document.body.dataset.crmApiBase = '../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="../assets/crm-inbox.js" defer></script>
<?php admin_footer(); ?>
