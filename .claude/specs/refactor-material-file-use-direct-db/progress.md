# Progress — refactor-material-file-use-direct-db

**Status:** ✅ Done
**Started:** 2026-03-27
**Last updated:** 2026-03-27

## Done
- Step 1: Bổ sung `$include_course_items` vào `MaterialFilesFilter`
- Step 2: Bổ sung logic subquery `include_course_items` vào `MaterialFilesDB::get_files`
- Step 3: Bổ sung xóa file vật lý vào `MaterialFilesModel::delete()`
- Step 4: Refactor `class-lp-rest-material-controller.php` (L133, L233, L289, L413, L428)
- Step 5: Refactor `CourseMaterialTemplate.php` (L68, L133, L143)
- Step 6: `abstract-course.php` L1471 → bỏ qua (deprecated block)
- Step 7: Refactor `materials.php` meta-box (L38)
- Step 8: Lint check — chỉ có lỗi pre-existing trong `materials.php`, không có lỗi mới

## In progress

## Next

## Decisions made
- L507/L509 trong REST controller nằm trong block deprecated/commented → bỏ qua
- `delete()` trong model: thêm `$force_delete` param — REST controller truyền `true` để bypass `check_permission()` vì đã tự check
- `MaterialFilesModel` có properties public → caller dùng `$m->file_name` vẫn hoạt động sau khi thay từ stdClass
- `update_material_orders` (L380) giữ nguyên, không refactor
- L1471 trong `abstract-course.php` nằm trong commented-out deprecated block → bỏ qua

## Blockers / Notes
- Lint errors còn lại trong `materials.php` (lines 81, 87, 107, 122, 156-159) là pre-existing I18n violations trong commented-out HTML template — không liên quan đến refactor này