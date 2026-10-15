# How to continue (Prompt 6+)

Development is **paused** after Prompt 5 handoff. Do not assume production matches local until you deploy and migrate.

## 1. Stabilize before new features

1. **Commit / deploy** local WIP (or merge via PR) so `origin/main` matches workspace.
2. On **staging**, run all migrations in order (see `PROJECT_STATUS.md`).
3. Fix **`tests/whatsapp_webhook_test.php`** failure (`External send typed as external or template`).
4. Re-run full test suite with DB populated:

   ```bash
   php tests/whatsapp_webhook_security_test.php
   php tests/whatsapp_webhook_test.php
   php tests/crm_smoke.php
   php tests/crm_contacts_stage1.php
   php tests/saas_tenant_isolation.php
   php tests/razorpay_signature_test.php
   ```

5. **Smoke production** (manual): Meta test message → webhook → inbox; agent reply → queue/worker → delivery status.

## 2. Prompt 6 — suggested focus (when you resume)

Pick one track based on business priority:

### Track A — Production readiness

- Deploy uncommitted CRM/SaaS to Hostinger (`git pull`, migrations, cron worker).
- Set `RAZORPAY_*`, `CRM_CREDENTIALS_KEY`, keep `SAAS_STRICT_WEBHOOK=0` until all numbers mapped; then enable strict mode.
- SMTP for email verification (remove reliance on `SAAS_EXPOSE_VERIFY_TOKEN`).
- MySQL backup schedule + rollback drill.

### Track B — Prompt 3 remainder (product)

- Stage 2: **Lead pipeline** (`crm_leads`, Kanban, APIs) — stages partially seeded in SaaS provisioner.
- Stage 3: **Tasks / reminders** linked to contacts/leads.
- Stage 4: **Template manager** UI (wrap existing `whatsapp_fetch_message_templates_result`).

### Track C — Prompt 4 remainder (SaaS)

- Complete **Meta Embedded Signup** server-side token exchange + WABA webhook subscription per current Meta docs.
- **Super-admin** auth + tenant list (no default access to message bodies).
- **Quota enforcement** tying `crm_subscription_plans.limits_json` to API/inbox actions.

### Track D — Prompt 5 (if your Prompt 5 scope differs)

Document your Prompt 5 checklist in an issue and map each item to repo paths; this handoff treated Prompt 5 as **SaaS + Razorpay billing** completion locally.

## 3. Configuration checklist (secrets — you fill)

Private `digital_creators_config.php` or server env:

- Existing: `WHATSAPP_*`, `META_APP_SECRET`
- SaaS: `CRM_CREDENTIALS_KEY`, `SAAS_APP_URL`, optional `SAAS_STRICT_WEBHOOK`
- Meta signup: `META_APP_ID`, `META_EMBEDDED_SIGNUP_CONFIG_ID`
- Razorpay: `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`
- Map Razorpay Plan IDs in `crm_subscription_plans`

See `docs/RAZORPAY_BILLING.md`, `docs/META_EMBEDDED_SIGNUP.md`, `docs/SAAS_INSTALL.md`.

## 4. What not to do on resume

- Do not restart stack (stay Core PHP + MySQL).
- Do not enable marketing campaigns before compliance + queue are verified on staging.
- Do not set `SAAS_STRICT_WEBHOOK=1` until every production `phone_number_id` is in `crm_whatsapp_numbers`.

## 5. First command when you return

```bash
git status
cat PROJECT_STATUS.md
```

Then tell the agent: *"Resume from NEXT_STEPS.md Track [A/B/C]"*.
