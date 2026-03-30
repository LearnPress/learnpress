# Progress — material-file-service

**Status:** ✅ Done
**Started:** 2026-03-27
**Last updated:** 2026-03-27

## Done
- Step 1: Tạo `inc/Services/MaterialFileService.php` — Singleton, `get_files()`, `create_file()`
- Step 2: `composer lint` — pass sạch

## In progress

## Next

## Decisions made
- `Singleton` trait bắt buộc implement `init()` — để trống, dùng cho hook registration sau
- `get_files` trả về `array of MaterialFilesModel` (không phải raw stdClass)
- `create_file` dùng `force_save=true` — permission check do caller (AJAX/REST) tự xử lý
- `create_file` tự set `created_at = gmdate(...)` nếu không truyền

## Blockers / Notes