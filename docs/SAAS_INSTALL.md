# Multi-tenant SaaS install (Prompt 4 foundation)

## Migration order

```bash
php sql/migrate_crm_foundation.php
php sql/migrate_crm_phase3.php
php sql/migrate_crm_stage1_contacts.php
php sql/migrate_crm_saas_foundation.php
php sql/migrate_crm_billing_razorpay.php
```

## Environment

Add to private `digital_creators_config.php` or server env:

```php
'saas' => [
    'strict_webhook' => false, // true after all numbers in crm_whatsapp_numbers
    'credentials_key' => 'long-random-secret',
    'app_url' => 'https://digitalcreatorss.com/app',
    'meta_app_id' => '',
    'meta_config_id' => '',
],
```

```env
SAAS_EXPOSE_VERIFY_TOKEN=0
CRM_CREDENTIALS_KEY=
SAAS_STRICT_WEBHOOK=0
```

## URLs

| URL | Purpose |
|-----|---------|
| `/app/` | SaaS landing |
| `/app/register.php` | Signup |
| `/app/login.php` | SaaS login |
| `/api/v1/index.php/saas/register` | Registration API |
| `/admin/crm/*` | CRM (admin or SaaS session + membership) |

## Verify

1. Register a new workspace via `/app/register.php`
2. Set `SAAS_EXPOSE_VERIFY_TOKEN=1` in dev → verify email token
3. Log in → dashboard → confirm tenant_id in session
4. Attempt cross-tenant API access → `403` (`tests/saas_tenant_isolation.php`)

## Rollback

Drop tables from `sql/crm_saas_foundation.sql` (not `crm_tenants` if tenant 1 still used for Digital Creatorss).

## Razorpay

See [RAZORPAY_BILLING.md](RAZORPAY_BILLING.md). UI: `/app/billing.php`, webhook: `/api/razorpay-webhook.php`.

## Not implemented yet

- Super-admin UI
- Full Meta token exchange
- Email delivery (wire SMTP for verification links)
