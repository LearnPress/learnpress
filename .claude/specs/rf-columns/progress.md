# Progress — rf-columns

**Status:** 🔴 Blocked
**Started:** 2026-09-17
**Last updated:** 2026-09-17

## Done
<!-- Numbered list: 1. Step name, 2. Step name … -->
1. Created spec folder and initial `spec.md`, `plan.md`, `progress.md`.
2. Audited existing `columns_head`, `columns_content`, and `sortable_columns` implementations in all target custom-post-type files.
3. Confirmed `CoursesTable` pattern: custom `WP_Posts_List_Table` subclass + `wp_list_table_class_name` override in the post-type class.
4. Added `$_screen_list = 'edit-' . {CPT};` to all six target post-type classes.
5. Created `LessonsTable` and wired it into `LP_Lesson_Post_Type`; removed legacy `columns_head`/`columns_content`/`sortable_columns` methods from `lesson.php`.
6. Created `OrdersTable` and wired it into `LP_Order_Post_Type`; removed legacy `columns_head`/`columns_content`/`sortable_columns` methods from `order.php` (kept `order_title`).
7. Created `QuestionsTable` and wired it into `LP_Question_Post_Type`; removed legacy `columns_head`/`columns_content`/`sortable_columns` methods from `question.php`.
8. Created `QuizzesTable` and wired it into `LP_Quiz_Post_Type`; removed legacy `columns_head`/`columns_content`/`sortable_columns` methods from `quiz.php`.
9. Wired existing `AssignmentsTable` into `LP_Assignment_Post_Type`; removed legacy `columns_head`/`columns_content`/`sortable_columns` methods from `assignment.php`.
10. Created `LPH5PsTable` and wired it into `LP_H5P_Post_Type`; removed legacy `columns_head`/`columns_content`/`sortable_columns` methods from `lph5p.php`; PHP syntax checks passed.

## In progress
- Step 8: All 12 touched PHP files pass `php -l`; `phpcs` remains unavailable.

## Next
- Run `phpcs --standard=phpcs.xml` on all touched PHP files when PHPCS is available.
- Step 9: Admin verification checklist.

## Decisions made
- Fully remove post-type class `sortable_columns` methods and rely on `*Table::get_sortable_columns()` (matches `CoursesTable`).
- Preserve current behavior where Lesson `duration` column has no content renderer (render nothing in `LessonsTable::column_duration`).
- Keep `LP_Order_Post_Type::order_title()` because it is used outside the list table (single order edit screen title override).
- phpcs is not installed in this environment; using `php -l` for syntax checks and will document phpcs as a pending verification step.

## Blockers / Notes
- `phpcs` is not installed globally and no project PHPCS executable exists under `vendor`; acceptance criterion for PHPCS cannot yet be verified.
- Step 9 requires a running WordPress admin environment and visual comparison of lesson, order, question, quiz, assignment, and H5P list tables.
