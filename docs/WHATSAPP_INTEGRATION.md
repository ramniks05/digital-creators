# WhatsApp Cloud API — Production Integration (Digital Creatorss)

## Phase 1 — Codebase audit (current stack)

| Item | Finding |
|------|---------|
| Runtime | Plain **PHP** (no full framework), **PDO/MySQL**, session admin auth |
| Routing | Direct `.php` files + Apache rewrites (`api/hook-wa`, `api/wa-health`, `api/contact`) |
| Config | `includes/config.php` + **private** `digital_creators_config.php` (Hostinger, not in Git) |
| Webhook | `webhook/whatsapp.php` ← `api/hook-wa.php` |
| Send | `includes/whatsapp_send.php` (Graph `/{phone-number-id}/messages`) |
| Inbox | `admin/whatsapp.php` (chats, templates, delivery banner) |
| DB (core) | `sql/whatsapp_webhook.sql` — inbound, outbound, status log, dedupe |
| DB (extended) | `sql/whatsapp_contacts_campaigns.sql` — contacts consent, campaigns |
| Tests | `tests/whatsapp_webhook_test.php` (synthetic webhook + DB when available) |

**Reuse principle:** extend existing modules; do not replace working send/webhook paths.

---

## Phase 2 — Configuration

Keys (env or private PHP):

- `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_WABA_ID` (optional; resolved from phone)
- `WHATSAPP_WEBHOOK_VERIFY_TOKEN`, `META_APP_SECRET`
- `WHATSAPP_API_VERSION` (default `v21.0`)

**Admin:** WhatsApp → **Setup** tab uses `includes/whatsapp_config_status.php` (masked secrets, Graph ping).

**Public health (no secrets):** `GET /api/wa-health`

---

## Phase 3 — Outgoing messages

Implemented in `includes/whatsapp_send.php`:

| Feature | Status |
|---------|--------|
| Text (24h window) | Yes |
| Reply context (`context.message_id`) | Yes (chat reply form) |
| Template + header/body variables | Yes (Meta `components`) |
| Image / document (HTTPS URL) | Yes |
| Graph transient retry (429/5xx, once) | Yes |
| Stores `wamid`, outbound row | Yes |
| Distinguishes API accept vs delivery | Yes (status via webhook) |

Not yet: interactive buttons/lists, audio/video upload helpers (can add via same Graph payload pattern).

---

## Phase 4 — Webhook

`includes/whatsapp_cloud.php`:

- GET verify (`hub.mode`, `hub.verify_token`, `hub.challenge`)
- POST HMAC `X-Hub-Signature-256`, 3 MiB limit
- Parses `messages` field: inbound + statuses
- Dedupe table prevents duplicate rows on Meta retries
- Status updates do not regress `delivered` → `sent`

Callback URL: **`https://digitalcreatorss.com/api/hook-wa`**

---

## Phase 5 — Inbox

`admin/whatsapp.php`:

- Chat list, thread, template send, delivery errors on bubbles
- Latest send banner (sent vs delivered vs failed)
- Inbound media: `admin/whatsapp_media.php?inbound_id=` (admin auth)

Inbound rows created only when webhook POST succeeds — verify Meta subscription + signature.

---

## Phase 6 — Templates

- List/sync: `{waba}/message_templates` with pagination
- Admin **Templates** tab + dropdowns (approved only)
- Variable fields driven by Meta `components` (header TEXT + BODY)

---

## Phase 7 — Marketing campaigns

- `admin/whatsapp_campaigns.php` — draft + send to consented contacts
- Rate: ~350ms between sends (basic throttle)
- `admin/whatsapp_contacts.php` — opt-in / opt-out
- Inbound message → `whatsapp_contacts` opt-in (unless opted out)

**Requires:** `php sql/migrate_whatsapp_extensions.php`

---

## Phase 8 — Migrations

```bash
php sql/migrate_whatsapp_webhook.php      # core tables
php sql/migrate_whatsapp_extensions.php   # contacts + campaigns
```

---

## Phase 9 — Production (Hostinger)

1. Private config outside `public_html`
2. `git pull`, run migrations
3. Meta: Live app, webhook URL, subscribe **`messages`**
4. System User token with `whatsapp_business_messaging` + `whatsapp_business_management`
5. HTTPS only; never log tokens

---

## Phase 10 — Testing

```bash
php tests/whatsapp_webhook_test.php
```

Manual: GET verify, signed POST, send template with variables, customer reply → inbox, check delivery webhook.

**Known limitations**

- No background job queue (webhook processed inline in request)
- Campaign send is synchronous in admin request (suitable for small lists)
- Template creation in Meta UI only (not Management API create flow)
- Media: download via Graph; no CDN storage

---

## Files created/updated (integration pass)

| Created | Purpose |
|---------|---------|
| `docs/WHATSAPP_INTEGRATION.md` | This document |
| `sql/whatsapp_contacts_campaigns.sql` | Contacts + campaigns schema |
| `sql/migrate_whatsapp_extensions.php` | Migration runner |
| `includes/whatsapp_contacts.php` | Consent registry |
| `includes/whatsapp_media.php` | Media download |
| `includes/whatsapp_config_status.php` | Masked admin status |
| `admin/whatsapp_campaigns.php` | Campaign UI |
| `admin/whatsapp_contacts.php` | Consent UI |
| `admin/whatsapp_media.php` | Secure media proxy |

| Modified | Purpose |
|----------|---------|
| `includes/whatsapp_cloud.php` | Contact touch, status rank |
| `includes/whatsapp_send.php` | Retry, reply context, marketing filter |
| `admin/whatsapp.php` | Setup status, reply-to, media hints |
| `admin/layout.php` | Nav links |

---

## Rollback

- Revert Git deploy; core WhatsApp tables can remain
- Disable webhook in Meta dashboard to stop inbound processing
- Campaign tables optional; safe to leave empty
