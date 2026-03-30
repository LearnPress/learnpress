# Progress — refactor-material-db

**Status:** ✅ Done
**Started:** 2026-03-27
**Last updated:** 2026-03-27

## Done

- Step 1: Tạo `inc/Databases/MaterialFilesDB.php` (PSR-4, `namespace LearnPress\Databases`, extends `DataBase`)
- Step 2: Cập nhật `CourseMaterialTemplate.php` (3 chỗ) và `SingleCourseTemplate.php` (1 chỗ)
- Step 3: Cập nhật `class-lp-rest-material-controller.php` (5 chỗ)
- Step 4: Cập nhật `abstract-course.php` (1 chỗ) và `materials.php` (2 chỗ)
- Step 5: `composer lint` — `MaterialFilesDB.php` pass sạch

## In progress

## Next

## Decisions made

- **Không dùng shim / class_alias** — giữ nguyên `class-lp-material-db.php` để backward compat tuyệt đối. Add-ons ngoài dùng `LP_Material_Files_DB` vẫn chạy bình thường.
- **Không sửa `learnpress.php`** — file cũ vẫn được load như hiện tại.
- Hai class (`LP_Material_Files_DB` và `MaterialFilesDB`) tồn tại song song — code nội bộ dùng class mới, add-ons ngoài vẫn dùng class cũ.

## Blockers / Notes