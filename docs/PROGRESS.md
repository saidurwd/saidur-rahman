# Progress

| Phase | Status |
| --- | --- |
| 0 · Foundation | Done (tag `phase-0`) |
| 1 · Data model | Done (tag `phase-1`) |
| 2 · Admin shell | Done (tag `phase-2`) |
| 3 · Design system | Done (tag `phase-3`) |
| 4 · Admin modules | Done (tag `phase-4`) |
| 5 · Public pages | Done (tag `phase-5`) |
| 6 · Blog | Done (tag `phase-6`) |
| 7 · Contact & CV | Done (awaiting review and tag) |
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

## Phase 5 notes
- Routes: `/`, `/about`, `/experience`, `/projects`, `/projects/{slug}`, `/services`, `/services/{slug}`, and editable pages (privacy and so on) through `Route::fallback`, so the admin and later routes always win. Blog, contact and CV arrive in Phases 6 and 7; until then those menu links 404 and the home page falls back to a `mailto:` contact link and hides the CV button and articles.
- `SiteContent` is the only reader of public data. It returns published content only and caches it in the `content` group (cleared by the model observers). `SiteShell` shares the name, menus, socials and availability with every `site.*` view once per request. `SeoService` builds Person and BreadcrumbList JSON-LD.
- **Cache gotcha:** Laravel 13 refuses to unserialize objects from the cache. The Eloquent models and media that `SiteContent` caches are allow-listed in `config/cache.php` (`serializable_classes`). Add any new cached model class there. The test suite runs the array cache with `serialize` on (`CACHE_ARRAY_SERIALIZE`), which catches a missing entry.
- New editable settings: leadership philosophy and the numbers strip (up to four value/label pairs, counted up on scroll). Capability pillars on the home page are the first three skill groups.
- Images: `x-site.image` uses the WebP `thumb` and `large` conversions with `srcset`, and width/height recorded at upload by `StoreMediaDimensions`, so there is no layout shift. Images uploaded before this phase have no recorded size until they are re-uploaded.
- Error pages: a styled 404 on the site layout, and a self-contained 500/503/403/419/429 that needs no database or build.
- `[x-cloak]` now hides only when JavaScript runs, so timeline details are readable without it.

## Phase 6 notes
- Admin: Articles (`PostController`, `SavePost`), Categories (generated from the module pattern) and Tags (a small plain module like Redirects: no Trash, deleting only detaches). Sidebar gained Categories and Tags.
- Publishing: status is Draft, Scheduled or Published. `SavePost` settles the pair: a Published article with a future date becomes Scheduled, a Scheduled one with a past date becomes Published, and editing keeps the original date. `posts:publish-scheduled` runs every minute from `routes/console.php` (it needs the scheduler cron from the deploy checklist) and saves each due post so the public cache clears. Visibility does not depend on it: `Post::published()` already counts a scheduled post whose time has passed.
- Dates and times are read and shown in `config('app.timezone')` (UTC unless `APP_TIMEZONE` is set). Set `APP_TIMEZONE` in `.env` to your own zone so "publish at 09:00" means 09:00 for you.
- Reading time is computed on save (200 words per minute). Views are counted without cookies: one per visitor per article per 30 minutes, bots and empty user agents ignored, written straight to the table so the cache, activity log and `updated_at` are untouched.
- Public: `/blog` (9 per page, category chips with counts, search), `/blog/category/{slug}`, `/blog/tag/{slug}`, `/blog/{slug}` and `/feed` (RSS 2.0 with full content). The list, categories and article pages come from `SiteContent` and are cached; search is not cached. Search uses the MySQL full-text index for terms of 3+ characters and a literal LIKE otherwise (checked on MySQL; the test suite runs on SQLite, which takes the LIKE path).
- Article pages: table of contents with unique heading ids (`ArticleHtml`), reading progress bar, a copy-link `#` on headings, related articles by shared tags and category, share links (no third-party scripts) and Article plus breadcrumb JSON-LD. Search results are `noindex`; page 2 and later have their own canonical.
- The cache allow-list (`config/cache.php`) gained Category, Tag and Pivot.

## Phase 7 notes
- **Contact form** (`/contact`): `ContactRequest` (single-line fields lose line breaks, so mail headers cannot be injected), a hidden honeypot field, an encrypted "form rendered at" token (submissions under 3 seconds are dropped silently; expired or tampered tokens get an error), a rate limit of 3 a minute and 10 an hour per address (`RateLimiter::for('contact')`), a ten-minute duplicate guard, and Cloudflare Turnstile. Bots that fail the honeypot or timing check see the normal thank-you page. Messages with more than two links are saved as Spam and send no email.
- **Turnstile** is enabled when both `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are set; with neither (local work) the check is skipped. If Cloudflare cannot be reached the form fails closed. Phase 8's CSP must allow `challenges.cloudflare.com`.
- **Mail**: `StoreContactMessage` saves the message and queues `NewContactMessage` (to the contact email setting, falling back to `MAIL_FROM_ADDRESS`; Reply-To is the sender) and `ContactAutoReply` (switchable in Profile & settings). Both are Markdown mailables using a branded copy of Laravel's mail theme in `resources/views/vendor/mail`. They are queued, so a queue worker must run in production (`QUEUE_CONNECTION=database`; Supervisor in Phase 9), and `.env` needs real `MAIL_*` settings. Locally `MAIL_MAILER=log` writes them to the log. Before launch, SPF/DKIM/DMARC for the sending domain (checklist in the doc).
- **Inbox** (`/admin/messages`): Inbox/Unread/Starred/Spam folders with counts, search, a reading view that marks a message read once, a mailto reply with the original quoted, star, mark replied/unread/spam, bulk actions, Trash, and CSV export of the current folder (cells that start like a spreadsheet formula are neutralised). The unread badge in the sidebar and navbar is the number of messages with status New.
- **CV** (`/cv`, `/cv/download/{id}`): lists the active version per label; the download is streamed from the private disk under a tidy file name with `X-Robots-Tag: noindex`. Each download writes a `cv_downloads` row (same-day visitor hash, country, referring host) and bumps `cv_files.downloads` directly in the table, so `updated_at` and the public cache are untouched. The same visitor counts once per ten minutes and bots are not counted.
- **Analytics** (`TrackPageView` on all public routes): records successful HTML page loads after the response is sent (`defer`), without cookies. It stores the path without its query string, a visitor hash that includes the date (so a person cannot be followed across days), the country from Cloudflare's `CF-IPCountry` header when present, the referring host only, and mobile/tablet/desktop. Bots, Do Not Track, Global Privacy Control, the admin, errors and non-GET requests are skipped. Rows older than 400 days are pruned daily (`model:prune`).
- **Dashboard**: stat boxes, an ApexCharts traffic line chart (30 days, zero-filled) with a table alternative, CV downloads by version (last 30 days and all time), top pages, top referrers, latest messages and drafts. ApexCharts loads only on the dashboard and follows the light/dark switch.
- The sidebar now has a real route behind every entry (a test enforces it).
