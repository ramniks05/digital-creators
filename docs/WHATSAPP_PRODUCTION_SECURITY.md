# WhatsApp Webhook — Production Security & Automation (Digital Creatorss)

## Architecture summary

| Layer | Location |
|--------|-----------|
| Public URL | `https://digitalcreatorss.com/api/hook-wa` → `api/hook-wa.php` |
| Handler | `includes/whatsapp_cloud.php` |
| Secrets | `domains/.../private/digital_creators_config.php` or env vars |
| Database | MySQL via PDO — inbound, outbound, status log, dedupe, contacts |
| Automation | `includes/whatsapp_automation.php` (off by default) |
| Diagnostics | `private/wa_webhook_trace.jsonl`, PHP `error_log` |

**Flow:** GET → Meta verify token → echo challenge. POST → HMAC-SHA256 on raw body → JSON parse → transaction (dedupe + persist) → **200** `EVENT_RECEIVED` → optional automation (opt-out / welcome) **after** commit.

## Security controls (implemented)

1. **Webhook verification** — GET `hub.verify_token` compared with `hash_equals`; POST `X-Hub-Signature-256` vs `META_APP_SECRET`.
2. **Configuration** — No secrets in Git; loaded from private file / env.
3. **Idempotency** — `whatsapp_webhook_dedupe` keys: `inbound:{wamid}`, `status:{wamid}:{status}:{ts}`, `autoreply:{kind}:{wamid}`.
4. **HTTP semantics** — Invalid signature → **403**; bad JSON → **400**; DB failure → **500** (Meta retries); success → **200**.
5. **Status tracking** — Rank-aware updates on `whatsapp_outbound_messages`; history in `whatsapp_message_status_log`.
6. **Automatic replies** — **Disabled by default.** Welcome-on-Hi only when both `WHATSAPP_AUTO_REPLY` and `WHATSAPP_WELCOME_HI` are true.
7. **STOP / opt-out** — Inbound text triggers `whatsapp_contact_record_opt_out` (always, independent of auto-reply flag).
8. **Logging** — `whatsapp_log_event()` / trace file: no tokens, no message bodies; masked phone via `whatsapp_mask_wa_id()`.

## Gaps / operational notes

- **Background queue:** Not implemented (Hostinger shared hosting). Heavy work should stay out of the webhook; current automation only runs a single quick Graph send after 200-boundary commit.
- **HTTP integration tests:** Signature tests against live config require `META_APP_SECRET` on the server; CLI tests use crypto/unit checks in `tests/whatsapp_webhook_security_test.php`.
- **Do not** enable welcome auto-reply until you accept Graph API sends from production.

## Files changed (security pass)

| File | Why |
|------|-----|
| `includes/whatsapp_automation.php` | Opt-out, optional welcome, deduped auto-reply |
| `includes/whatsapp_cloud.php` | Return new inbound list; run automation after commit |
| `includes/whatsapp_webhook_diagnostics.php` | Masked IDs + structured logging |
| `includes/config.php` | Automation env defaults (all off) |
| `tests/whatsapp_webhook_security_test.php` | Unit/security tests |
| `docs/WHATSAPP_WEBHOOK.md` | Env vars + link here |

## Environment variables

See `docs/WHATSAPP_WEBHOOK.md`. Minimum for webhook: `WHATSAPP_WEBHOOK_VERIFY_TOKEN`, `META_APP_SECRET`, DB vars. For outbound/admin: `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`.

Example private override (automation stays off until you opt in):

```php
'whatsapp' => [
    'automation' => [
        'enabled' => false,
        'welcome_hi_enabled' => false,
    ],
],
```

To enable welcome reply only after review:

```php
'automation' => [
    'enabled' => true,
    'welcome_hi_enabled' => true,
],
```

## Deployment

```bash
cd ~/domains/digitalcreatorss.com/public_html
git pull origin main
php sql/migrate_whatsapp_webhook.php
php sql/migrate_whatsapp_extensions.php   # contacts table for opt-out
php tests/whatsapp_webhook_test.php
php tests/whatsapp_webhook_security_test.php
```

Ensure `private/` is **not** web-accessible and `digital_creators_config.php` is present on the server.

## Rollback

1. `git checkout <previous-commit> -- includes/whatsapp_cloud.php includes/whatsapp_automation.php` (or full `git revert`).
2. Redeploy; webhook continues to verify and persist without automation if `whatsapp_automation.php` is removed from the post-commit hook (older `whatsapp_cloud.php` had no automation call).
3. No DB rollback required for automation (uses existing dedupe table).

## Production verification checklist

- [ ] Meta webhook URL still `https://digitalcreatorss.com/api/hook-wa` — GET verify succeeds in Developer Console.
- [ ] `META_APP_SECRET` matches App → Basic (signature failures → 403 in trace).
- [ ] Send a test message from your phone → row in `whatsapp_inbound_messages` and admin chat.
- [ ] Send STOP → `whatsapp_contacts.consent_status = opted_out`.
- [ ] With automation **off**, Hi message does **not** trigger automatic Graph send.
- [ ] Outbound from admin → status progresses sent → delivered in portal.
- [ ] `private/wa_webhook_trace.jsonl` shows `webhook_post_ok` without secrets.

## Test commands

```bash
php tests/whatsapp_webhook_test.php
php tests/whatsapp_webhook_security_test.php
```

Do not run live message campaigns during tests unless explicitly approved.
