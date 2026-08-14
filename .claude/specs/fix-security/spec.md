# fix-security

> Fix LearnPress 4.4.2 Multisite authorization bypass vulnerability

## Goal
Fix a security vulnerability where a WordPress Multisite Site Administrator (not Super Administrator) can use the LearnPress add-on REST endpoint to install and activate network-shared WordPress plugins despite lacking the core `install_plugins` and `activate_plugins` capabilities.

## Requirements
- [ ] Prevent non-Super Administrators from installing network-shared plugins via LearnPress add-on REST endpoint
- [ ] Prevent non-Super Administrators from activating network-shared plugins via LearnPress add-on REST endpoint
- [ ] Implement proper capability checks before invoking WordPress plugin installation and activation primitives
- [ ] Ensure authorization checks align with WordPress core security requirements

## Acceptance Criteria
- [ ] Site Administrators without `install_plugins` capability cannot install plugins via the add-on endpoint
- [ ] Site Administrators without `activate_plugins` capability cannot activate plugins via the add-on endpoint
- [ ] Super Administrators can still perform these actions as expected
- [ ] Existing functionality for single-site installations remains unaffected
- [ ] Code passes PHPCS standards

## Scope
- `inc/rest-api/v1/frontend/class-lp-rest-addon-controller.php` - Main file containing the vulnerable add-on REST endpoint
- `action()` method (lines 140-213) - Contains switch cases for:
  - `install` (line 159) - Calls `$this->lp_addons->install()` without checking `install_plugins` capability
  - `activate` (line 191) - Calls `$this->lp_addons->activate()` without checking `activate_plugins` capability
  - `update` (line 160) - Calls `$this->lp_addons->update()` without checking `install_plugins` capability
- `permission_callback()` method (lines 51-53) - Only checks `administrator` role, missing capability-specific checks

## Out of scope
- Changes to WordPress core functionality
- Changes to other LearnPress features unrelated to add-on management
- UI changes (this is a backend security fix)

## References
- Security report from Charles Research
- WordPress Multisite capability documentation
- LearnPress 4.4.2 REST API implementation
