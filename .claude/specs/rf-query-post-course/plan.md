# Plan — rf-query-post-course

## Steps

- [ ] **Step 1: Audit existing course query hooks**
  - Read `inc/custom-post-types/course.php` lines ~32-38 and ~209-328.
  - List hooks to disable: `posts_fields`, `posts_join_paged`, `posts_where_paged`, `posts_orderby`, `_posts_join_paged_course_items`, `_posts_where_paged_course_items`.

- [ ] **Step 2: Design the `posts_pre_query` replacement**
  - Mirror `LP_Order_Post_Type::posts_pre_query` in `inc/custom-post-types/order.php`.
  - Convert `WP_Query` request params to a `LearnPress\Filters\CoursePostFilter extends PostFilter` (same pattern as `OrderPostFilter`):
    - `post_type = LP_COURSE_CPT`
    - `page` from `get_query_var( 'paged' )`
    - `limit` from `edit_lp_course_per_page`
    - `post_status` from `$wp_query->get( 'post_status' )`
    - `post_author` from `$wp_query->get( 'author' )`
    - `post_title` / `key_word` from `$wp_query->get( 's' )`
    - date filter `m` from `$wp_query->get( 'm' )`
    - price filter `filter_price` from `$_REQUEST['filter_price']`
    - orderby `price` → join `postmeta` and order by `CAST(meta_value AS UNSIGNED)`
    - orderby `title|date|author` → pass through to `order_by`
  - Fetch rows via `PostDB::getInstance()->get_posts( $filter, $total_rows )`.
  - Set `$wp_query->post_count` and `$wp_query->found_posts` and return the rows.

- [ ] **Step 3: Implement `LP_Course_Post_Type::posts_pre_query( $posts, $wp_query )`**
  - Guard: `is_admin()`, correct `post_type`, early return otherwise.
  - Build filter, run `PostDB::getInstance()->get_posts()`, assign totals, return posts.
  - Wrap in `try/catch( Throwable )` with `LP_Debug::error_log()`.

- [ ] **Step 4: Wire the single hook and disable the old ones**
  - In `__construct()`: add `add_action( 'posts_pre_query', array( $this, 'posts_pre_query' ), 999, 2 );`.
  - Comment out the two `add_filter( 'posts_where_paged', ... )` and `add_filter( 'posts_join_paged', ... )` for course items.
  - Comment out or no-op the methods `posts_fields`, `posts_join_paged`, `posts_where_paged`, `posts_orderby`, `_posts_join_paged_course_items`, `_posts_where_paged_course_items` in `course.php`.

- [ ] **Step 5: Verify and format**
  - Run `php -l inc/custom-post-types/course.php`.
  - Run `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/custom-post-types/course.php` (if phpcs is present).
  - Smoke-test `wp-admin/edit.php?post_type=lp_course` with filters, sorting, and search.

## Files to create

| File | Purpose |
|------|---------|
| `inc/Filters/CoursePostFilter.php` | Filter class for course post queries, extends `LearnPress\Filters\PostFilter` (mirrors `OrderPostFilter`) |

## Files to modify

| File | Change |
|------|--------|
| `inc/Filters/CoursePostFilter.php` | New file: `class CoursePostFilter extends PostFilter` with `public $post_type = LP_COURSE_CPT;` |
| `inc/custom-post-types/course.php` | Add `posts_pre_query`; register single hook; disable old query filters/methods |

## Format code when create file done run

```bash
php -l inc/Filters/CoursePostFilter.php
php -l inc/custom-post-types/course.php
php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/Filters/CoursePostFilter.php inc/custom-post-types/course.php
```

## Open questions

- Does `CoursePostFilter` need any course-specific fields beyond the base `PostFilter`?
