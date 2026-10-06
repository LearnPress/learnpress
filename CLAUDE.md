# LearnPress — AI Project Instructions

## Overview
LearnPress is a WordPress LMS (Learning Management System) plugin.
- **Language:** PHP 7.4+
- **Framework:** WordPress
- **JS Build:** Webpack + Babel
- **CSS:** SCSS → compiled CSS
- **Testing:** PHPUnit, phpcs (WordPress Coding Standards)

## Architecture
- Entry point: `learnpress.php`
- Autoloading: PSR-4 maps the `LearnPress\` namespace to `inc/`
- Core classes: `inc/`
- Models: `inc/Models/` (PostModel pattern)
- Database layer: `inc/Databases/` (DB classes with `wpdb`)
- Services: `inc/Services/`
- AJAX handlers: `inc/Ajax/`
- Query filters: `inc/Filters/`
- Template hooks: `inc/TemplateHooks/`
- Shared helpers: `inc/Helpers/`
- Templates: `templates/`
- Assets (JS/CSS): `assets/`
- JavaScript source: `assets/src/js/` (vanilla JavaScript)
- Config: `config/`

## Code Standards
- Follow WordPress Coding Standards (phpcs.xml)
- Use PSR-4 for new PHP classes under `inc/`; namespace segments must mirror subdirectories and class names must match filenames
- Do not add manual `require` or `include` statements for PSR-4 classes
- Run `composer dump-autoload` after adding or moving PSR-4 classes
- Use Model/DB pattern instead of raw `$wpdb` queries where possible
- Prefer `PostModel::find()` / `PostModel::delete()` over direct SQL

## Key Conventions
- Post types: `lp_course`, `lp_lesson`, `lp_quiz`, `lp_question`, `lp_order`
- Custom tables: `learnpress_*` prefix
- Hooks: `learn-press/*` or `learnpress/*` filter/action naming
- Singleton pattern for main classes
- JavaScript in `assets/src/js/` must use vanilla JavaScript and reuse exports from `assets/src/js/utils.js` when equivalent utilities exist
- Import Toastify notifications from `assets/src/js/lpToastify.js`
- Use the pre-enqueued `window.lpAJAXG` for LearnPress AJAX; do not import `assets/src/js/loadAJAX.js`
- Reuse `AdminTemplate::html_form_filter()` for admin filters with multiple fields and actions
- Reuse `AdminTemplate::html_toggle_enable()` for enable/disable toggle controls
- Reuse `AdminTemplate::html_tom_select()` for Tom Select controls
- Reuse `AdminTemplate::editor_tinymce()` for TinyMCE editors
- Reuse `Template::print_message()` for LearnPress status messages
- Reuse `Template::instance()->html_pagination()` for pagination markup

## Testing
- PHPUnit config: `phpunit.xml`
- Tests directory: `tests/`

## Build
- `npm run build` — production build
- `npm run dev` — development watch

## Specs & Planning
- Feature specs: `.claude/specs/<feature-slug>/`
- Workflow: `/new-spec`, `/plan-spec`, `/resume`, `/status`
