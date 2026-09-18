# Plan — optimize-css

## Steps
- [x] Step 1: Establish the intended shared contract by comparing the active frontend, admin, and block `.lp-button` / `.loading` declarations; classify base button rules, common loading rules, and context-specific overrides before moving code.
- [x] Step 2: Refactor `assets/src/scss/_buttons.scss` into the authoritative shared button partial, reusing the existing global `loading()` mixin from `_mixin.scss` and retaining `.lp-btn-edit-primary` behavior.
- [x] Step 3: Import the shared partial once at root scope in the frontend, admin, block, and course-builder compilation paths; adjust existing nested `_buttons.scss` imports so the new `.lp-button` rules cannot acquire unintended parent selectors.
- [x] Step 4: Remove the duplicate `.lp-button` / `.loading` rules and the duplicate admin `loading()` mixin from their current files, while leaving `#lp-button`, `.large`, AJAX states, hover/focus rules, `.btn-finish-course`, and other context-specific variants in their owning contexts.
- [x] Step 5: Run `gulp styles`, confirm all affected SCSS entry points compile, then inspect generated selectors for duplicate global `.lp-button` output and unintended nesting/specificity.
- [x] Step 6: Review the final diff to confirm only button consolidation/import changes were made and update the spec acceptance checkboxes and progress record.

## Files to create
| File | Purpose |
|------|---------|
| None | No new implementation files are required |

## Files to modify
| File | Change |
|------|--------|
| `assets/src/scss/_buttons.scss` | Own shared `.lp-button` base/loading rules while preserving `.lp-btn-edit-primary` |
| `assets/src/scss/frontend/_global.scss` | Remove shared declarations; retain frontend-only `.learnpress-page` and `#lp-button` behavior |
| `assets/src/scss/admin/_general.scss` | Remove duplicate loading mixin and shared loading selector |
| `assets/src/scss/admin/admin.scss` | Import `_buttons.scss` once at admin bundle root scope after mixins/variables |
| `assets/src/scss/learnpress.scss` | Import `_buttons.scss` once at frontend bundle root scope |
| `assets/src/scss/learnpress-block.scss` | Import shared buttons and remove its handwritten loading implementation |
| `assets/src/scss/wp-block/_block-global.scss` | Keep `.btn-finish-course` as a block-specific variant; remove only rules superseded by shared loading behavior |
| `assets/src/scss/course-builder.scss` | Ensure the shared partial is emitted once at root scope rather than indirectly duplicated through `frontend/global` |
| `assets/src/scss/edit-quiz.scss` | Prevent its existing nested `_buttons.scss` import from nesting shared `.lp-button` output |
| `assets/src/scss/edit-question.scss` | Prevent its existing nested `_buttons.scss` import from nesting shared `.lp-button` output |
| `assets/src/scss/edit-curriculum.scss` | Prevent its existing nested `_buttons.scss` import from nesting shared `.lp-button` output |
| `assets/src/scss/_popupSelectItemToAdd.scss` | Prevent its existing nested `_buttons.scss` import from nesting shared `.lp-button` output |
| `.claude/specs/optimize-css/spec.md` | Mark verified requirements and acceptance criteria after implementation |
| `.claude/specs/optimize-css/plan.md` | Track completed implementation steps |
| `.claude/specs/optimize-css/progress.md` | Track completion, verification, and decisions |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Verification
- Run `gulp styles` from the plugin root; the gulp task compiles every file under `assets/src/scss/**/*.scss`.
- Search generated CSS for global `.lp-button.loading` and nested variants introduced by imports.
- Compare frontend, admin, block, and course-builder button/loading behavior before and after the refactor.
- Review `git diff` restricted to the planned files.

## Open questions
- Should `.lp-button` base visual properties be global across all bundles, or should `_buttons.scss` centralize only the currently identical border, radius, padding, and loading-state rules while visual variants remain contextual? Recommended: centralize only identical rules.
- Should `#lp-button` remain under `.learnpress-page`? Recommended: yes, because the requirement targets `.lp-button` and changing the ID selector scope could alter behavior.
- Existing `_buttons.scss` imports occur inside parent selectors to scope `.lp-btn-edit-primary`. Adding `.lp-button` directly to the partial would also scope it there. Recommended: expose shared button rules through a mixin included only at bundle root, while retaining the scoped `.lp-btn-edit-primary` output, unless those editor imports can safely move to root scope after generated-CSS comparison.
