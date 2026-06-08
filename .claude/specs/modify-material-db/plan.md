# modify-material-db Implementation Plan

## Goal
Add `get_files` method to MaterialFilesDB class that accepts MaterialFilter and follows the CourseJsonDB `get_courses` implementation pattern.

## Implementation Steps

### Step 1: Implement get_files method in MaterialFilesDB.php
**File:** `inc/Databases/MaterialFilesDB.php`
- Add `use LearnPress\Filters\MaterialFilter;` import
- Implement `get_files(MaterialFilter $filter, int &$total_rows = 0)` method
- Follow CourseJsonDB pattern:
  - Merge filter fields with all_fields
  - Set table name and alias
  - Map each MaterialFilter property to WHERE clause conditions
  - Apply filters for file_id, file_name, file_type, item_id, item_ids, item_type, method, etc.
  - Add default ordering
  - Apply filter hook
  - Call `$this->execute($filter, $total_rows)` at the end

### Step 2: Verify MaterialFilter compatibility
**File:** `inc/Filters/MaterialFilter.php`
- Confirm all filter properties are properly mapped
- Ensure `all_fields` array matches table columns
- No modifications needed per spec requirements

### Step 3: Create unit test for get_files method
**File:** `tests/Unit/Databases/MaterialFilesDBTest.php`
- Test that get_files accepts MaterialFilter
- Test filtering by each available property
- Test pagination and total_rows return
- Verify no breaking changes to existing methods

### Step 4: Run existing tests to confirm no regression
- Run `composer test:filter MaterialFilesDB`
- Run `composer test:filter MaterialFilter`

## Files to Modify
| File | Action | Purpose |
|------|--------|---------|
| `inc/Databases/MaterialFilesDB.php` | ✏️ Modify | Add get_files method implementation |
| `tests/Unit/Databases/MaterialFilesDBTest.php` | 🆕 Create | Unit tests for new method |

## Open Questions
✅ No open questions. The spec is clear and reference implementation exists.

## Dependencies
- None. MaterialFilter already exists and is complete.
