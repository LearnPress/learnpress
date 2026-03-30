# Plan — refactor-material-db

## Steps

- [x] Step 1: Tạo `inc/Databases/MaterialFilesDB.php` — PSR-4 class mới
  Copy toàn bộ methods từ `LP_Material_Files_DB`, đổi:
  - Thêm `namespace LearnPress\Databases;`
  - `class MaterialFilesDB extends DataBase`
  - Xóa guard `if ( class_exists(...) ) return;` (không cần với autoload)
  - Xóa dòng `LP_Material_Files_DB::getInstance();` ở cuối (không cần tự khởi tạo)

- [x] Step 2: Cập nhật TemplateHooks
  - `inc/TemplateHooks/Course/CourseMaterialTemplate.php`: đổi `use LP_Material_Files_DB` → `use LearnPress\Databases\MaterialFilesDB`, đổi tất cả `LP_Material_Files_DB::getInstance()` → `MaterialFilesDB::getInstance()` (3 chỗ)
  - `inc/TemplateHooks/Course/SingleCourseTemplate.php`: tương tự (1 chỗ)

- [x] Step 3: Cập nhật REST controller
  - `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php`: thêm `use LearnPress\Databases\MaterialFilesDB;`, đổi 5 chỗ `LP_Material_Files_DB::getInstance()` → `MaterialFilesDB::getInstance()`

- [x] Step 4: Cập nhật `abstract-course.php` và meta-box
  - `inc/course/abstract-course.php`: thêm `use LearnPress\Databases\MaterialFilesDB;`, đổi 1 chỗ
  - `inc/admin/views/meta-boxes/fields/materials.php`: thêm `use LearnPress\Databases\MaterialFilesDB;`, đổi 2 chỗ

- [x] Step 5: Kiểm tra — `composer lint` ✅ (MaterialFilesDB.php pass sạch)

## Files to create
| File | Purpose |
|------|---------|
| `inc/Databases/MaterialFilesDB.php` | PSR-4 class mới, tương đương `LP_Material_Files_DB` |

## Files to modify
| File | Change |
|------|--------|
| `inc/TemplateHooks/Course/CourseMaterialTemplate.php` | Dùng `MaterialFilesDB` (3 chỗ) |
| `inc/TemplateHooks/Course/SingleCourseTemplate.php` | Dùng `MaterialFilesDB` (1 chỗ) |
| `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php` | Dùng `MaterialFilesDB` (5 chỗ) |
| `inc/course/abstract-course.php` | Dùng `MaterialFilesDB` (1 chỗ) |
| `inc/admin/views/meta-boxes/fields/materials.php` | Dùng `MaterialFilesDB` (2 chỗ) |

## Open questions
- `materials.php` là view file thuần PHP, không có namespace — `use` statement ở global scope vẫn hoạt động bình thường trong PHP.