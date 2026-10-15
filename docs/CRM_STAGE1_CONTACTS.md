# Stage 1 — Advanced Contact Management

## Audit (Prompt 2 baseline)

- **Existing:** `crm_contacts`, `crm_contact_consents`, `crm_contact_suppressions`, webhook auto-touch, basic API list/create/patch, live inbox.
- **Gaps addressed:** relational tags, custom fields, activity timeline, notes, archive/merge, import/export, duplicate hints, owner/source, admin UI.

## Architecture

- **`CrmContactService`** — tenant-scoped CRUD, archive, merge, consent/opt-out.
- **`contact_tags.php`**, **`contact_activity.php`**, **`contact_custom_fields.php`**, **`contact_import.php`**
- **API:** `includes/crm/api_contacts_routes.php` mounted from `api/v1/index.php`
- **UI:** `admin/crm/contacts/index.php`, `view.php`

## Install

```bash
php sql/migrate_crm_foundation.php
php sql/migrate_crm_phase3.php
php sql/migrate_crm_stage1_contacts.php
```

## Verify

1. Admin → **CRM Contacts** — search, tag filter, import CSV, export.
2. Open a contact — timeline, WhatsApp messages, add note, opt-out/archive.
3. `php tests/crm_contacts_stage1.php` on server with MySQL.

## Rollback

Drop Stage 1 tables only (keep `crm_contacts` core):

`crm_contact_segment_members`, `crm_contact_segments`, `crm_contact_merge_log`, `crm_contact_notes`, `crm_contact_activity`, `crm_contact_custom_values`, `crm_contact_custom_fields`, `crm_contact_tag_assignments`, `crm_contact_tags`

Optional: drop added columns on `crm_contacts` if needed.

## Remaining (Stage 2+)

- Lead pipelines / Kanban
- Tasks & reminders
- Templates UI, automation, campaigns, chatbot, analytics
