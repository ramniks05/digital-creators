<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();
require_once dirname(__DIR__, 3) . '/includes/crm/contact_tags.php';

$pdo = db();

if (!defined('CRM_ASSET_PREFIX')) {
    define('CRM_ASSET_PREFIX', '../../assets/');
}
require_once dirname(__DIR__) . '/layout.php';

crm_app_header('Contacts', 'contacts', [
    ['label' => 'Dashboard', 'href' => '../index.php'],
    ['label' => 'Contacts'],
], [
    'nav_base' => '../',
    'extra_css' => ['crm-contacts.css'],
    'extra_js' => ['crm-contacts.js'],
]);

if (!crm_tables_ready($pdo)): ?>
  <div class="crm-alert crm-alert-error">Run <code>php sql/migrate_crm_foundation.php</code> and <code>php sql/migrate_crm_stage1_contacts.php</code>.</div>
  <?php crm_app_footer(); exit; endif;

$tenantId = crm_current_tenant_id();
$tags = crm_list_tags($pdo, $tenantId);
?>
<div class="crm-contacts-toolbar">
  <input type="search" id="crm-contacts-search" placeholder="Search name, phone, email…" autocomplete="off" aria-label="Search contacts">
  <select id="crm-contacts-tag" aria-label="Filter by tag">
    <option value="">All tags</option>
    <?php foreach ($tags as $tag): ?>
      <option value="<?= (int) $tag['id'] ?>"><?= e((string) $tag['label']) ?></option>
    <?php endforeach; ?>
  </select>
  <a class="crm-btn crm-btn-secondary" href="../live.php">Inbox</a>
  <a class="crm-btn crm-btn-primary" href="<?= e('../../api/v1/index.php/contacts/export') ?>">Export CSV</a>
</div>

<details class="crm-panel crm-contacts-import">
  <summary style="cursor:pointer;font-weight:600">Import CSV</summary>
  <p class="crm-field-hint" style="margin:12px 0">Required column: <code>wa_id</code>. Optional: display_name, email, source, tags (pipe-separated), marketing_consent, consent_evidence.</p>
  <form id="crm-import-form">
    <div class="crm-field">
      <label for="crm-import-file">CSV file</label>
      <input type="file" id="crm-import-file" accept=".csv,text/csv" required>
    </div>
    <button class="crm-btn crm-btn-primary crm-btn-sm" type="submit">Import</button>
  </form>
</details>

<div class="crm-table-wrap">
  <table class="crm-table crm-contacts-table">
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
      <tr><td colspan="6"><div class="crm-skeleton" style="height:32px"></div></td></tr>
    </tbody>
  </table>
</div>
<p id="crm-contacts-pagination" style="display:flex;align-items:center;gap:12px;margin-top:12px"></p>
<script>
  document.body.dataset.crmApiBase = '../../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
  document.body.dataset.crmInitialQ = <?= json_encode((string) ($_GET['q'] ?? ''), JSON_THROW_ON_ERROR) ?>;
</script>
<?php crm_app_footer(); ?>
