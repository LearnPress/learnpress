# Plan — material-files-model

## Steps

- [ ] Step 1: Tạo `inc/Models/MaterialFilesModel.php` — dùng `/new-model`

  **Properties** (khớp tên cột bảng):
  ```php
  public int    $file_id    = 0;
  public string $file_name  = '';
  public string $file_type  = '';
  public int    $item_id    = 0;
  public string $item_type  = '';
  public string $method     = 'upload';
  public string $file_path  = '';
  public int    $orders     = 0;
  public ?string $created_at = null;
  ```

  **`__construct($data = null)`**:
  ```php
  public function __construct( $data = null ) {
      if ( $data ) {
          $this->map_to_object( $data );
      }
  }
  ```

  **`map_to_object($data)`** — loop foreach, gán nếu `property_exists`.

  **Getters** (9 methods):
  `get_id()→int`, `get_file_name()→string`, `get_file_type()→string`,
  `get_item_id()→int`, `get_item_type()→string`, `get_method()→string`,
  `get_file_path()→string`, `get_orders()→int`, `get_created_at()→?string`

  **`static get_item_model_from_db(MaterialFilesFilter $filter): self|false`**:
  ```php
  $db = MaterialFilesDB::getInstance();
  $db->get_query_single_row( $filter );   // limit=1, return_string_query=true
  $rs = $db->get_files( $filter );
  $row = $db->wpdb->get_row( $rs );
  if ( ! $row ) return false;
  return new static( $row );
  ```

  **`static find(int $file_id): self|false`**:
  ```php
  if ( ! $file_id ) return false;
  $filter          = new MaterialFilesFilter();
  $filter->file_id = $file_id;
  return self::get_item_model_from_db( $filter );
  ```

  **`save(bool $force_save = false): static`**:
  ```php
  if ( ! $force_save ) { $this->check_permission(); }
  $db     = MaterialFilesDB::getInstance();
  $filter = new MaterialFilesFilter();
  $args   = [
      'data'       => get_object_vars( $this ),
      'filter'     => $filter,
      'table_name' => $db->table_name,
  ];
  if ( empty( $this->file_id ) ) {
      // INSERT — key_auto_increment sẽ bị unset trước khi insert
      $args['key_auto_increment'] = MaterialFilesFilter::COL_FILE_ID;
      $this->file_id = $db->insert_data( $args );
  } else {
      // UPDATE
      $args['where_key'] = MaterialFilesFilter::COL_FILE_ID;
      $db->update_data( $args );
  }
  return $this;
  ```

  **`delete(): void`**:
  ```php
  $db                 = MaterialFilesDB::getInstance();
  $filter             = new MaterialFilesFilter();
  $filter->collection = $db->table_name;
  $filter->where[]    = $db->wpdb->prepare( 'AND file_id = %d', $this->file_id );
  $db->delete_execute( $filter );
  ```

  **`check_permission(): void`**:
  ```php
  if ( ! current_user_can( 'edit_post', $this->item_id )
      && ! current_user_can( 'manage_options' ) ) {
      throw new Exception( 'You do not have permission to manage this material file!' );
  }
  ```

- [ ] Step 2: `composer lint` — fix nếu có lỗi

## Files to create
| File | Purpose |
|------|---------|
| `inc/Models/MaterialFilesModel.php` | Model class cho bảng `learnpress_files` |

## Files to modify
| File | Change |
|------|--------|
| — | Không có |

## Open questions
- Không còn open question — signature `insert_data` / `update_data` / `delete_execute` đã xác nhận.