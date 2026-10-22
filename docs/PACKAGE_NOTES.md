# Package integration notes

## Installed versions

| Package | Version | Pinned commit |
| --- | --- | --- |
| `imrandevbd/drutogoti` | `dev-main` | `6c5b4c94bf42e350d5b06c88736dcbf2b8e199f1` |
| `imrandevbd/laravel-admin-panel` | `dev-main` | `67305195926b7e151f8cb061a1765d23645098a1` |
| `imrandevbd/laravel-universal-slug` | `dev-main` | `9fdcf1659a514e94e7ac58b17ec783e73003fb7b` |

Read the installed READMEs, config and source before integration. Actual source takes precedence over advertised features. No vendor changes were made.

## Admin Panel

The installed source exposes only dashboard, dashboard alias, asset fallback and theme routes. The installer publishes config/assets and optionally migrates; it does not create a super admin. Only install/publish commands are registered. Resource/widget/page generators, platform authentication, RBAC, impersonation, audit/queue/log viewers and the other advertised services are absent from this revision.

Ledgo uses the existing package dashboard/layout and a `PlatformConsole` adapter. `RequirePlatformAdmin` protects all package routes. A host view replaces sample revenue with real counts. This is a security boundary and branding adaptation, not a replacement for the missing package framework. Its README claims substantially more than the installed code provides; the owner must resolve this upstream before the full platform console can meet the specification. Filament and this dashboard render independently in automated smoke tests; no collision was observed.

## Drutogoti

Published migrations/config and used a `PerformanceProfiler` adapter. Its actual service provider registers query capture and dispatches the captured context. The storage job persists telemetry and analyzes slow/repeated queries; this path is smoke-tested. Dashboard routes, cookie/cache/image watcher registration and retention pruning are absent from the inspected provider. Sampling/exclusion settings exist but are not enforced by its request capture path. Do not treat the configured sample rate, dashboard middleware or retention as verified functionality. The package also records raw query bindings; keep profiling disabled until sensitive-binding handling and retention are addressed upstream. It defaults to disabled in Ledgo. The dashboard guard is configured defensively for a future compatible version.

## Universal Slug

Actual namespace is `ImranDev\UniversalSlug`. Business uses the package's `HasUniversalSlug` and `HasSlugHistory` traits, with options supplied by the `SlugService` adapter. Unicode is preserved, business slugs are globally unique, reserved paths include app/admin/api/login/iclock and history migration is installed. Tests exercise Bangla creation and duplicate-name suffixing. Product/category tenant scoping and public 301 resolution await those modules.

## Other required packages

Unicode PDF, AI Hub, AI Catalog and Attendance Hub are not installed yet. Their own READMEs/config/APIs must be inspected in their delivery phases. No fabricated adapter methods or false smoke-test claims have been added for them.

## BulkSMSBD

First SMS provider confirmed by the owner. Adapter uses POST form fields api_key/senderid/number/message and requires response_code 202. Documentation: https://bulksmsbd.com/faq.php. Submission is not delivery. Credentials remain server-side in environment settings. Tests fake HTTP; no paid/live SMS was submitted.
