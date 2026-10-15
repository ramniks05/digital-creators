<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();
require_once dirname(__DIR__, 3) . '/includes/crm/contact_tags.php';

$pdo = db();

require_once dirname(__DIR__, 2) . '/layout.php';

admin_header('CRM Contacts', 'crm/contacts/index.php');

if (!crm_tables_ready($pdo)): ?>
  <div class="alert alert-error">Run <code>php sql/migrate_crm_foundation.php</code> and <code>php sql/migrate_crm_stage1_contacts.php</code>.</div>
  <?php admin_footer(); exit; endif;

$tenantId = crm_current_tenant_id();
$tags = crm_list_tags($pdo, $tenantId);
?>
<link rel="stylesheet" href="../../assets/crm-contacts.css">
<div class="crm-contacts-toolbar">
  <input type="search" id="crm-contacts-search" placeholder="Search name, phone, email…" autocomplete="off">
  <select id="crm-contacts-tag">
    <option value="">All tags</option>
    <?php foreach ($tags as $tag): ?>
      <option value="<?= (int) $tag['id'] ?>"><?= e((string) $tag['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <a class="btn btn-secondary" href="../index.php">CRM home</a>
  <a class="btn" href="<?= e('../../api/v1/index.php/contacts/export') ?>">Export CSV</a>
</div>

<form id="crm-import-form" class="panel" style="margin-bottom:12px">
  <h3 style="margin-top:0">Import CSV</h3>
  <p class="muted">Required column: <code>wa_id</code>. Optional: display_name, email, source, tags (pipe-separated), marketing_consent, consent_evidence. Import does <strong>not</strong> grant marketing consent unless both consent columns are set.</p>
  <input type="file" id="crm-import-file" accept=".csv,text/csv" required>
  <button class="btn btn-sm" type="submit">Import</button>
</form>

<table class="crm-contacts-table">
  <thead>
    <tr>
      <th>Name</th>
      <th>WhatsApp</th>
      <th>Source</th>
      <th>Consent</th>
      <th>Tags</th>
      <th></th>
    </tr>
  </thead>
  <tbody id="crm-contacts-list-body">
    <tr><td colspan="6" class="muted">Loading…</td></tr>
  </tbody>
</table>
<script>
  document.body.dataset.crmApiBase = '../../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
</script>
<script src="../../assets/crm-contacts.js" defer></script>
<?php admin_footer(); ?>
