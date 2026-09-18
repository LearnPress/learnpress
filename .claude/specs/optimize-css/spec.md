# optimize-css

> Consolidate reusable `.lp-button` and `.lp-button.loading` styles in `_buttons.scss` and import them from the relevant SCSS bundles.

## Goal
Centralize shared LearnPress button styles to remove duplicate declarations while preserving the current frontend, admin, and block behavior.

## Requirements
- [x] Move shared `.lp-button` declarations into `assets/src/scss/_buttons.scss`.
- [x] Move shared `.lp-button.loading` behavior into `assets/src/scss/_buttons.scss`.
- [x] Import the shared button partial from each relevant SCSS entry point.
- [x] Remove only duplicate declarations made redundant by the shared partial.
- [x] Preserve context-specific button variants and existing visual behavior.
- [x] Avoid unrelated SCSS cleanup.

## Acceptance Criteria
- [x] Shared `.lp-button` and loading styles have one authoritative definition in `_buttons.scss`.
- [x] Frontend, admin, course-builder, and block bundles that need the styles include them exactly once.
- [x] Context-specific selectors such as `.lp-button.btn-finish-course` continue to work.
- [x] Generated selectors do not gain unintended nesting or specificity.
- [x] All affected SCSS entry points compile without errors.
- [x] No unrelated files or styles are changed.

## Scope
- `assets/src/scss/_buttons.scss`
- `assets/src/scss/frontend/_global.scss`
- `assets/src/scss/admin/_general.scss`
- `assets/src/scss/admin/admin.scss`
- `assets/src/scss/wp-block/_block-global.scss`
- `assets/src/scss/learnpress.scss`
- `assets/src/scss/learnpress-block.scss`
- `assets/src/scss/course-builder.scss`
- Other existing SCSS entry points only where required to consume the shared partial

## Out of scope
- Redesigning button appearance or loading animation.
- Renaming `.lp-button`, `#lp-button`, or variant classes.
- Refactoring unrelated button classes or SCSS architecture.
- Changing JavaScript or PHP behavior.

## References
- Existing `loading()` mixin in `assets/src/scss/_mixin.scss`.
- Existing `_buttons.scss` imports in editor-related SCSS files.
