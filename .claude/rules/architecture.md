# Architecture Rules — LearnPress

## Directory Structure
- `inc/Models/` — Data models (PostModel, DB-backed models); business logic, no raw `$wpdb`
- `inc/Databases/` — Table-specific DB classes; all query execution lives here
- `inc/Filters/` — Query criteria objects passed to DB classes
- `inc/Services/` — Business logic for complex operations
- `inc/Ajax/` — AJAX request handling, validation and responses
- `inc/TemplateHooks/` — Hook registration and template composition
- `inc/Helpers/` — Generic reusable utilities only (no domain logic)
- `inc/admin/` — WordPress admin functionality
- `templates/` — Frontend template files (logic-free)
- `config/` — Configuration
- `assets/src/` — Source JS/SCSS; `assets/js/`, `assets/css/`, `assets/dist/js/` — compiled output (do not edit)

## PHP
- New classes under `inc/` use PSR-4: `LearnPress\` root namespace mirrors the directory (`inc/Services/Foo.php` → `LearnPress\Services\Foo`), StudlyCaps class names matching filenames
- Never add `require`/`include` for PSR-4 classes; run `composer dump-autoload` after adding or moving them
- Never use raw `$wpdb` in Models — delegate to DB classes
- Load post-type entities with `PostModel::find()`; prefer `PostModel::delete()` over direct SQL
- Main plugin classes use the singleton pattern
- Keep templates logic-free — pass data from TemplateHooks or use template tags
- REST endpoints must validate permissions via `permission_callback`

## Reuse existing UI helpers
- `AdminTemplate::html_form_filter()` — admin filters with multiple fields and actions
- `AdminTemplate::html_toggle_enable()` — enable/disable toggles
- `AdminTemplate::html_tom_select()` — Tom Select controls
- `AdminTemplate::editor_tinymce()` — TinyMCE editors
- `Template::print_message()` — LearnPress status messages
- `Template::instance()->html_pagination()` — pagination markup
