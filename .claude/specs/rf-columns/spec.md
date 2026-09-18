# rf-columns

> Refactor admin list-table display columns for LearnPress custom post types to follow the `CoursesTable` pattern.

## Goal

Standardize how admin list-table columns are registered and rendered for LearnPress custom post types by extracting column logic into dedicated `WPTables\*Table` classes, mirroring the existing `CoursesTable` implementation. This reduces duplication in custom-post-type classes, makes column definitions testable, and keeps rendering logic cohesive.

## Requirements

- [ ] Create a `WPTables\*Table` class for each target post type: lesson, order, question, quiz, assignment (addon), and lph5p (addon).
- [ ] Move existing `manage_*_posts_columns`, `manage_*_posts_custom_column`, and sortable-column logic from the corresponding `custom-post-types/*.php` files into the new table classes.
- [ ] Preserve existing column order, labels, and rendered output (no admin UX regressions).
- [ ] Retain sortable column behavior where it currently exists.
- [ ] Hook the new table classes via `*_posts_list_table` or equivalent filters used by `LP_Course_Post_Type`/`CoursesTable`.
- [ ] Ensure the new classes extend `WP_Posts_List_Table` and follow the `CoursesTable` naming/structure conventions.

## Acceptance Criteria

- [ ] Each target post type has a corresponding `inc/Models/WPTables/*Table.php` class (or equivalent path in addons).
- [ ] Old column/sortable filters are removed from the custom-post-type files and replaced by calls to the new table classes.
- [ ] Admin list view for each post type still shows the same columns, labels, and content as before the refactor.
- [ ] `phpcs --standard=phpcs.xml` reports no new errors in touched files.

## Scope

- `inc/Models/WPTables/LessonsTable.php`
- `inc/Models/WPTables/OrdersTable.php`
- `inc/Models/WPTables/QuestionsTable.php`
- `inc/Models/WPTables/QuizzesTable.php`
- `inc/custom-post-types/lesson.php`
- `inc/custom-post-types/order.php`
- `inc/custom-post-types/question.php`
- `inc/custom-post-types/quiz.php`
- `Plugins/learnpress-addons/4.x.x/learnpress-assignments/inc/Models/WPTables/` (to create)
- `Plugins/learnpress-addons/4.x.x/learnpress-assignments/inc/custom-post-types/assignment.php`
- `Plugins/learnpress-addons/4.x.x/learnpress-h5p/inc/Models/WPTables/` (to create)
- `Plugins/learnpress-addons/4.x.x/learnpress-h5p/inc/custom-post-types/lph5p.php`

## Out of scope

- Changing the visible columns, labels, or sort behavior.
- Refactoring bulk actions, quick edit, or filters above the list table.
- Modifying the underlying post-type registration arguments.

## References

- `inc/custom-post-types/course.php`
- `inc/Models/WPTables/CoursesTable.php`
