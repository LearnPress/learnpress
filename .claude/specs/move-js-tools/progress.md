# Progress — move-js-tools

**Status:** ✅ Done
**Started:** 2026-09-11
**Last updated:** 2026-09-11

## Done
1. Created `assets/src/js/admin/tools/handle-sample-data.js` from old apps path.
2. Created `assets/src/js/admin/tools/reset-course-progress.js` from old apps path.
3. Created `assets/src/js/admin/tools/reset-item-progress.js` from old apps path.
4. Updated `assets/src/apps/js/admin/pages/tools.js` to remove imports and init calls for the moved modules.
5. Updated `assets/src/js/admin/admin-tools.js` to import and initialize the moved modules.
6. Deleted the old files under `assets/src/apps/js/admin/pages/tools/`.
7. Ran `npm run build` successfully (3 bundle-size warnings only).

## In progress

## Next

## Decisions made
- Kept original module contents unchanged; `lpAssetsJsPath` alias works from the new location.
- No PHP/webpack asset key changes needed because `admin-tools.js` is already the configured entry point.

## Blockers / Notes
