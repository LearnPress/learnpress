# Progress — material-files-filter

**Status:** ✅ Done
**Started:** 2026-03-27
**Last updated:** 2026-03-27

## Done
- Step 1: Tạo `inc/Filters/MaterialFilesFilter.php` — 9 `COL_*` const, `$all_fields`, typed properties, `$field_count`
- Step 2: Thêm `get_files(MaterialFilesFilter $filter, int &$total_rows = 0)` vào `MaterialFilesDB` — merge fields, set collection/alias `'f'`, WHERE cho `file_id`, `file_ids`, `item_id`, `item_ids`, `item_type`, `method`
- Step 3: `composer lint` — pass sạch cả 2 file

## In progress

## Next

## Decisions made
- `DataBase::execute()` nhận `$filter` mixed — không cần type-hint hay cast
- Collection alias = `'f'`
- Dùng `isset()` cho single-value properties, `!empty()` cho array properties

## Blockers / Notes