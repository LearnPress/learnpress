# material-file-service

> Tạo `MaterialFileService` — Service layer cho material files, orchestrate giữa `MaterialFilesModel`, `MaterialFilesFilter`, `MaterialFilesDB`

## Goal
Tách collection logic và orchestration ra khỏi Model/DB layer, theo đúng pattern `inc/Services/CourseService.php`.
`MaterialFilesModel` chỉ đại diện 1 object — `MaterialFileService` xử lý các tác vụ phức tạp hơn: lấy danh sách, tạo mới với validation.

## Requirements
- [ ] Tạo `inc/Services/MaterialFileService.php`, `namespace LearnPress\Services`, dùng `Singleton` trait
- [ ] Method `get_files(MaterialFilesFilter $filter, int &$total_rows = 0): array` — gọi `MaterialFilesDB::get_files()`, trả về array of `MaterialFilesModel`
- [ ] Method `create_file(array $data): MaterialFilesModel` — tạo `MaterialFilesModel` từ array `$data`, gọi `model->save()`, trả về model đã có `file_id`

## Acceptance Criteria
- [ ] `get_files($filter)` trả về `array` (rỗng hoặc có phần tử `MaterialFilesModel`)
- [ ] `get_files($filter, $total_rows)` gán đúng giá trị vào `$total_rows`
- [ ] `create_file($data)` với data hợp lệ → trả về `MaterialFilesModel` có `file_id > 0`
- [ ] `create_file($data)` thiếu `item_id` → throw `Exception`
- [ ] `composer lint` pass sạch

## Scope
- `inc/Services/MaterialFileService.php` (file mới)

## Out of scope
- Cập nhật caller (REST controller, Ajax handler) để dùng Service
- `delete_file`, `update_file` — có thể bổ sung sau
- Upload file vật lý — đó là việc của AJAX handler

## References
- `inc/Services/CourseService.php` — pattern: Singleton, init(), typed methods
- `inc/Models/MaterialFilesModel.php` — model để instantiate
- `inc/Filters/MaterialFilesFilter.php` — filter để truyền vào DB
- `inc/Databases/MaterialFilesDB.php` — DB class có `get_files()`
- `inc/Helpers/Singleton.php` — trait Singleton