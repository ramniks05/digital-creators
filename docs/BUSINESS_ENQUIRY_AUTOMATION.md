# Core business enquiry automation — Digital Creatorss

## What it does

| Channel | Automation |
|---------|------------|
| **Website CTA forms** (`/api/contact`) | Saves lead, records WhatsApp consent on `whatsapp_contacts`, optional **staff alert** on WhatsApp |
| **WhatsApp inbound** | Creates **lead** (deduped by `wa_message_id`), detects **service** from keywords, optional **ack reply** + **services menu** |
| **Admin** | `admin/leads.php` shows source + link to WhatsApp chat |

All outbound automation is **off by default** until you enable it in private config.

## Enable on production

In `domains/.../private/digital_creators_config.php`:

```php
'whatsapp' => [
    'business_enquiry' => [
        'enabled' => true,
        'whatsapp_leads' => true,
        'whatsapp_ack' => true,
        'staff_notify' => true,
        'staff_wa_ids' => '917903152429',  // comma-separated E.164 digits, no +
        'services_menu_enabled' => true,
    ],
    'automation' => [
        'enabled' => true,           // optional: Hi welcome (separate from enquiry ack)
        'welcome_hi_enabled' => false,
    ],
],
```

Or environment variables:

- `DC_ENQUIRY_AUTOMATION=1`
- `DC_ENQUIRY_STAFF_WA_IDS=917903152429`
- `DC_ENQUIRY_STAFF_NOTIFY=1`

### Welcome / auto-reply

| Trigger | How | Template? |
|---------|-----|-----------|
| **WhatsApp message** (Hi or short text) | Text welcome (`inbound_welcome_message`) | No — 24h window |
| **WhatsApp message** (long enquiry) | Text ack with detected service | No |
| **Website form submit** | Approved **Meta template** to customer phone | **Yes** — configure `form_welcome_template` |

Example private config:

```php
'business_enquiry' => [
    'enabled' => true,
    'form_welcome_enabled' => true,
    'form_welcome_template' => 'enquiry_thank_you', // APPROVED on WABA (English)
    'form_welcome_language' => 'en',
    'inbound_welcome_enabled' => true,
    'whatsapp_ack' => true,
],
```

Template variables are filled automatically: **{{1}}** = first name, **{{2}}** = service name (if the template has 2 body params). Use Admin → WhatsApp → Templates to confirm param count. For zero-param templates use `hello_world`.

## Database

```bash
php sql/migrate_leads_enquiry.php
```

Adds `leads.source`, `leads.consent_at`, `leads.wa_message_id`.

## Service detection

Matches your core offerings: Custom Software, Website Development, App Development, Web Applications, Cloud Hosting, Server Management, DevOps, Maintenance (plus DB `services` titles).

Customer can reply **services** / **menu** to receive the list (when automation enabled).

**Repeat messages:** Welcome and enquiry ack are **not sent again** if your business already sent a WhatsApp to that number within the cooldown (default **4 hours**). Typing `services` / `menu` still works anytime.

**One lead per chat (default 24h):** Further WhatsApp messages from the same phone do **not** create new rows in Admin → Leads until `whatsapp_lead_cooldown_hours` passes.

### Where to set (Hostinger — not in Git)

Edit:

`~/domains/digitalcreatorss.com/private/digital_creators_config.php`

```php
<?php
return [
    'whatsapp' => [
        'access_token' => '...',
        'phone_number_id' => '...',
        'business_enquiry' => [
            'enabled' => true,
            'inbound_autoreply_cooldown_hours' => 4,
            'whatsapp_lead_cooldown_hours' => 24,
        ],
    ],
];
```

Env alternatives: `DC_ENQUIRY_COOLDOWN_HOURS`, `DC_ENQUIRY_LEAD_COOLDOWN_HOURS`.

## Compliance

- Forms require **consent** checkbox (homepage, contact page, popup).
- **STOP** still opts out via `whatsapp_automation.php`.
- Staff notifications use `allowOutsideServiceWindow` for your internal numbers only — configure staff IDs you control.

## Public WhatsApp number (+91 83681 42191 / Cloud API)

Website `wa.me` buttons use **`whatsapp_public_chat_digits()`** — by default the **Meta Cloud API display phone** (same as webhook inbox).

1. In private config, set `phone_number_id` and token for the **8368142191** business line.
2. Admin → **Settings** shows resolved Graph number and test `wa.me` link.
3. Optional override: `WHATSAPP_PUBLIC_NUMBER=918368142191` or `'public_number' => '918368142191'` in private config.
4. Clear stale cache if needed: delete `private/wa_display_phone.cache.json` on the server.

## Deploy

```bash
cd ~/domains/digitalcreatorss.com/public_html
git pull origin main
php sql/migrate_leads_enquiry.php
php tests/whatsapp_webhook_test.php
```
