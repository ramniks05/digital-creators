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

if (!defined('CRM_ASSET_PREFIX')) {
    define('CRM_ASSET_PREFIX', '../../assets/');
}
require_once dirname(__DIR__) . '/layout.php';

$name = $contact ? ($contact['display_name'] ?: '+' . $contact['wa_id']) : 'Contact';
crm_app_header($name, 'contacts', [
    ['label' => 'Dashboard', 'href' => '../index.php'],
    ['label' => 'Contacts', 'href' => 'index.php'],
    ['label' => $name],
], [
    'nav_base' => '../',
    'extra_css' => ['crm-contacts.css'],
    'extra_js' => ['crm-contacts.js'],
]);

if ($contact === null): ?>
  <div class="crm-alert crm-alert-error">Contact not found.</div>
  <p><a class="crm-btn crm-btn-secondary" href="index.php">Back to directory</a></p>
  <?php crm_app_footer(); exit; endif;
?>
<div class="crm-contact-detail-grid">
  <div class="crm-panel">
    <h2 class="crm-panel-title" id="crm-contact-title"><?= e($contact['display_name'] ?: '+' . $contact['wa_id']) ?></h2>
    <div id="crm-contact-meta"></div>
    <p style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px">
      <a class="crm-btn crm-btn-primary" href="../live.php">Open inbox</a>
      <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" id="crm-btn-opt-out">Record opt-out</button>
      <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" id="crm-btn-archive">Archive</button>
    </p>
  </div>
  <div class="crm-panel">
    <h3 class="crm-panel-title">WhatsApp history</h3>
    <div class="crm-msg-preview" id="crm-contact-messages"></div>
  </div>
  <div class="crm-panel">
    <h3 class="crm-panel-title">Activity</h3>
    <ul class="crm-timeline" id="crm-contact-timeline"></ul>
  </div>
  <div class="crm-panel">
    <h3 class="crm-panel-title">Internal note</h3>
    <p class="crm-field-hint">Notes are internal only and are not sent to WhatsApp.</p>
    <form id="crm-note-form" class="crm-field">
      <label for="crm-note-body">Note</label>
      <textarea id="crm-note-body" rows="3" required placeholder="Add a note…"></textarea>
      <button class="crm-btn crm-btn-primary crm-btn-sm" type="submit" style="margin-top:8px">Save note</button>
    </form>
  </div>
</div>
<script>
  document.body.dataset.crmApiBase = '../../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
  document.body.dataset.contactId = <?= (int) $id ?>;
</script>
<script>
(function () {
  var id = <?= (int) $id ?>;
  var api = window.crmContactsApi && window.crmContactsApi.api;
  document.getElementById('crm-btn-opt-out')?.addEventListener('click', function () {
    if (!api || !confirm('Record opt-out for this contact?')) return;
    api('/contacts/' + id + '/opt-out', { method: 'POST' }).then(function () {
      if (window.crmToast) crmToast('Opt-out recorded', 'success');
      location.reload();
    });
  });
  document.getElementById('crm-btn-archive')?.addEventListener('click', function () {
    if (!api || !confirm('Archive this contact?')) return;
    api('/contacts/' + id + '/archive', { method: 'POST' }).then(function () {
      location.href = 'index.php';
    });
  });
})();
</script>
<?php crm_app_footer(); ?>
