# saidur-rahman.com — Architecture & Claude Code Build Plan

Oct 9, 2026 · @Saidur Rahman

## Overview

Build saidur-rahman.com as one Laravel app with two surfaces: a hand-designed public site for visitors and an AdminLTE panel at `/admin` where you manage every piece of content without touching code. Ship it in 10 phases, each a self-contained Claude Code session you review and commit before moving on.

**Who the site is for:** recruiters and hiring managers in Qatar and the wider GCC, CIOs and IT leaders, and potential consulting clients. They decide in under a minute whether you are senior, credible and current.

**Positioning line (draft):** IT leader and builder — 21+ years running enterprise IT, and still shipping production systems (ERP, HMS, AssetTaskPro) myself.

**What success looks like:**

- A visitor can see role, experience, flagship projects and a way to contact you within one scroll
- CV download, case studies and articles are all editable from the admin panel
- Lighthouse 90+ on Performance, Accessibility, Best Practices and SEO on mobile
- Contact enquiries land in the admin inbox and your email, with spam filtered out
- The site does not look like a template: no stock theme, no generic hero with a laptop photo

## Tech stack

Use Laravel 13 on PHP 8.3+, MySQL 8, AdminLTE 4 for the admin and a separate Tailwind front end for the public site, both built with Vite. Keeping the two CSS worlds separate is what lets the public site look unique while the admin stays standard.

| Layer | Choice | Why |
| --- | --- | --- |
| Framework | [Laravel 13](https://laravel-news.com/laravel-13-released) (PHP 8.3 minimum) | Current major; security fixes into Q1 2028 |
| Database | MySQL 8.0+ (utf8mb4) | Your requirement; full-text search for articles |
| Public templates | Blade components + Tailwind CSS 4 + Alpine.js | Full design control, tiny JS, no template look |
| Admin templates | [AdminLTE 4](https://heise.de/-11306526) (Bootstrap 5.3, no jQuery) via [colorlibhq/adminlte-laravel](https://laraplugins.io/plugins/colorlibhq/adminlte-laravel) | Official Laravel integration, dark mode, Vite-ready |
| Rich text | TinyMCE or Trix in admin; HTML purified on save (mews/purifier) | Safe article and case-study editing |
| Auth | Laravel Breeze (Blade), restyled into AdminLTE login page | Simple session auth, single admin user + 2FA |
| Images | spatie/laravel-medialibrary + WebP conversions | Responsive images, thumbnails, alt text |
| SEO | spatie/laravel-sitemap, custom meta component, JSON-LD | Sitemap, Open Graph, Person schema |
| Mail | Laravel Mail via SMTP (Zoho, Brevo or Amazon SES) + queued jobs | Contact notifications without slowing the form |
| Spam | Honeypot + time-trap + rate limiter + Cloudflare Turnstile | Blocks bots without annoying recruiters |
| Analytics | Plausible or Umami (privacy-friendly), plus own page-view counter | Simple, no cookie banner needed |
| Testing | Pest + Laravel Dusk (optional) | Claude Code writes tests per phase |
| Code quality | Laravel Pint, Larastan level 6 | Keeps AI-written code consistent |
| Hosting | VPS (Hetzner, DigitalOcean or Contabo) with Laravel Forge or Ploi; Cloudflare in front | Full control, cheap, fast in GCC with CDN |

The AdminLTE 4 Laravel package is new; if it gives trouble, install `admin-lte@^4` from npm and build your own `layouts/admin.blade.php` instead. That fallback is 1–2 hours of work.

## Site map

Nine public pages and fourteen admin modules; every public section is driven by an admin module, so nothing is hard-coded.

**Public website**

| Page | URL | Content |
| --- | --- | --- |
| Home | `/` | Hero with positioning line, key numbers, featured projects, short career timeline, latest articles, contact call-to-action |
| About | `/about` | Story, leadership philosophy, skills matrix, certifications, education |
| Experience | `/experience` | Full career timeline with roles, scope and achievements |
| Projects | `/projects`, `/projects/{slug}` | Case studies: problem, role, architecture, stack, outcomes, screenshots (HMS ERP, Tea ERP, AssetTaskPro) |
| Articles | `/blog`, `/blog/{slug}`, `/blog/category/{slug}` | Writing on IT leadership, ERP, Laravel, digital transformation |
| Services | `/services` | Optional: consulting offers (ERP advisory, IT audit, digital transformation) |
| Speaking & media | `/media` | Optional: talks, interviews, awards |
| Contact | `/contact` | Form, email, LinkedIn, location (Dhaka, open to Qatar/GCC), availability status |
| CV | `/cv` | Download tracked PDF; choose IT Leadership or Engineering version |
| System | `/sitemap.xml`, `/robots.txt`, `/feed`, `404`, `500` | SEO and error pages in site style |

**Admin panel (`/admin`)**

| Module | What you manage |
| --- | --- |
| Dashboard | Visits, new enquiries, CV downloads, latest posts |
| Profile & settings | Name, headline, bio, photo, social links, SEO defaults, availability toggle |
| Experience | Roles with company, dates, location, achievements; drag to reorder |
| Education & certifications | Degrees, certificates, issuer, year, credential link |
| Skills | Grouped skills with level and order |
| Projects | Case studies with gallery, tech tags, featured flag, status |
| Articles | Posts, categories, tags, drafts, scheduled publish, SEO fields |
| Services | Optional offers with icon and order |
| Testimonials | Quotes from colleagues and clients, with approval |
| Messages | Contact inbox: read, star, reply by email, archive, export |
| CV files | Upload versions, set active, see download counts |
| Media library | All uploaded images and files |
| Pages & menus | Simple editable pages (privacy, custom landing) and navigation order |
| Activity log | Who changed what and when |

## Architecture

One Laravel app, two route files, two layouts, one shared service layer. Controllers stay thin; all logic lives in Services and Actions so public and admin code never duplicate rules.

&#91;embedded content: application architecture · two surfaces, one core\]

Requests split at the route file: public pages read cached, published content; admin routes sit behind authentication and two-factor, and every write goes through a Form Request and a Policy.

**Folder structure**

```
app/
  Actions/            # single-purpose writes: PublishPost, ReorderItems, StoreContactMessage
  Enums/              # PostStatus, ProjectStatus, MessageStatus
  Http/
    Controllers/
      Site/           # HomeController, ProjectController, BlogController, ContactController
      Admin/          # DashboardController, ProjectController, PostController, MessageController...
    Requests/Admin/   # StoreProjectRequest, UpdatePostRequest...
    Middleware/       # TrackPageView, EnsureTwoFactor
  Models/
  Policies/
  Services/           # SeoService, SettingService, ContactService, CacheService
  View/Components/    # Site\Hero, Site\ProjectCard, Admin\FormInput...
  Mail/               # NewContactMessage, ContactAutoReply
  Jobs/
resources/
  views/
    site/             # layouts/app.blade.php, pages/, partials/, components/
    admin/            # layouts/admin.blade.php, modules per folder
    emails/
  css/site.css        # Tailwind entry
  css/admin.css       # AdminLTE entry
  js/site.js          # Alpine + small interactions
  js/admin.js         # AdminLTE + editors
routes/
  web.php             # public
  admin.php           # prefix admin, name admin., middleware auth + 2fa
```

**Rules Claude Code must follow**

- Controllers: under 15 lines per method; validate with Form Requests, authorize with Policies, delegate to an Action or Service
- Settings (name, headline, socials, SEO defaults) come from a `settings` table through `SettingService`, cached forever and cleared on save
- Public queries always scope to `published()` and are cached; the cache is cleared by model observers when admin saves
- Slugs are unique and stable; changing a slug writes a 301 redirect record
- Two separate Vite entries so admin CSS never loads on the public site
- Every admin module follows one CRUD pattern (index table, create/edit form, soft delete, restore) so Claude can repeat it

## Database schema

About 20 tables. Every content table has `sort_order`, `is_published` (or `status`), timestamps and soft deletes, so one admin CRUD pattern fits all of them.

| Table | Key columns | Notes |
| --- | --- | --- |
| `users` | name, email, password, two\_factor\_secret, last\_login\_at | Single admin; seed one user |
| `settings` | key, value (json), group | Site name, headline, bio, socials, SEO defaults, availability, theme toggles |
| `experiences` | company, role, location, employment\_type, start\_date, end\_date, is\_current, summary, achievements (json) | Career timeline |
| `educations` | institution, degree, field, start\_year, end\_year |  |
| `certifications` | name, issuer, issued\_at, expires\_at, credential\_url |  |
| `skill_groups` / `skills` | group name; skill name, level (1–5), years | Skills matrix |
| `projects` | title, slug, client, role, period, summary, problem, solution, architecture, outcomes, tech\_stack (json), live\_url, repo\_url, is\_featured, status, seo\_title, seo\_description | Case studies; images via media table |
| `posts` | title, slug, excerpt, body, category\_id, status (draft/scheduled/published), published\_at, reading\_minutes, views, seo\_title, seo\_description, og\_image | Articles |
| `categories` / `tags` / `post_tag` | name, slug |  |
| `services` | title, slug, icon, summary, body | Optional |
| `testimonials` | name, position, company, quote, photo, is\_approved |  |
| `messages` | name, email, company, subject, body, ip, user\_agent, status (new/read/replied/spam), read\_at, starred | Contact inbox |
| `cv_files` | label (Leadership / Engineering), file\_path, is\_active, downloads |  |
| `cv_downloads` | cv\_file\_id, ip\_hash, country, referrer, created\_at | Who downloads your CV |
| `page_views` | path, ip\_hash, country, referrer, device, created\_at | Lightweight own analytics; hash IPs |
| `pages` | title, slug, body, is\_published, seo fields | Privacy page, custom pages |
| `menus` | label, url or route, location (header/footer), sort\_order |  |
| `redirects` | from\_path, to\_path, code | Slug changes stay SEO-safe |
| `media` | Spatie medialibrary table | Images, conversions, alt text |
| `activity_log` | Spatie activitylog table | Audit trail |
| `jobs`, `failed_jobs`, `cache`, `sessions` | Laravel defaults | Database queue and cache to start |

Index every `slug` (unique), `status` + `published_at` on posts, and `created_at` on views and downloads. Store dates as `DATE` for career entries so "Present" is simply `is_current = 1`.

## Public site design direction

Recommended concept: **"The Architect's Ledger"** — an editorial, blueprint-inspired look that says "senior systems leader who still builds". Calm and confident, not flashy; closer to a well-designed annual report than a developer portfolio template.

**Visual identity**

- **Palette:** deep ink navy (#0F1B2D) as base, warm paper off-white (#F7F5F0) for light sections, one accent in tea-leaf green (#2F7D5B) as a quiet nod to the tea-estate work, plus a muted brass (#B08D57) for small highlights. Dark mode inverts ink and paper
- **Type:** a characterful serif for headings (Fraunces or Newsreader), a clean sans for body (Inter or IBM Plex Sans), and a mono (JetBrains Mono) for labels, dates and tech tags. Self-host fonts for speed
- **Signature motif:** fine blueprint grid lines and thin connector lines that tie sections together, like a system diagram. Use it in the hero background and the career timeline
- **Monogram:** "SR" mark drawn as two connected nodes; use as favicon, logo and loading mark

**Signature sections that make it unique**

1. **Hero:** large serif headline ("I lead IT and I still build the systems."), one line of proof, two buttons (View work, Download CV), a real professional portrait (not stock), animated grid lines drawn on load
2. **Numbers strip:** 21+ years, systems shipped, sites or users supported, team size — set in mono, counting up once on scroll. Use only figures you can defend in an interview
3. **Career as a system map:** the timeline drawn as a vertical connector line with nodes per role; each node expands to show scope and achievements
4. **Case study pages:** written like consulting reports — Context, Challenge, My role, Architecture (with a diagram image), Outcome, Stack. This is what separates you from template portfolios
5. **Capability grid:** three pillars — IT Leadership & Governance, Enterprise Systems (ERP/HMS), Infrastructure & Security — each with proof links
6. **Contact block:** "Open to senior IT leadership roles in Qatar and the GCC" status chip, controlled from admin

**Motion and polish:** subtle fade-up on scroll (respecting `prefers-reduced-motion`), sticky slim header that shrinks on scroll, reading progress bar on articles, copy-link buttons on headings, print stylesheet for case studies.

**Avoid:** carousels, skill progress bars with percentages, stock laptop photos, more than one accent colour, auto-playing anything.

**How to get Claude Code to produce this:** before Phase 3, ask it to load the `frontend-design` skill (if available in your Claude Code setup) and build a static `/styleguide` page first — colours, type scale, buttons, cards, timeline node — and approve that before any real page is built.

## Admin panel with AdminLTE 4

Keep AdminLTE close to stock so it stays maintainable; brand it with your monogram, the tea-leaf green as primary colour and dark mode on by default.

- **Layout:** one `admin/layouts/admin.blade.php` with sidebar, top navbar (view site, notifications for new messages, profile, logout), breadcrumbs and flash toasts
- **Sidebar groups:** Dashboard · Content (Projects, Articles, Experience, Education, Skills, Services, Testimonials, Pages) · Inbox (Messages with unread badge) · Files (CV files, Media) · Site (Profile & Settings, Menus, Redirects) · System (Activity log, Cache clear)
- **Reusable Blade components:** `x-admin.card`, `x-admin.form.input`, `.textarea`, `.select`, `.editor`, `.image-upload`, `.toggle`, `x-admin.table`, `x-admin.delete-button`, `x-admin.status-badge`. Claude builds these once in Phase 2, then every module uses them
- **List screens:** search, status filter, pagination, drag-and-drop reorder (SortableJS) where order matters, bulk publish/unpublish/delete
- **Edit screens:** two columns — content left, publish box right (status, publish date, featured, SEO fields, featured image); live slug preview; "View on site" button
- **Dashboard widgets:** page views last 30 days (ApexCharts line), new messages, CV downloads by version, top pages, latest drafts
- **Messages:** read view with "Reply by email" (mailto with quoted text), mark as spam, export CSV
- **Safety:** confirm dialogs on delete, soft delete with Trash view, unsaved-changes warning on forms

## Security, performance and SEO

**Security**

- Admin login at `/admin/login` only; no public registration route; two-factor (TOTP) required; login throttled to 5 attempts per minute
- Optional: restrict `/admin` by IP or put it behind Cloudflare Access for extra protection
- Purify all rich-text HTML on save; escape everything else with `{{ }}`
- Validate uploads by MIME and size; store CVs with random names; strip EXIF from photos
- Security headers middleware: CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy
- `APP_DEBUG=false` in production, `.env` outside web root, nightly database + storage backups to off-site storage (S3 or Backblaze) with 30-day retention
- Hash visitor IPs in analytics tables; publish a short privacy page

**Performance**

- Cache settings and public queries; clear by observers on save. Add `spatie/laravel-responsecache` for full-page cache on public pages
- Responsive WebP images with `srcset`, lazy loading below the fold, width/height set to avoid layout shift
- Self-hosted, subset fonts with `font-display: swap`; preload the heading font
- Run `config:cache`, `route:cache`, `view:cache`, `event:cache` on deploy; OPcache on
- Target: under 1.5 s Largest Contentful Paint on mobile 4G, under 150 KB of JS on public pages

**SEO**

- Per-page `<title>`, meta description, canonical, Open Graph and Twitter tags from one `x-seo` component with admin overrides
- JSON-LD: `Person` on home and about (with `sameAs` LinkedIn), `Article` on posts, `BreadcrumbList` on inner pages
- XML sitemap regenerated nightly; RSS feed for articles; clean slugs; 301 redirects table
- Write the home page headline and about page for the searches recruiters run: "Head of IT", "IT Director", "ERP", "Qatar", your full name
- Verify the domain in Google Search Console and Bing Webmaster Tools at launch

## Claude Code setup

Put the rules in a `CLAUDE.md` at the project root and this guide in `docs/ARCHITECTURE.md`; Claude Code reads `CLAUDE.md` every session, which keeps phase 8 consistent with phase 1.

**Starter `CLAUDE.md`**

```markdown
# saidur-rahman.com

Personal website of Saidur Rahman (Head of IT). Laravel 13, PHP 8.3, MySQL 8, Blade.
Public site: Tailwind CSS 4 + Alpine.js (resources/views/site). Admin: AdminLTE 4 (resources/views/admin, /admin).
Full architecture: docs/ARCHITECTURE.md. Current progress: docs/PROGRESS.md.

## Commands
- composer dev            # serve + queue + vite
- php artisan test        # Pest
- ./vendor/bin/pint       # format
- ./vendor/bin/phpstan    # Larastan level 6

## Rules
- Thin controllers. Validation in Form Requests, authorization in Policies, logic in app/Actions or app/Services.
- Admin modules follow the Projects module pattern exactly (copy it, don't invent).
- Use existing Blade components in resources/views/components before creating new ones.
- Never put admin CSS/JS on the public site. Two Vite entries: site and admin.
- Public queries: published() scope + cache. Clear cache in model observers.
- All settings via SettingService, never hard-coded.
- Every new feature gets Pest feature tests. Run tests, Pint and PHPStan before saying a task is done.
- No new composer/npm packages without asking me first.
- Update docs/PROGRESS.md at the end of each phase.
```

**Working method per phase**

1. Start a fresh session, paste the phase prompt from the next section
2. Ask for a plan first (plan mode); correct it before any code is written
3. Let it build; review the diff; run the site locally and click through
4. Ask it to run tests, Pint and PHPStan and fix all failures
5. Commit with a phase tag (`git tag phase-3`), then start the next phase in a new session

**Tips:** keep one feature branch per phase; when something goes wrong, `/clear` and restate rather than arguing in a long session; give screenshots of designs you like when asking for visual changes; use a custom `/new-admin-module` command for the repeated CRUD pattern.

## Development phases

Ten phases, about 12–18 working sessions in total. Each phase ends with passing tests, a commit and a tag; never start the next phase on a red build.

| Phase | Delivers | Sessions |
| --- | --- | --- |
| 0 · Foundation | Project, tooling, CLAUDE.md, two Vite entries | 1 |
| 1 · Data model | Migrations, models, seeders, SettingService | 1–2 |
| 2 · Admin shell | AdminLTE layout, login + 2FA, components, dashboard skeleton | 1–2 |
| 3 · Design system | Styleguide page, public layout, header, footer, SEO component | 2 |
| 4 · Admin modules | Projects as the reference module, then all others | 2–3 |
| 5 · Public pages | Home, About, Experience, Projects, case studies | 2–3 |
| 6 · Blog | Articles admin + public, categories, tags, RSS | 1–2 |
| 7 · Contact & CV | Contact form, inbox, emails, CV downloads, analytics | 1 |
| 8 · Hardening | SEO, performance, security headers, test coverage | 1 |
| 9 · Launch | Server, deploy pipeline, backups, go-live | 1 |

### Phase 0 — Foundation

```
Read docs/ARCHITECTURE.md. Create a new Laravel 13 project for saidur-rahman.com with MySQL.
Set up: Pest, Laravel Pint, Larastan (level 6), Tailwind CSS 4 + Alpine.js for the public site,
and two separate Vite entries (resources/css/site.css + js/site.js, resources/css/admin.css + js/admin.js).
Create routes/admin.php registered with prefix 'admin', name 'admin.', middleware web.
Create CLAUDE.md from the docs and docs/PROGRESS.md. Add a composer 'dev' script.
Show me the plan first.
```

**Done when:** home route shows a placeholder using the site bundle; `/admin` placeholder uses the admin bundle; tests, Pint and PHPStan pass.

### Phase 1 — Data model

```
Using the schema in docs/ARCHITECTURE.md, create migrations, models (with casts, relations,
scopes published() and ordered()), enums, factories and seeders for all content tables.
Install spatie/laravel-medialibrary and spatie/laravel-activitylog.
Build SettingService (get/set, cached, cleared on save) and a settings seeder with my profile
defaults. Seed realistic sample content so pages can be built against it.
Add model observers that clear related caches on save/delete. Write unit tests for scopes and SettingService.
```

**Done when:** `php artisan migrate:fresh --seed` runs clean and tests cover scopes, slugs and settings.

### Phase 2 — Admin shell and authentication

```
Install AdminLTE 4 via colorlibhq/adminlte-laravel (fallback: admin-lte@^4 from npm with our own layout).
Build admin/layouts/admin.blade.php: sidebar groups from docs, navbar with View site, unread messages,
profile, logout; breadcrumbs; toast flash messages; dark mode default; my SR monogram and green primary colour.
Auth: login only (no registration), throttle 5/min, TOTP two-factor with recovery codes, password change page.
Create reusable components: x-admin.card, form inputs (input, textarea, select, editor, image-upload,
toggle), x-admin.table, delete-button, status-badge. Dashboard with placeholder widgets.
Feature tests: guests are redirected, login throttle, 2FA required.
```

**Done when:** you can log in with 2FA and see a branded, empty admin; all components render on a demo page.

### Phase 3 — Design system and public layout

```
Use the frontend-design skill. Implement the 'Architect's Ledger' design direction from docs/ARCHITECTURE.md.
First build a /styleguide page (local only): colours, dark mode, type scale with Fraunces/Inter/JetBrains Mono
(self-hosted), buttons, links, cards, tags, timeline node, section headings, blueprint grid background.
Then build site/layouts/app.blade.php: sticky slim header with menu from the menus table, mobile menu with
Alpine, footer with socials from settings, dark-mode toggle, and an x-seo component (title, description,
canonical, Open Graph, JSON-LD slot). Respect prefers-reduced-motion. No component libraries.
```

**Done when:** you approve the styleguide on desktop and phone. Do not continue until the look feels like you.

### Phase 4 — Admin content modules

```
Build the Projects admin module as the reference pattern: index (search, status filter, pagination,
drag reorder with SortableJS, bulk actions), create/edit with two-column layout (content | publish box
with status, featured, SEO fields, featured image, gallery), TinyMCE editor with HTML purifier,
slug auto-generation with redirect on change, soft delete + Trash, Form Requests, Policy, activity log.
Write feature tests. Then document this pattern in docs/ADMIN_MODULE_PATTERN.md.
```

Then one short session per remaining module: *"Following docs/ADMIN\_MODULE\_PATTERN.md exactly, build the Experience module"* — repeat for Education, Certifications, Skills, Services, Testimonials, Pages, Menus, Redirects, CV files and Profile & Settings.

**Done when:** every section of the public site can be edited from admin and every module has passing tests.

### Phase 5 — Public pages

```
Build the public pages from docs/ARCHITECTURE.md using only data from the database:
Home (hero, numbers strip, capability pillars, featured projects, career preview, latest articles, contact CTA),
About, Experience (career system-map timeline with expandable nodes), Projects index with tech filter,
and case study pages (Context, Challenge, My role, Architecture, Outcome, Stack, gallery, next/previous).
Responsive images via medialibrary conversions. Styled 404 and 500 pages. Person JSON-LD on home and about.
Feature tests: pages return 200, unpublished items are hidden.
```

**Done when:** the site reads well on a phone with your real content entered, not sample text.

### Phase 6 — Articles

```
Build the blog: Posts admin module (pattern file), categories and tags, draft/scheduled/published with a
scheduled command to publish, reading time, view counter. Public: /blog with category filter and search,
post page with table of contents, reading progress bar, related posts, share links, Article JSON-LD.
RSS feed at /feed. Tests for scheduling and visibility.
```

### Phase 7 — Contact, inbox and CV

```
Contact form: Form Request, honeypot + minimum-time check, rate limit, Cloudflare Turnstile.
StoreContactMessage action saves the message and dispatches queued mails: NewContactMessage to me,
ContactAutoReply to the sender (Markdown mailables, my branding). Admin Messages inbox: unread badge,
read view, star, mark spam, reply via mailto, export CSV.
CV: /cv page offers the active versions; downloads go through a controller that logs cv_downloads
(hashed IP, referrer) and streams the file. Lightweight TrackPageView middleware for page_views.
Dashboard widgets with ApexCharts: views (30 days), messages, CV downloads by version, top pages.
```

### Phase 8 — Hardening

```
Audit the whole project against the Security, performance and SEO section of docs/ARCHITECTURE.md.
Add security headers middleware (CSP, HSTS...), response cache for public pages, sitemap generation
(scheduled nightly), robots.txt, canonical URLs, image dimensions, font preloading.
Run npx lighthouse (mobile) against Home, a case study and an article; list issues, then fix them.
Raise test coverage on Actions and Services; make sure PHPStan level 6 passes with no baseline.
Give me a report of what you changed and anything left for me to decide.
```

**Done when:** Lighthouse mobile is 90+ in all four categories on Home, a case study and an article.

### Phase 9 — Deployment and launch

```
Prepare production deployment: .env.example for production, a deploy script (composer install --no-dev,
npm ci && npm run build, migrate --force, optimize, queue:restart), Supervisor config for the queue
worker, scheduler cron line, Nginx config for saidur-rahman.com with www redirect, and a backup
command (spatie/laravel-backup) to off-site storage. Write docs/DEPLOYMENT.md step by step.
```

## Deployment and launch checklist

Go live on a small VPS behind Cloudflare; plan one evening for setup and one week of soft launch before you put the link on your CV.

**Server**

- [ ] VPS with 2 vCPU / 4 GB RAM (Ubuntu 24.04), provisioned by Forge or Ploi with PHP 8.3+, Nginx, MySQL 8, Redis optional
- [ ] Point saidur-rahman.com DNS to Cloudflare; proxied A record; SSL mode Full (strict)
- [ ] Git-based deploy from GitHub `main`, running the Phase 9 deploy script
- [ ] Supervisor queue worker and scheduler cron active
- [ ] Off-site nightly backups tested by an actual restore

**Email**

- [ ] Mailbox for hello@saidur-rahman.com (Zoho Mail or Google Workspace)
- [ ] Transactional SMTP set in `.env`; SPF, DKIM and DMARC records added so contact emails don't land in spam

**Content before launch**

- [ ] Professional portrait and an OG share image (1200×630)
- [ ] At least 3 case studies (HMS ERP, Tea ERP, AssetTaskPro) with architecture diagrams and real, defensible outcomes
- [ ] Both CV versions uploaded; same facts as the site and LinkedIn
- [ ] 2–3 articles so the blog is not empty
- [ ] Privacy page

**Go-live**

- [ ] Lighthouse 90+ on mobile, no console errors, all forms tested from a phone
- [ ] Google Search Console + Bing Webmaster verified; sitemap submitted
- [ ] Uptime monitor (UptimeRobot or Better Stack) on home and `/up`
- [ ] Add the URL to LinkedIn headline/featured, CV header and email signature

## Sources

- [Laravel 13 released — Laravel News](https://laravel-news.com/laravel-13-released)
- [AdminLTE 4.0 release — heise](https://heise.de/-11306526)
- [colorlibhq/adminlte-laravel — Laraplugins](https://laraplugins.io/plugins/colorlibhq/adminlte-laravel)
