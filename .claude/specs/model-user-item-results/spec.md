# model-user-item-results

> Add model/filter/database classes for the new `tb_lp_user_item_results` table, following the existing `UserItemModel` pattern.

## Goal

Introduce a clean data-access layer for `tb_lp_user_item_results` so other features can read, create, update and delete user-item result rows through model objects instead of raw SQL.

## Requirements

- [ ] Create `LearnPress\Models\UserItemResults\UserItemResultModel` in `inc/Models/UserItemResults/UserItemResultModel.php`.
- [ ] Create `LearnPress\Filters\UserItemResultsFilter` in `inc/Filters/UserItemResultsFilter.php`.
- [ ] Create `LearnPress\Databases\UserItemResultsDB` in `inc/Databases/UserItemResultsDB.php`.
- [ ] All classes must map to the `tb_lp_user_item_results` table schema defined in `config/table/tables-v5.php`.
- [ ] Model API must mirror `UserItemModel` conventions: property mapping, constructor, getters, `save()`, `delete()`, and static find/get helpers.
- [ ] Filter must expose every table column as constants and public properties, plus array variants for batch queries.
- [ ] Database class must be a singleton extending `DataBase`, with a query method that accepts `UserItemResultsFilter` and builds `WHERE` clauses for supported filters.

## Acceptance Criteria

- [ ] New files follow LearnPress namespace, naming and PHPCS coding standards (`phpcs.xml`).
- [ ] `UserItemResultModel` maps all columns from `tb_lp_user_item_results` (`id`, `user_item_id`, `user_id`, `guest_key`, `item_id`, `item_type`, `status`, `graduation`, `ref_id`, `ref_type`, `parent_id`, `result`, `extra_data`).
- [ ] `UserItemResultsFilter` defines column constants and includes `all_fields`, `field_count`, plus single/array properties matching the table.
- [ ] `UserItemResultsDB::get_user_item_results()` supports querying by `user_item_id`, `user_item_ids`, `user_id`, `guest_key`, `item_id`, `item_ids`, `item_type`, `status`, `graduation`, `ref_id`, `ref_type`, and `parent_id`.
- [ ] `phpcs` runs on the three new files without generating new errors.

## Scope

- `inc/Models/UserItemResults/UserItemResultModel.php`
- `inc/Filters/UserItemResultsFilter.php`
- `inc/Databases/UserItemResultsDB.php`

## Out of scope

- UI/REST API controllers
- Migration/upgrade scripts to populate the table
- Caching layer (can be added later)

## References

- `inc/Models/UserItems/UserItemModel.php`
- `inc/Filters/UserItemsFilter.php`
- `inc/Databases/UserItemsDB.php`
- `config/table/tables-v5.php` (`$lp_db->tb_lp_user_item_results`)
