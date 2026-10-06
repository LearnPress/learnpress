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

- Step 3: `NoteService` (Singleton) — can_create / check_can_create (reason), can_manage (owner + still enrolled), can_view (owner/admin/course author via `check_user_is_author`), get_item_notes, save_note (update changes content only; owner forced to current user), delete_note. Filters: `learn-press/note/can-create|can-manage|can-view`
  - Tests: `NoteServiceTest` (19) with a FakeNoteService double
- Step 4: `NoteAjax` — `lp_note_list`, `lp_note_save`, `lp_note_delete` (registered in `learnpress.php`); JSON `data` read raw (`wp_check_invalid_utf8`) so quotes match lesson text
  - E2E on Local via `NoteAjax::catch_lp_ajax()`: create/list/edit/delete OK; other user / not enrolled / guest / lesson not in course / bad nonce / admin edit → rejected; admin list → OK with `can_edit:false`
- Fix: plain text sanitizing (`NoteModel::sanitize_plain_text`) keeps lone "<" but never decodes into a tag (security review finding); `highlight_text` always derived from anchor quote

## In progress

## Next
- Step 5: `CourseNoteTemplate` (launcher + panel)
- Step 6–7: JS panel + highlight

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
- Admin/instructor cannot edit or delete student notes (view only)
- Edit keeps item/type/anchor; only content changes
- Note text output must always be escaped (`esc_html` / `textContent`); a lone "<" is stored as-is, "x<y" style input is stored as "x&lt;y"

## Blockers / Notes
- Full PHPUnit suite on develop already fails (fatal `Cannot redeclare learn_press_get_order()` + some errors) — unrelated; run note tests by file
