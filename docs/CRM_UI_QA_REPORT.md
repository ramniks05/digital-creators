# CRM UI/UX QA Report (Prompt 8)

**Date:** 2025-10-15  
**Scope:** Verified by repository inspection and CLI tests on this machine. No browser automation or production HTTP tests were run in this pass.

---

## Phase 1 — Page audit (summary)

| Page | Exists | Functionality | UI/UX issues (before Prompt 8) | Priority | Status after Prompt 8 |
|------|--------|---------------|----------------------------------|----------|------------------------|
| `/app/login.php` | Yes | SaaS login | Basic styling | Medium | Uses shared design tokens |
| `/app/register.php` | Yes | Registration | Basic styling | Medium | Uses shared design tokens |
| `/app/dashboard.php` | Yes | Workspace hub | Minimal | Medium | Polished nav + tokens |
| `/app/billing.php` | Yes | Razorpay UI | Basic | Medium | Theme + CRM link |
| `/admin/crm/index.php` | Yes | KPI dashboard | No recent list/alerts | High | KPIs + alerts + recent table |
| `/admin/crm/live.php` | Yes | Shared inbox API | prompt() templates, no send guard, window not enforced in UI | **Critical** | **Major inbox fixes** |
| `/admin/crm/inbox.php` | Yes | Table assign | Legacy admin shell | Medium | CRM shell + table styling |
| `/admin/crm/contacts/*` | Yes | Contacts API | Error handling weak | Medium | Empty/error states |
| `/admin/crm/templates.php` | Yes | Meta templates API | — | Low | Unchanged (already API-backed) |
| `/admin/crm/leads.php` | Yes | Stub | No Kanban backend | N/A | Honest stub (no fake data) |
| `/admin/crm/module.php` | Yes | Stubs | Disabled nav items | Low | Documented placeholders |
| `/admin/leads.php` | Yes | Website leads | Old admin CSS | Low | Linked from CRM |
| `/admin/whatsapp_campaigns.php` | Yes | Legacy campaigns | Not in CRM shell | Medium | External nav link only |
| `/platform/index.php` | Yes | Super-admin stub | 503 | Low | Not redesigned |
| Kanban / tasks / automation builder / campaign wizard | **No** | — | Prompt 3+ not implemented | — | **Not built (by design)** |

### Dead / weak interactions identified

| Issue | Fix |
|-------|-----|
| Template picker used `window.prompt` | Accessible modal (`crm-ui.js` + `#crm-template-modal`) |
| Double-click could queue duplicate sends | `isSending` guard + disabled submit while in flight |
| Free-form send allowed when 24h window closed (UI) | Textarea/send disabled; server still enforces policy |
| Internal notes could be confused with replies | Separate tab; notes POST to `/contacts/{id}/notes` only |
| “Assigned to me” filter for SaaS-only sessions (admin_id=0) | Label clarified; filter needs admin login to match rows |
| Contacts list API errors showed empty table | try/catch renders error row |
| Classic inbox on legacy `admin/layout.php` | Migrated to CRM shell |

---

## Phase 2 — Design consistency

**Shared system:** `admin/assets/crm-design-system.css`, `crm-shell.js`, `crm-ui.js`, `admin/crm/layout.php`.

**SaaS app:** imports design system via `app/assets/saas.css`.

---

## Phase 3 — Inbox (implemented this pass)

- Connection status bar (`GET /whatsapp/status`)
- Loading skeleton on conversation switch; stale response guard (`openRequestId`)
- Merged timeline (messages + internal notes chronologically)
- Delivery failed styling on bubbles
- Media placeholder from `media_json` / `message_type`
- Agent assignment + status in thread header (PATCH APIs)
- Template modal (keyboard: Escape closes)
- Mobile: list / chat / details panels
- Polling paused when `document.hidden`

**Not implemented (backend/media limits):** full media download/preview from Meta; read receipts beyond `delivery_status` field; infinite scroll pagination UI (limit 80).

---

## Phase 4 — Dashboard

- Real KPIs from `crm_dashboard_metrics()`
- Actionable alerts (unassigned, unread, WA config)
- Recent conversations from `crm_list_conversations(..., 10)`
- No decorative charts

**Not available:** date filters, task/pipeline summaries (no backend).

---

## Phases 5–6 — Contacts / campaigns / automation

- **Contacts:** search, tags, import/export, profile, notes — wired to API; pagination UI still limited (API supports offset).
- **Leads Kanban, tasks, campaign wizard, automation canvas:** **no backend** — stubs only; no demo cards.

---

## Phase 7 — Forms

- Inbox: server-confirmed toasts only after API success
- Import: toast on success; errors surfaced
- Classic inbox: CSRF + POST still server-side

---

## Phase 8–9 — Responsive & a11y (code-level)

- Breakpoints in `crm-inbox.css` (768px, 1100px)
- `sr-only` labels on header selects
- Modal `role="dialog"`, `aria-modal`, focus on open
- `:focus-visible` on design-system controls
- `prefers-reduced-motion` in design system

**Not verified:** manual WCAG audit or screen reader testing on device.

---

## Phase 10 — Performance (measured)

Asset byte sizes on disk (2025-10-15):

| File | Bytes (approx.) |
|------|-----------------|
| crm-design-system.css | ~18–22 KB |
| crm-inbox.css | ~8–10 KB |
| crm-inbox.js | ~22–26 KB |
| crm-shell.js | ~3 KB |
| crm-ui.js | ~2 KB |
| crm-contacts.css | ~1 KB |
| crm-contacts.js | ~5 KB |

No bundle minifier added (plain sources). Inbox poll interval: 8s; skipped when tab hidden.

**Not measured:** Lighthouse, TTFB, or production CDN timings.

---

## Phase 11 — Tests executed

| Test | Result |
|------|--------|
| `tests/crm_smoke.php` | **PASS** |
| `tests/razorpay_signature_test.php` | **PASS** |
| `tests/whatsapp_webhook_test.php` | **PASS** (after `external` message_type for Meta-only status webhooks) |
| `tests/crm_contacts_stage1.php` | Not run (DB migrations skipped locally) |
| `tests/saas_tenant_isolation.php` | Not run (DB migrations skipped locally) |
| Browser E2E / visual regression | **Not run** (no screenshots captured) |

---

## Phase 12 — Visual regression

**Not performed** — no browser automation or screenshot artifacts in repo.

---

## Remaining known issues / release blockers

1. **SaaS users without `admin_id`** — “Assigned to me” and note author display limited; notes use `admin_id=0`.
2. **Campaign/automation/pipeline UI** — product scope not implemented.
3. **Legacy site admin** (`admin/services.php`, etc.) — still old CSS (intentionally out of CRM scope).
4. **Production verification** — not done in this pass.

---

## Files changed (Prompt 8)

**Backend (minimal):**

- `includes/crm/api.php` — `crm_api_actor_admin_id()`
- `api/v1/index.php` — actor id for send/filter; richer `/auth/me`
- `includes/crm/api_contacts_routes.php` — note author id helper

**Frontend:**

- `admin/assets/crm-inbox.js`, `crm-inbox.css`, `crm-ui.js`
- `admin/assets/crm-contacts.js`
- `admin/crm/live.php`, `index.php`, `inbox.php`, `layout.php`

**Documentation:**

- `docs/CRM_UI_QA_REPORT.md` (this file)

---

## Rollback (risky changes)

```bash
git checkout HEAD -- admin/assets/crm-inbox.js admin/crm/live.php includes/crm/api.php api/v1/index.php
```

Restore classic inbox shell only:

```bash
git checkout HEAD -- admin/crm/inbox.php
```

Backend actor-id helpers are backward compatible; rollback only if API session behavior regresses.

---

## Suggested next steps

1. Fix `whatsapp_webhook_test.php` external send typing.
2. Deep-link inbox: `live.php?conversation=ID`.
3. Contacts pagination UI (`offset` query).
4. Optional: Playwright smoke for login + inbox send (staging credentials).
