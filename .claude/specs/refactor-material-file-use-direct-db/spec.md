# refactor-material-file-use-direct-db

> Thay thế toàn bộ các lời gọi trực tiếp đến `MaterialFilesDB` bằng `MaterialFileService` và `MaterialFilesModel`

## Goal
Loại bỏ các lời gọi trực tiếp tới `MaterialFilesDB::create_material`, `get_material_by_item_id`, `get_total`, `get_material`, `delete_material`, trong caller code.
Thay thế bằng Service + Model layer đã xây dựng.
Bỏ qua file `class-lp-material-db.php` (legacy) và `MaterialFilesDB.php` (giữ nguyên các method cũ để backward compat với add-ons ngoài).

## Requirements
- [ ] `create_material(...)` → `MaterialFileService::instance()->create_file($data)`
- [ ] `get_material_by_item_id(...)` → `MaterialFileService::instance()->get_files($filter)`
- [ ] `get_total(...)` → `MaterialFileService::instance()->get_files($filter, $total_rows)` với `$filter->query_count = true`
- [ ] `get_material($file_id)` → `MaterialFilesModel::find($file_id)`
- [ ] `delete_material($file_id)` → `MaterialFilesModel::find($file_id)->delete()`

## Acceptance Criteria
- [ ] Không còn lời gọi `->create_material(`, `->get_material_by_item_id(`, `->get_total(`, `->get_material(`, `->delete_material(`,  trong các file caller
- [ ] `composer lint` pass sạch trên tất cả file đã sửa

## Scope
Các file caller cần sửa:

| File | Methods đang dùng |
|------|-------------------|
| `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php` | `create_material` (L233), `get_material_by_item_id` (L133, L289, L509), `get_total` (L507), `get_material` (L413), `delete_material` (L428), `update_material_orders` (L380) |
| `inc/TemplateHooks/Course/CourseMaterialTemplate.php` | `get_total` (L68), `get_material_by_item_id` (L143) |
| `inc/course/abstract-course.php` | `get_material_by_item_id` (L1471) |
| `inc/admin/views/meta-boxes/fields/materials.php` | `get_material_by_item_id` (L38) |

## Out of scope
- `class-lp-material-db.php` (legacy file) — giữ nguyên backward compat
- `MaterialFilesDB.php` — giữ nguyên các method cũ (backward compat với add-ons ngoài)

## Complications / Notes
1. **`get_material_by_item_id` cho course** có logic đặc biệt: khi `item_id` là `LP_COURSE_CPT`, nó JOIN `lp_section_items` + `lp_sections` để lấy cả file của lessons trong course. `MaterialFilesDB::get_files` hiện tại **chưa có** logic này → cần bổ sung vào `MaterialFilesDB::get_files` hoặc `MaterialFileService::get_files` trước khi thay thế.
2. **`update_material_orders`** là bulk UPDATE nhiều rows cùng lúc (1 SQL) → thay bằng loop `MaterialFilesModel->save()` sẽ kém hiệu quả hơn. Cần cân nhắc: giữ `update_material_orders` trong DB hoặc thêm method `update_orders` vào Service.

## References
- `inc/Services/MaterialFileService.php`
- `inc/Models/MaterialFilesModel.php`
- `inc/Filters/MaterialFilesFilter.php`
- `inc/Databases/MaterialFilesDB.php`
