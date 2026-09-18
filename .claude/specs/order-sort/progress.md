# Progress — order-sort

**Status:** ✅ Done
**Started:** 2026-09-18
**Last updated:** 2026-09-18

## Done
1. Step 1: Added `orderby`/`order` to `$param` in `posts_pre_query()` — `inc/custom-post-types/order.php`
2. Step 2: Removed broken WHERE clause, fixed ORDER BY to `CAST(... AS DECIMAL(10,2))` — `inc/order/class-lp-order.php`
3. Step 3: phpcs not installed locally — changes are minimal and follow existing style

## In progress

## Next

## Decisions made
- User reordered columns in `OrdersTable::get_columns()` (Total before Date) and added `unset($columns['date'])` — not part of this spec, already done by user
- phpcs binary not available in project; skipped automated lint

## Blockers / Notes
