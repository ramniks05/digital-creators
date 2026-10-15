# Razorpay billing setup

## 1. Migration

```bash
php sql/migrate_crm_saas_foundation.php
php sql/migrate_crm_billing_razorpay.php
```

## 2. Server secrets (you fill these)

In private `digital_creators_config.php` or environment:

```php
'razorpay' => [
    'key_id' => 'rzp_test_xxxxxxxx',      // Dashboard → API Keys
    'key_secret' => 'your_secret_here',   // Never expose in browser or Git
    'webhook_secret' => 'whsec_...',      // Dashboard → Webhooks → secret
],
```

Or env:

```env
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
```

Only **key_id** is sent to the browser for Checkout.js. **key_secret** and **webhook_secret** stay server-side only.

## 3. Razorpay Dashboard

1. Create **Plans** (monthly/yearly) for Starter, Growth, Business.
2. Copy each Plan ID (`plan_xxxx`) into MySQL:

```sql
UPDATE crm_subscription_plans SET razorpay_plan_id_monthly = 'plan_xxx' WHERE slug = 'growth';
UPDATE crm_subscription_plans SET razorpay_plan_id_yearly = 'plan_yyy' WHERE slug = 'growth';
```

3. Webhook URL: `https://digitalcreatorss.com/api/razorpay-webhook.php`  
   Events (minimum): `subscription.activated`, `subscription.charged`, `subscription.pending`, `subscription.halted`, `subscription.cancelled`, `payment.captured`

## 4. User flow

1. Tenant owner opens `/app/billing.php`
2. Server creates Razorpay **Subscription** via API (with `notes.tenant_id`)
3. Razorpay Checkout opens (subscription mode)
4. On success, browser calls `/saas/billing/confirm` → server verifies **HMAC signature** → activates `crm_subscriptions` only if Razorpay API reports active status
5. Webhooks provide idempotent backup/reconciliation

## 5. GST / invoices

`crm_invoices` table is ready for future GST line items. Have invoices reviewed by your accountant before production use.

## 6. Test mode

Use Razorpay **test keys** until go-live. Do not treat Checkout success alone as paid—always rely on signature + API verification or signed webhooks.
