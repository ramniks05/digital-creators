# Meta WhatsApp Cloud API Webhook — Digital Creatorss

## Implementation plan (summary)

| Finding | Action |
|--------|--------|
| Plain PHP + PDO (`includes/db.php`, `includes/config.php`) | Reuse existing stack |
| Public endpoints under `api/` | Added `webhook/whatsapp.php` (Meta-friendly path) |
| No Graph API send module yet (only `wa.me` links) | Added `whatsapp_outbound_messages` + helpers to link future sends to delivery webhooks |
| No WhatsApp tables | Added migration `sql/whatsapp_webhook.sql` |

## Files

| File | Purpose |
|------|---------|
| `webhook/whatsapp.php` | GET verify + POST events |
| `includes/whatsapp_cloud.php` | Signature verify, parse, persist |
| `includes/config.php` | `whatsapp` config keys (env + local override) |
| `sql/whatsapp_webhook.sql` | Table definitions |
| `sql/migrate_whatsapp_webhook.php` | One-time migration runner |
| `sql/patch_whatsapp_contact_name.sql` | Adds `contact_name` on existing DBs |
| `tests/whatsapp_webhook_test.php` | Synthetic tests |

## Meta webhook spec alignment

- **Payload limit:** Meta allows up to **3 MiB** per webhook; the endpoint enforces the same maximum (`WHATSAPP_WEBHOOK_MAX_BYTES`).
- **Subscribed field:** This URL processes only the **`messages`** field (inbound chat + outbound delivery/read/failed statuses). Other dashboard fields (`calls`, `message_template_status_update`, `account_update`, etc.) are acknowledged with HTTP 200 if signed but are **not** stored unless you extend `includes/whatsapp_cloud.php`.
- **Contact names:** When Meta sends `value.contacts[].profile.name`, it is saved on `whatsapp_inbound_messages.contact_name` for the matching `wa_id`.
- **Retries:** Non-200 responses are retried for up to 7 days; dedupe keys prevent duplicate rows when Meta resends the same event.

## Configuration

Secrets must **not** be committed. Use either:

1. **Hostinger private config** (recommended for production):  
   `domains/digitalcreatorss.com/private/digital_creators_config.php`

2. **Local**: `includes/config.local.php` (gitignored)

3. **Server environment variables** (Apache/cPanel or PHP-FPM)

| Key | Env variable | Purpose |
|-----|----------------|---------|
| Webhook verify token | `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | Meta GET verification |
| App secret | `META_APP_SECRET` | `X-Hub-Signature-256` validation |
| Access token | `WHATSAPP_ACCESS_TOKEN` | Outbound Graph API (existing/future send code) |
| Phone Number ID | `WHATSAPP_PHONE_NUMBER_ID` | Outbound + metadata matching |
| API version | `WHATSAPP_API_VERSION` | Optional, default `v21.0` |

Example private PHP override:

```php
'whatsapp' => [
    'access_token' => 'EAA...',
    'phone_number_id' => '123456789012345',
    'webhook_verify_token' => 'your-long-random-verify-token',
    'app_secret' => 'your-meta-app-secret',
],
```

Generate a strong verify token (32+ random bytes). **Never** log tokens or app secrets.

## Database migration

```bash
php sql/migrate_whatsapp_webhook.php
```

Tables:

- `whatsapp_inbound_messages` — customer → business messages (includes optional `contact_name` from webhook `contacts`)
- `whatsapp_message_status_log` — sent / delivered / read / failed history
- `whatsapp_webhook_dedupe` — idempotency for Meta retries
- `whatsapp_outbound_messages` — business → customer (for send module + status updates)

## Meta Developer Dashboard

1. Open [Meta for Developers](https://developers.facebook.com/) → your app.
2. **WhatsApp** → **Configuration** (or **Webhooks**).
3. **Callback URL** (pick one that returns HTTP 200 on GET verify, no 301/302 HTML):
   - `https://digitalcreatorss.com/api/hook-wa` (recommended on Hostinger; uses `.htaccess` internal rewrite)
   - Health check: `https://digitalcreatorss.com/api/wa-health`
   - `https://digitalcreatorss.com/webhook/whatsapp` (extensionless; avoid `.php` under `/webhook/` — Hostinger may redirect)
   - If you get **301**, try the same path on **`www.digitalcreatorss.com`** or check `Location:` with `curl -sSI … | grep -i location`
4. **Verify token**: same value as `WHATSAPP_WEBHOOK_VERIFY_TOKEN` on the server.
5. Click **Verify and save**.
6. Subscribe to the **`messages`** field (incoming messages + status updates).
7. Under **WhatsApp** → **API Setup**, confirm the **WhatsApp Business Account** is linked to the app.
8. In **App settings** → **Basic**, note **App secret** → set as `META_APP_SECRET`.

### Development vs production

| Mode | Behavior |
|------|----------|
| **Development** | Test numbers, limited recipients, dashboard test tools; webhooks still require HTTPS. |
| **Live** | App mode **Live**, Business Verification as required, approved templates for outbound marketing; production WABA subscribed to the app. |

Permissions typically used: `whatsapp_business_management`, `whatsapp_business_messaging` (as required by your app type).

## Outbound send integration (when you add Graph API sends)

After a successful send response from Graph API:

```php
require_once __DIR__ . '/includes/whatsapp_cloud.php';

$rowId = whatsapp_record_outbound_message('919876543210', 'template', null, null, 'hello_world', ['lang' => 'en']);
whatsapp_attach_outbound_message_id($rowId, $graphMessageId);
```

Webhook status events update `whatsapp_outbound_messages.delivery_status` by `wa_message_id`.

**No auto-reply** is implemented on inbound messages.

## Testing

### Automated (synthetic)

```bash
php tests/whatsapp_webhook_test.php
```

### GET verification (browser or curl)

```bash
curl -s "https://digitalcreatorss.com/webhook/whatsapp.php?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=12345"
```

Expected: body `12345`, HTTP 200. Wrong token → HTTP 403.

### POST with signature

```bash
BODY='{"object":"whatsapp_business_account","entry":[]}'
SIG="sha256=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "YOUR_APP_SECRET" | awk '{print $2}')"
curl -s -X POST "https://digitalcreatorss.com/webhook/whatsapp.php" \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: $SIG" \
  -d "$BODY"
```

Expected: HTTP 200, body `EVENT_RECEIVED`.

### Test plan checklist

- [ ] GET verify success / wrong token (403)
- [ ] POST invalid signature (403)
- [ ] POST valid empty payload (200)
- [ ] Inbound text message stored once; duplicate retry ignored
- [ ] Inbound image metadata in `media_json`
- [ ] Status sent → delivered → read logged; outbound row updated
- [ ] Failed status with `errors[]` stored
- [ ] Multiple entries in one payload
- [ ] Malformed JSON (400)
- [ ] Oversized body (413 above 3 MiB)
- [ ] DB down (500, Meta retries)

## Hostinger / Apache deployment

1. Deploy code via Git (webhook file must be under `public_html/webhook/whatsapp.php`).
2. Run `sql/migrate_whatsapp_webhook.php` via SSH or import `sql/whatsapp_webhook.sql` in phpMyAdmin.
3. Add WhatsApp secrets to **private** `digital_creators_config.php` (not `public_html`).
4. Confirm **HTTPS** is active (Meta requires public HTTPS).
5. Hit GET verify URL from Meta dashboard.

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| HTTP 403 on GET | Verify token mismatch or `hub.mode` not `subscribe` |
| HTTP 403 on POST | Wrong `META_APP_SECRET` or body altered (proxy/mod_security) |
| HTTP 404 | File not deployed or wrong URL path |
| HTTP 500 POST | DB tables missing or DB credentials; check PHP error log |
| HTTP 500 POST, log mentions app secret | `META_APP_SECRET` not set |
| No events | `messages` field not subscribed; WABA not linked; app in Dev without test number |
| Duplicate rows | Should not happen if dedupe table exists; check migration |

## Security notes

- Signature verification is **required** for POST when `META_APP_SECRET` is configured.
- Raw body is read once for HMAC; max size 256 KiB.
- Prepared statements only; minimal logging (no access tokens, no full message bodies in logs by default).
