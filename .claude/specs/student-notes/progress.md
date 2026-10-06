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

- Step 5–8: `CourseNoteTemplate` (Singleton; launcher in `learn-press/course-item-footer-launchers`, panel on `wp_footer`, JS data via inline script in `render_panel()` because LP registers handles at `wp_enqueue_scripts` priority 1000), `course-notes.js` + `course-notes-anchor.js`, `course-notes.scss`
  - Browser-tested on Local (Playwright): selection → Add Note → highlight saved; highlight across `<em>` (2 marks); text note with newlines; empty note blocked; edit; delete (SweetAlert) restores identical HTML; cancel removes pending mark; reload re-anchors; stale position re-located via prefix; orphan shown with message; `#lp-note-{id}` opens + focuses; Esc closes; wide screens push `#popup-content`/header/footer instead of covering; mobile full width; admin `?lp_note_user=2` read-only; quiz page + guest → nothing rendered
  - Test notes + temporary session tokens removed afterwards

- Step 9: `AdminStudentNotesTemplate` (LearnPress → Student Notes): stats (follow filters), GET filters (student/course/type via `html_tom_select` + search over note/quote/student/email/course/lesson), sortable columns (whitelisted, `note_id` tie-break), `html_pagination`, expandable content (`<details>`), "Open Lesson" → `?lp_note_user=&#lp-note-`
  - Scope: admin = all; instructor = authored courses + `learn-press/note/viewable-course-ids` filter (co-instructor); forcing another course → no rows
  - Filter options are built server-side from notes in scope (admin-only REST search endpoints would 403 / leak for instructors)
  - `NoteDB`: `join_details` (users u, courses c, items l), `get_note_users()`, `get_note_courses()`
  - Tests: NoteDBTest 8, NoteServiceTest 22, NoteModelTest 18 — pass; browser + CLI verified; test data removed

## In progress

## Next
- Step 10: cleanup hooks, GDPR, setting `enable_student_notes` (already read by `NoteService::is_enabled()`, default yes)

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
- Notes buttons do not use `.lp-button` (learnpress.css overrides their colors)
- Opening Notes closes AI Assistant and vice versa (`lp-footer-panel:open` document event)
- Frontend for admin/instructor = read-only view of a student's notes via `?lp_note_user=` (assumed, user said "continue")
- Admin/instructor cannot edit or delete student notes (view only)
- Edit keeps item/type/anchor; only content changes
- Note text output must always be escaped (`esc_html` / `textContent`); a lone "<" is stored as-is, "x<y" style input is stored as "x&lt;y"

## Blockers / Notes
- Built assets are not tracked in git: run `npm run build` (+ `npm run start`/dev build when LP debug is on, it loads non-min files) and `gulp styles && gulp mincss`
- Not in phase 1: "View All" (all notes of the course) from the mockup header
- Full PHPUnit suite on develop already fails (fatal `Cannot redeclare learn_press_get_order()` + some errors) — unrelated; run note tests by file
