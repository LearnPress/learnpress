# Implementation Plan — refactor-material-db

## Overview

Migrate `LP_Material_Files_DB` (legacy style, no namespace) to `MaterialFilesDB` (PSR-4 style, `LearnPress\Databases` namespace) while maintaining backward compatibility by keeping the old file untouched.

---

## Step 1: Create MaterialFilesDB class ✅

**File:** `inc/Databases/MaterialFilesDB.php` (NEW)
**Use skill:** `/new-db-class`

Create PSR-4 style class with:
- Namespace: `LearnPress\Databases`
- Extends: `DataBase`
- Singleton: `getInstance()`
- Properties:
  - `$_instance` (private static)
  - `$table_name` (public) — set to `$this->tb_lp_files` in constructor
- Methods (copied from `LP_Material_Files_DB`):
  - `create_material($data)` — insert new material
  - `get_material($file_id)` — get single material by ID
  - `get_material_by_item_id($item_id, $perpage, $offset, $is_admin)` — get materials for course/lesson
  - `get_total($item_id)` — count materials
  - `update_material_orders($orders, $item_id)` — batch update sort order
  - `delete_material($file_id)` — delete material + local file
  - `delete_material_by_item_id($item_id)` — delete all materials for item
  - `delete_local_file($file_path)` — helper for file deletion

**Verification:** Class follows same pattern as `UserItemsDB.php`

---

## Step 2: Update CourseMaterialTemplate ✅

**File:** `inc/TemplateHooks/Course/CourseMaterialTemplate.php`

Changes:
| Line | Current | New |
|------|---------|-----|
| 15 | `use LP_Material_Files_DB;` | `use LearnPress\Databases\MaterialFilesDB;` |
| 66 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 133 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 138 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |

---

## Step 3: Update SingleCourseTemplate ✅

**File:** `inc/TemplateHooks/Course/SingleCourseTemplate.php`

Changes:
| Line | Current | New |
|------|---------|-----|
| 31 | `use LP_Material_Files_DB;` | `use LearnPress\Databases\MaterialFilesDB;` |
| 1243 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |

---

## Step 4: Update REST controller ✅

**File:** `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php`

Changes:
- Add `use LearnPress\Databases\MaterialFilesDB;` at top (after namespace line)
- Replace all occurrences:

| Line | Current | New |
|------|---------|-----|
| 127 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 132 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 284 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 378 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 411 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |

---

## Step 5: Update meta-box materials view ✅

**File:** `inc/admin/views/meta-boxes/fields/materials.php`

Changes:
- Add `use LearnPress\Databases\MaterialFilesDB;` after line 8 (after the `if ( ! class_exists(...) )` check)
- Replace occurrences:

| Line | Current | New |
|------|---------|-----|
| 36 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |
| 190 | `LP_Material_Files_DB::getInstance()` | `MaterialFilesDB::getInstance()` |

---

## Step 6: Verify no changes needed for abstract-course.php ✅

**File:** `inc/course/abstract-course.php`
**Status:** NO CHANGES NEEDED

After review, the only reference to `LP_Material_Files_DB` is in the deprecated method `get_downloadable_material()` (line 1469-1472) which is already commented out. No changes required.

---

## Step 7: Run tests ✅

```bash
composer lint     # Check PHP code standards — PASSED (1 minor fix: missing EOF newline)
composer test     # Run PHPUnit tests — PASSED (154 tests, 225 assertions)
```

---

## Files Summary

| File | Action | Status |
|------|--------|--------|
| `inc/Databases/MaterialFilesDB.php` | CREATE | ✅ |
| `inc/TemplateHooks/Course/CourseMaterialTemplate.php` | MODIFY (4 changes) | ✅ |
| `inc/TemplateHooks/Course/SingleCourseTemplate.php` | MODIFY (2 changes) | ✅ |
| `inc/rest-api/v1/frontend/class-lp-rest-material-controller.php` | MODIFY (6 changes) | ✅ |
| `inc/admin/views/meta-boxes/fields/materials.php` | MODIFY (3 changes) | ✅ |
| `inc/course/abstract-course.php` | NO CHANGE | ✅ |
| `inc/Databases/class-lp-material-db.php` | NO CHANGE (kept for backward compat) | ✅ |

---

## Rollback Plan

1. Delete `inc/Databases/MaterialFilesDB.php`
2. Revert `use` statements and class references in modified files
3. Original `LP_Material_Files_DB` was never modified
