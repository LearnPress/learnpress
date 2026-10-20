# addon-update

> Refine the add-on purchase-code input and license-clearing behavior.

## Goal
Make the add-on license form clearly distinguish between an existing masked purchase code and an editable empty purchase-code field.

## Requirements
- [ ] Add a `Clear license` action to the purchase panel.
- [ ] When `purchase_code_masked` is not empty, show the masked code in a disabled input, hide Submit with `lp-hidden`, and show `Clear license`.
- [ ] When `purchase_code_masked` is empty, enable the input, show Submit, and hide `Clear license` with `lp-hidden`.
- [ ] Change the purchase-panel divider text from `Don't have a code?` to `Or`.
- [ ] Clicking `Clear license` clears the visible purchase-code input and hidden `purchase-code` input, enables the visible input, hides `Clear license`, and shows Submit.
- [ ] Remove the behavior that clears a masked purchase code when the purchase-code input itself is clicked.
- [x] After `Clear license`, show a Cancel button beside Submit; clicking it restores the masked disabled input and `Clear license` state.

## Acceptance Criteria
- [ ] Opening license management with a saved purchase code displays its masked value in a disabled input.
- [ ] With a saved purchase code, Submit is hidden and `Clear license` is visible.
- [ ] Clicking `Clear license` switches the panel to the editable empty state without clicking the input.
- [ ] Without a saved purchase code, the input is enabled, Submit is visible, and `Clear license` is hidden.
- [ ] Clicking the purchase-code input does not clear its value.
- [ ] The divider reads `Or`.
- [ ] PHP and JavaScript checks pass for the modified files.
- [x] Cancel after clearing restores the original masked purchase code, hides Submit and Cancel, and shows `Clear license`.

## Scope
- `inc/TemplateHooks/Admin/AdminAddonsPage.php`
- `assets/src/js/admin/addons.js`

## Out of scope
- Server-side license activation/deactivation behavior.
- Add-on installation and update request handling unrelated to purchase-code UI state.
- Styling changes beyond existing utility classes.

## References
- `AdminAddonsPage::html_purchase_panel()`
- `AdminAddons::handleBtnAction()`
- `AdminAddons::handlePurchaseCodeClick()`
- `AdminAddons::handlePurchaseCodeInput()`
