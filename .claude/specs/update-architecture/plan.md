# Plan — update-architecture

## Steps
- [x] Step 1: Update the `CLAUDE.md` Architecture list with concise entries for `inc/Ajax/`, `inc/Filters/`, `inc/TemplateHooks/`, and `inc/Helpers/`, preserving the existing label-and-path style.
- [x] Step 2: Update `.claude/rules/architecture.md` Directory Structure with matching roles for the four PHP directories, and add only focused placement rules needed to distinguish request handling, query criteria, template composition, and shared utilities.
- [x] Step 3: Verify both documents list the four PHP directories, omit `inc/Rest/`, retain matching JavaScript guidance, render as valid Markdown, and contain no unrelated changes.

## Completed work
- [x] Removed the obsolete `inc/Rest/` entries after verifying that the directory does not exist.
- [x] Documented `assets/src/js/` as vanilla JavaScript source in both guidance documents.
- [x] Documented reuse of `utils.js`, Toastify imports from `lpToastify.js`, and use of pre-enqueued `window.lpAJAXG` without importing `loadAJAX.js`.

## Files to create
| File | Purpose |
|------|---------|
| None | No files are created during implementation |

## Files to modify
| File | Change |
|------|--------|
| `CLAUDE.md` | Add concise architecture entries for Ajax, Filters, TemplateHooks, and Helpers |
| `.claude/rules/architecture.md` | Add matching directory roles and focused layer-placement rules |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- None.
