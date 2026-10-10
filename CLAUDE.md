# saidur-rahman.com

Personal website of Saidur Rahman (Head of IT). Laravel 13, PHP 8.4, MySQL 8, Blade.
Public site: Tailwind CSS 4 + Alpine.js (resources/views/site). Admin: AdminLTE 4 (resources/views/admin, /admin).
Full architecture: docs/ARCHITECTURE.md. Current progress: docs/PROGRESS.md.

## Commands
- composer dev            # serve + queue + vite
- php artisan test        # Pest
- ./vendor/bin/pint       # format
- ./vendor/bin/phpstan    # Larastan level 6
- php artisan launch:check  # go-live checklist as code (run on the server)
- composer test           # clears cached config first; never run tests with a cached config (it points them at the real database)

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
