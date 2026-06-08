# Plan — create-material-filter

## Overview

Create `MaterialFilter` class following the canonical `/new-filter` pattern (same as `UserItemsFilter`), mapping all 9 columns from the `learnpress_files` table.

---

## Step 1: Create MaterialFilter class

**File:** `inc/Filters/MaterialFilter.php` (NEW)
**Use skill:** `/new-filter`

Create PSR-4 Filter class with:
- Namespace: `LearnPress\Filters`
- Extends: `FilterBase`
- 9 column constants:

| Constant | Column | DB Type |
|----------|--------|---------|
| `COL_FILE_ID` | `file_id` | bigint(20) PK |
| `COL_FILE_NAME` | `file_name` | varchar(191) |
| `COL_FILE_TYPE` | `file_type` | varchar(10) |
| `COL_ITEM_ID` | `item_id` | bigint(20) |
| `COL_ITEM_TYPE` | `item_type` | varchar(100) |
| `COL_METHOD` | `method` | varchar(10) — 'upload' or 'external' |
| `COL_FILE_PATH` | `file_path` | varchar(255) |
| `COL_ORDERS` | `orders` | int(4) |
| `COL_CREATED_AT` | `created_at` | datetime |

- `$all_fields` array with all 9 constants
- Typed public properties:
  - `$file_id` (int) — single value
  - `$file_name` (string) — single value
  - `$file_type` (string) — single value
  - `$item_id` (int) — single value
  - `$item_ids` (array int) — plural for IN queries
  - `$item_type` (string) — single value
  - `$method` (string) — single value
  - `$file_path` (string) — single value
  - `$orders` (int) — single value
  - `$created_at` (string) — single value
- `$field_count = self::COL_FILE_ID` (primary key)

**Verification:** Class follows same pattern as `UserItemsFilter.php`

---

## Step 2: Run phpcs lint

```bash
composer lint -- inc/Filters/MaterialFilter.php
```

Fix any coding standard issues.

---

## Step 3: Run PHPUnit tests

```bash
composer test
```

Ensure no regressions (new file, no callers yet — should be clean).

---

## Files Summary

| File | Action |
|------|--------|
| `inc/Filters/MaterialFilter.php` | CREATE |

## Open questions
- None — straightforward single-file creation following established pattern
