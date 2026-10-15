<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();
require_once dirname(__DIR__, 3) . '/includes/crm/ContactService.php';

$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$tenantId = crm_current_tenant_id();
$contact = $id > 0 ? (new CrmContactService($pdo, $tenantId))->get($id) : null;

require_once dirname(__DIR__, 2) . '/layout.php';
admin_header('Contact', 'crm/contacts/index.php');

if ($contact === null): ?>
  <div class="alert alert-error">Contact not found.</div>
  <p><a href="index.php">Back to directory</a></p>
  <?php admin_footer(); exit; endif;
?>
<link rel="stylesheet" href="../../assets/crm-contacts.css">
<p><a href="index.php">&larr; Contacts</a> · <a href="../live.php">Open inbox</a></p>
<h2 id="crm-contact-title"><?= e($contact['display_name'] ?: '+' . $contact['wa_id']) ?></h2>

<div class="crm-contact-detail-grid">
  <div class="panel">
    <h3>Profile</h3>
    <div id="crm-contact-meta"></div>
    <form method="post" action="index.php" class="form-grid" style="margin-top:12px">
      <?= csrf_field() ?>
      <p class="muted">Quick edits use the API from the list page; archive and consent via API buttons below.</p>
    </form>
    <p>
      <button type="button" class="btn btn-sm btn-secondary" id="crm-btn-opt-out">Record opt-out</button>
      <button type="button" class="btn btn-sm" id="crm-btn-archive">Archive</button>
    </p>
  </div>
  <div class="panel">
    <h3>WhatsApp history</h3>
    <div class="crm-msg-preview" id="crm-contact-messages"></div>
  </div>
  <div class="panel">
    <h3>Activity</h3>
    <ul class="crm-timeline" id="crm-contact-timeline"></ul>
  </div>
  <div class="panel">
    <h3>Internal note</h3>
    <form id="crm-note-form">
      <textarea id="crm-note-body" rows="3" required placeholder="Add a note…"></textarea>
      <button class="btn btn-sm" type="submit">Save note</button>
    </form>
  </div>
</div>
<script>
  document.body.dataset.crmApiBase = '../../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
  document.body.dataset.contactId = <?= (int) $id ?>;
</script>
<script src="../../assets/crm-contacts.js" defer></script>
<script>
(function () {
  const id = <?= (int) $id ?>;
  const api = window.crmContactsApi && window.crmContactsApi.api;
  document.getElementById('crm-btn-opt-out')?.addEventListener('click', () => {
    if (!api || !confirm('Record opt-out for this contact?')) return;
    api('/contacts/' + id + '/opt-out', { method: 'POST' }).then(() => location.reload());
  });
  document.getElementById('crm-btn-archive')?.addEventListener('click', () => {
    if (!api || !confirm('Archive this contact?')) return;
    api('/contacts/' + id + '/archive', { method: 'POST' }).then(() => location.href = 'index.php');
  });
})();
</script>
<?php admin_footer(); ?>
