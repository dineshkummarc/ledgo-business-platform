# Ledgo — SME Business Platform

Sell, track, grow: your whole business in one app.

Ledgo targets small businesses in Bangladesh, with Bangla and English interfaces and a Laravel 12 / Filament 3 architecture. **This repository currently contains a working foundation increment, not the completed ERP.** Features below are labelled by their actual implementation status. Do not use it for live financial records yet.

## Current working features

- Public landing page and phone sign-in at `/auth/phone`.
- Six-digit OTP, hashed in the cache, five-minute validity, five failed attempts, one-time consumption, per-IP and per-phone sending limits, session regeneration, and logout.
- BulkSMSBD adapter using server-side credentials. Development defaults to log-only SMS; it cannot silently use that driver in production. No live messages are sent by the test suite.
- Onboarding collects business name, type and language; creates a BDT/BD business, owner membership, main branch, 12-month free-until date, six business roles and an audit entry in one transaction.
- Filament 3 owner panel at `/app`, with business switching and owner-only profile editing. Phone verification is required for panel access.
- Row-scoped branches, settings and audit models. Reads return no rows without a business context; model writes and deletes reject a missing or mismatched context. Context is restored after requests and nested operations.
- Spatie roles scoped by `business_id`: owner, manager, accountant, cashier, stock keeper and viewer. Role assignment and isolation are tested. Feature-specific permissions and staff-management screens are still pending.
- Platform dashboard at `/admin`, served by `imrandevbd/laravel-admin-panel`, protected by a platform-admin check, with real business/user counts instead of the package's sample revenue.
- Universal Slug business slugs, Bangla preservation, collision handling, reserved paths and slug history.
- Drutogoti query-telemetry package and migrations, configuration adapter and telemetry-storage smoke test. Production queue mode, 100 ms slow threshold, N+1 threshold 3, seven-day retention setting and 10% production sampling setting are configured; see package limitations below.
- English/Bangla strings for the new public workflows, business language selection and initial mobile bottom navigation with large controls.
- Demo business seeder for a Bangla grocery, pharmacy and service business. No shared default password is created.
- MySQL/Redis development infrastructure in Docker Compose, frontend build and GitHub Actions checks.

## Setup

Requires PHP 8.3+ with the extensions required by Composer (including intl, mbstring, PDO SQLite/MySQL, zip), Composer 2, and Node.js 22+ with npm. Local verification was run on PHP 8.5.4 with SQLite. CI is configured for PHP 8.3; a hosted CI run and MySQL integration verification have not yet been performed.

```bash
composer install
cp .env.example .env
php artisan key:generate
# For local SQLite, create database/database.sqlite if it does not exist.
php artisan migrate
php artisan filament:assets
npm ci
npm run build
php artisan serve
```

Visit `/auth/phone`. With `SMS_DRIVER=log`, find the development code in `storage/logs/laravel.log`. This mode is local/testing only; log files contain development verification codes and must remain private. Enter the code, then create your business.

Optional local demo data:

```bash
php artisan db:seed --class=DemoBusinessSeeder
```

Demo accounts have random passwords and no configured phone numbers; they are seed data, not shared login accounts. Use your own phone-authenticated account for normal onboarding.

### BulkSMSBD

```dotenv
SMS_DRIVER=bulksmsbd
BULKSMSBD_API_KEY=your-private-api-key
BULKSMSBD_SENDER_ID=your-approved-sender-id
```

Use Bangladesh phone numbers in `8801XXXXXXXXX` format. The adapter submits form fields to the [official BulkSMSBD endpoint](https://bulksmsbd.com/faq.php). Response code 202 means **submitted**, not confirmed delivery. Delivery reports, reminder campaigns, business credit wallets and retries are not implemented yet. The adapter intentionally does not retry OTP submissions automatically, to avoid duplicate charges after ambiguous timeouts.

### MySQL and Redis

```bash
docker compose up -d
```

Compose starts infrastructure only; PHP and Node run on the host. Set `DB_CONNECTION=mysql`, `DB_DATABASE=ledgo`, `DB_USERNAME=ledgo`, and the local password shown in `compose.yaml`; host is `127.0.0.1`. Set the Redis client to match your installed PHP extension/client before using Redis cache/session/queues. Compose credentials are for isolated local development only.

### Platform access

There is no public platform registration. An operator can promote an existing user using `php artisan tinker`:

```php
$user = App\Models\User::where('phone', '8801XXXXXXXXX')->firstOrFail();
$user->forceFill(['is_platform_admin' => true])->save();
```

Sign in through phone OTP and visit `/admin`. Normal business users receive HTTP 403. Platform staff RBAC and audited impersonation remain pending because the installed admin package does not implement its advertised APIs; details are in `docs/PACKAGE_NOTES.md`.

## Feature roadmap and actual status

The complete original requirements are preserved in [docs/MASTER_BUILD_PROMPT.md](docs/MASTER_BUILD_PROMPT.md). This table summarizes every functional module; “pending” means it is not operational.

| Area | Implemented | Pending requirements |
| --- | --- | --- |
| Onboarding | Phone OTP, business creation, five type choices, language, main branch | Three-step wizard, category/unit/account templates, first-action flow, tour, support actions, optional Google login |
| Dashboard | Owner shell; real platform business/user counts | Sales/profit/cash/due cards, charts, alerts, quick actions, daily summaries and cache invalidation |
| Sales and POS | None | Quotations, orders, invoices, challans, returns, custom POS, barcode capture, held bills, split/partial/advance payments, tax, discounts, numbering, recurring sales, sharing, payment tracking, Mushak, A4/A5/thermal templates |
| Purchases | None | Purchase orders, receiving, bills, supplier payments, returns, landed costs and aging |
| Expenses and income | None | Quick expenses, receipt photos, recurring entries, other income, loans, capital and drawings |
| Contacts and khata | None | Customers/suppliers, credit limits, opening balances, tags/groups, loyalty, ledgers, statements, aging, promises and reminders |
| Inventory | None | Products/variants, units, categories, brands, barcodes, warehouses, movements, batches/expiry, serials, stocktake, costing, reorder and bill of materials |
| Accounting | None | Double-entry posting, chart of accounts, journals, cash/bank/day books, reconciliation, trial balance, P&L, balance sheet, cash flow, fixed assets, depreciation and period locks |
| Reports | None | PDF/Excel/CSV exports, scheduling, sales/purchases/profit/stock/VAT/aging/closing/ranking reports and cost-vs-earning summary |
| Smart capture | None | AI OCR, image/PDF/CSV/Excel imports, review/confidence UI, duplicate detection, private originals, rollback, voice, contacts, product-by-photo/link and switch wizard |
| Communications | OTP submission adapter only | SMS/WhatsApp/Messenger/Instagram/email/push hub, delivery tracking, templates, automations, campaigns, opt-outs, fallback, credits and short links |
| Payments | None | bKash, Nagad, Rocket, SSLCOMMERZ, Stripe, transfer verification, pay links, invoice QR and subscriptions |
| HR and payroll | None; misleading success-only attendance routes removed | Attendance Hub machines and virtual check-in, employee mapping, consent/vault isolation, shifts, leave, salaries, advances, commissions and payslips |
| Ask Ledgo | None | Scoped read-only tools, Bangla/English chat/voice, budgets and metering |
| Settings | Owner name/language editor, scoped settings storage | Branding, branch management, taxes, currencies, payment methods, invoice settings, staff/permissions, notification preferences, 2FA, device/PIN controls, full export/deletion and backups |
| Languages | Initial en/bn application strings and tenant locale | Complete validation/localization audit, document locale, numerals, grouping, RTL, extra languages and translation editor/command |
| Billing | Free-until field and confirmed volume components in configuration | Configurable pricing rules, integer-money calculations, boundary tests, usage meters, estimates, month-end bills, 7-day grace/read-only, coupons/referrals/resellers and paid credits |
| API | Sanctum installed and token schema available | `/api/v1` business endpoints, token lifecycle, Scribe docs, idempotency, API policies and rate limits |
| Android / PWA | None | Manifest/icons, service worker, IndexedDB queue, conflict handling, offline sales/payments, Capacitor, camera, FCM, share, contacts, printer, biometrics, links and store pack |
| Security / operations | Session/OTP protections, initial tenant tests, panel authorization, CI definition | Financial audit coverage, malware hook, encrypted channel data, consent/deletion, backups/restore, Sentry, Horizon, Semgrep, Larastan, browser checks, coverage targets and load tests |

## Confirmed pricing requirements — not implemented yet

The owner confirmed that monthly volume means **sales + purchases + expenses**. Planned pricing is free for the first 12 months, then ৳10 per started ৳100,000 of monthly volume, minimum ৳10, with configurable rates, seven-day grace and read-only enforcement. No billing or collection currently occurs. Money must use integer minor units or exact decimals when Phase 2 is implemented.

## Packages

| Package | Status |
| --- | --- |
| `imrandevbd/drutogoti` | Installed; query telemetry smoke-tested; advertised dashboard/watchers have gaps |
| `imrandevbd/laravel-admin-panel` | Installed; protected dashboard shell; advertised RBAC/resource APIs absent |
| `imrandevbd/laravel-universal-slug` | Installed; business slugs/history in use; tested |
| `imrandevbd/laravel-unicode-pdf` | Pending Phase 2 |
| `imrandevbd/laravel-ai-hub` | Pending Phase 5 |
| `imrandevbd/laravel-ai-catalog` | Pending Phase 5 |
| `imrandevbd/laravel-attendance-hub` | Pending Phase 8 |

Versions and commit references are pinned in `composer.lock`; reviewed APIs, adaptations and missing package functionality are recorded in [docs/PACKAGE_NOTES.md](docs/PACKAGE_NOTES.md). No vendor package has been forked or patched.

## Architecture

```text
app/
  Filament/Pages/               Owner profile UI
  Http/Controllers/            Public phone authentication and onboarding
  Http/Middleware/             Locale, tenant and platform access
  Models/Concerns/             BelongsToBusiness fail-closed scope
  Modules/Auth/                OTP and business role assignment
  Support/Integrations/        SMS, slug, profiler and platform adapters
  Support/Tenancy/             Scoped business context
```

Tenant models must use `BelongsToBusiness`, disallow mass assignment of `business_id`, and run under `BusinessContext::run()`. Queued work will need an explicitly established and authorized business context. Global scopes do not protect raw SQL or deliberately unscoped queries; new features require their own authorization and isolation tests. Business membership remains the authority for access to a business.

## Verification

```bash
php artisan test
vendor/bin/pint --test
composer validate --strict
composer audit
npm run build
```

The foundation currently has 14 passing tests / 41 assertions, including homepage rendering, both panels, tenant isolation, owner membership, onboarding, role isolation, OTP expiry/single use/attempt limits, fake SMS submission, slug collisions and real profiler storage. These do not prove accounting, offline behavior, production SMS delivery, accessibility, performance targets or missing modules.

## Delivery phases

1. **Foundation — in progress:** working increment delivered; remaining package gaps, complete mobile theme/style guide, fine permissions/settings, full localization and deployment validation are tracked in `docs/PHASE_STATUS.md`.
2. Core money: contacts, products, sales/POS, payments, expenses, purchases, accounting, dashboard, basic reports, Unicode PDF.
3. Inventory and khata.
4. Communication hub.
5. Smart capture with AI Hub and AI Catalog.
6. Billing and gateway payments.
7. PWA and Capacitor Android.
8. HR, Ask Ledgo, advanced reporting and production hardening.

The master specification requires a review and “go” between phases. A phase is not complete merely because its dependencies are installed.

## 🤝 Let's Connect & Collaborate

I am open to **Senior Remote Full-Stack Roles**, **AI Platform Architecture Contracts**, and **Enterprise Technical Advisory**.

- **Timezone:** UTC+6 (Dhaka / Rangpur, Bangladesh) — Flexible overlap with **US, UK, and European** business hours.
- **Delivery Mode:** Async-ready, Slack, Discord, Jira, GitHub, and production-first accountability.

| **Channel** | **Address / Handle** | **Quick Action** |
| --- | --- | --- |
| 🌐 **Primary Portfolio** | [imrandev.bd](https://imrandev.bd/) | [Visit Site ↗](https://imrandev.bd/) |
| 📦 **Packagist Packages** | [packagist.org/packages/imrandevbd/](https://packagist.org/packages/imrandevbd/) | [View Packages ↗](https://packagist.org/packages/imrandevbd/) |
| 💼 **LinkedIn Profile** | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) | [Connect ↗](https://linkedin.com/in/imranbru99) |
| 🐙 **GitHub Profile** | [github.com/imranbru99](https://github.com/imranbru99) | [Follow ↗](https://github.com/imranbru99) |
| 💬 **WhatsApp Direct** | [+880 1576-918420](https://wa.me/8801576918420) | [Chat Now ↗](https://wa.me/8801576918420) |
| 📧 **Direct Email** | [me@imrandev.bd](mailto:me@imrandev.bd) | [Send Email ↗](mailto:me@imrandev.bd) |
| 🐦 **X (Twitter)** | [@imrandev_bd](https://x.com/imrandev_bd) | [Follow ↗](https://x.com/imrandev_bd) |
| 📺 **YouTube Tech** | [@ImranDevBD](https://youtube.com/@ImranDevBD) | [Subscribe ↗](https://youtube.com/@ImranDevBD) |
