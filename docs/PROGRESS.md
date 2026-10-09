# Progress

| Phase | Status |
| --- | --- |
| 0 · Foundation | Done (tag `phase-0`) |
| 1 · Data model | Done (tag `phase-1`) |
| 2 · Admin shell | Done (awaiting review and tag) |
| 3 · Design system | Pending |
| 4 · Admin modules | Pending |
| 5 · Public pages | Pending |
| 6 · Blog | Pending |
| 7 · Contact & CV | Pending |
| 8 · Hardening | Pending |
| 9 · Launch | Pending |

## Phase 0 notes
- Laravel 13, PHP 8.4 locally (8.3+ required), Pest 5, Larastan level 6.
- Two Vite entries: `site` (Tailwind 4 + Alpine) and `admin` (plain placeholder until Phase 2).
- Local DB: MySQL `saidur_rahman` (credentials in git-ignored `.env`). Tests run on in-memory SQLite per `phpunit.xml`.

## Phase 1 notes
- 20 content migrations plus Spatie `media` and `activity_log`; users gained 2FA and `last_login_at` columns for Phase 2.
- Models in `app/Models` use shared traits in `app/Models/Concerns`: `HasSlug` (unique, stable, never reuses trashed slugs), `Orderable` (`ordered()`), `IsPublishable` (`published()`), `LogsChanges` (activity log).
- `published()` means different things per model: posts are live when published or scheduled and due; projects use the `Published` status; testimonials use `is_approved`; CV files use `is_active`.
- Caching: `SettingService` caches all settings forever and clears on save. `CacheService` provides group-versioned caching (works on the database cache store, no tags). `ContentObserver` flushes the `content` group when any content model is saved, deleted or restored.
- Media: `Project` (cover, gallery) and `Post` (cover) generate WebP `thumb` and `large` conversions.
- Seeding: `php artisan migrate:fresh --seed`. The admin password comes from `ADMIN_PASSWORD`, or is generated and printed once. All content is sample text to replace in the admin panel.
- Slug-change redirects are written by the admin modules in Phase 4, not by the models.

## Phase 2 notes
- AdminLTE 4 comes from npm (`admin-lte@^4`, Bootstrap 5.3, Bootstrap Icons) with our own layout, not the `colorlibhq/adminlte-laravel` package. Compiled CSS is imported directly, so no Sass is needed. Tea-leaf green is applied by overriding Bootstrap variables in `resources/css/admin.css`. Dark mode is the default and uses AdminLTE's colour-mode toggle (stored in `localStorage`).
- Layout: `admin.layouts.admin` (sidebar from `config/admin.php`, navbar, breadcrumbs via `@extends(..., ['breadcrumbs' => [...]])`, toasts from `status`/`warning`/`error` flashes) and `admin.layouts.auth`. Sidebar items whose route does not exist yet render disabled, so later phases only add the route.
- Auth: login only (no registration), 5 attempts per minute per email+IP, TOTP 2FA via `pragmarx/google2fa` with a locally rendered SVG QR (`chillerlan/php-qrcode`; `bacon/qr-code` was not installable). 8 recovery codes, single use, shown once. Password change needs the current password and 12+ characters.
- `EnsureTwoFactor` (`admin.2fa`) gates the admin: no 2FA means redirect to setup, unverified session means redirect to the challenge. `admin.2fa:allow-setup` guards the setup routes.
- Components live in `resources/views/components/admin/` (card, stat, status-badge, table, delete-button, monogram, `form/{input,textarea,select,editor,image-upload,toggle}`). `/admin/components` renders them all in local and testing only. `x-admin.form.editor` is a plain textarea marked `data-editor="rich"` until TinyMCE arrives in Phase 4.
- The site Tailwind bundle now scans only `resources/views/site`, so admin classes never leak into it.
- Seeded admin has no 2FA yet: sign in, then you are sent to set it up.
