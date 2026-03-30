# Plan — refactor-material-file-use-direct-db

## Steps

- [x] Step 1: Bổ sung `$include_course_items` vào `MaterialFilesFilter`
  - Thêm property: `public bool $include_course_items = false;`
  - Khi `true`: `get_files` sẽ thêm subquery JOIN sections để lấy cả file của lessons trong course

- [x] Step 2: Bổ sung logic `include_course_items` vào `MaterialFilesDB::get_files`
  Sau khi build WHERE thông thường, nếu `$filter->include_course_items && isset($filter->item_id)`:
  ```php
  // Thay WHERE item_id = X bằng:
  // WHERE (item_id IN (subquery lessons của course) OR item_id = course_id)
  $filter->where[] = $this->wpdb->prepare(
      "AND ($ca.item_id IN (
          SELECT si.item_id FROM $this->tb_lp_section_items AS si
          INNER JOIN $this->tb_lp_sections AS s ON s.section_id = si.section_id
          WHERE s.section_course_id = %d
      ) OR $ca.item_id = %d)",
      $filter->item_id,
      $filter->item_id
  );
  // Đồng thời KHÔNG thêm WHERE item_id = X thông thường nữa
  ```
  → Khi `include_course_items=false` (default): chỉ WHERE `item_id = X` thông thường

- [x] Step 3: Bổ sung xóa file vật lý vào `MaterialFilesModel::delete()`
  Trước khi gọi `delete_execute`, kiểm tra method là `upload` → xóa file vật lý:
  ```php
  if ( $this->method === 'upload' && ! empty( $this->file_path ) ) {
      $file_init = LP_WP_Filesystem::instance();
      $full_path = wp_upload_dir()['basedir'] . $this->file_path;
      if ( $file_init->file_exists( $full_path ) ) {
          $file_init->unlink( $full_path );
      }
  }
  ```

- [x] Step 4: Refactor `class-lp-rest-material-controller.php`
  - **L133** `get_material_by_item_id($item_id, 0, 0, 1)` (admin, no subquery) → filter + `MaterialFileService::instance()->get_files()`
  - **L233** `create_material($insert_arr)` → `MaterialFileService::instance()->create_file()`
  - **L289** `get_material_by_item_id($item_id, $per_page, $offset, true)` (admin) → filter + `get_files()`
  - **L413** `get_material($file_id)` + **L428** `delete_material($file_id)` → `MaterialFilesModel::find()` + `->delete(true)`

- [x] Step 5: Refactor `CourseMaterialTemplate.php`
  - **L68** `get_total($item_id)` → filter + `query_count=true` + `include_course_items`
  - **L133** `get_total($courseModel->get_id())` → filter + count query
  - **L143** `get_material_by_item_id(...)` → filter + `get_files()`

- [x] Step 6: Refactor `abstract-course.php`
  - **L1471** inside `@deprecated` commented-out block → **bỏ qua, không sửa**

- [x] Step 7: Refactor `materials.php` (meta-box)
  - **L38** `$material_init->get_material_by_item_id($thepostid, 0, 0, 1)` → filter + `MaterialFileService::instance()->get_files()`

- [x] Step 8: `composer lint` trên tất cả file đã sửa
  - Kết quả: chỉ có lỗi pre-existing trong `materials.php` (I18n violations trong commented-out HTML)
  - Không có lỗi mới nào được đưa vào bởi refactor này

## Files to create
| File | Purpose |
|------|---------|
| — | Không có |

## Files to modify
| File | Change |
|------|--------|
| `inc/Filters/MaterialFilesFilter.php` | Thêm `$include_course_items` property |
| `inc/Databases/MaterialFilesDB.php` | Bổ sung logic `include_course_items` trong `get_files` |
| `inc/Models/MaterialFilesModel.php` | Thêm xóa file vật lý vào `delete()` |
| `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php` | Thay L133, L233, L289, L413, L428 |
| `inc/TemplateHooks/Course/CourseMaterialTemplate.php` | Thay L68, L133, L143 |
| `inc/course/abstract-course.php` | Thay L1471 (skipped — deprecated block) |
| `inc/admin/views/meta-boxes/fields/materials.php` | Thay L38 |

## Open questions
- L507/L509 trong REST controller nằm trong block `@deprecated` đã comment → **bỏ qua, không sửa**
- `delete()` trong model hiện tại **không** gọi `check_permission()` → REST controller tự check permission trước khi gọi → **an toàn**
- `CourseMaterialTemplate::material_item()` nhận `$material` là `stdClass` (từ DB) — sau refactor nhận `MaterialFilesModel` → cần kiểm tra có dùng `$m->file_name` (property) hay `$m->get_file_name()` (getter). Property public nên vẫn hoạt động được.