# Plan — move-js-tools

## Steps
- [x] Step 1: Create `assets/src/js/admin/tools/handle-sample-data.js` by copying content from `assets/src/apps/js/admin/pages/tools/handle-sample-data.js`. No import changes needed.
- [x] Step 2: Create `assets/src/js/admin/tools/reset-course-progress.js` by copying content from `assets/src/apps/js/admin/pages/tools/reset-course-progress.js`. No import changes needed.
- [x] Step 3: Create `assets/src/js/admin/tools/reset-item-progress.js` by copying content from `assets/src/apps/js/admin/pages/tools/reset-item-progress.js`. No import changes needed.
- [x] Step 4: Update `assets/src/apps/js/admin/pages/tools.js` to remove imports and init calls for `ResetCourseProgress`, `ResetItemProgress`, and `HandleSampleData`.
- [x] Step 5: Update `assets/src/js/admin/admin-tools.js` to import and initialize the three moved modules.
- [x] Step 6: Delete the three old source files under `assets/src/apps/js/admin/pages/tools/`.
- [x] Step 7: Run `npm run build` and fix any JS/ESLint errors.

## Files to create
| File | Purpose |
|------|---------|
| `assets/src/js/admin/tools/handle-sample-data.js` | Sample data install/uninstall logic (moved) |
| `assets/src/js/admin/tools/reset-course-progress.js` | Reset course progress tool (moved) |
| `assets/src/js/admin/tools/reset-item-progress.js` | Reset item progress tool (moved) |

## Files to modify
| File | Change |
|------|--------|
| `assets/src/apps/js/admin/pages/tools.js` | Remove imports and init calls for the three modules |
| `assets/src/js/admin/admin-tools.js` | Add imports and init calls for the three modules |

## Files to delete
| File |
|------|
| `assets/src/apps/js/admin/pages/tools/handle-sample-data.js` |
| `assets/src/apps/js/admin/pages/tools/reset-course-progress.js` |
| `assets/src/apps/js/admin/pages/tools/reset-item-progress.js` |

## Open questions
-
