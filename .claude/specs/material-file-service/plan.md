# Plan — material-file-service

## Steps

- [ ] Step 1: Tạo `inc/Services/MaterialFileService.php`

  **Full structure:**
  ```php
  namespace LearnPress\Services;

  use Exception;
  use LearnPress\Databases\MaterialFilesDB;
  use LearnPress\Filters\MaterialFilesFilter;
  use LearnPress\Helpers\Singleton;
  use LearnPress\Models\MaterialFilesModel;

  class MaterialFileService {
      use Singleton;           // bắt buộc có init()
      public function init() {} // hook registration nếu cần sau này
  }
  ```

  **`get_files(MaterialFilesFilter $filter, int &$total_rows = 0): array`**
  - Gọi `MaterialFilesDB::getInstance()->get_files($filter, $total_rows)`
  - Map mỗi row → `new MaterialFilesModel($row)`
  - Trả về array (rỗng nếu không có kết quả)

  **`create_file(array $data): MaterialFilesModel`**
  - Validate: `empty($data['item_id'])` → throw `Exception('item_id is required!')`
  - Set default: `$data['created_at'] = gmdate('Y-m-d H:i:s')` nếu chưa có
  - `$model = new MaterialFilesModel($data)`
  - `$model->save(true)` — `force_save=true`, caller tự xử lý permission
  - Return `$model` (đã có `file_id` sau insert)

  **`$data` shape** (document bằng comment trong code):
  ```php
  // [
  //   'file_name'  => string   // tên file hiển thị
  //   'file_type'  => string   // extension: pdf, doc, mp4...
  //   'item_id'    => int      // (bắt buộc) ID của course hoặc lesson
  //   'item_type'  => string   // lp_course | lp_lesson
  //   'method'     => string   // upload | external
  //   'file_path'  => string   // relative path (upload) hoặc full URL (external)
  //   'orders'     => int      // thứ tự hiển thị, mặc định 0
  //   'created_at' => string   // Y-m-d H:i:s, tự set nếu bỏ trống
  // ]
  ```

- [ ] Step 2: `composer lint` — fix nếu có lỗi

## Files to create
| File | Purpose |
|------|---------|
| `inc/Services/MaterialFileService.php` | Service orchestration cho material files |

## Files to modify
| File | Change |
|------|--------|
| — | Không có |

## Open questions
- Không còn open question.