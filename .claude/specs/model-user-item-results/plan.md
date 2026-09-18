# Plan — model-user-item-results

## Steps

- [x] Step 1: Create `UserItemResultsFilter` matching the `tb_lp_user_item_results` schema.
- [x] Step 2: Create `UserItemResultsDB` singleton extending `DataBase`, with `get_user_item_results()` and CRUD helpers.
- [x] Step 3: Create `UserItemResultModel` with full table field mapping, constructor, getters, `save()`, `delete()`, and static find/get helpers.
- [x] Step 4: Run PHPCS on the three new files and fix any violations.

## Step details

### Step 1: UserItemResultsFilter

Create `inc/Filters/UserItemResultsFilter.php`:

- Extend `LearnPress\Filters\FilterBase`.
- Define constants for every column: `COL_ID`, `COL_USER_ITEM_ID`, `COL_USER_ID`, `COL_GUEST_KEY`, `COL_ITEM_ID`, `COL_ITEM_TYPE`, `COL_STATUS`, `COL_GRADUATION`, `COL_REF_ID`, `COL_REF_TYPE`, `COL_PARENT_ID`, `COL_RESULT`, `COL_EXTRA_DATA`.
- Set `public array $all_fields` to the list of all column names.
- Add single-value public properties: `id`, `user_item_id`, `user_id`, `guest_key`, `item_id`, `item_type`, `status`, `graduation`, `ref_id`, `ref_type`, `parent_id`, `result`, `extra_data`.
- Add array public properties for batch queries: `user_item_ids`, `user_ids`, `item_ids`.
- Set `public $field_count = self::COL_ID;`.

### Step 2: UserItemResultsDB

Create `inc/Databases/UserItemResultsDB.php`:

- Extend `LearnPress\Databases\DataBase` and implement singleton `getInstance()`.
- Add `get_user_item_results( UserItemResultsFilter $filter, int &$total_rows = 0 )` that:
  - Merges `all_fields` into `fields`.
  - Defaults `collection` to `$this->tb_lp_user_item_results` and alias to `uir`.
  - Builds `WHERE` clauses for `id`, `user_item_id`, `user_item_ids`, `user_id`, `guest_key`, `item_id`, `item_ids`, `item_type`, `status`, `graduation`, `ref_id`, `ref_type`, and `parent_id`.
  - Applies filter `lp/user_item_results/query/filter`.
  - Calls `$this->execute( $filter, $total_rows )`.
- Add `insert_data( array $data ): int` that validates against `UserItemResultsFilter->all_fields`, removes the auto-increment `id`, and inserts into `tb_lp_user_item_results`.
- Add `update_data( array $data ): bool` that requires `id`, validates against `all_fields`, builds SET/WHERE, and uses `update_execute()`.

### Step 3: UserItemResultModel

Create `inc/Models/UserItemResults/UserItemResultModel.php`:

- Namespace `LearnPress\Models\UserItemResults`.
- Define public properties matching the table columns.
- Keep `private $id = 0` and add a private setter for `id`.
- Implement `__construct( $data = null )` that calls `map_to_object()` if data is provided.
- Implement `map_to_object()`.
- Add getter methods: `get_id()`, `get_user_item_id()`, `get_user_id()`, `get_guest_key()`, `get_item_id()`, `get_item_type()`, `get_status()`, `get_graduation()`, `get_ref_id()`, `get_ref_type()`, `get_parent_id()`, `get_result()` (decoded JSON if not null), `get_extra_data()` (decoded JSON if not null).
- Add static `find( int $id )` that returns a `UserItemResultModel|false` using `UserItemResultsDB::getInstance()` + `get_query_single_row()`.
- Add static `get_user_item_result_model_from_db( UserItemResultsFilter $filter )` that fetches a single row and returns a model instance.
- Add static `find_by_user_item_id( int $user_item_id, bool $check_cache = false )` convenience helper.
- Implement `save(): UserItemResultModel`:
  - Validate required fields (`user_item_id`, `user_id` or `guest_key`, `item_id`, `item_type`).
  - If `id` is empty, insert via `UserItemResultsDB::insert_data( $data )`.
  - Otherwise update via `UserItemResultsDB::update_data( $data )`.
  - Clear caches if any.
- Implement `delete()` using `delete_execute()` with `id` or `user_item_id` filter.
- Add `clean_caches()` (initially empty or clearing simple static cache keys).

### Step 4: Code style verification

Run:

```bash
php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml \
  inc/Models/UserItemResults/UserItemResultModel.php \
  inc/Filters/UserItemResultsFilter.php \
  inc/Databases/UserItemResultsDB.php
```

Fix any reported issues until the command reports no new errors.

## Files to create

| File | Purpose |
|------|---------|
| `inc/Filters/UserItemResultsFilter.php` | Filter object for `tb_lp_user_item_results` |
| `inc/Databases/UserItemResultsDB.php` | Database access singleton for the table |
| `inc/Models/UserItemResults/UserItemResultModel.php` | Model mapping rows from the table |

## Files to modify

| File | Change |
|------|--------|
| None | This spec only adds new files |

## Open questions

- No custom cache helper for the first version; add later if performance becomes a concern.
