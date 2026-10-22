# Delivery status

## Foundation increment — 23 October 2026

### Verified working

- [x] Laravel 12, Filament 3 and Livewire 3 dependencies locked.
- [x] Original homepage 500 repaired; placeholder attendance success endpoints removed.
- [x] Phone OTP authentication, expiry, one-time use, failed-attempt limit and request throttles.
- [x] BulkSMSBD adapter with fake HTTP verification and fail-fast missing credentials.
- [x] Business/branch/membership/settings/audit migrations.
- [x] Fail-closed tenant scope and restored request/business context.
- [x] Filament business tenancy and owner-only business name/language editor.
- [x] Six Spatie roles per business; owner assignment and team isolation tested.
- [x] Protected package platform dashboard with actual business/user counts.
- [x] Three Phase 1 packages installed, config/source read, adapters and smoke tests.
- [x] Initial en/bn public UI and tenant locale selection.
- [x] Initial mobile bottom navigation and large controls.
- [x] Demo seed data for three Bangla business types.
- [x] MySQL/Redis Compose infrastructure and GitHub Actions definition.
- [x] README with setup, configuration, module inventory and honest status.

### Still required before Phase 1 acceptance

- [ ] Upstream admin-panel implementation of advertised authentication/RBAC/resource APIs, impersonation and operational services.
- [ ] Upstream profiler fixes/features: real sampling/exclusions, protected dashboard, additional watchers, redaction and retention.
- [ ] Full mobile theme, self-hosted Bengali fonts, finalized five-tab navigation once the target workflows exist, style guide and 360px/browser/accessibility validation.
- [ ] Fine permissions/staff management and comprehensive settings UI.
- [ ] General audit coverage beyond current onboarding/profile changes.
- [ ] Full localization of validation, auth and all introduced/retained package UI.
- [ ] Business-type category/unit/account/invoice templates as the domain schemas arrive.
- [ ] Full PHP app Docker image and VPS nginx/PHP-FPM/Supervisor deployment configuration.
- [ ] Production BulkSMSBD delivery verification with operator-supplied credentials; no live messages were sent.
- [ ] Hosted CI and MySQL/Redis integration execution. Local tests used SQLite.

## Phases 2–8

Not implemented. Detailed module requirements and the confirmed pricing definition are listed in README.md and the original master specification is preserved in MASTER_BUILD_PROMPT.md. No financial transactions, invoices, accounting balances, payment integrations, AI/OCR, offline sync or payroll are operational.

The master prompt says: “After each phase: run migrations and tests, show what passes, and wait for my ‘go’.” This increment must not be represented as full Phase 1 or full product completion.
