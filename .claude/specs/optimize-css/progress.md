# Progress — optimize-css

**Status:** ✅ Complete
**Started:** 2026-09-16
**Last updated:** 2026-09-16

## Done
1. Created the feature spec workspace.
2. Audited active `.lp-button` and `.loading` declarations across frontend, admin, block, and course-builder SCSS.
3. Mapped main bundle imports and existing nested `_buttons.scss` imports.
4. Confirmed `_mixin.scss` already provides the canonical `loading()` mixin and admin `_general.scss` duplicates it.
5. Confirmed `gulp styles` compiles every SCSS file under `assets/src/scss/**/*.scss`.
6. Prepared the ordered implementation and verification plan.
7. Classified shared rules: the reusable contract is the `.lp-button` border/radius/padding base plus the centered disabled loading state using the canonical `loading()` mixin; frontend states, `#lp-button`, block finish-course styling, and other variants remain contextual.
8. Added opt-in `lp-button-styles()` to `_buttons.scss`, preserving scoped `.lp-btn-edit-primary` output.
9. Emitted shared button styles once at root scope in frontend, admin, block, and course-builder bundles.
10. Removed duplicate frontend/admin/block loading rules, the duplicate admin `loading()` mixin, and the old root `_general.scss` button base.
11. Ran `npx gulp styles` successfully.
12. Verified each target bundle emits exactly one root `.lp-button.loading` selector and no nested loading selector.
13. Reviewed the scoped diff and confirmed context-specific button variants remain in place.

## In progress

## Next
- None.

## Decisions made
- Keep `#lp-button` and frontend-only states in `frontend/_global.scss` unless implementation evidence shows they are safely shareable.
- Reuse `loading()` from `_mixin.scss`; remove the duplicate admin mixin during implementation.
- Preserve `.lp-button.btn-finish-course` as a block-specific variant.
- Treat nested `_buttons.scss` imports as a specificity risk that must be resolved before adding emitted global selectors.
- Centralize only declarations proven shared; do not redesign button visuals.
- Implement shared `.lp-button` output through a mixin from `_buttons.scss` so existing scoped imports continue emitting only their intended `.lp-btn-edit-primary` rules.
- Verification passed for `learnpress.css`, `admin/admin.css`, `learnpress-block.css`, and `course-builder.css`.

## Blockers / Notes
- No blockers.
- The working tree contains unrelated pre-existing user changes; they were not modified as part of this spec.
