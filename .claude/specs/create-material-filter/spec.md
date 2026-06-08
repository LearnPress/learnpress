# create-material-filter

> Tạo MaterialFilter class (PSR-4) ánh xạ toàn bộ columns từ bảng learnpress_files

## Goal
Hiện tại `MaterialFilesDB` thực hiện raw SQL queries trực tiếp mà không có Filter class tương ứng. Mục tiêu là tạo `MaterialFilter` trong namespace `LearnPress\Filters`, kế thừa `FilterBase`, chứa toàn bộ const ánh xạ columns từ bảng `learnpress_files` — đồng bộ với pattern của `CourseJsonFilter`.

## Requirements
- [ ] Class `MaterialFilter` nằm trong `inc/Filters/MaterialFilter.php`, namespace `LearnPress\Filters`, extends `FilterBase`
- [ ] Định nghĩa const cho tất cả 9 columns của bảng `learnpress_files`: `file_id`, `file_name`, `file_type`, `item_id`, `item_type`, `method`, `file_path`, `orders`, `created_at`
- [ ] Khai báo `$all_fields` array chứa tất cả const
- [ ] Khai báo typed public properties cho các column thường dùng trong query (file_id, file_name, item_id, item_type, method)
- [ ] Tuân thủ coding standards (phpcs)

## Acceptance Criteria
- [ ] `MaterialFilter` class load được qua PSR-4 autoload
- [ ] Tất cả 9 columns có const tương ứng
- [ ] `$all_fields` chứa đầy đủ 9 fields
- [ ] phpcs pass
- [ ] Không break code hiện tại (class mới, chưa có caller)

## Scope
- `inc/Filters/MaterialFilter.php` — file mới (tạo mới)

## Out of scope
- Không sửa `MaterialFilesDB` để dùng Filter (sẽ là feature riêng)
- Không sửa bất kỳ file nào khác
- Không thay đổi logic query SQL hiện tại

## References
- Pattern tham khảo: `inc/Filters/Course/CourseJsonFilter.php`
- Base class: `inc/Filters/FilterBase.php`
- Table definition: `config/table/tables-v4.php` → `$lp_db->tb_lp_files`
