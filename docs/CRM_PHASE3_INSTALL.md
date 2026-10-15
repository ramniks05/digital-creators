# WhatsApp CRM — Phase 3 (messaging + API + live inbox)

## Migrations (safe, non-destructive)

```bash
php sql/migrate_crm_foundation.php
php sql/migrate_crm_phase3.php
```

Rollback: drop only `crm_*` tables created in `sql/crm_phase3_messaging.sql` if you need to revert phase 3 (do not drop legacy `whatsapp_*` tables).

## Cron / worker

```bash
* * * * * php /home/USER/domains/digitalcreatorss.com/public_html/scripts/crm_outbound_worker.php >> /tmp/crm_worker.log 2>&1
```

## URLs

| Purpose | URL |
|--------|-----|
| Existing webhook (keep) | `/api/hook-wa` |
| Meta alias | `/api/webhooks-meta-whatsapp.php` or `/webhooks/meta/whatsapp.php` |
| REST API | `/api/v1/index.php/auth/me` (or `/api/v1/...` with Apache rewrite) |
| Live inbox | `/admin/crm/live.php` |

## Verify flow

1. Send WhatsApp message to your business number → check `crm_messages` and live inbox.
2. Reply from live inbox → job queue → Meta send → delivery status updates `crm_messages.delivery_status`.
3. `php tests/crm_smoke.php` for local checks.

## Security

- Session + CSRF on mutating API calls (`X-CSRF-Token` header).
- Tenant membership enforced on every API call.
- No WhatsApp tokens in browser JavaScript.
