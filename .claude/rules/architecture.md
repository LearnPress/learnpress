# Architecture Rules — LearnPress

## Directory Structure
- `inc/Models/` — Data models (PostModel, DB-backed models)
- `inc/Databases/` — Database abstraction layer (table-specific DB classes)
- `inc/Services/` — Business logic services
- `inc/Ajax/` — AJAX request handlers
- `inc/Filters/` — Query filter and criteria objects
- `inc/TemplateHooks/` — Hook-based template rendering and composition
- `inc/Helpers/` — Reusable shared utilities
- `inc/admin/` — WordPress admin functionality
- `templates/` — Frontend template files
- `assets/src/` — Source JS/SCSS files
- `assets/src/js/` — Vanilla JavaScript source files
- `assets/js/`, `assets/css/` — Compiled output
- `assets/dist/js/` — Compiled output

## Patterns
- **PSR-4 autoloading:** Map the `LearnPress\` namespace to `inc/`; namespaces and class paths must match exactly
- **Model/DB separation:** Models handle business logic, DB classes handle queries
- **PostModel pattern:** Custom post types extend PostModel for CRUD
- **Singleton:** Main plugin classes use singleton pattern
- **Service layer:** Complex operations go through Service classes
- **Filter/Action hooks:** Use `learn-press/*` namespace

## Rules
- Use PSR-4 for new PHP classes under `inc/`; do not add manual `require` or `include` statements for them
- Use the `LearnPress\` root namespace and mirror the directory structure below `inc/` (for example, `inc/Services/Foo.php` uses `LearnPress\Services\Foo`)
- Use StudlyCaps class names and matching filenames
- Run `composer dump-autoload` after adding or moving PSR-4 classes
- Never use raw `$wpdb` in Models — delegate to DB classes
- Always use `PostModel::find()` for loading post-type entities
- Keep templates logic-free — use template tags or pass data from controllers
- REST endpoints must validate permissions via `permission_callback`
- Use `inc/Ajax/` for AJAX request handling, validation, and responses
- Use `inc/Filters/` to carry query criteria; keep query execution in database classes
- Use `inc/TemplateHooks/` for hook registration and template composition; keep template files logic-free
- Keep `inc/Helpers/` generic and reusable; put domain business logic in Services or Models
- Use `AdminTemplate::html_form_filter()` for admin filters with multiple fields and actions
- Use `AdminTemplate::html_toggle_enable()` for enable/disable toggle controls
- Use `AdminTemplate::html_tom_select()` for Tom Select controls
- Use `AdminTemplate::editor_tinymce()` for TinyMCE editors
- Use `Template::print_message()` for LearnPress status messages
- Use `Template::instance()->html_pagination()` for pagination markup
- Write files in `assets/src/js/` with vanilla JavaScript
- Reuse functions from `assets/src/js/utils.js` when an equivalent utility exists instead of duplicating them
- Import Toastify notifications from `assets/src/js/lpToastify.js`
- Use the pre-enqueued `window.lpAJAXG` for LearnPress AJAX; do not import `assets/src/js/loadAJAX.js`
