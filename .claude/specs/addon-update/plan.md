# Implementation Plan — addon-update

## Steps
- [x] Step 1: Update `AdminAddonsPage::html_purchase_panel()` to read `purchase_code_masked`, render it as the visible input value, disable that input only when a masked code exists, add a `Clear license` button with a dedicated action, toggle Submit/Clear initial visibility with `lp-hidden`, and change the divider text to `Or`.
- [x] Step 2: Extend `AdminAddons::handleBtnAction()` to synchronize the panel whenever it opens: update mode uses the masked code and `update-purchase`; purchase mode resets to an enabled empty input and `install`.
- [x] Step 3: Handle the dedicated clear-license action without AJAX by clearing both purchase-code inputs, enabling/focusing the visible input, clearing validation state, showing Submit, and hiding Clear.
- [x] Step 4: Remove the purchase-code click event registration and `handlePurchaseCodeClick()`, retaining input synchronization through `handlePurchaseCodeInput()`.
- [x] Step 5: Run PHP and JavaScript checks, then review the diff against every acceptance criterion.
- [x] Step 6: Add a Cancel button beside Submit that appears only after `Clear license` and restores the original masked disabled state.
- [x] Step 7: Verify the follow-up Cancel markup and JavaScript state transition with PHPCS, syntax checks, and `git diff --check`.

## Files to create
| File | Purpose |
|------|---------|
| None | No production files need to be created. |

## Files to modify
| File | Change |
|------|--------|
| `inc/TemplateHooks/Admin/AdminAddonsPage.php` | Render masked/empty states, Clear action, conditional visibility, and `Or`. |
| `assets/src/js/admin/addons.js` | Synchronize opening/clearing state and remove click-to-clear behavior. |
| `.claude/specs/addon-update/plan.md` | Track implementation steps. |
| `.claude/specs/addon-update/progress.md` | Track work and verification status. |

## Verification commands
- `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/TemplateHooks/Admin/AdminAddonsPage.php`
- Read `package.json` and run the narrowest applicable JavaScript lint check; use `npm run build` only if no targeted check exists.

## Open questions
- None. `Clear license` only changes local form state; it does not send a standalone deactivation request.
