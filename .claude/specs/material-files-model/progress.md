# Progress — material-files-model

**Status:** ✅ Done
**Started:** 2026-03-27
**Last updated:** 2026-03-27

## Done
- Step 1: Tạo `inc/Models/MaterialFilesModel.php` — properties, constructor, map_to_object, 9 getters, find, get_item_model_from_db, save, delete, check_permission
- Step 2: `composer lint` — pass sạch

## In progress

## Next

## Decisions made
- Không làm cache layer (giữ đơn giản)
- `save()` dùng `insert_data` khi `file_id = 0`, `update_data` khi `file_id > 0`
- `check_permission()` dùng `current_user_can('edit_post', $item_id)` hoặc `manage_options`
- `get_query_single_row()` + `get_files()` → `wpdb->get_row()` cho `find()`

## Blockers / Notes