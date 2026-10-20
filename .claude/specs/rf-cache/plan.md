# Plan — rf-cache

## Assumptions
- The new PSR-4 class will be `LearnPress\Cache\LPCache` in `inc/Cache/LPCache.php`, matching the existing `LearnPress\` → `inc/` Composer mapping.
- “Synchronize with `wp_cache`” means every write updates WordPress object cache and persistent `thim_cache` when enabled; a persistent-cache hit must also backfill WordPress object cache using the remaining TTL when available.
- Legacy global classes and their public behavior remain unchanged during the foundation phase.
- Existing `LP_Cache` consumers are inventory-only until the user reviews migration groups, cache keys, and explicitly confirms migration work.

## Steps
- [x] Step 1: Rename `inc/cache/` to `inc/Cache/` without changing the eight legacy class files or their global class names, then update the eight bootstrap includes in `learnpress.php` and the legacy cache require in `tests/bootstrap.php` to the case-correct directory.
- [x] Step 2: Add `LearnPress\Cache\LPCache` in `inc/Cache/LPCache.php`, porting the legacy cache API needed for compatibility (`set_cache`, `get_cache`, `clear`, grouped-key helpers, capability check, and clear-all behavior) while using PSR-4 naming and project coding standards.
- [x] Step 3: Make the new class synchronize both cache layers: write to `wp_cache` first, optionally persist encoded data to `Thim_Cache_DB`, read from `wp_cache` first, fall back to valid persistent data, and repopulate `wp_cache` after a persistent hit without changing false/miss semantics.
- [x] Step 4: Add focused unit tests for PSR-4 autoloadability and WordPress object-cache set/get/miss/clear/request-local behavior; verify persistent fallback and WordPress backfill with a standalone runtime check.
- [x] Step 5: Regenerate Composer autoload files, search the repository for stale `inc/cache` path references, and run focused PHPUnit plus PHPCS on every created or modified PHP file.
- [x] Step 6: Produce the complete `LP_Cache` migration inventory without modifying consumers. Group usages into direct instances, `cache_load_first`, legacy subclasses, cache-key registries, and invalidation paths; record each current key/group and the proposed new `LPCache` API/key.
- [x] Step 7: Present the inventory and proposed cache-key conversions to the user, request any additional migration requirements, and obtain explicit confirmation before adding consumer-migration steps or changing any `LP_Cache` call site.
- [ ] Step 8: Migrate direct `new LP_Cache()` consumers to `LearnPress\Cache\LPCache`, preserving current cache keys/groups and passing the expected persistent data type to `get_cache()` where needed.
- [ ] Step 9: Replace `LP_Cache::cache_load_first()` request-local consumers with `LPCache` non-persistent cache APIs, preserving existing keys and invalidation behavior.
- [ ] Step 10: Migrate legacy domain cache subclasses and their consumers to PSR-4 cache classes, preserving child groups, key registries, and invalidation paths; retain compatibility aliases until no active consumer requires the global classes.
- [ ] Step 11: Run migration verification: regenerate autoload files, search for active `LP_Cache` usages and stale paths, lint modified PHP files, run `git diff --check`, and run focused PHPUnit/PHPCS when executables are available.

## Files to create
| File | Purpose |
|------|---------|
| `inc/Cache/LPCache.php` | PSR-4 `LearnPress\Cache\LPCache` implementation synchronized with WordPress and optional persistent cache layers |
| `tests/Unit/Cache/CacheTest.php` | Focused tests for cache-layer behavior, fallback/backfill, groups, misses, and expiration |

## Files to move unchanged
| Source | Destination | Purpose |
|--------|-------------|---------|
| `inc/cache/class-lp-cache.php` | `inc/Cache/class-lp-cache.php` | Preserve legacy `LP_Cache` |
| `inc/cache/class-lp-course-cache.php` | `inc/Cache/class-lp-course-cache.php` | Preserve legacy course cache |
| `inc/cache/class-lp-courses-cache.php` | `inc/Cache/class-lp-courses-cache.php` | Preserve legacy courses cache |
| `inc/cache/class-lp-question-cache.php` | `inc/Cache/class-lp-question-cache.php` | Preserve legacy question cache |
| `inc/cache/class-lp-quiz-cache.php` | `inc/Cache/class-lp-quiz-cache.php` | Preserve legacy quiz cache |
| `inc/cache/class-lp-session-cache.php` | `inc/Cache/class-lp-session-cache.php` | Preserve legacy session cache |
| `inc/cache/class-lp-settings-cache.php` | `inc/Cache/class-lp-settings-cache.php` | Preserve legacy settings cache |
| `inc/cache/class-lp-user-items-cache.php` | `inc/Cache/class-lp-user-items-cache.php` | Preserve legacy user-items cache |

## Files to modify
| File | Change |
|------|--------|
| `learnpress.php` | Change the nine legacy cache includes from `inc/cache/` to `inc/Cache/`; leave unrelated `inc/cache.php` unchanged |
| `tests/bootstrap.php` | Change the manual legacy `LP_Cache` require to `inc/Cache/class-lp-cache.php` |
| `composer.lock` / `vendor/composer/*` | Regenerated only if the project tracks Composer-generated autoload changes |
| `.claude/specs/rf-cache/plan.md` | Track completed steps immediately |
| `.claude/specs/rf-cache/progress.md` | Track done/next items, decisions, inventory status, and confirmation gate immediately after each step |

## LP_Cache migration inventory scope (no changes before confirmation)
The current search found 84 `LP_Cache` references across 32 PHP files. The inventory must cover:
- The eight legacy classes moved under `inc/Cache/`.
- Direct `new LP_Cache()` consumers, including model and service cache reads/writes/clears.
- Static `LP_Cache::cache_load_first()` request-local consumers in models, databases, CURDs, order/user-item logic, template hooks, REST controllers, and custom post-type handlers.
- Legacy cache subclasses (`LP_Course_Cache`, `LP_Courses_Cache`, `LP_Quiz_Cache`, `LP_Question_Cache`, `LP_Session_Cache`, `LP_Settings_Cache`, `LP_User_Items_Cache`) and their key groups/invalidation paths.
- Commented/dead references, which should be listed separately and not migrated unless requested.

## Verification
- Run `composer dump-autoload` after adding/moving PSR-4 classes.
- Run `php vendor/bin/phpunit --filter CacheTest`.
- Run `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/Cache/LPCache.php tests/Unit/Cache/CacheTest.php learnpress.php tests/bootstrap.php`.
- Search for case-sensitive stale references to `inc/cache` and confirm only the unrelated file `inc/cache.php` remains where expected.
- Confirm all eight legacy global classes still load through `learnpress.php`.

## Open questions / confirmation gates
- Before Step 1 execution: no unresolved design decision; proceed only when the user asks to start.
- After Step 6: resolved on 2026-10-15; the user explicitly approved migrating all consumers in phases: direct instances, request-local static calls, then legacy subclasses.
- Preserve existing cache keys/groups during migration unless a concrete incompatibility requires a reviewed conversion.
