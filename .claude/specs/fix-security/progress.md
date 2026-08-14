# Progress — fix-security

**Status:** 🟡 In progress
**Started:** 2026-07-20
**Last updated:** 2026-07-20

## Done
1. Add capability-specific permission checks in `action()` method before sensitive operations
2. Implement `install_plugins` capability check for install/update actions
3. Implement `activate_plugins` capability check for activate action
4. Ensure checks work correctly for both single-site and multisite installations
5. Run PHPCS to ensure code standards compliance

## In progress

## Next
- Step 6: Test the fix manually or create test cases
- Step 7: Verify single-site installations remain unaffected

## Decisions made
- Capability checks should be inside `action()` method for action-specific checks
- `deactivate` action does not need `activate_plugins` capability check (less sensitive)
- `current_user_can()` function works correctly for both single-site and multisite installations

## Blockers / Notes
