# Plan — rf-db-refactor-database-version-6

## Steps
- [x] Step 1: Verify patterns in existing upgrade files and the updater loader (`inc/admin/class-lp-updater.php`, `inc/updates/learnpress-upgrade-4.php`, `inc/updates/learnpress-upgrade-5.php`).
- [x] Step 2: Update `learnpress.php` so `LearnPress::$db_version` is `6`.
- [x] Step 3: Create `inc/updates/learnpress-upgrade-6.php` with class `LP_Upgrade_6 extends LP_Handle_Upgrade_Steps` and singleton pattern.
- [x] Step 4: Register group step `learnpress_user_item_results` containing the three required sub-steps plus a `finish_upgrade` step to bump DB version.
- [x] Step 5: Implement `modify_tb_lp_user_item_results()`: add columns `user_id`, `guest_key`, `item_id`, `item_type`, `extra_data` to `{$lp_db->tb_lp_user_item_results}` using `add_col_table()`.
- [x] Step 6: Implement `modify_tb_lp_user_items()`: add column `extra_data` to `{$lp_db->tb_lp_user_items}` using `add_col_table()`.
- [x] Step 7: Implement `update_data_for_tb_lp_user_item_results()`: backfill `user_id`, `guest_key`, `item_id`, `item_type` in `learnpress_user_item_results` from related `learnpress_user_items` rows.
- [x] Step 8: Implement `finish_upgrade()`: update `LP_KEY_DB_VERSION` to `6` and mark step complete.
- [x] Step 9: Run phpcs on `inc/updates/learnpress-upgrade-6.php`.

## Files to create
| File | Purpose |
|------|---------|
| `inc/updates/learnpress-upgrade-6.php` | Database version 6 upgrade handler with the new `LP_Upgrade_6` class |

## Files to modify
| File | Change |
|------|---------|
| `learnpress.php` | Bump `LearnPress::$db_version` from `5` to `6` |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Should `finish_upgrade` be included in the group step to bump `LP_KEY_DB_VERSION` to `6`, or is the version bump handled elsewhere?
- What value should `guest_key` receive for existing rows that have no guest data? (Default empty string?)
- Should `update_data_for_tb_lp_user_item_results` be batched, and if so what batch size (e.g., 500 or 1000)?
