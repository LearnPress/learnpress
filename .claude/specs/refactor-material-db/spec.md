# refactor-material-db

> Migrate LP_Material_Files_DB sang PSR-4 style

## Goal
`LP_Material_Files_DB` (trong `inc/Databases/class-lp-material-db.php`) dùng kiểu đặt tên cũ, không có namespace, kế thừa `LP_Database` (legacy base class). Mục tiêu là tạo class `MaterialFilesDB` mới trong namespace `LearnPress\Databases`, kế thừa `DataBase` (PSR-4 base class), được autoload qua Composer — đồng bộ với pattern của `UserItemsDB`, `CourseSectionDB`, `PostDB`, v.v.

File cũ `class-lp-material-db.php` **giữ nguyên toàn bộ code** để đảm bảo backward compat tuyệt đối với add-ons bên ngoài — không cần shim, không cần alias.

## Requirements
- [ ] Class mới `MaterialFilesDB` nằm trong `inc/Databases/MaterialFilesDB.php`, namespace `LearnPress\Databases`, extends `DataBase`
- [ ] Tất cả các method hiện có trong `LP_Material_Files_DB` được copy sang `MaterialFilesDB` (không thay đổi logic, không đổi signature)
- [ ] Tất cả callers nội bộ của plugin cập nhật sang dùng `MaterialFilesDB::getInstance()`
- [ ] File cũ `class-lp-material-db.php` **không bị sửa** — vẫn load bình thường qua `require_once` trong `learnpress.php`

## Acceptance Criteria
- [ ] `MaterialFilesDB::getInstance()` trả về đúng instance, mọi method hoạt động như cũ
- [ ] `LP_Material_Files_DB` vẫn tồn tại độc lập — không break add-ons bên ngoài
- [ ] Không có lỗi PHP sau refactor
- [ ] Các file sau đã dùng class mới: `CourseMaterialTemplate.php`, `SingleCourseTemplate.php`, `class-lp-rest-material-controller.php`, `abstract-course.php`, `materials.php` (meta-box)

## Scope
- `inc/Databases/MaterialFilesDB.php` — file mới (tạo mới)
- `inc/TemplateHooks/Course/CourseMaterialTemplate.php` — cập nhật caller
- `inc/TemplateHooks/Course/SingleCourseTemplate.php` — cập nhật caller
- `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php` — cập nhật caller
- `inc/course/abstract-course.php` — cập nhật caller
- `inc/admin/views/meta-boxes/fields/materials.php` — cập nhật caller

## Out of scope
- Không sửa `inc/Databases/class-lp-material-db.php`
- Không sửa `learnpress.php`
- Không thay đổi logic query SQL
- Không thay đổi tên hoặc signature của các method

## References
- Pattern tham khảo: `inc/Databases/UserItemsDB.php`
- Base class mới: `inc/Databases/DataBase.php`
- File nguồn để copy methods: `inc/Databases/class-lp-material-db.php`