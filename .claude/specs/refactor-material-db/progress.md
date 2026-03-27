# Progress — refactor-material-db

**Status:** 🟡 In progress
**Started:** 2026-03-27
**Last updated:** 2026-03-27

## Done

## In progress

## Next
- Step 1: Tạo `inc/Databases/MaterialFilesDB.php` (PSR-4 class mới)
- Step 2: Cập nhật TemplateHooks (`CourseMaterialTemplate`, `SingleCourseTemplate`)
- Step 3: Cập nhật REST controller, `abstract-course.php`, meta-box view

## Decisions made
- **Không dùng shim / class_alias** — giữ nguyên `class-lp-material-db.php` để backward compat tuyệt đối. Add-ons ngoài dùng `LP_Material_Files_DB` vẫn chạy bình thường.
- **Không sửa `learnpress.php`** — file cũ vẫn được load như hiện tại.
- Hai class (`LP_Material_Files_DB` và `MaterialFilesDB`) tồn tại song song — code nội bộ dùng class mới, add-ons ngoài vẫn dùng class cũ.

## Blockers / Notes