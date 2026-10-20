# Progress — fix-secu

**Status:** 🟢 Done (pending manual verification)
**Started:** 2026-10-14
**Last updated:** 2026-10-14

## Done
1. Added ownership check in `CBEditCourseAjax::check_valid_course()` — `current_user_can( 'edit_' . LP_COURSE_CPT, $course_id )` after course load. Covers edit, duplicate, status-change, restore branches.
2. Added `current_user_can( 'delete_post', $course_id )` before `delete()` in the `action_type=delete` branch.
3. Added course cap check in `CourseSectionModel::create_item_and_add()` before creating the item post — prevents orphaned published posts.
4. Added up-front `current_user_can( 'edit_' . LP_COURSE_CPT, $course_id )` check in `EditCurriculumAjax::check_valid()` — protects all curriculum endpoints.
5. phpcs clean on all 3 modified files.
6. User changes: `PostModel::delete()` now checks `delete_post`; `check_valid_course()` uses `$courseModel->check_permission()`; `EditCurriculumAjax::check_valid()` finds course + `check_permission()` + returns `courseModel`.
7. Refactored all 7 handlers in `EditCurriculumAjax` to use `$data['courseModel']` instead of re-finding `CourseModel::find( $course_id )`; section lookups use `$courseModel->get_id()`. phpcs + phpcbf clean.

## In progress

## Next
- Manual verification: attempt duplicate/delete/add-item on another instructor's course → expect rejection; confirm owner/admin flows still work.

## Decisions made
- Cap used: `'edit_' . LP_COURSE_CPT` (`edit_lp_course` with `map_meta_cap`), matching `CoursePostModel::check_capabilities_update()` convention. `delete_post` for the delete branch.
- Up-front check in `check_valid_course()` makes a separate duplicate-branch check redundant.
- Orphaned-post fix done via pre-check (check before create), not draft-then-delete, to keep it minimal.

## Blockers / Notes
