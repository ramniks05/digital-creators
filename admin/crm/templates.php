<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();
require_once __DIR__ . '/layout.php';

crm_app_header('Templates', 'templates', [
    ['label' => 'Dashboard', 'href' => 'index.php'],
    ['label' => 'Templates'],
], ['extra_css' => []]);
?>
<div class="crm-panel">
  <h2 class="crm-panel-title">Approved WhatsApp templates</h2>
  <p style="color:var(--crm-text-muted);margin-top:0">Loaded from Meta via your configured Cloud API credentials. Send templates from the <a href="live.php">inbox</a>.</p>
  <div id="crm-templates-status" class="crm-alert" hidden role="status"></div>
  <div class="crm-table-wrap">
    <table class="crm-table" id="crm-templates-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Language</th>
          <th>Category</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="crm-templates-body">
        <tr><td colspan="4" class="crm-inbox-empty">Loading…</td></tr>
      </tbody>
    </table>
  </div>
</div>
<script>
  document.body.dataset.crmApiBase = '../../api/v1/index.php';
  document.body.dataset.csrf = <?= json_encode(csrf_token(), JSON_THROW_ON_ERROR) ?>;
  (function () {
    var apiBase = document.body.dataset.crmApiBase;
    fetch(apiBase + '/templates', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (payload) {
        var body = document.getElementById('crm-templates-body');
        var status = document.getElementById('crm-templates-status');
        if (!body) return;
        var data = payload.data || payload;
        if (data.fetch_error && status) {
          status.hidden = false;
          status.className = 'crm-alert crm-alert-warning';
          status.textContent = data.fetch_error;
        }
        var rows = data.templates || [];
        if (!rows.length) {
          body.innerHTML = '<tr><td colspan="4"><div class="crm-empty"><p class="crm-empty-title">No templates</p><p>Configure WhatsApp credentials or approve templates in Meta Business Manager.</p></div></td></tr>';
          return;
        }
        body.innerHTML = rows.map(function (t) {
          return '<tr><td>' + escapeHtml(t.name || '') + '</td><td>' + escapeHtml(t.language || '') + '</td><td>' + escapeHtml(t.category || '') + '</td><td>' + escapeHtml(t.status || '') + '</td></tr>';
        }).join('');
      })
      .catch(function (e) {
        var body = document.getElementById('crm-templates-body');
        if (body) body.innerHTML = '<tr><td colspan="4" class="crm-alert crm-alert-error">' + escapeHtml(e.message) + '</td></tr>';
      });
    function escapeHtml(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
  })();
</script>
<?php crm_app_footer(); ?>
