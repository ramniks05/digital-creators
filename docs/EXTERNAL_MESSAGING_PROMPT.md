# External WhatsApp platform — integration prompt

Meta WhatsApp Cloud API was **removed from this repository**. Website forms save to **`leads`**, then call **WhatsJet** for the approved template (when configured).

## What still works here (unchanged for visitors)

1. **Contact / CTA forms** → `POST /api/contact.php`
2. Lead saved in MySQL `leads` (`source = website_form`, `consent_at` when column exists)
3. **WhatsJet** `POST …/contact/send-template-message` with `enquiry_thank_you` (see `includes/whatsjet.php`)
4. Legacy **HTTP POST** webhook only if WhatsJet is **not** configured (`external_messaging_post_lead`)

## Configure this site (WhatsJet — production)

Server env or private config (**never commit `WHATSJET_API_TOKEN`**):

```env
WHATSJET_API_BASE=https://whatsapp.digitalcreatorss.com/api
WHATSJET_VENDOR_UID=your-vendor-uuid
WHATSJET_API_TOKEN=your-bearer-token
WHATSJET_TEMPLATE_NAME=enquiry_thank_you
WHATSJET_TEMPLATE_LANGUAGE=en
WHATSJET_CONTACT_GROUP=Website Form Leads
WHATSAPP_PUBLIC_NUMBER=918368142191
```

When all of `WHATSJET_API_BASE`, `WHATSJET_VENDOR_UID`, and `WHATSJET_API_TOKEN` are set, the generic lead webhook is **not** called (one WhatsApp message per form).

## Optional legacy webhook

```env
EXTERNAL_MESSAGING_ENABLED=1
EXTERNAL_MESSAGING_WEBHOOK_URL=https://your-platform.example.com/webhooks/digitalcreatorss/leads
EXTERNAL_MESSAGING_API_KEY=your-shared-secret
EXTERNAL_MESSAGING_PLATFORM_URL=https://your-platform.example.com/app
WHATSAPP_PUBLIC_NUMBER=918368142191
```

`EXTERNAL_MESSAGING_PLATFORM_URL` adds an optional **Messaging platform** button on the site admin dashboard (not Meta API — just a link to your app).

`WHATSAPP_PUBLIC_NUMBER` is **only** for `wa.me` links on the marketing site (no API calls).

## JSON payload (website form lead)

```json
{
  "event": "website_form_lead",
  "lead_id": 123,
  "name": "…",
  "email": "…",
  "phone": "+91…",
  "phone_normalized": "91…",
  "service": "website-development",
  "service_label": "Website Development",
  "message": "…",
  "budget": "…",
  "source": "website_form"
}
```

Your platform should:

- Send **template / welcome WhatsApp** (e.g. former `enquiry_thank_you`)
- Optionally notify staff
- Run **menu chatbot** if you moved that logic

## Meta webhook

- **Do not** point Meta to `digitalcreatorss.com/webhook/whatsapp` or `/api/hook-wa` on this site (returns **410 Gone**).
- Use your platform’s webhook URL in Meta Developer Console.

## Database cleanup (this site)

After deploy:

```bash
php sql/migrate_remove_whatsapp_integration.php
```

Removes `whatsapp_*`, `chatbot_*`, CRM message/outbound tables. **Keeps** `leads`, `admins`, core CRM contacts if present.

## Prompt for your platform agent (copy/paste)

> Implement webhook `POST /webhooks/digitalcreatorss/leads` accepting the JSON above. On `website_form_lead`, create/update CRM lead, send approved WhatsApp template to `phone_normalized`, and apply the same business rules we had: staff notify optional, dedupe by lead_id, respect opt-out. Inbound WhatsApp and chatbot menu live entirely on this platform; the marketing site only forwards form submissions and exposes wa.me CTAs using public number config.
