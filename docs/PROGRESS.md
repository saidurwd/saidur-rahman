# Progress

| Phase | Status |
| --- | --- |
| 0 · Foundation | Done (tag `phase-0`) |
| 1 · Data model | Done (awaiting review and tag) |
| 2 · Admin shell | Pending |
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
