# Plan — rf-columns

## Steps
- [x] Step 1: Prepare shared prerequisites.
  - Add `protected $_screen_list = 'edit-' . {CPT};` to each target post type class that lacks it.
  - Verify `LP_Abstract_Post_Type::wp_list_table_class_name` filter hook pattern works for all targets.
- [x] Step 2: Lesson — create `LessonsTable` and wire `LP_Lesson_Post_Type`.
  - Create `inc/Models/WPTables/LessonsTable.php` extending `WP_Posts_List_Table`.
  - Move `columns_head` → `get_columns()`, `columns_content` → `column_*` methods, `sortable_columns` → `get_sortable_columns()`.
  - In `lesson.php`: import `LessonsTable`, override `wp_list_table_class_name`, remove `columns_head`/`columns_content`/`sortable_columns`.
- [x] Step 3: Order — create `OrdersTable` and wire `LP_Order_Post_Type`.
  - Create `inc/Models/WPTables/OrdersTable.php`.
  - Rebuild the same column set in `get_columns()`; render `order_student`, `order_items`, `order_date`, `order_total`, `order_status` in `column_*` methods.
  - In `order.php`: import `OrdersTable`, override `wp_list_table_class_name`, remove old column/sortable methods, keep `order_title()` (used outside list table).
- [x] Step 4: Question — create `QuestionsTable` and wire `LP_Question_Post_Type`.
  - Create `inc/Models/WPTables/QuestionsTable.php`.
  - Move `columns_head`, `columns_content`, `sortable_columns` logic into the table class.
  - In `question.php`: import `QuestionsTable`, override `wp_list_table_class_name`, remove old methods.
- [x] Step 5: Quiz — create `QuizzesTable` and wire `LP_Quiz_Post_Type`.
  - Create `inc/Models/WPTables/QuizzesTable.php`.
  - Move `columns_head`, `columns_content`, `sortable_columns` logic into the table class.
  - In `quiz.php`: import `QuizzesTable`, override `wp_list_table_class_name`, remove old methods.
- [x] Step 6: Assignment — wire existing `AssignmentsTable` and clean up `LP_Assignment_Post_Type`.
  - Verify `AssignmentsTable` matches the current `columns_head`/`columns_content`/`sortable_columns` logic in `assignment.php`.
  - In `assignment.php`: import `AssignmentsTable`, add `_screen_list`, override `wp_list_table_class_name`, remove old column/sortable methods.
- [x] Step 7: H5P — create `LPH5PsTable` and wire `LP_H5P_Post_Type`.
  - Create `learnpress-addons/4.x.x/learnpress-h5p/inc/Model/WPTables/LPH5PsTable.php`.
  - Move `columns_head`, `columns_content`, `sortable_columns` logic into the table class.
  - In `lph5p.php`: import `LPH5PsTable`, add `_screen_list`, override `wp_list_table_class_name`, remove old methods.
- [ ] Step 8: Run `phpcs` on every created/modified PHP file and fix new violations.
- [ ] Step 9: Admin verification checklist — visually compare list-table columns/labels/content for each post type before/after refactor.

## Files to create
| File | Purpose |
|------|---------|
| `inc/Models/WPTables/LessonsTable.php` | Admin columns + rendering for `lp_lesson` |
| `inc/Models/WPTables/OrdersTable.php` | Admin columns + rendering for `lp_order` |
| `inc/Models/WPTables/QuestionsTable.php` | Admin columns + rendering for `lp_question` |
| `inc/Models/WPTables/QuizzesTable.php` | Admin columns + rendering for `lp_quiz` |
| `learnpress-addons/4.x.x/learnpress-h5p/inc/Model/WPTables/LPH5PsTable.php` | Admin columns + rendering for `lp_h5p` |

## Files to modify
| File | Change |
|------|---------|
| `inc/custom-post-types/lesson.php` | Add `_screen_list` and `LessonsTable` import; override `wp_list_table_class_name`; remove `columns_head`/`columns_content`/`sortable_columns` |
| `inc/custom-post-types/order.php` | Add `_screen_list` and `OrdersTable` import; override `wp_list_table_class_name`; remove old column/sortable methods (keep `order_title`) |
| `inc/custom-post-types/question.php` | Add `_screen_list` and `QuestionsTable` import; override `wp_list_table_class_name`; remove old column/sortable methods |
| `inc/custom-post-types/quiz.php` | Add `_screen_list` and `QuizzesTable` import; override `wp_list_table_class_name`; remove old column/sortable methods |
| `learnpress-addons/4.x.x/learnpress-assignments/inc/custom-post-types/assignment.php` | Add `_screen_list` and `AssignmentsTable` import; override `wp_list_table_class_name`; remove old column/sortable methods |
| `learnpress-addons/4.x.x/learnpress-h5p/inc/custom-post-types/lph5p.php` | Add `_screen_list` and `LPH5PsTable` import; override `wp_list_table_class_name`; remove old column/sortable methods |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Should we keep the post-type class's `sortable_columns` method as a no-op fallback, or fully remove it? Decision: fully remove and rely on the `*Table::get_sortable_columns()` pattern used by `CoursesTable`.
- How should we handle the `duration` column in Lesson that currently has no `columns_content` case? Decision: preserve current behavior — render nothing in the Lesson table class's `column_duration` method.
