<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/crm/tenant.php';
require_once dirname(__DIR__) . '/includes/crm/auth_session.php';
require_once dirname(__DIR__) . '/includes/saas/UserAuth.php';
require_once dirname(__DIR__) . '/includes/saas/config.php';

crm_require_crm_user();
crm_bootstrap_session_context();

$tenantId = crm_current_tenant_id();
$csrf = csrf_token();
$apiBase = '../api/v1/index.php';
$rzConfigured = saas_razorpay_configured();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subscription & Billing</title>
  <script>(function(){try{var t=localStorage.getItem('crm-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <link rel="stylesheet" href="assets/saas.css">
  <?php if ($rzConfigured): ?>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <?php endif; ?>
</head>
<body class="crm-auth-page" style="align-items:flex-start;padding-top:48px">
<button type="button" class="crm-icon-btn crm-auth-theme" id="crm-theme-toggle" aria-label="Toggle theme">◐</button>
<div class="saas-shell">
  <p class="saas-app-nav"><a class="saas-btn" href="../admin/crm/index.php" style="background:var(--crm-text-secondary)">← CRM</a></p>
  <h1>Subscription</h1>
  <p class="saas-muted">SaaS fees are separate from Meta WhatsApp messaging charges.</p>
  <?php if (!$rzConfigured): ?>
    <div class="saas-alert-error">Razorpay is not configured. Set <code>RAZORPAY_KEY_ID</code> and <code>RAZORPAY_KEY_SECRET</code> on the server.</div>
  <?php else: ?>
    <div id="sub-summary" class="saas-card" style="max-width:100%;margin-bottom:16px">Loading subscription…</div>
    <div class="saas-card" style="max-width:100%">
      <h3>Upgrade plan</h3>
      <label>Plan</label>
      <select id="plan-slug">
        <option value="starter">Starter</option>
        <option value="growth" selected>Growth</option>
        <option value="business">Business</option>
      </select>
      <label>Billing interval</label>
      <select id="plan-interval">
        <option value="month">Monthly</option>
        <option value="year">Yearly</option>
      </select>
      <p class="saas-muted">Each plan needs a matching Plan ID from your Razorpay Dashboard (stored in <code>crm_subscription_plans</code>).</p>
      <button type="button" class="saas-btn" id="btn-checkout">Continue to Razorpay</button>
      <p id="billing-error" class="saas-alert-error" hidden></p>
    </div>
    <h2>Billing history</h2>
    <div id="billing-history" class="saas-muted">Loading…</div>
  <?php endif; ?>
  <p style="margin-top:24px"><a href="dashboard.php">← Dashboard</a></p>
</div>
<?php if ($rzConfigured): ?>
<script>
  const API = <?= json_encode($apiBase, JSON_THROW_ON_ERROR) ?>;
  const CSRF = <?= json_encode($csrf, JSON_THROW_ON_ERROR) ?>;

  async function api(path, opts = {}) {
    const res = await fetch(API + path, {
      credentials: 'same-origin',
      headers: Object.assign({ 'Accept': 'application/json', 'X-CSRF-Token': CSRF }, opts.headers || {}),
      method: opts.method || 'GET',
      body: opts.body ? JSON.stringify(opts.body) : undefined,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) throw new Error(data.error || 'Request failed');
    return data.data !== undefined ? data.data : data;
  }

  async function loadSummary() {
    try {
      const d = await api('/saas/billing/subscription');
      const s = d.subscription;
      document.getElementById('sub-summary').innerHTML =
        '<strong>' + (s.plan_name || s.plan_slug) + '</strong> · Status: ' + s.status +
        (s.current_period_end ? '<br>Period ends: ' + s.current_period_end : '');
    } catch (e) {
      document.getElementById('sub-summary').textContent = 'No active subscription record yet (trial may apply).';
    }
  }

  async function loadHistory() {
    try {
      const d = await api('/saas/billing/history');
      const rows = d.transactions || [];
      if (!rows.length) {
        document.getElementById('billing-history').textContent = 'No transactions yet.';
        return;
      }
      document.getElementById('billing-history').innerHTML = rows.map(t =>
        '<div>' + t.created_at + ' · ' + t.status + ' · ' + (t.amount_cents/100) + ' ' + t.currency + '</div>'
      ).join('');
    } catch (e) {
      document.getElementById('billing-history').textContent = 'Could not load history.';
    }
  }

  document.getElementById('btn-checkout').addEventListener('click', async () => {
    const err = document.getElementById('billing-error');
    err.hidden = true;
    try {
      const d = await api('/saas/billing/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: {
          plan_slug: document.getElementById('plan-slug').value,
          interval: document.getElementById('plan-interval').value,
        },
      });
      const c = d.checkout;
      const rzp = new Razorpay({
        key: c.key_id,
        subscription_id: c.subscription_id,
        name: c.name,
        description: c.description,
        prefill: c.prefill || {},
        notes: c.notes || {},
        handler: async function (response) {
          try {
            await api('/saas/billing/confirm', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: {
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_subscription_id: response.razorpay_subscription_id,
                razorpay_signature: response.razorpay_signature,
              },
            });
            alert('Payment verified. Subscription activated.');
            loadSummary();
            loadHistory();
          } catch (e) {
            alert('Server verification failed: ' + e.message + '. If payment succeeded, wait for webhook or contact support.');
          }
        },
      });
      rzp.open();
    } catch (e) {
      err.textContent = e.message;
      err.hidden = false;
    }
  });

  loadSummary();
  loadHistory();
</script>
<?php endif; ?>
<script src="../admin/assets/crm-shell.js" defer></script>
</body>
</html>
