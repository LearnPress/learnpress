# material-files-model

> Tạo `MaterialFilesModel` — Model class cho bảng `learnpress_files`, theo pattern CourseModel + MaterialFilesFilter + MaterialFilesDB

## Goal
Áp dụng pattern Model–Filter–DB chuẩn của LearnPress cho bảng `learnpress_files`.
Hiện tại không có Model class cho material file → code gọi thẳng `MaterialFilesDB`, không có encapsulation, không có permission check.

## Requirements
- [ ] Tạo `inc/Models/MaterialFilesModel.php`, `namespace LearnPress\Models`
- [ ] Properties ánh xạ đến từng cột của bảng (dùng tên cột khớp với `MaterialFilesFilter::COL_*`): `$file_id`, `$file_name`, `$file_type`, `$item_id`, `$item_type`, `$method`, `$file_path`, `$orders`, `$created_at`
- [ ] `__construct($data = null)` — gọi `map_to_object($data)` nếu `$data` không null
- [ ] `map_to_object($data)` — loop qua key/value, gán vào property nếu `property_exists`
- [ ] Getter methods: `get_id()`, `get_file_name()`, `get_file_type()`, `get_item_id()`, `get_item_type()`, `get_method()`, `get_file_path()`, `get_orders()`, `get_created_at()`
- [ ] `static find(int $file_id)` — dùng `MaterialFilesFilter` + `MaterialFilesDB::get_files()` để lấy 1 row, trả về `MaterialFilesModel|false`
- [ ] `save(bool $force_save = false)` — gọi `check_permission()`, dùng `get_files()` kiểm tra tồn tại, rồi `insert_data` hoặc `update_data` qua `MaterialFilesDB`
- [ ] `delete()` — dùng `MaterialFilesDB::delete_execute()` với filter theo `file_id`
- [ ] `check_permission()` — kiểm tra current user có quyền `edit_post` trên `item_id` không, throw `Exception` nếu không

## Acceptance Criteria
- [ ] `new MaterialFilesModel($row)` với `$row` là stdClass từ DB → tất cả properties được gán đúng
- [ ] `MaterialFilesModel::find(1)` → trả về `MaterialFilesModel` hoặc `false`
- [ ] `save()` insert khi `file_id = 0`, update khi `file_id > 0`
- [ ] `delete()` xóa row theo `file_id`
- [ ] `check_permission()` throw khi user không có quyền
- [ ] `composer lint` pass sạch

## Scope
- `inc/Models/MaterialFilesModel.php` (file mới)

## Out of scope
- Cập nhật các caller (REST controller, TemplateHooks, v.v.) để dùng model mới
- Cache layer (CourseModel dùng LP_Course_Cache — MaterialFilesModel bỏ qua trước)

## References
- `inc/Models/CourseModel.php` — pattern chính: constructor, map_to_object, find, save, delete, check_permission
- `inc/Filters/MaterialFilesFilter.php` — Filter class đã tạo
- `inc/Databases/MaterialFilesDB.php` — DB class đã tạo (có `get_files`, `create_material`, `delete_material`)
- `inc/Databases/DataBase.php` — `insert_data`, `update_data`, `delete_execute` methods