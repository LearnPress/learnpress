# Progress — refactor-material-db

**Status:** ✅ Done
**Started:** 2026-03-27
**Last updated:** 2026-04-02

## Done
1. ✅ **Step 1:** Created `inc/Databases/MaterialFilesDB.php` — PSR-4 class with all methods from `LP_Material_Files_DB`
2. ✅ **Step 2:** Updated `CourseMaterialTemplate.php` — replaced use statement + 3 class references
3. ✅ **Step 3:** Updated `SingleCourseTemplate.php` — replaced use statement + 1 class reference
4. ✅ **Step 4:** Updated `class-lp-rest-material-controller.php` — replaced use statement + 5 class references
5. ✅ **Step 5:** Updated `materials.php` (meta-box) — replaced use statement + 2 class references
6. ✅ **Step 6:** Verified `abstract-course.php` — no changes needed (deprecated method already commented out)
7. ✅ **Step 7:** Ran tests — phpcs passed (1 minor EOF newline fix applied), PHPUnit passed (154 tests, 225 assertions)

## In progress
- None

## Next
- None — feature complete

## Decisions made
- **Không dùng shim / class_alias** — giữ nguyên `class-lp-material-db.php` để backward compat tuyệt đối. Add-ons ngoài dùng `LP_Material_Files_DB` vẫn chạy bình thường.
- **Không sửa `learnpress.php`** — file cũ vẫn được load như hiện tại.
- **`abstract-course.php` KHÔNG CẦN SỬA** — method `get_downloadable_material()` đã deprecated và comment out.
- Hai class (`LP_Material_Files_DB` và `MaterialFilesDB`) tồn tại song song — code nội bộ dùng class mới, add-ons ngoài vẫn dùng class cũ.
- **EOF newline fix** — `MaterialFilesDB.php` was missing trailing newline, fixed to pass phpcs.

## Blockers / Notes
- None
