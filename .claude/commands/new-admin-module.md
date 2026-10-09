---
description: Add a new admin content module by copying the Projects pattern
argument-hint: <module name, e.g. Experience>
---

Build the admin module "$ARGUMENTS".

1. Read `docs/ADMIN_MODULE_PATTERN.md` and the Projects module it references. Follow it exactly: copy, do not invent.
2. Check the model, migration and sidebar entry (`config/admin.php`) for this module already exist. If the model lacks `SoftDeletes`, `Orderable` or a `published()` scope, add them as in the Projects model.
3. Create the policy, Form Requests, controller (using `ManagesModule`), views and the `Route::adminModule(...)` line. Use existing `x-admin.*` components.
4. Write Pest feature tests by copying `tests/Feature/Admin/ProjectsTest.php` and trimming what does not apply.
5. Run `php artisan test`, `./vendor/bin/pint` and `./vendor/bin/phpstan`, and fix everything before finishing.
6. Do not add packages without asking. Update `docs/PROGRESS.md`.
