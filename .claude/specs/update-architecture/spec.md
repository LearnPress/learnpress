# update-architecture

> Document the Ajax, Filters, TemplateHooks, and Helpers architecture in the project guidance.

## Goal
Document the responsibilities of the existing `inc/Ajax/`, `inc/Filters/`, `inc/TemplateHooks/`, and `inc/Helpers/` directories so contributors and AI agents can place code in the correct architectural layer.

## Requirements
- [x] Add `inc/Ajax/` to the architecture directory overview in `CLAUDE.md` and `.claude/rules/architecture.md`.
- [x] Add `inc/Filters/` to the architecture directory overview in `CLAUDE.md` and `.claude/rules/architecture.md`.
- [x] Add `inc/TemplateHooks/` to the architecture directory overview in `CLAUDE.md` and `.claude/rules/architecture.md`.
- [x] Add `inc/Helpers/` to the architecture directory overview in `CLAUDE.md` and `.claude/rules/architecture.md`.
- [x] Remove the obsolete `inc/Rest/` entry from both documents because the directory does not exist.
- [x] Describe each directory consistently with its current responsibility and patterns.
- [x] Keep the documentation concise and consistent with the existing Markdown style.
- [x] Document `assets/src/js/` as vanilla JavaScript source.
- [x] Require reuse of existing functions from `assets/src/js/utils.js` when applicable.
- [x] Require Toastify notifications to be imported from `assets/src/js/lpToastify.js`.
- [x] Require AJAX consumers to use the pre-enqueued `window.lpAJAXG` without importing `assets/src/js/loadAJAX.js`.

## Acceptance Criteria
- [x] Both architecture documents list all four directories and do not list `inc/Rest/`.
- [x] Directory descriptions accurately distinguish AJAX request handling, query filter objects, hook-based template composition, and reusable helper utilities.
- [x] No application code or unrelated documentation is changed.
- [x] Markdown remains valid and easy to scan.
- [x] Both documents contain matching JavaScript source, utility reuse, Toastify, and AJAX-global guidance.

## Scope
- `CLAUDE.md`
- `.claude/rules/architecture.md`
- `inc/Ajax/`
- `inc/Filters/`
- `inc/TemplateHooks/`
- `inc/Helpers/`
- `assets/src/js/`

## Out of scope
- Refactoring or changing PHP implementation files.
- Adding new architectural layers or coding conventions unrelated to the four directories.
- Updating generated documentation or external project documentation.

## References
- `inc/Ajax/AbstractAjax.php`
- `inc/Filters/FilterBase.php`
- `inc/TemplateHooks/TemplateAJAX.php`
- `inc/Helpers/Singleton.php`
- `assets/src/js/utils.js`
- `assets/src/js/lpToastify.js`
- `assets/src/js/loadAJAX.js`
