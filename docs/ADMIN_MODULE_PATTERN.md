# Admin module pattern

Every content module in `/admin` follows the Projects module. To add one, copy Projects and change the names; do not invent a new shape. In Claude Code, use the `/new-admin-module` command, which points at this file.

Reference files: `app/Http/Controllers/Admin/ProjectController.php`, `resources/views/admin/projects/`, `tests/Feature/Admin/ProjectsTest.php`.

## Files per module

| File | Purpose |
| --- | --- |
| `app/Models/Thing.php` | Model with `SoftDeletes`, `Orderable`, `LogsChanges`, a `published()` scope, and `HasPublishState` if it can be published. |
| `app/Policies/ThingPolicy.php` | `class ThingPolicy extends AdminPolicy {}` (auto-discovered). Override a method to restrict it. |
| `app/Http/Requests/Admin/SaveThingRequest.php` | One Form Request for create and update; `authorize()` picks the `create` or `update` ability from the route. Split into Store/Update only when the rules really differ (see `ProjectRequest` and its two subclasses). |
| `app/Actions/Admin/SaveThing.php` | Create and update in one place: purify rich text, set `sort_order`, write redirects for slug changes, handle media. For modules with a slug and rich text, reuse `SaveSlugged` (the model implements `HasPublicPath`). Trivial modules skip the action and call `create()`/`update()` in the controller. |
| `app/Http/Controllers/Admin/ThingController.php` | Thin: `index`, `create`, `store`, `edit`, `update`, `destroy`, plus `use ManagesModule` for Trash, restore, force delete, reorder and bulk. |
| `resources/views/admin/things/index.blade.php`, `form.blade.php` | List and the shared create/edit form. Trash uses `admin/modules/trash.blade.php`. |
| `routes/admin.php` | One line: `Route::adminModule('things', ThingController::class);` |
| `config/admin.php` | The sidebar entry already exists for each planned module; its route name must match. |
| `tests/Feature/Admin/ThingsTest.php` | Copy `ProjectsTest` and trim what does not apply. |

## Controller rules

- Under 15 lines per method. Validate with a Form Request, authorize with the policy (`$this->authorize(...)`, or in the request's `authorize()`), delegate to an Action.
- The trait needs `modelClass()`, `routeBase()` (e.g. `admin.things`), `moduleLabel()` and optionally `labelAttribute()` (the field that names a record in Trash).
- Trash/restore/force routes take a plain numeric `{id}`; `store`/`update`/`edit` use model binding.
- Redirect after a write with a `status` flash message; the layout turns it into a toast.

## List screen

- Search (`q`) and a status filter as GET parameters, `ordered()`, `paginate(15)->withQueryString()`.
- Include `admin.modules.partials.bulk-bar` and put `form="bulk"` on each row checkbox. Pass `publishable => false` if the model has no publish state.
- Drag reorder: add `data-sortable` and `data-reorder-url` to the `<tbody>`, `data-id` on each `<tr>`, and a `data-drag-handle` cell. Enable it only when no filter is active. Skip it when the order is not manual (for example career entries are ordered by date).
- Use `x-admin.delete-button` for row deletes (it is a form, so keep it outside other forms). Delete means soft delete; show a Trash link with a count when there is something in Trash.

## Edit screen

- Two columns: content on the left, a Publish card on the right (status or published toggle, featured, save button, "View on site" when public), then SEO fields, then images.
- Use the `x-admin.form.*` components. `x-admin.form.editor` loads TinyMCE; its HTML is purified in the Action with `HtmlSanitizer::clean()`. Plain text fields go through `HtmlSanitizer::plain()`.
- Put `data-unsaved-warning` on the form and `enctype="multipart/form-data"` when it has uploads. `data-slug-source` on the title input gives a live slug preview.
- Delete lives under the form, never inside it.

## Slugs and redirects

- `HasSlug` fills a unique slug on create. On update, if the slug changed, call `RedirectService::record($oldPath, $newPath)` (see `SaveProject`). The exception handler in `bootstrap/app.php` serves recorded redirects for 404s.
- Give the model a `publicPath()` so the redirect and the "View on site" link use one definition.

## Publish state

- Boolean models use `is_published` and `IsPublishable`. Status models override `publishState(bool)` from `HasPublishState` (Projects maps it to `Published`/`Draft`). Bulk publish and unpublish call this method.

## Caching

- Models carry `#[ObservedBy(ContentObserver::class)]`, so saving, deleting or restoring through the admin clears the public content cache. Nothing extra is needed per module.

## Tests to include

Guests are redirected; index lists, searches, filters and paginates; create and update validate and store (with purified HTML and slug rules where relevant); soft delete, restore and force delete; reorder and bulk actions if the module has them; the activity log records a change. `signInAsAdmin()` in `tests/Pest.php` signs in an admin who has passed 2FA.

## Modules built on this pattern

Projects (reference, own `SaveProject` action for media), Experience, Education, Certifications, Skill groups, Skills, Services, Pages, Menus, Testimonials. Variations worth copying:

- No drag reorder: Experience (ordered by date). Pass `'reorder' => false` to `Route::adminModule`.
- Slug and redirects through `SaveSlugged`: Services, Pages (Pages also blocks reserved slugs).
- Select options from another table: Skills (`groups` passed from the controller).
- Media on the model: Testimonials (`HasMedia`, a `photo` collection, `syncPhoto()`).
- Approval instead of publish: Testimonials override `publishState()` and `publishColumn()`.

Modules that deliberately do not follow the full shape:

- **Redirects**: no Trash, order or publish state, so a plain controller, hard delete, and `RedirectController::bulk` for bulk delete.
- **CV files**: `ManagesModule` plus `SaveCvFile`; files live on the private `local` disk under random names, one active version per label, download through an authenticated route.
- **Profile & settings**: a single form handled by `SaveSettings`; images go through `ImageStore` (WebP, EXIF removed).
- **Activity log** and **Media**: read-only lists (policies for these vendor models are registered in `AppServiceProvider`).
