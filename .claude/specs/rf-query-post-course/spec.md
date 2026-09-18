# rf-query-post-course

> Refactor the backend `lp_course` list-table query to use a single `posts_pre_query` hook, matching the `LP_Order_Post_Type::posts_pre_query` pattern.

## Goal

Replace the current multi-filter SQL-hook approach in `inc/custom-post-types/course.php` with a single `posts_pre_query` action that fetches course rows through the model/PostDB layer (`CoursesTable`/`CoursePostModel`), similar to how `LP_Order_Post_Type::posts_pre_query` handles orders.

## Requirements

- [ ] Implement `LP_Course_Post_Type::posts_pre_query( $posts, $wp_query )` for the `lp_course` admin list table.
- [ ] Register only one query hook: `add_action( 'posts_pre_query', array( $this, 'posts_pre_query' ), 999, 2 );`
- [ ] Comment out (do not delete) the existing course query hooks so they can be restored if needed:
  - `add_filter( 'posts_where_paged', ... )`
  - `add_filter( 'posts_join_paged', ... )`
  - `add_filter( 'posts_fields', ... )`
  - `add_filter( 'posts_orderby', ... )`
  - `add_filter( '_posts_join_paged_course_items', ... )`
  - `add_filter( '_posts_where_paged_course_items', ... )`
- [ ] Preserve existing list-table behavior: search, author filter, date filter, status filter, price sorting/filtering, and course-item filtering.
- [ ] Convert `WP_Query` request parameters into a `CoursePostFilter` (or equivalent) and call the DB layer.

## Acceptance Criteria

- [ ] The course admin list table still loads and filters correctly.
- [ ] Only `posts_pre_query` is active for course query customization.
- [ ] No fatal errors or PHP notices on the `wp-admin/edit.php?post_type=lp_course` screen.
- [ ] Existing `posts_where_paged`, `posts_join_paged`, `posts_fields`, `posts_orderby` filters are disabled (commented) but remain in source.

## Scope

- `inc/custom-post-types/course.php`
- `inc/Models/CoursePostModel.php`
- `inc/Models/WPTables/CoursesTable.php`
- Reference implementation: `inc/custom-post-types/order.php::posts_pre_query`

## Out of scope

- Frontend course archive queries.
- Refactoring order or other post types.

## References

- `inc/custom-post-types/order.php:L463-513` — `LP_Order_Post_Type::posts_pre_query`
