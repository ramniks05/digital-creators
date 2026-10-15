# WhatsApp rule-based chatbot

## Requirements

- CRM foundation + phase 3 migrations applied
- Meta WhatsApp Cloud API configured (same as CRM inbox)
- No extra paid tools

## Database

```bash
php sql/migrate_crm_chatbot.php
```

Creates `chatbot_sessions`, `chatbot_services`, `chatbot_assets`, `agent_handoffs` and seeds default services for the default tenant.

## Enable

Set in environment or private config:

```env
WHATSAPP_CHATBOT_ENABLED=1
```

When enabled, the chatbot runs after opt-out handling and before legacy business-enquiry automation.

## Brochure PDF

Generate a **dummy test PDF** (replace later with your portfolio):

```bash
php scripts/generate_dummy_brochure.php
```

Default file: `assets/uploads/brochures/digital-creatorss-brochure.pdf`

Or set `CHATBOT_BROCHURE_PATH` to an absolute path on the server.

On first “Get brochure”, the app uploads the file to Meta and stores `meta_media_id` in `chatbot_assets`. If send fails (expired media), it re-uploads automatically.

## CRM agent controls

In **Inbox → Details**, use:

- **Take over** — assigns you, pauses the bot (`mode=human`)
- **Resume bot** — returns automation
- **Close enquiry** — resolves conversation and closes the handoff queue row

API: `POST /api/v1/conversations/{id}/chatbot/takeover|resume|close|transfer`

## Worker

Outbound replies use `crm_outbound_jobs`. Ensure cron runs:

```bash
php scripts/crm_outbound_worker.php
```

## Test order

1. Welcome menu and service list  
2. Brochure  
3. Name → requirement → budget → timeline  
4. Lead in `leads` (`source=whatsapp`)  
5. “Talk to agent” + take over in inbox  
6. Duplicate webhook IDs (ignored) and returning customer (`menu` retains saved answers)
