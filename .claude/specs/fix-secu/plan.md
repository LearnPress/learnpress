# Plan — fix-secu

## Steps
- [ ] Step 1: Read `inc/ajax/coursebuilder/cbeditcourseajax.php` — inspect `check_valid_course()`, `cb_quick_edit_save_course()` branches (duplicate/delete/draft), and existing ownership checks in the draft branch to mirror.
- [ ] Step 2: Add ownership check in `check_valid_course()` / after course load: `current_user_can( 'edit_course', $course_id )` (or `edit_post`) — decide exact cap by reading how the draft branch does it.
- [ ] Step 3: In `action_type=delete` branch, require `current_user_can( 'delete_post', $course_id )` before `delete()`.
- [ ] Step 4: Read `inc/models/coursesectionmodel.php` `create_item_and_add()` — move capability check on parent course before post creation, or create as draft + cleanup on failure.
- [ ] Step 5: Read `inc/ajax/editcurriculumajax.php` `check_valid()` (~line 222) — add up-front `current_user_can( 'edit_course', $course_id )` verification.
- [ ] Step 6: Run `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml` on modified files; fix reported issues.

## Files to create
| File | Purpose |
|------|---------|
| | |

## Files to modify
| File | Change |
|------|--------|
| `inc/ajax/coursebuilder/cbeditcourseajax.php` | Ownership checks for edit/duplicate/delete branches |
| `inc/ajax/editcurriculumajax.php` | Up-front course capability check in `check_valid()` |
| `inc/models/coursesectionmodel.php` | Check permission before creating item post; avoid orphaned published posts |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Which capability string matches existing conventions: `edit_course`, `edit_post`, or LP-specific cap used in draft branch? (Resolve during Step 1/2.)
- Should `check_valid_course()` return/throw an error message consistent with existing responses (e.g. `LP_Error`/json error)?
