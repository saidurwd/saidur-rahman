# Progress

| Phase | Status |
| --- | --- |
| 0 · Foundation | Done (tag `phase-0`) |
| 1 · Data model | Done (tag `phase-1`) |
| 2 · Admin shell | Done (tag `phase-2`) |
| 3 · Design system | Done (tag `phase-3`) |
| 4 · Admin modules | Done (awaiting review and tag) |
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

## Phase 3 notes
- Design: "The Architect's Ledger". Tokens live in `resources/css/site.css` (`@theme` plus semantic CSS variables `--bg`, `--surface`, `--fg`, `--muted`, `--line`, `--accent`, `--highlight` that flip with `[data-theme="dark"]`). Use the semantic utilities (`bg-bg`, `text-fg`, `text-muted`, `border-line`, `text-accent`) so both themes work; use brand colours (`bg-leaf`, `text-brass-deep`) only for fixed brand uses.
- Fonts are self-hosted via `@fontsource-variable/{fraunces,inter,jetbrains-mono}` (latin subsets load on demand); the heading font is preloaded in the layout.
- Theme: an inline script in `<head>` sets `data-theme` (stored choice, else the system preference) and a `js` class before first paint. `x-site.theme-toggle` stores the choice in `localStorage`. Phase 8's CSP needs a nonce or hash for this inline script.
- Components in `resources/views/components/site/`: `seo`, `button`, `tag`, `section-heading`, `timeline-node`, `theme-toggle`, `monogram`. Reusable CSS classes: `.wrap`, `.label`, `.eyebrow`, `.btn*`, `.link`, `.card`, `.tag*`, `.blueprint`, `.draw`, `.timeline`.
- Layout `site.layouts.app`: skip link, sticky header that shrinks on scroll, mobile menu (Alpine), footer. Header and footer menus come from the `menus` table via `NavigationService` (cached, cleared by the content observer). Socials, email and the availability chip come from settings and are hidden when empty.
- `x-site.seo`: title, description, canonical, Open Graph, Twitter, robots, and JSON-LD through a `:schema` prop (one array or a list). The prop encodes safely, so values cannot break out of the script tag. Pages set it with `@section('seo')`.
- Motion: `data-reveal` fade-up and the connector-line draw only apply when `prefers-reduced-motion: no-preference`.
- The Tailwind bundle scans only `views/site` and `views/components/site`.
- `/styleguide` is local and testing only.

## Phase 4 notes
- Reference module: Projects. Pattern and variations are in `docs/ADMIN_MODULE_PATTERN.md`; `/new-admin-module` (in `.claude/commands/`) walks Claude through adding another.
- Modules: Projects, Experience, Education, Certifications, Skill groups, Skills, Services, Pages, Menus, Testimonials, Redirects, CV files, Profile & settings, plus read-only Activity log and Media. Articles come in Phase 6 and Messages in Phase 7.
- Shared pieces: `ManagesModule` trait (Trash, restore, force delete, reorder, bulk), `Route::adminModule()` macro, `ReorderItems` and `ApplyBulkAction` actions, `SaveSlugged`, `HtmlSanitizer` (HTMLPurifier), `RedirectService` plus a 404 handler that serves recorded redirects, `ImageStore`, `AdminPolicy`.
- TinyMCE 8 is self-hosted and loaded only on pages with a rich-text field. It runs under the GPL licence key (`license_key: 'gpl'`); check that is acceptable for how the site is distributed, or switch to Trix.
- New npm/composer packages in this phase: `tinymce`, `sortablejs`, `ezyang/htmlpurifier`.
- Sidebar entries gained Certifications. Skill groups are reached from the Skills page.
- Public pages do not read these yet (Phase 5). Redirects for changed slugs already work for any path.
