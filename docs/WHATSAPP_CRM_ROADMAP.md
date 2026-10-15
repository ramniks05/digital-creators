# WhatsApp CRM & SaaS — Digital Creatorss

## Phase 1 — Audit (complete)

**Preserved (do not rewrite):**

- `api/hook-wa` → `includes/whatsapp_cloud.php` (verify, HMAC, dedupe, inbound/outbound/status)
- `includes/whatsapp_send.php` (Graph send, templates, service window)
- `includes/whatsapp_automation.php` + `includes/business_enquiry.php`
- `admin/whatsapp.php` (chat UI)
- Tables: `whatsapp_*`, `leads`, `whatsapp_contacts`, campaigns

**Gaps vs full CRM:** conversation entity, roles, assignment, compliance engine, queue, multi-tenant WABA per client, REST API, automation builder UI.

## Phase 2 — Foundation (implemented)

| Deliverable | Location |
|-------------|----------|
| SQL schema | `sql/crm_foundation.sql` |
| Migration | `php sql/migrate_crm_foundation.php` |
| Tenant context | `includes/crm/tenant.php` |
| Conversations sync | `includes/crm/conversations.php` |
| Webhook raw store | `includes/crm/webhook_store.php` |
| CRM dashboard | `admin/crm/index.php` |
| Team inbox | `admin/crm/inbox.php` |

**Install:**

```bash
cd ~/domains/digitalcreatorss.com/public_html
git pull origin main
php sql/migrate_crm_foundation.php
```

## Phase 3–8 (planned)

3. Inbox UX merge (single UI), notes, tags, mark-read on chat open  
4. Compliance engine + outbound job queue  
5. Lead pipeline Kanban, tasks, template sync table  
6. Automation rules + campaigns queue  
7. Analytics dashboard (real SQL metrics)  
8. Multi-tenant signup, Embedded Signup, billing  

## Architecture

Modular monolith: new code under `includes/crm/` and `admin/crm/`. WhatsApp modules stay in `includes/whatsapp_*.php` with thin hooks into CRM sync.

Default tenant id `1` = Digital Creatorss until SaaS onboarding ships.
