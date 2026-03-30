# Plan — material-files-filter

## Steps

- [x] Step 1: Tạo `inc/Filters/MaterialFilesFilter.php` — dùng `/new-filter`
  - `namespace LearnPress\Filters;`
  - `class MaterialFilesFilter extends FilterBase`
  - 9 `const COL_*`:
    ```
    COL_FILE_ID    = 'file_id'
    COL_FILE_NAME  = 'file_name'
    COL_FILE_TYPE  = 'file_type'
    COL_ITEM_ID    = 'item_id'
    COL_ITEM_TYPE  = 'item_type'
    COL_METHOD     = 'method'
    COL_FILE_PATH  = 'file_path'
    COL_ORDERS     = 'orders'
    COL_CREATED_AT = 'created_at'
    ```
  - `public array $all_fields` = array of all 9 `self::COL_*`
  - Typed properties cho WHERE: `public int $file_id`, `public int $item_id`, `public string $item_type`, `public string $method`

- [x] Step 2: Thêm method `get_files` vào `inc/Databases/MaterialFilesDB.php`
  - Thêm `use LearnPress\Filters\MaterialFilesFilter;` vào phần đầu file (sau `namespace`)
  - Method signature:
    ```php
    public function get_files( MaterialFilesFilter $filter, int &$total_rows = 0 )
    ```
  - Body:
    1. Merge `$filter->all_fields` vào `$filter->fields` (giống `CourseJsonDB::get_courses`)
    2. Set `$filter->collection = $this->table_name` nếu rỗng
    3. Set `$filter->collection_alias = 'f'` nếu rỗng
    4. `$ca = $filter->collection_alias`
    5. WHERE từ properties (dùng `isset()` để không bắt buộc set):
       - `isset($filter->file_id)`  → `AND $ca.file_id = %d`
       - `isset($filter->item_id)`  → `AND $ca.item_id = %d`
       - `isset($filter->item_type)` → `AND $ca.item_type = %s`
       - `isset($filter->method)`   → `AND $ca.method = %s`
    6. `return $this->execute($filter, $total_rows)`
  - `DataBase::execute()` nhận `$filter` dạng mixed (không type-hint) — truyền thẳng được

- [x] Step 3: `composer lint` — fix nếu có lỗi autofix, confirm pass sạch

## Files to create
| File | Purpose |
|------|---------|
| `inc/Filters/MaterialFilesFilter.php` | Filter class cho bảng `learnpress_files`, extends `FilterBase` |

## Files to modify
| File | Change |
|------|--------|
| `inc/Databases/MaterialFilesDB.php` | Thêm `use MaterialFilesFilter` + method `get_files` |

## Open questions
- Không còn open question — `DataBase::execute()` dùng `$filter` mixed, không cần casting.