# Progress — rf-cache

**Status:** 🟡 In progress
**Started:** 2026-10-15
**Last updated:** 2026-10-16

## Done
1. Step 1: Renamed `inc/cache/` to `inc/Cache/`, preserved all eight legacy cache files, updated eight includes in `learnpress.php` and one require in `tests/bootstrap.php`, and verified no stale legacy cache path remains.
2. Step 2: Added PSR-4 `LearnPress\Cache\LPCache` with the legacy-compatible cache API.
3. Step 3: Synchronized WordPress object cache with optional `thim_cache`, including WordPress cache backfill after a persistent hit; a standalone runtime check passed.
4. Step 4: Added focused unit coverage for PSR-4 loading and WordPress cache set/get/miss/clear/request-local behavior.
5. Step 5: Verification completed for Composer autoload generation, PHP syntax, diff whitespace, stale paths, and a standalone synchronization check; PHPUnit and PHPCS remain unavailable locally.
6. Step 6: Inventoried the current `LP_Cache` surface into direct instances, request-local static calls, legacy subclasses, grouped key registries, invalidation paths, and commented references without changing consumers.
7. Step 7: Presented the migration scope and received explicit user confirmation to migrate all consumers in phases.
8. Added non-persistent request-local helpers to `LPCache`: `get_not_persistent_cache`, `set_not_persistent_cache`, `remember_not_persistent` using group `lp_cache_not_persistent` and `wp_cache_add_non_persistent_groups`, so Redis/Memcached will not persist these values across requests.
9. Step 10: Migrated the course cache domain to PSR-4 `LearnPress\Cache\LPCourseCache`; all active core `LP_Course_Cache` consumers now use the new class, while deprecated enrolled-only cache methods were intentionally omitted.
10. Step 8: Migrated `AddonService` remote add-on data and purchase-info caches, including invalidation, from `LP_Cache` to PSR-4 `LearnPress\Cache\LPCache`; persistent Thim cache reads explicitly decode objects.
11. Step 8: Added PSR-4 `LPCache` to admin theme remote data with key `lp_themes_data`, array decoding, 24-hour expiration, and uncached local fallback for invalid or unavailable remote data.
12. Step 8: Added PSR-4 `LPCache` to rendered admin notices with a two-minute expiration; permission checks remain before cache reads and dismiss actions invalidate the cached HTML.

## In progress
- Step 5 verification is complete except PHPUnit and PHPCS, whose executables remain unavailable locally.
- Step 10: Migrated the settings cache domain to PSR-4 `LearnPress\Cache\LPSettingsCache`, including all discovered consumers in LearnPress core and the exams, membership, and Braintree add-ons; migrated the course cache domain and all active core consumers to `LearnPress\Cache\LPCourseCache`; remaining legacy domain caches are not yet migrated.

## Next
- Continue Step 10 with the next user-selected legacy domain cache.
- Step 8: Migrate direct `new LP_Cache()` consumers to `LearnPress\Cache\LPCache`, preserving cache keys/groups and specifying persistent result types where required.
- Step 9: Replace request-local `LP_Cache::cache_load_first()` usages with non-persistent `LPCache` APIs.
- Step 11: Run final migration verification.
- Make project-local PHPUnit and PHPCS executables available, then finish Step 5 checks.

## Decisions made
- Preserve all eight legacy cache class files and global class names for backward compatibility.
- Use `LearnPress\Cache\LPCache` at `inc/Cache/LPCache.php`, matching Composer's existing `LearnPress\` → `inc/` PSR-4 mapping.
- Complete and verify the cache foundation before migrating any `LP_Cache` consumer.
- Treat persistent-cache fallback as synchronized only when a valid persistent hit is written back to `wp_cache`.
- Inventory all 84 current `LP_Cache` references across 32 PHP files, including direct instances, request-local static calls, legacy subclasses, key registries, invalidation paths, and commented references.
- Require user review of proposed cache-key conversions, additional requirements, and explicit confirmation before consumer migration.
- User explicitly approved migration of all consumers in phases: direct instances, request-local static calls, then legacy subclasses.
- Preserve current cache keys and groups by default during migration.
- Settings cache intentionally changed from group/key `learn_press/settings` + `lp_settings` to group/key `learn_press` + `settings`; values are stored as arrays and persistent reads decode as arrays.
- Course cache preserves group `learn_press/course` and key `total-students-enrolled-or-purchased`; request-local reads now use the non-persistent `LPCache` APIs.
- `LPCourseCache` intentionally omits deprecated enrolled-only methods (`set/get/clean_total_students_enrolled`); deprecated callers query the database directly and invalidation paths clear only the enrolled-or-purchased key.
- Keep the legacy `LP_Settings_Cache` declaration and bootstrap include temporarily for third-party backward compatibility; no active core or scanned add-on consumer uses it.

## Blockers / Notes
- `vendor/bin/phpunit` and `vendor/squizlabs/php_codesniffer/bin/phpcs` do not exist in the current checkout, and no global `phpunit` or `phpcs` executable is available.
- PHP lint, `git diff --check`, Composer autoload generation, stale-path search, and a standalone WordPress/persistent-cache synchronization check pass.
- Consumer migration confirmation gate was cleared on 2026-10-15.
