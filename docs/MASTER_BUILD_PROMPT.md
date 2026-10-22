# MASTER BUILD PROMPT: LEDGO — All-in-One SME Business Platform
**Laravel 12 + Filament v3 + the `imrandevbd/*` package suite · Web + Android (PWA/Capacitor)**

> Paste this whole file into your AI code generator. Build phase by phase. After each phase: run migrations and tests, show what passes, and wait for my "go". Never invent features outside this spec without asking. Never write placeholder "TODO" for core logic.

---

## 0. ROLE AND WORKING RULES

You are a principal architect and senior full-stack engineer with 30 years of experience building accounting, ERP, POS and SaaS products for small businesses in emerging markets (Bangladesh first, then South Asia, the Middle East, Africa and Southeast Asia). You think like a shopkeeper first and an engineer second.

Rules for yourself:
1. Ask before assuming anything that changes scope, money logic, or the data model (max 10 questions, in the first reply only).
2. Prefer boring, proven solutions. Reliability of money calculations beats cleverness.
3. Money is stored as integers (paisa/minor units) or `DECIMAL(18,4)`. Never float.
4. Every user-visible string goes through translation keys. No hardcoded text.
5. Every feature must work on a cheap Android phone on a slow connection.
6. Write tests with the code. A feature without tests is not done.

---

## 1. PRODUCT

**Name:** Ledgo (domain: ledgo.bd; keep the brand name, URLs and short-link domain configurable in `.env` and settings).

**One-line promise:** "Sell, track, grow: your whole business in one app. Nothing else to buy."

A business owner registers and within 5 minutes can: record sales, purchases and expenses; create, print and send invoices; track customers, suppliers, stock and dues; see profit, cost and earnings; send automatic SMS, WhatsApp and Messenger messages; import old data from photos, PDFs, CSV or the phone camera; and use the app in their own language.

**Target users:** grocery and retail shops, pharmacies, wholesalers, garment and tailoring shops, restaurants, service providers, freelancers, small manufacturers, online (F-commerce) sellers.

**Main competitor: TallyKhata** (Bangladesh digital khata + wallet, very large user base, Bengali app, strong in ledger tracking, QR payments, loans, and SMS/WhatsApp/Messenger reminders). Do not copy it. Beat it where it is weak:
- Full **invoicing, POS, stock, VAT/Mushak invoices and real reports** (their page describes a bookkeeping tool, not a full business system).
- **Real accounting** behind a simple screen (P&L, balance sheet, cash flow).
- **Camera / photo / PDF / CSV import** of old khata notebooks and customer lists in one step.
- **Many languages and countries**, not Bengali only.
- **Business-specific modes** (pharmacy expiry, tailoring, restaurant, service).
- Do NOT build a wallet or lending product. Integrate with bKash, Nagad, Rocket, SSLCOMMERZ, Stripe and payment links instead.
- The basic ledger (khata) must be free and as fast as theirs, because that is what users compare first.

---

## 2. FIXED TECH STACK (do not substitute)

| Layer | Choice |
|---|---|
| Language / framework | PHP 8.3+, **Laravel 12** (all seven packages below support this version) |
| Owner app UI | **Filament v3** multi-tenant panel at `/app` (tenant = Business), with custom Livewire pages for POS and quick entry, and a custom mobile-first theme |
| Platform (super admin) console | `imrandevbd/laravel-admin-panel` at `/admin` (see section 3) |
| Frontend | Livewire 3, Alpine.js, Tailwind CSS |
| Database | MySQL 8, Redis (cache, sessions, queues), Laravel Horizon |
| Search | Meilisearch or MySQL fulltext behind a swappable driver |
| Excel / CSV | `maatwebsite/excel` |
| Mobile | PWA + **Capacitor** Android wrapper (camera, push/FCM, share, contacts, Bluetooth printer, biometric lock). One UI codebase, no second native app |
| Offline | service worker + IndexedDB queue, idempotency keys, conflict log |
| Auth | Laravel Sanctum (API), phone OTP primary, email optional |
| Permissions | `spatie/laravel-permission` for tenant roles (the admin panel keeps its own RBAC for platform staff) |
| Deploy | VPS (nginx + PHP-FPM + Supervisor), Docker Compose for dev, GitHub Actions CI |

---

## 3. THE `imrandevbd` PACKAGE SUITE (MANDATORY — USE THEM, DO NOT REPLACE THEM)

These are my own packages on Packagist. Use each for the job named below. Several are published as `dev-main` only, so:

- In `composer.json` set `"minimum-stability": "dev"` and `"prefer-stable": true` (or require `dev-main` for each) and pin to a commit hash in `composer.lock`.
- **After installing each package, read its `README.md` and `config` file inside `vendor/imrandevbd/<package>` and adapt to the real API.** The notes below come from the Packagist pages. If the real API differs, follow the real API, tell me, and do not invent methods.
- Wrap every package behind a thin **adapter interface** in `app/Support/Integrations/` (for example `PdfRenderer`, `AiGateway`, `AttendanceGateway`, `SlugService`). Application code calls the adapter, never the package directly, so a package can be swapped or patched in one place.
- Create `docs/PACKAGE_NOTES.md` recording: version/commit used, what we use, workarounds, and any bug found. Report bugs to me instead of forking.
- Each package gets at least one smoke test (and use the package's fake where it has one).

### 3.1 `imrandevbd/drutogoti` — speed and performance (profiler)
- **What it is:** a performance profiler that detects N+1 queries, slow queries (with backtraces), cache hit/miss, oversized cookies and uncompressed images, and suggests fixes. Requires PHP ^8.3, Laravel 11 or 12.
- **Install:** `composer require imrandevbd/drutogoti`, publish `drutogoti-config` and `drutogoti-migrations`, migrate.
- **Config for Ledgo:** storage driver `queue` in production (`sync` locally), slow threshold 100 ms, N+1 threshold 3, sample rate 0.1 in production and 1.0 in staging, telemetry retention 7 days. Protect the dashboard route (`/drutogoti`) so only platform super admins can open it.
- **How we use it:**
  - Run it on staging against realistic seeded data (3 business types, 1M invoices) before each release.
  - Fix every reported N+1 and slow query on the hot paths: dashboard, POS, invoice list, ledger, reports.
  - Use its image scan to compress uploaded receipts, logos and product photos (queue a compression job on upload).
  - Add a CI step that fails on new N+1 issues in the main feature tests.
- Drutogoti finds problems; you still must build speed in: eager loading, indexes, pre-computed daily summary tables, Redis caching of dashboard aggregates, and pagination everywhere.

### 3.2 `imrandevbd/laravel-admin-panel` — platform dashboard (super admin console)
- **What it is:** a standalone admin panel and CRUD framework with a drag-and-drop 12-column dashboard, stat/chart/table widgets, multi-panel support, RBAC, audited impersonation, audit log, log viewer, queue inspector, system-health page, webhooks and API. It states it has **no Filament dependency** and positions itself as an alternative to Filament. Requires PHP ^8.2, Laravel 11 or 12.
- **Install:** `composer require imrandevbd/laravel-admin-panel`, then `php artisan admin-panel:install` (publishes config and assets, runs migrations, creates the first super admin). Console at `/admin`.
- **Role in Ledgo:** the **platform owner's console**, separate from the tenant app at `/app`:
  - Platform dashboard: total businesses, active users, revenue, MRR, signups, churn, SMS/WhatsApp/AI usage, failed jobs, queue health.
  - Manage businesses, plans/pricing rules, subscriptions, invoices to businesses, coupons, referrals, reseller accounts, support tickets, announcements.
  - Audited impersonation of a business owner (for support), log viewer, queue inspector, system health, translation editor, feature flags.
- Use `admin-panel:make-resource`, `make-widget`, `make-page` to scaffold.
- **Compatibility guard:** Filament (owner app) and this panel must live on separate route prefixes and layouts with no shared assets. Add a smoke test that loads both `/app` and `/admin`. If a conflict appears, stop and tell me instead of hacking around it.

### 3.3 `imrandevbd/laravel-ai-catalog` — product data from text, photos, URLs, voice and files
- **What it is:** builds product drafts from a keyword, photo, supplier/competitor URL (rewritten, not copied), voice note, datasheet (`pdf, md, txt, csv, json, html`) or bulk CSV/JSON: titles, descriptions, SEO fields, specs, categories, tags, FAQs, translations, per-field confidence, a draft → review → approve workflow, cost tracking and feeds. Built on laravel-ai-hub. Facade `ImranDevBd\AiCatalog\Facades\AiCatalog`; Filament action `GenerateWithAiAction` and `AiCatalogPlugin`. Requires `imrandevbd/laravel-ai-hub`.
- **Install:** `composer require imrandevbd/laravel-ai-catalog`, publish `ai-catalog-config`, migrate.
- **Role in Ledgo:** the **product entry engine**, so a shopkeeper never types product data:
  - "Add product by photo": take a photo of the item or its shelf label, get a draft (name, category, unit, specs, Bangla/English name), review, approve.
  - "Add from supplier link or price list": paste a URL or upload a datasheet/CSV, get drafts in bulk.
  - "Add by voice": say the product and price.
  - Map its schema to our `products` table in `config/ai-catalog.php` (column map, `on_approve = update_or_create`), match categories only against the business's own categories (taxonomy `min_score`), set the low-confidence threshold (default 0.70) so doubtful fields are highlighted yellow in our Review screen, and set `tenancy` so drafts, costs and budgets are scoped per business.
  - Add `GenerateWithAiAction` to the Filament Product resource form and a "Scan product" button in POS and stock screens.
  - Respect each business's AI budget and show their usage.
- Not for invoice/receipt/khata reading. That is section 3.4 (ai-hub vision with structured output).

### 3.4 `imrandevbd/laravel-ai-hub` — every AI feature
- **What it is:** one API over 13 providers (Gemini, OpenAI, Claude, Grok, DeepSeek, Mistral, Groq, Ollama, OpenRouter, Azure, Together, Fireworks, Perplexity) with failover, retries, JSON auto-repair, structured output, vision, tools, embeddings, streaming, chat sessions, response cache, per-request cost tracking, budgets, a Studio UI at `/ai-hub`, and `AIHub::fake()` for tests. Facade `ImranDevBd\AiHub\Facades\AIHub`; Filament `AiHubPlugin`. Requires PHP ^8.2, Laravel 10–13.
- **Install:** `composer require imrandevbd/laravel-ai-hub`, publish `ai-hub-config`, migrate, `php artisan ai-hub:configure`.
- **Role in Ledgo (all AI goes through it, never call OpenAI/Gemini SDKs directly):**
  - **OCR and extraction:** receipts, bills, handwritten khata pages, bank-statement PDFs, delivery slips → structured JSON via `AIHub::structured($schema)` with vision. Result goes to the Review & Confirm screen.
  - **Voice entry** ("Karim ke 5000 taka dilam") → structured transaction.
  - **Ask Ledgo** assistant in Bangla/English using safe read-only **tools** that return only the current business's data (never raw SQL from the model, never cross-tenant).
  - Smart suggestions: reorder, overdue follow-up, expense anomaly, cash-flow warning.
  - Message drafting and translation of templates.
- **Provider setup:** default provider Gemini (cost) with OpenAI/Claude as failover; keys held centrally by the platform and managed in the Studio (encrypted), never exposed to businesses.
- **Metering:** tag every call with `forJob()` including `business_id` and feature name. Build a per-business AI credit wallet on top of the cost log: show usage, block or warn at the limit (`AI_HUB_BUDGET_ON_EXCEED`), and bill overage as credits. Prune logs after 90 days.
- Restrict `/ai-hub` (Studio) to platform super admins via the package's role/Gate settings. Use `AIHub::fake()` in every test so tests never call a real provider.

### 3.5 `imrandevbd/laravel-attendance-hub` — attendance machines (HR-lite module)
- **What it is:** one driver interface for biometric and access-control devices: ZKTeco, Hikvision, Anviz, FingerTec, eSSL, VIRDI, Suprema, Deli, Granding, Soyal (about 166 models), plus a webhook bridge (ESP32/Raspberry Pi/Wiegand), a **virtual provider** (GPS geofence + rotating QR check-in from a phone) and a fake driver. Includes ADMS listener endpoints for devices behind NAT (`/iclock/cdata`), deduplicated punch logs, a daily shift calculator (late, early, overtime, status), an encrypted biometric template vault, anti-passback and debounce, multi-tenancy via `BelongsToTenant`, HMAC-signed webhooks, and a Filament v3 plugin `AttendanceHubPlugin`. Facade `ImranDevBd\AttendanceHub\Facades\AttendanceHub`. Requires PHP ^8.2, Laravel 10–12; `ext-sockets` for native ZKTeco.
- **Install:** `composer require imrandevbd/laravel-attendance-hub`, publish `attendance-hub-migrations` and `attendance-hub-config`, migrate.
- **Role in Ledgo:** the attendance side of the optional **HR & Payroll module** (off by default, toggled per business):
  - Register a machine by choosing brand/model (use `attendance:catalog` data in the UI), auto-detect on the LAN, or receive pushes via ADMS.
  - Map device user IDs to employees (`device_user_maps`), pull/sync logs on a schedule (`attendance:sync`, `attendance:health`), and feed late/overtime/absent days into the salary sheet.
  - For shops without a machine: **virtual check-in** from the Ledgo app (GPS radius + QR), with the owner setting the radius.
  - Wire its **tenancy resolver** to `business_id` and make sure one business can never see another's devices, logs or templates (add a test).
  - Register `AttendanceHubPlugin` in the Filament owner panel under "HR". Hide it for businesses that did not enable HR.
  - Document the Nginx `/iclock/` location block and the Supervisor queue worker in the deployment guide.
  - Biometric templates are sensitive: keep encrypted at rest, restrict who can export them, and show a consent notice.

### 3.6 `imrandevbd/laravel-unicode-pdf` — PDFs in any language
- **What it is:** multilingual PDF generation with automatic script and RTL/LTR detection, font fallback, complex-script shaping (Bengali, Arabic, Devanagari…), 20 script presets (bengali, arabic, urdu, hindi/indian, thai, cjk…), numeral conversion (for example Bangla digits), a built-in native engine (no other PDF library needed), optional Dompdf/mPDF/TCPDF/Browsershot engines, queueing, caching, storing to disks, mail attachments, named profiles (`invoice`, `receipt`, `rtl-report`) and `UnicodePdf::fake()`. Facade `ImranDev\UnicodePdf\Facades\UnicodePdf` (note: `ImranDev`, not `ImranDevBd`). Requires PHP 8.1–8.5, Laravel 10–13.
- **Install:** `composer require imrandevbd/laravel-unicode-pdf`, `php artisan unicode-pdf:font:install --font=bengali` (and other fonts for launch languages), publish `unicode-pdf-config`.
- **Role in Ledgo:** the **only PDF generator** for invoices, quotations, receipts, delivery challans, credit notes, customer statements, payslips, reports and Mushak/VAT forms.
  - Use the business's invoice language, which can differ from the UI language (Bangla app, English invoice, or bilingual).
  - Use `preset(...)` from the document language, `NumeralConverter` for Bangla/Arabic digits, and the `invoice` and `receipt` profiles.
  - Generate large reports with `->queue()` and `->cache()`, store to the private disk, and attach to emails/WhatsApp through signed links.
  - **Thermal receipts (58mm/80mm):** use a receipt profile with the right paper width if the package supports custom sizes; if not, fall back to print-CSS HTML for thermal printing and tell me.
  - Add a test that renders a Bangla invoice with conjunct letters (for example "ক্ষ", "শ্রী") plus an Arabic invoice and checks the PDF is produced without errors (`unicode-pdf:diagnose` in CI).

### 3.7 `imrandevbd/laravel-universal-slug` — slugs in any language
- **What it is:** URL slugs in any script (preserve Unicode, transliterate to Latin, or ASCII-only), Eloquent trait `HasUniversalSlug` with `SlugOptions`, unique-slug collision strategies, scoped uniqueness (tenant/locale), reserved words, slug history with 301 redirects, stopwords for English, Bengali, Arabic, Hindi, French, Spanish and German, validation rules, and Artisan commands `slug:generate`, `slug:regenerate`, `slug:prune`, `slug:audit`. Facade `ImranDev\UniversalSlug\Facades\UniversalSlug`. Requires PHP 8.1–8.5, Laravel 10–13.
- **Install:** `composer require imrandevbd/laravel-universal-slug`, publish its config and migrations, migrate.
- **Role in Ledgo:** every public or human-readable URL:
  - Products, categories, brands, **each business's public shop/catalog page** (`ledgo.bd/s/<business-slug>`), price-list pages, help-center articles, blog posts, landing pages per language.
  - Scope uniqueness per business (tenant scope) for products/categories, and globally for business public slugs. Keep reserved words (`admin`, `app`, `api`, `login`, `iclock`…).
  - Default mode `preserve_unicode` for Bangla/Arabic names with a `transliterate` option per business for cleaner sharing links. Keep slug history so old shared links still work.
  - Do NOT use slugs for invoices or payment links (use short random signed tokens, section 4.12).

---

## 4. FUNCTIONAL MODULES (build all)

All modules are Filament resources/pages/widgets in the tenant panel unless stated. Everything also has an `/api/v1` endpoint (Sanctum, OpenAPI docs via Scribe, rate limits, idempotency keys on POST).

### 4.1 Onboarding (target: first invoice in under 5 minutes)
- Register with phone + OTP; Google login optional; email optional.
- 3-step wizard: business name and type → language, country, currency → "What do you want to do first?" (sell / record expense / import old data).
- Business-type templates pre-load categories, units, invoice layout and the right mode (grocery, pharmacy, garment, restaurant, service…).
- Interactive tour, short tips, a help button on every page, WhatsApp/phone support button.

### 4.2 Dashboard
- Today / week / month / custom: sales, purchases, expenses, profit, cash in hand, bank and mobile-wallet balances, receivable, payable.
- Plain-word cards ("Today you earned ৳12,400"), charts (sales trend, profit, top products, top customers, expense split), alerts (low stock, expiry, overdue, unusual expense, subscription/usage), and quick-action buttons: New Sale, New Expense, Receive Payment, Add Customer, Scan.
- Cached aggregates from daily summary tables, invalidated on writes.

### 4.3 Sales and invoicing
- Quotation → Sales order → Invoice → Delivery challan → Credit note / Return.
- **Fast POS** (custom Livewire page): barcode scan by camera or scanner, quick-add, hold/resume bills, split payment, cash/card/bKash/Nagad/Rocket/bank/due, keyboard-free flow on mobile.
- Item and invoice discounts, VAT/tax (inclusive/exclusive), shipping, notes, terms, multi-currency, rounding, partial and advance payment, recurring invoices, configurable numbering per branch and year.
- Printing: A4, A5, thermal 58/80 mm, 5+ templates, logo, QR, bilingual (via section 3.6).
- Share: PDF, WhatsApp, SMS link, email, **public invoice link** with viewed/paid tracking and "Pay now".
- Bangladesh Mushak/VAT layout (BIN field) as a country option.

### 4.4 Purchases and suppliers
- PO → goods received → bill → return; supplier ledger; payable aging; payment vouchers; landed-cost allocation.

### 4.5 Expenses and other income
- Fast expense entry (under 10 seconds) with receipt photo; recurring expenses with reminders; other income; loans given/taken; owner capital and drawings. Receipt photo auto-reads amount/date/vendor (section 4.10).

### 4.6 Customers, suppliers and due ledger (khata/baki)
- Unified contacts with credit limit, tags, opening balance, groups, price lists, loyalty points.
- Simple "You will get / You will give" ledger per person, statement PDF, aging, promise-to-pay dates.
- One-tap reminder via SMS/WhatsApp/Messenger with balance and pay link. **This screen must be as fast and simple as TallyKhata's.**

### 4.7 Inventory
- Products and variants (via ai-catalog, section 3.3), units and conversions, categories, brands, barcodes/QR, multi-warehouse, transfers, adjustments, camera stocktake.
- Batch/lot with expiry (pharmacy), serial numbers, low-stock and expiry alerts, reorder suggestions, weighted-average costing (FIFO optional), simple bill of materials for tailors and small manufacturers.

### 4.8 Accounting
- Real double-entry underneath, auto-posted from every sale, purchase, expense and payment. Chart of accounts per business type.
- Cash book, bank book, day book, journal, trial balance, balance sheet, P&L, cash flow; bank/wallet reconciliation by CSV; fixed assets and depreciation; period locking.
- **Simple mode** (default, plain words) and **Accountant mode** (full ledger).

### 4.9 Reports (PDF/Excel/CSV, shareable, schedulable to WhatsApp/email)
Sales, purchases, P&L (per item, customer, invoice, branch), expenses, stock valuation and movement, receivable/payable aging, VAT summary, daily closing, salesperson performance, rankings, cash flow, and a one-page **"Cost vs Earning"** summary.

### 4.10 Smart capture and import (key differentiator)
Everything shows a **Review & Confirm screen** first: editable table, low-confidence cells highlighted, bulk fix, then save. Every import is an `import_batch` with row counts, error file and **rollback**. Original files kept in private storage with signed URLs.
1. Camera scan of paper bills, receipts, handwritten khata pages (ai-hub vision, 3.4).
2. Image to text (JPG/PNG/WhatsApp images, Bangla and English, printed and handwritten).
3. PDF extract (text and scanned PDFs, bank statements).
4. **CSV/Excel import** of old customer, supplier, product and opening-balance lists: AI column mapping, duplicate detection by phone/name, validation errors, sample templates.
5. Phone-contacts import (Capacitor, with permission).
6. Voice entry (Bangla and English).
7. Barcode/QR scanning.
8. Product data from photos/URLs/files (ai-catalog, 3.3).
9. "Switch to Ledgo" import wizard for exports from other khata apps (CSV templates for TallyKhata-style and generic ledgers).

### 4.11 Communication hub
- Channels (provider-pluggable, per-business credentials and credit): SMS (Bangladeshi gateways such as BulkSMSBD, Alpha SMS, Greenweb, SSL Wireless; Twilio abroad), WhatsApp Business Cloud API (templates, opt-in, delivery status) with click-to-chat fallback, Facebook Messenger, Instagram DM, email, push (FCM); Telegram and Viber optional.
- Templates with variables (`{customer}`, `{amount}`, `{due_date}`, `{invoice_link}`), approval workflow for WhatsApp templates.
- No-code automation rules: invoice created → send receipt; 3 days before due → reminder; payment received → thank you; birthday → wishes; low stock → alert owner; daily closing → owner's WhatsApp.
- Campaigns to customer segments, schedule, opt-out, delivery/read reports, fallback chain (WhatsApp → SMS), SMS credit wallet (buy through bKash/Nagad/card), low-credit alert.
- **Short links:** a short-link service on a configurable domain (`SHORT_LINK_DOMAIN`) for invoice and payment links, to save SMS characters; click tracking; signed tokens.

### 4.12 Payments
- Collect: bKash, Nagad, Rocket, SSLCOMMERZ, Stripe, bank transfer; pay-link and QR on invoices; optional auto-verification of bKash/Nagad payment SMS (TrxID parsing).
- Business subscription billing through the same gateways.

### 4.13 HR and payroll (optional module)
- Employees, shifts, attendance (machines and virtual check-in through attendance-hub, 3.5), leave, salary sheet, advances, commission, payslip PDF (3.6).

### 4.14 Ask Ledgo (AI assistant)
- Chat/voice in Bangla or English: "Last month's profit?", "Who owes me more than 5000?", "Which product is not selling?"
- Answers come only from the current business's data through scoped read-only tools (3.4). Metered, and can be turned off per business.

### 4.15 Settings
Business profile, logo, branches, taxes, units, currencies, payment methods, invoice templates and numbering, users/roles with fine permissions ("can see profit", "can edit price", "can see cost price", "can delete invoice"), notification preferences, 2FA, PIN/biometric lock, device list, full **data export (JSON/Excel, no lock-in)**, data deletion on request, backups.

---

## 5. MULTI-TENANCY, ROLES, AUDIT

- Single database, **row-level tenancy** with `business_id` on every tenant table, a `BelongsToBusiness` trait and global scope; Filament panel tenancy uses the `Business` model.
- Wire every package's tenancy hook (ai-catalog `tenancy`, attendance-hub `BelongsToTenant`/resolver, universal-slug scoped uniqueness, ai-hub `forJob` tagging) to `business_id`.
- Automated tests prove one business can never read another's data, including through packages, API, exports, queues and Filament global search.
- Roles per business: Owner, Manager, Accountant, Cashier/Salesperson, Stock keeper, Viewer. Multi-branch with branch-level reports and stock.
- Platform roles (super admin, support, finance) live in laravel-admin-panel.
- Full audit log for create/update/delete of financial records: who, when, old/new value, IP, device.

---

## 6. LANGUAGES (i18n / l10n)

- Launch with **Bangla and English**; prepared files for Hindi, Urdu, Arabic (RTL), Nepali, Sinhala, Indonesian, Malay, Tamil, Spanish. `lang/xx/*.json`, no hardcoded strings.
- Per-user UI language and per-document language. Bangla digits option, lakh/crore grouping, locale-aware dates and currency, RTL layout.
- `php artisan translate:missing xx` uses ai-hub to draft missing translations, flagged "needs review". A translation editor in the platform console.
- PDFs via unicode-pdf (3.6); slugs via universal-slug (3.7).
- Fonts: Noto Sans Bengali / Hind Siliguri for UI.

---

## 7. PRICING AND SUBSCRIPTION ENGINE (configurable by super admin, never hardcoded)

- **First 12 months free** from registration (`free_until` date, extendable per business by super admin). Full features; SMS, WhatsApp and AI scans are credit-based so costs stay controlled.
- **After 12 months:** monthly fee = **৳10 × ceil(monthly_volume ÷ ৳1,00,000)**, minimum ৳10. Up to 1 lakh → ৳10; up to 2 lakh → ৳20; up to 3 lakh → ৳30, and so on. "Monthly volume" = total value of sales + purchases + expenses recorded that month (make this definition a setting and confirm it with me).
- Rates live in a `pricing_rules` table. Unit tests cover boundaries: exactly 1,00,000 → ৳10; 1,00,001 → ৳20; free-period expiry day; month with zero activity.
- Month-end invoice, SMS/WhatsApp reminder, pay by bKash/Nagad/card, **7-day grace**, then **read-only mode**. Never delete data; export always allowed.
- Live widget: "This month's volume and your fee estimate". Coupons, referral rewards (extra free month), reseller/agent accounts.
- Pay-per-use credits: SMS, WhatsApp, AI scans/OCR, with a transparent price list in the app.

---

## 8. UX RULES (NON-NEGOTIABLE, EVEN INSIDE FILAMENT)

Filament is a desktop-style admin tool, so you must adapt it for shopkeepers:
1. **Mobile-first at 360px.** Custom Filament theme: large touch targets (44px+), high contrast, readable fonts, dark mode. On phones show a **bottom navigation** (Home, Sell, Money, People, More) through render hooks instead of the sidebar.
2. **Simple mode by default:** hide advanced Filament features (bulk actions, column toggles, filters) behind "More". Show at most 7 navigation items. Accountant mode reveals the rest.
3. **Plain language:** "You will get" instead of "Accounts receivable", with the accounting term in small text.
4. **Max 3 taps** to create a sale, an expense, or receive a payment. Smart defaults, remember last values, numeric keypad for amounts, quick-add buttons (+100, +500, +1000).
5. POS, Quick Expense, Receive Payment and Scan are **custom Livewire pages**, not generic CRUD forms.
6. Autosave drafts, undo after delete, confirm only destructive actions.
7. Teaching empty states (illustration + one button). Friendly errors in the user's language, never stack traces.
8. Visible trust cues: "Saved ✓", online/offline/syncing icon, last backup time.
9. Speed: first load under 3 s on 3G, skeleton loaders, lazy loading, pagination, instant search.
10. Accessibility WCAG AA, keyboard navigation on desktop.
11. One design system (Tailwind tokens, reusable components) with a living style guide page.

---

## 9. DATA MODEL (start here, extend as needed)

`users, businesses, branches, business_user, subscriptions, pricing_rules, usage_meters, wallets, wallet_transactions, contacts, contact_groups, products, product_variants, categories, brands, units, unit_conversions, warehouses, stock_levels, stock_movements, batches, serials, price_lists, quotations, sales_orders, invoices, invoice_items, payments, payment_allocations, credit_notes, purchase_orders, bills, bill_items, purchase_returns, expenses, expense_categories, other_incomes, accounts, journal_entries, journal_lines, bank_accounts, fixed_assets, employees, payrolls, taxes, currencies, message_templates, message_logs, automation_rules, campaigns, short_links, channel_credentials, import_batches, import_rows, ocr_jobs, attachments, notifications, audit_logs, devices, support_tickets, coupons, referrals, settings` plus the tables created by the seven packages.

Rules: UUID public identifiers; soft deletes with audit on financial records; foreign keys enforced; index `(business_id, created_at)` on tenant tables; unique invoice number per `(business_id, branch_id, fiscal_year)`.

---

## 10. ARCHITECTURE, QUALITY, SECURITY

- Modular monolith `app/Modules/{Sales,Purchases,Inventory,Accounting,Contacts,Communication,Imports,Billing,Reports,AI,HR}`; actions/services/DTOs/form requests/policies; thin controllers; package adapters in `app/Support/Integrations`.
- Domain events (`InvoiceCreated`, `PaymentReceived`…) → double-entry postings inside DB transactions; idempotent and reversible.
- Queues with priorities (messages, OCR/AI, PDF, imports, reports, billing); Horizon; failed-job UI.
- Tests (Pest): unit (pricing, tax, costing), feature (API, tenancy isolation, accounting reconciliation: P&L and balance sheet always equal ledger sums), browser tests (Playwright/Dusk) for the 5 key flows. 80%+ coverage on Accounting, Billing, Inventory. Larastan level 6+, Pint, `composer audit`, Semgrep in CI.
- Performance: use drutogoti (3.1) in staging and CI; no N+1 on hot paths; load-test 10k businesses and 1M invoices.
- Security: OWASP Top 10, HTTPS only, encrypted channel credentials and biometric templates, signed URLs, upload validation and malware-scan hook, rate limits and OTP-abuse protection, 2FA, consent for AI/OCR processing, SMS/WhatsApp opt-in/out, GDPR-ready export and deletion, Bangladesh data-protection readiness.
- Observability: Sentry, structured logs, health endpoint, slow-query log. Daily DB backups with off-site copy and one-click restore for super admin.

---

## 11. ANDROID / PWA

- Installable PWA (manifest, icons, splash, offline shell) plus Capacitor project in `/mobile`.
- Native bridges: document camera scan with edge detection and crop, barcode scanner, FCM push, share, contacts, Bluetooth thermal printer, biometric/PIN lock, deep links for invoice URLs, update prompt.
- Offline: sales, expenses, customers and payments can be created offline and sync later without duplicates (idempotency keys, conflict log for stock/payments).
- Play Store pack: privacy policy page, data-safety answers, signing guide, screenshots checklist. Target Android 8+, small APK.

---

## 12. DELIVERY PHASES

**Phase 1 — Foundation:** Laravel 12, Docker, CI, tenancy, phone-OTP auth, roles, i18n (bn/en), Filament owner panel shell with mobile theme and bottom nav, laravel-admin-panel console installed (`/admin`), audit log, settings. Install **drutogoti** and **universal-slug**.
**Phase 2 — Core money:** contacts, products, invoices/POS, payments, expenses, purchases, double-entry posting, dashboard, basic reports. Install **unicode-pdf** and produce A4/thermal invoices in Bangla and English.
**Phase 3 — Inventory and khata:** stock, batches/expiry, warehouses, due ledger, statements, aging, manual reminders.
**Phase 4 — Communication hub:** SMS/WhatsApp/Messenger/email, templates, automation rules, campaigns, credit wallet, short links.
**Phase 5 — Smart capture:** install **ai-hub** then **ai-catalog**; camera/image/PDF/CSV import with Review screen and rollback, voice entry, product-by-photo/URL, "Switch to Ledgo" wizard, AI budget and credit wallet.
**Phase 6 — Billing engine:** 12-month free, volume pricing, usage meters, gateway payments, grace/read-only, platform console pages (businesses, plans, coupons, tickets).
**Phase 7 — Mobile:** PWA offline sync, Capacitor build, push, printer, biometrics, Play Store package.
**Phase 8 — HR and polish:** install **attendance-hub**, payroll, Ask Ledgo, advanced reports, more languages, security hardening, performance pass with drutogoti, load test.

At the end of each phase deliver: working code, migrations, seeders (realistic Bangla demo data for 3 business types), passing tests, README update, and a done-vs-pending checklist.

---

## 13. ACCEPTANCE CRITERIA

- A new user registers and prints a Bangla invoice in under 5 minutes without help.
- An old customer list imports from CSV and from a photo of a handwritten khata, with the Review screen and rollback.
- An overdue reminder auto-sends by WhatsApp with SMS fallback, and delivery status shows.
- Sales and expenses work offline and sync without duplicates.
- P&L and balance sheet always match ledger totals (reconciliation test).
- Pricing returns ৳0 for the first 12 months, ৳10 at ≤1 lakh and ৳20 at ≤2 lakh, including boundary tests.
- No cross-tenant leakage in any automated test, including package data (devices, drafts, slugs, AI logs).
- Each of the seven packages is installed, used for its stated job, behind an adapter, with a smoke test and an entry in `docs/PACKAGE_NOTES.md`.
- Lighthouse mobile score ≥ 85 (performance and accessibility); drutogoti reports no N+1 on hot paths.
- The owner can export all their data at any time.

---

## 14. HOW YOU (THE GENERATOR) MUST RESPOND

1. **First reply only:** confirm your understanding in 15 lines; list assumptions and up to 10 questions (include: the exact definition of "monthly volume", which SMS gateways to start with, and whether Filament and laravel-admin-panel coexist cleanly once installed); propose the folder structure and the Phase 1 task list. Then **wait for my "go"**.
2. After "go": produce small, runnable steps with file paths, explain complex decisions in a line or two, and keep each commit-sized.
3. Never output placeholder "TODO" for core logic. Never call AI providers directly (use ai-hub). Never generate PDFs any other way (use unicode-pdf). Never write slugs by hand (use universal-slug).

**Begin with step 1 now.**