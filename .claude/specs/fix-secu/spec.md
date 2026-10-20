# fix-secu

> Cross-tenant IDOR in LearnPress course-builder AJAX: any authenticated instructor can delete, duplicate (exfiltrate), and inject published items into other users' courses. Reported by Patchstack triage — still vulnerable in 4.4.10.

## Goal
Close the IDOR holes so every course-builder / curriculum AJAX action enforces ownership (`current_user_can( 'edit_course'/'delete_post', $course_id )`) before any side effects (clone, delete, item creation). Prevent orphaned published posts.

## Requirements
- [ ] `check_valid_course()` in `CBEditCourseAjax` must verify the caller can edit the loaded course (ownership), not just `is_instructor()`.
- [ ] `action_type=duplicate` in `cb_quick_edit_save_course()`: before duplicating, check `current_user_can( 'edit_course', $course_id )` (or equivalent) on the source course.
- [ ] `action_type=delete`: require `current_user_can( 'delete_post', $course_id )` before calling `$course_post_model->delete()`.
- [ ] `CourseSectionModel::create_item_and_add()`: check permission on the parent course before creating the lesson/quiz post — OR create it as draft and delete it if adding to the section fails (no orphaned published posts).
- [ ] `EditCurriculumAjax::check_valid()`: verify `current_user_can( 'edit_course', $course_id )` up front, before handlers run side effects.
- [ ] No regression: legitimate owner/admin flows (edit own course, duplicate own, delete own trashed, add items to own course) still work.

## Acceptance Criteria
- [ ] An instructor cannot duplicate another user's course (draft/private/published) — request is rejected before cloning.
- [ ] An instructor cannot permanently delete another user's trashed course.
- [ ] An instructor cannot add a published lesson/quiz to another user's course section; no orphaned post is left behind on failure.
- [ ] Status-change (draft) branch behaviour unchanged.
- [ ] Admins retain full access to all courses.
- [ ] `phpcs --standard=phpcs.xml` passes on modified files.

## Scope
- `inc/ajax/coursebuilder/cbeditcourseajax.php` (around lines 47, 260, 287)
- `inc/ajax/editcurriculumajax.php` (`check_valid()`, around line 222)
- `inc/models/coursesectionmodel.php` (`create_item_and_add()`, around lines 223–300 / 251)

## Out of scope
- Non-course-builder AJAX handlers
- Frontend/UI changes
- Refactoring `abstractajax.php` nonce flow (kept as-is; handlers enforce authorization)

## References
- Patchstack triage report (CVSS 3.1: 8.1) — cross-tenant IDOR in course-builder ajax
- `inc/ajax/abstractajax.php` — `catch_lp_ajax()` only verifies public REST nonce
