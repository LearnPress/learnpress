# move-js-tools

> Move tool-related JS modules from apps source to admin source and wire them into admin-tools.js.

## Goal

Centralize admin tool scripts by relocating shared progress/sample-data helpers from `assets/src/apps/js/admin/pages/tools/` to `assets/src/js/admin/tools/`, and make `admin-tools.js` the single entry point for these features.

## Requirements
- [ ] Move `handle-sample-data.js`, `reset-course-progress.js`, and `reset-item-progress.js` to `assets/src/js/admin/tools/`.
- [ ] Remove imports and initialization calls for these modules from `assets/src/apps/js/admin/pages/tools.js`.
- [ ] Import and initialize these modules in `assets/src/js/admin/admin-tools.js`.
- [ ] Keep existing webpack/PHP references unchanged; `admin-tools.js` is already built as `assets/dist/js/admin/admin-tools`.

## Acceptance Criteria
- [ ] The three JS modules live only under `assets/src/js/admin/tools/`.
- [ ] `assets/src/apps/js/admin/pages/tools.js` no longer imports them.
- [ ] `assets/src/js/admin/admin-tools.js` imports and initializes them on the admin tools page.
- [ ] `npm run build` completes without errors.
- [ ] Admin Tools page features (reset course/item progress, sample data) still function.

## Scope
- `assets/src/apps/js/admin/pages/tools.js`
- `assets/src/js/admin/admin-tools.js`
- `assets/src/js/admin/tools/handle-sample-data.js` (new)
- `assets/src/js/admin/tools/reset-course-progress.js` (new)
- `assets/src/js/admin/tools/reset-item-progress.js` (new)
- `assets/src/apps/js/admin/pages/tools/handle-sample-data.js` (remove)
- `assets/src/apps/js/admin/pages/tools/reset-course-progress.js` (remove)
- `assets/src/apps/js/admin/pages/tools/reset-item-progress.js` (remove)

## Out of scope
- Webpack output path changes (already handled in previous refactor).
- PHP asset class changes (no new asset keys required).

## References
- `assets/src/apps/js/admin/pages/tools.js`
- `assets/src/js/admin/admin-tools.js`
