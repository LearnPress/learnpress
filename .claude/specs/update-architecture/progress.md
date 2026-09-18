# Progress — update-architecture

**Status:** 🟢 Complete
**Started:** 2026-09-18
**Last updated:** 2026-09-18

## Done
1. Planned the initial `update-architecture` task.
2. Removed the obsolete `inc/Rest/` entries after verifying that the directory does not exist.
3. Added matching `assets/src/js/` conventions to both architecture guidance documents.
4. Replanned the remaining work against the expanded specification.
5. Added the four PHP directory entries to the Architecture list in `CLAUDE.md`.
6. Added matching directory roles and focused placement rules to `.claude/rules/architecture.md`.
7. Verified both documents against all acceptance criteria.

## In progress
- None.

## Next
- None.

## Decisions made
- Limit implementation to architecture documentation in `CLAUDE.md` and `.claude/rules/architecture.md`.
- Remove `inc/Rest/` from both architecture documents because the directory does not exist.
- Describe `inc/Ajax/` as AJAX request handlers, `inc/Filters/` as query filter/value objects, `inc/TemplateHooks/` as hook-based template rendering/composition, and `inc/Helpers/` as reusable shared utilities.
- Preserve the concise directory-list style already used by both documents.
- Do not change PHP implementation files.
- Architecture lists only include existing directories.
- JavaScript in `assets/src/js/` uses vanilla JavaScript and reuses applicable exports from `utils.js`.
- Toastify notifications import from `lpToastify.js`.
- LearnPress AJAX consumers use the pre-enqueued `window.lpAJAXG` and do not import `loadAJAX.js`.

## Blockers / Notes
- None. All acceptance criteria passed.
