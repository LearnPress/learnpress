# rf-cache

> Refactor the LearnPress cache foundation toward a PSR-4 Cache class while preserving backward compatibility.

## Goal
Keep existing cache consumers working, normalize the cache directory name, and introduce a PSR-4 Cache class whose cache operations remain synchronized with the WordPress object cache. Inventory and confirm the later LP_Cache migration separately before changing existing consumers.

## Requirements
- [ ] Preserve all legacy cache classes so code not yet migrated continues to work.
- [ ] Rename `inc/cache` to `inc/Cache`.
- [ ] Find and update every include or path reference from `/cache` to `/Cache`.
- [ ] Add a PSR-4 Cache class based on `inc/cache/class-lp-cache.php`.
- [ ] Ensure the new class's `set_cache` and `get_cache` behavior stays synchronized with `wp_cache`.
- [ ] Do not migrate existing `LP_Cache` consumers during the foundation phase.
- [ ] After the foundation phase, list all existing `LP_Cache` usages and identify required migration work, including cache-key changes.
- [ ] Ask for confirmation and additional migration requirements before changing any `LP_Cache` consumer.

## Acceptance Criteria
- [ ] Existing legacy cache classes remain available after the directory rename.
- [ ] No active include or path reference still expects `inc/cache`.
- [ ] The new Cache class follows the project's PSR-4 conventions and autoloads successfully.
- [ ] Cache values written and read through the new class remain consistent with the WordPress object cache.
- [ ] Relevant formatting, static checks, and focused tests pass.
- [ ] A complete `LP_Cache` usage inventory is presented before consumer migration begins.
- [ ] No `LP_Cache` consumer migration starts without explicit user confirmation.

## Scope
- `inc/cache/`
- `inc/Cache/`
- LearnPress autoload configuration
- Include and path references to `inc/cache`
- Tests covering the new Cache class and WordPress object-cache synchronization
- Existing `LP_Cache` usage inventory

## Out of scope
- Migrating existing `LP_Cache` call sites before review and confirmation
- Unrelated cache behavior or performance changes
- Unrelated refactors

## References
- `inc/cache/class-lp-cache.php`
