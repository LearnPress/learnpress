# Progress — rf-query-post-course

**Status:** 🟡 In progress
**Started:** 2026-09-17
**Last updated:** 2026-09-17

## Done
1. Spec created.
2. Plan finalized with concrete steps and param mapping.
3. Re-checked `LP_Course_Post_Type::posts_pre_query()` (user applied fixes: `LP_Request::get_param(..., 'key')`, exclude auto-draft, default `menu_order`).
4. Applied the same `posts_pre_query` refactor to:
   - `LP_Lesson_Post_Type` (`inc/custom-post-types/lesson.php`) + `LessonPostFilter`
   - `LP_Question_Post_Type` (`inc/custom-post-types/question.php`) + `QuestionPostFilter`
   - `LP_Quiz_Post_Type` (`inc/custom-post-types/quiz.php`) + `QuizPostFilter`
   - `LP_Assignment_Post_Type` (`learnpress-assignments/inc/custom-post-types/assignment.php`) + `AssignmentPostFilter`
   - `LP_H5P_Post_Type` (`learnpress-h5p/inc/custom-post-types/lph5p.php`) + `H5PPostFilter`
5. Created all PostFilter subclasses for the post types above.
6. Commented out old `posts_where_paged`, `posts_join_paged`, `posts_orderby`, `posts_fields` hook methods in each file.
7. Added the `get_current_screen()` early-return guard to all `posts_pre_query` methods (course, lesson, question, quiz, assignment, h5p) and removed the `empty( $posts )` guard.
9. Simplified `lesson.php` course filter to a direct INNER JOIN with `section_items` and `sections`, removed unused `CourseSectionDB`/`CourseSectionFilter` imports, and cleaned up leftover commented debug line.
10. Replaced `learn_press_get_item_courses()` with `CourseSectionItemModel::get_courses_from_item_id()` in `LP_Abstract_Post_Type::get_courses_of_item()` (renamed from `_get_item_course()`).
11. Updated `lesson.php` preview filter to query preview lesson IDs via `PostFilter` with custom `join`/`where` for `_lp_preview` meta, then apply IN/NOT IN to the main `LessonPostFilter`. `preview=yes` limits to those IDs, `preview=no` excludes them (which includes lessons without the meta key).
12. Fixed lost pagination in all `posts_pre_query` methods: set `post_count` to current page count, `found_posts` to total rows, and `max_num_pages` to `ceil(total / posts_per_page)`.
13. Fixed hierarchical post type admin list pagination by setting `'hierarchical' => false` in `lp_lesson`, `lp_quiz`, `lp_assignment`, and `lp_h5p` post type registrations (lessons/quizzes/assignments/H5P do not use parent-child hierarchy). This prevents WordPress from forcing `fields=id=>parent` and `posts_per_page=-1` on the admin list, allowing `posts_pre_query` pagination to work normally. Removed the temporary `parse_query` workaround.
14. `php -l` passed for all modified/created files.

## In progress

## Next
- Smoke-test admin list tables:
  - `wp-admin/edit.php?post_type=lp_course`
  - `wp-admin/edit.php?post_type=lp_lesson`
  - `wp-admin/edit.php?post_type=lp_question`
  - `wp-admin/edit.php?post_type=lp_quiz`
  - `wp-admin/edit.php?post_type=lp_assignment`
  - `wp-admin/edit.php?post_type=lp_h5p`
- Run `phpcs` when the tool is available again (currently not installed in `vendor`).

## Decisions made
- Use a `*PostFilter extends PostFilter` for every post type to mirror `CoursePostFilter` / `OrderPostFilter`.
- Preserve existing per-post-type filters in `posts_pre_query`: course price orderby; lesson unassigned/preview; question quiz filter/quiz-name orderby/unassigned; quiz question-count orderby/course-name orderby/unassigned; assignment & h5p course filter/course-name orderby/unassigned.
- Follow the same `post_count = found_posts = $total_rows` pattern used in `LP_Order_Post_Type::posts_pre_query`.

## Blockers / Notes
- `phpcs` binary is not present in `vendor/bin` (only `autoload.php`, `composer`, `symfony`, `tijsverkoyen` remain), so code-style checks were skipped; syntax checks (`php -l`) passed.
