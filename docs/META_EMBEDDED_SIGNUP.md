# Meta Embedded Signup — prerequisites (external)

This project includes **scaffold only** (`MetaEmbeddedSignupService`, onboarding session table, wizard page). It does **not** replace Meta App Review or business verification.

## Concepts

| Entity | Role |
|--------|------|
| Meta App | Your SaaS app in developers.facebook.com |
| Business Portfolio | Customer’s Meta business |
| WABA | WhatsApp Business Account |
| Phone number ID | Routing key for webhooks and sends |

## Typical prerequisites (verify on current Meta docs)

- WhatsApp product added to the Meta App
- Embedded Signup configuration + `config_id`
- Authorized OAuth redirect / JS SDK domains (HTTPS)
- Permissions such as `whatsapp_business_management`, `whatsapp_business_messaging` (exact set per current docs)
- **App Review** and **Advanced Access** where required
- Customer **business verification** as required by Meta
- Tech Provider / partner programs if you onboard third-party WABAs at scale

## Server env

```env
META_APP_ID=
META_EMBEDDED_SIGNUP_CONFIG_ID=
CRM_CREDENTIALS_KEY=   # 32+ char secret for encrypting stored tokens
SAAS_STRICT_WEBHOOK=0  # set 1 when all numbers mapped in crm_whatsapp_numbers
```

## Implementation status in repo

- [x] Onboarding session + state token (server)
- [x] Client config endpoint (no secrets)
- [ ] OAuth / code exchange on server
- [ ] WABA webhook subscription via Graph API
- [ ] Token rotation and revocation handling

Do not mark Embedded Signup as production-ready until live callback tests pass in Meta’s test environment.
