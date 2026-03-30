# material-files-filter

> Thêm `MaterialFilesFilter extends FilterBase` và method `get_files` vào `MaterialFilesDB`

## Goal
Áp dụng pattern Filter–DB chuẩn của LearnPress (như `CourseJsonFilter` + `CourseJsonDB`) cho bảng `learnpress_files`.
Hiện tại `MaterialFilesDB` dùng tham số rời, không có Filter class → khó mở rộng và không nhất quán với phần còn lại của codebase.

## Requirements
- [ ] Tạo `inc/Filters/MaterialFilesFilter.php` — class `MaterialFilesFilter extends FilterBase`, `namespace LearnPress\Filters`
- [ ] Class có `const` cho từng cột của bảng `learnpress_files`: `COL_FILE_ID`, `COL_FILE_NAME`, `COL_FILE_TYPE`, `COL_ITEM_ID`, `COL_ITEM_TYPE`, `COL_METHOD`, `COL_FILE_PATH`, `COL_ORDERS`, `COL_CREATED_AT`
- [ ] Class có property `$all_fields` là array tất cả các const trên (dùng `self::COL_*`)
- [ ] Class có các typed property tương ứng với các điều kiện lọc hay dùng: `$file_id`, `$item_id`, `$item_type`, `$method`
- [ ] Thêm method `get_files(MaterialFilesFilter $filter, int &$total_rows = 0)` vào `MaterialFilesDB`
- [ ] Method `get_files` thiết lập `$filter->collection`, `$filter->collection_alias`, xây dựng WHERE từ các property của filter, rồi gọi `$this->execute($filter, $total_rows)`

## Acceptance Criteria
- [ ] `MaterialFilesFilter` có đủ 9 `COL_*` const khớp với tên cột thực trong DB
- [ ] `$all_fields` list đủ tất cả const
- [ ] `get_files` hoạt động với filter rỗng (trả về tất cả), filter theo `item_id`, filter theo `item_type`, filter theo `method`
- [ ] `composer lint` pass sạch trên 2 file mới

## Scope
- `inc/Filters/MaterialFilesFilter.php` (file mới)
- `inc/Databases/MaterialFilesDB.php` (thêm method `get_files`)

## Out of scope
- Thay thế các method cũ (`get_material_by_item_id`, `get_total`, v.v.) — giữ nguyên backward compat
- Cập nhật caller code để dùng `get_files` thay vì method cũ

## References
- `inc/Filters/Course/CourseJsonFilter.php` — pattern Filter class tham khảo
- `inc/Databases/Course/CourseJsonDB.php` — pattern DB class tham khảo
- `inc/Filters/FilterBase.php` — base class
- `inc/Databases/MaterialFilesDB.php` — file sẽ được bổ sung method
- `inc/class-lp-install.php::create_table_learnpress_files()` — schema bảng