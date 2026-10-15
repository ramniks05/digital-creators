# Digital Creatorss WhatsApp CRM SaaS — Project status (handoff)

**Date:** 2025-10-15  
**Repository:** `https://github.com/ramniks05/digital-creators.git`  
**Last pushed commit (origin/main):** `ef4e232` — *Add WhatsApp CRM foundation*  
**Local workspace:** Large CRM / SaaS / billing increment **not yet pushed** (see Git section).

This document reflects **evidence from the repo and tests run on this machine**, not production server state unless noted.

---

## Architecture (current)

| Layer | Technology |
|--------|----------------|
| Backend | Core PHP 8.2+, modular includes, PDO |
| Database | MySQL 8 (shared schema, `crm_*` + legacy `whatsapp_*`) |
| WhatsApp | Meta Cloud API — `includes/whatsapp_cloud.php`, `includes/whatsapp_send.php` |
| Webhook (production URL) | `api/hook-wa.php` → `webhook/whatsapp.php` |
| CRM admin UI | `admin/crm/*`, legacy `admin/whatsapp.php` |
| REST API | `api/v1/index.php` (session auth, CSRF on mutations) |
| SaaS app UI | `app/*` (register, login, dashboard, billing, onboarding scaffold) |
| Billing | Razorpay subscriptions — `includes/saas/billing/*`, `api/razorpay-webhook.php` |
| Worker | `scripts/crm_outbound_worker.php` (outbound job queue) |

**Tenant model:** `crm_tenants` with default tenant `1` (Digital Creatorss). SaaS adds `crm_users`, `crm_tenant_users`. Legacy admins use `admins` + `crm_admin_tenant_roles`.

---

## Implemented modules (in local workspace)

### WhatsApp integration (pre-CRM, preserved)

- Webhook GET verify + POST HMAC (`whatsapp_handle_webhook_post`)
- Inbound/outbound/status persistence, dedupe, echoes, automation hooks
- Graph send: `whatsapp_send_graph`, `/api/wa-messages.php`
- Admin chat: `admin/whatsapp.php`

### CRM — committed on `main` (ef4e232)

- `sql/crm_foundation.sql`, migrate script
- Tenants, conversations, contacts sync, webhook raw store, admin inbox list, CRM dashboard

### CRM — local only (uncommitted)

| Area | Key paths |
|------|-----------|
| Phase 3 messaging | `sql/crm_phase3_messaging.sql`, `includes/crm/messages.php`, `OutboundJobService.php`, compliance services |
| Live inbox | `admin/crm/live.php`, `admin/assets/crm-inbox.*` |
| REST API v1 | `api/v1/index.php`, auth, conversations, messages queue |
| Stage 1 contacts | `ContactService`, tags, import/export, `admin/crm/contacts/*` |
| SaaS foundation | `sql/crm_saas_foundation.sql`, `app/*`, `includes/saas/*` |
| Razorpay billing | `sql/crm_billing_razorpay.sql`, `app/billing.php`, webhook handler |
| Docs | `docs/CRM_*`, `docs/SAAS_*`, `docs/RAZORPAY_BILLING.md`, `docs/META_EMBEDDED_SIGNUP.md` |

---

## Incomplete / not in repo (Prompts 3–5 scope)

- Lead pipeline **Kanban**, tasks/reminders UI, template manager UI
- Visual **automation builder**, rule engine executions
- **Campaign** management (compliant bulk)
- **Chatbot** builder
- **Analytics** dashboards (beyond basic CRM dashboard metrics)
- Meta **Embedded Signup** production flow (token exchange, WABA subscribe) — scaffold only
- **Super-admin** panel (503 stub at `platform/index.php`)
- **Email** delivery for verification / invitations
- **Payment** live verification on Razorpay (needs your keys + Plan IDs)
- PHPUnit/CI suite; most tests are standalone PHP scripts
- Prompt 3 Stages 2–10 as originally specified — **not implemented**

---

## Test results (executed 2025-10-15, Windows, PHP 8.2.12 via XAMPP)

| Script | Result | Notes |
|--------|--------|--------|
| `tests/razorpay_signature_test.php` | **PASS** (exit 0) | HMAC unit checks only; no Razorpay API |
| `tests/whatsapp_webhook_security_test.php` | **PASS** (exit 0) | Unit tests; **DB block SKIPPED** — `whatsapp_contacts` table missing locally |
| `tests/crm_smoke.php` | **PASS** (exit 0) | No DB required for checks run |
| `tests/saas_tenant_isolation.php` | **SKIP** (exit 0) | `crm_users` / SaaS tables not migrated locally |
| `tests/crm_contacts_stage1.php` | **SKIP** (exit 0) | Stage 1 tables not migrated locally |
| `tests/whatsapp_webhook_test.php` | **FAIL** (exit 1) | **1 failure:** `External send typed as external or template` |

**Not run:** Live Meta webhook, live Graph send, Razorpay Checkout, browser E2E, production `https://digitalcreatorss.com` smoke.

---

## WhatsApp integration — code review (not live retest)

| Check | Finding |
|--------|---------|
| Production entry `api/hook-wa.php` | Still delegates to `webhook/whatsapp.php` — **unchanged path** |
| `whatsapp_handle_webhook_post` | Still requires app secret + signature; returns 403 on bad signature — **present** |
| CRM hooks | Optional includes (`crm/webhook_store.php`, `crm/messages.php`); failures logged; core WA tables still processed in transaction |
| Send path | `whatsapp_send_graph` unchanged as primary sender; CRM records via optional `crm/messages.php` |

**Conclusion:** Integration structure is **intact in source**. Production behavior after deploy depends on running migrations and not breaking config on server. **Not verified** against live Meta on this handoff.

---

## Security — critical / high (review)

| Item | Severity | Status |
|------|----------|--------|
| Webhook HMAC | High | Implemented; unit tests pass for malformed headers |
| CRM API session + CSRF on POST/PATCH | High | Implemented for `/api/v1` mutations |
| Razorpay webhook HMAC | High | Implemented in `SubscriptionBillingService` |
| Secrets in Git | High | `.gitignore` includes `config.local.php`; use private config on server — **no secrets committed in this handoff** |
| Multi-tenant webhook fallback | Medium | Default tenant `1` used when `SAAS_STRICT_WEBHOOK=0` and phone unmapped — **risk for true multi-tenant** until strict mode + all numbers in `crm_whatsapp_numbers` |
| `SAAS_EXPOSE_VERIFY_TOKEN` | Medium | Dev-only email token exposure — must stay `0` in production |
| Platform admin | Medium | Not implemented — no production super-admin surface |
| SaaS vs admin CRM access | Medium | `crm_require_crm_user()` added for some pages; not all admin routes audited |
| SQL injection | Ongoing | PDO prepared statements used in reviewed CRM code; full audit not done |

---

## Known bugs / test failures

1. **`tests/whatsapp_webhook_test.php`:** `FAIL: External send typed as external or template`
2. **Local DB:** Many migrations not applied — tests skip or partially skip (see above)
3. **`docs/SAAS_AUDIT_PROMPT4.md`:** Partially outdated (billing/SaaS signup now exist locally)

---

## Database migrations & backup

### Migration scripts (repo)

Run in order on each environment:

```bash
php sql/migrate_whatsapp_webhook.php          # legacy WA tables (if fresh)
php sql/migrate_whatsapp_extensions.php       # as needed
php sql/migrate_crm_foundation.php
php sql/migrate_crm_phase3.php
php sql/migrate_crm_stage1_contacts.php
php sql/migrate_crm_saas_foundation.php
php sql/migrate_crm_billing_razorpay.php
```

**Local evidence (this machine):** MySQL database `digital_creators` exists but **missing** `whatsapp_contacts` and CRM/SaaS tables — migrations **not fully applied locally**.

**Production evidence:** Not inspected in this handoff. Last Git push (`ef4e232`) implies production may have **foundation only** if ops ran `migrate_crm_foundation.php` after pull — **unconfirmed**.

### Backup

- No automated backup scripts found in repository.
- **Recommendation:** Hostinger/MySQL dumps before running new migrations; document RPO/RTO outside repo.

---

## Deployment status

| Environment | Git | CRM/SaaS code | Migrations |
|-------------|-----|---------------|------------|
| `origin/main` | Through `ef4e232` | Foundation only | Unknown on server |
| Local workspace | Ahead of origin (many untracked/modified files) | Phases 3, contacts, SaaS, Razorpay | Not applied locally |

**Production site:** `https://digitalcreatorss.com` — webhook `api/hook-wa` — **not HTTP-tested in this handoff**.

---

## Git (handoff commit)

After this pause, commit includes `PROJECT_STATUS.md`, `NEXT_STEPS.md`, and all safe WIP CRM/SaaS files. **No** private config or `.env` with secrets.

---

## Commands to resume development

```bash
cd /path/to/digital_creators
git pull
composer install   # optional; composer.json minimal

# Configure DB + secrets (private config, not Git)
# Then migrations in order (see above)

php scripts/crm_outbound_worker.php   # manual or cron
php tests/razorpay_signature_test.php
php tests/whatsapp_webhook_security_test.php
php tests/whatsapp_webhook_test.php     # fix known failure first
```

SaaS app: `/app/register.php` · API: `/api/v1/index.php/saas/auth/me` · Billing: `/app/billing.php`
