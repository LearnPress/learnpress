# Progress — student-notes

**Status:** 🟡 In progress
**Started:** 2026-10-05
**Last updated:** 2026-10-06

## Done
- Spec + DB design agreed
- Step 1: table `learnpress_notes` (tables-v4 config + `LP_Install::create_table_notes()` runtime guard + `LP_Settings::is_created_tb_notes()`)
- Step 2: `NoteFilter`, `NoteDB` (get_notes, get_note, get_stats, insert/update, delete_notes, delete_notes_by), `NoteModel` (validate, sanitize_anchor, find/query/save/delete, get_user_item_notes)
  - Tests: `NoteModelTest` (15), `NoteDBTest` (6) — pass; PHPCS clean
  - Verified on Local MySQL: table + indexes auto-created, CRUD + stats round trip OK

## In progress

## Next
- Step 3: `NoteService` (permissions) + tests
- Step 4: `NoteAjax`

## Decisions made
- Custom table `learnpress_notes` (not usermeta / comments)
- Phase 1: lesson only; schema keeps `item_type` + `anchor.scope` for quiz later
- Create: enrolled (or finished) students only; no guests / preview
- Edit/delete: owner only
- View: owner, admin, instructor (author/co-instructor) of the note's course — both frontend and backend
- Note content: plain text, max 5000 chars
- Anchor: quote (exact/prefix/suffix) + position; orphan fallback
- Anchor quote strings are not tag-stripped (only invalid UTF-8 removed) so they match lesson text exactly; JS must only use them as text, never as HTML
- `@since 4.4.9.2` for new code
- Frontend for admin/instructor = read-only view of a student's notes via `?lp_note_user=` (assumed, user said "continue")

## Blockers / Notes
- Full PHPUnit suite on develop already fails (fatal `Cannot redeclare learn_press_get_order()` + some errors) — unrelated; run note tests by file
