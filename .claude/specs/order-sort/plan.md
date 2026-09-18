# Plan — order-sort

## Steps
- [x] Step 1: In `posts_pre_query()` (`inc/custom-post-types/order.php` ~line 499), add `'orderby'` and `'order'` keys to the `$param` array, reading from `$wp_query->get('orderby')` and `$wp_query->get('order')`
- [x] Step 2: In `handle_params_query_list_orders()` (`inc/order/class-lp-order.php` ~line 1546-1549), fix the `order_total` branch:
  - Remove line 1548: broken WHERE clause `'AND CAST(pm2.meta_value AS UNSIGNED)'`
  - Change line 1549: `order_by` from `'pm2.meta_value'` to `'CAST(pm2.meta_value AS DECIMAL(10,2))'` for correct numeric sorting
- [x] Step 3: Run phpcs on both changed files to verify no coding standard violations

## Files to create
| File | Purpose |
|------|---------|
| (none) | |

## Files to modify
| File | Change |
|------|--------|
| `inc/custom-post-types/order.php` | Add `orderby`/`order` to `$param` array in `posts_pre_query()` (2 lines added) |
| `inc/order/class-lp-order.php` | Remove broken WHERE, fix ORDER BY cast in `handle_params_query_list_orders()` (2 lines changed) |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- None
