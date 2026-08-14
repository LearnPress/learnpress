# Plan — fix-security

## Steps
- [x] Step 1: Add capability-specific permission checks in `action()` method before sensitive operations
- [x] Step 2: Implement `install_plugins` capability check for install/update actions
- [x] Step 3: Implement `activate_plugins` capability check for activate action
- [x] Step 4: Ensure checks work correctly for both single-site and multisite installations
- [ ] Step 5: Test the fix manually or create test cases
- [x] Step 6: Run PHPCS to ensure code standards compliance
- [ ] Step 7: Verify single-site installations remain unaffected

## Files to create
| File | Purpose |
|------|---------|
| | |

## Files to modify
| File | Change |
|------|--------|
| `inc/rest-api/v1/frontend/class-lp-rest-addon-controller.php` | Add capability checks in `action()` method before calling install/update/activate |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Should capability checks be in `permission_callback()` or inside `action()` method? (Decision: Inside `action()` for action-specific checks)
- Should `deactivate` action also check `activate_plugins` capability? (Decision: No, deactivate is less sensitive)
