# order-sort

> Fix sorting by order_total and date not working in admin Orders list

## Goal
When clicking column headers "Date" or "Total" in WP Admin → LearnPress → Orders, the list should sort correctly by the clicked column. Currently sorting is broken because:
1. `posts_pre_query()` does not forward `orderby`/`order` query vars to `handle_params_query_list_orders()`.
2. The `order_total` sort produces invalid SQL (`AND CAST(pm2.meta_value AS UNSIGNED)` as a WHERE clause with no comparison) and uses non-numeric `ORDER BY` on a meta_value string field.

## Requirements
- [x] Pass `orderby` and `order` from `$wp_query` to `$param` in `posts_pre_query()`
- [x] Remove the broken `$post_filter->where[]` line for `order_total` sorting
- [x] Use `CAST(pm2.meta_value AS DECIMAL(10,2))` in `ORDER BY` for proper numeric sorting of totals

## Acceptance Criteria
- [x] Clicking "Date" column header sorts orders by `post_date` ASC/DESC
- [x] Clicking "Total" column header sorts orders by `_order_total` meta value numerically ASC/DESC
- [x] Default sort (no orderby param) remains `post_date DESC`
- [x] No SQL errors in debug log

## Scope
- `inc/custom-post-types/order.php` — `posts_pre_query()` method
- `inc/order/class-lp-order.php` — `handle_params_query_list_orders()` method

## Out of scope
- Adding new sortable columns beyond what already exists
- OrdersTable column definitions (already correct)

## References
- `inc/Models/WPTables/OrdersTable.php` — defines sortable columns
- `inc/Filters/FilterBase.php` — `order_by` / `order` properties
- `inc/Databases/DataBase.php` — builds `ORDER BY` clause from filter
