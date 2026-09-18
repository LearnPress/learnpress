# rf-db-refactor-database-version-6

> Refactor LearnPress database schema to version 6 by restructuring `learnpress_user_item_results` and extending `learnpress_user_items`.

## Goal
Refactor the user item result storage so that `learnpress_user_item_results` stores direct references to `user_id`, `guest_key`, `item_id`, `item_type`, and additional `extra_data`, while `learnpress_user_items` gains an `extra_data` column. Migrate existing data into the new columns during the upgrade process.

## Requirements
- [ ] Create `learnpress-upgrade-6.php` in `inc/updates`.
- [ ] Register a group step `learnpress_user_item_results`.
- [ ] Add upgrade step `modify_tb_lp_user_item_results` to add columns `user_id`, `guest_key`, `item_id`, `item_type`, and `extra_data` to `learnpress_user_item_results`.
- [ ] Add upgrade step `modify_tb_lp_user_items` to add column `extra_data` to `learnpress_user_items`.
- [ ] Add upgrade step `update_data_for_tb_lp_user_item_results` to populate `user_id`, `guest_key`, `item_id`, and `item_type` from existing relationships.
- [ ] Follow existing upgrade file patterns (`learnpress-upgrade-4.php`, `learnpress-upgrade-5.php`) for class structure and step registration.

## Acceptance Criteria
- [ ] `learnpress-upgrade-6.php` exists and is loadable by LearnPress updater.
- [ ] Group step `learnpress_user_item_results` contains all three sub-steps.
- [ ] Table modifications run without fatal errors on existing installations.
- [ ] Data update step correctly backfills new columns from related records.

## Scope
- `inc/updates/learnpress-upgrade-6.php`
- `inc/updates/learnpress-upgrade-4.php` (reference only)
- `inc/updates/learnpress-upgrade-5.php` (reference only)

## Out of scope
- UI changes
- Frontend behavior changes
- Changes to tables other than `learnpress_user_item_results` and `learnpress_user_items`

## References
- Existing upgrade files: `inc/updates/learnpress-upgrade-4.php`, `inc/updates/learnpress-upgrade-5.php`
- Table definitions: `config/table/tables-v5.php`
