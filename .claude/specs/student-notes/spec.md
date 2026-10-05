# student-notes

> Let enrolled students highlight lesson text and save plain-text notes; let admins and course instructors review them.

## Goal
On the learning page, a student selects text inside a lesson and clicks a floating "Add Note" button to save a highlight with an optional note. A Notes panel (opened from the right-side launcher icon) lists, edits and deletes the student's notes for the current lesson and also supports notes that are not tied to a highlight. In the backend, a "Student Notes" page lets admins and course instructors review students' notes.

## Scope (phase 1)
- **Lesson only** (`lp_lesson`). Quiz support is deferred; keep the schema ready for it (`item_type`, `anchor.scope`).
- Note content is **plain text** only (max 5000 chars, `sanitize_textarea_field`).

## Data model — table `{prefix}learnpress_notes`
```sql
CREATE TABLE IF NOT EXISTS {prefix}learnpress_notes (
  note_id        bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id        bigint(20) unsigned NOT NULL,
  course_id      bigint(20) unsigned NOT NULL,
  item_id        bigint(20) unsigned NOT NULL,
  item_type      varchar(45)  NOT NULL DEFAULT 'lp_lesson',
  note_type      varchar(20)  NOT NULL DEFAULT 'text',   -- text | highlight
  content        longtext     NULL,                      -- plain text note
  highlight_text text         NULL,                      -- snapshot of selected text
  anchor         longtext     NULL,                      -- JSON, highlight only
  created_at     datetime     NOT NULL,
  updated_at     datetime     NULL DEFAULT NULL,
  PRIMARY KEY (note_id),
  KEY user_item (user_id, item_id),
  KEY user_course (user_id, course_id),
  KEY course_id (course_id),
  KEY item_id (item_id),
  KEY created_at (created_at)
) $collation;
```

`anchor` JSON (W3C Web Annotation style):
```json
{
  "scope": "lesson_content",
  "quote": { "exact": "...", "prefix": "...(<=32 chars)", "suffix": "...(<=32 chars)" },
  "position": { "start": 412, "end": 698 }
}
```
Re-anchoring: try `position` and verify against `quote.exact` → else search `exact`, disambiguating with `prefix`/`suffix` → else the note is **orphaned** (shown in the panel with `highlight_text`, no highlight drawn).

## Permissions
| Action | Who |
|---|---|
| Create note | Logged-in student who is enrolled in (or has finished) `course_id`, and the lesson is assigned to that course. Lesson preview / guests: not allowed. |
| Edit / delete note | Note owner only (same enrollment check as create). |
| View note (frontend) | Note owner; admin (`manage_options`); instructor of the note's course (`CourseModel::check_user_is_author()`, which also covers co-instructors via `learn-press/course/is-author`). |
| View notes (backend page) | Admin: all notes. Instructor: only notes whose `course_id` is a course they author/co-instruct. |

Frontend view for admin/instructor: they are not enrolled, so they cannot create notes. They see a student's notes **read-only** when opening the lesson from the backend "Open Lesson" link (`?lp_note_user={user_id}#lp-note-{note_id}`). The server re-checks permission for `lp_note_user`.

## Requirements
- [ ] Create the `learnpress_notes` table on install/upgrade (explicit guard, like the webhooks table)
- [ ] `NoteDB` / `NoteFilter` / `NoteModel`, using the Model/DB pattern with no raw `$wpdb` in the model
- [ ] `NoteService` for permission checks, validation and the create/update/delete business rules
- [ ] `NoteAjax` (via `AbstractAjax`, nonce `wp_rest`): `note_list`, `note_save`, `note_delete`
- [ ] Notes launcher icon in `learn-press/course-item-footer-launchers`, shown only on lesson items
- [ ] Notes panel showing: help box, "Add Note" button, editor (Cancel / Save Note), and a list of note cards (type, date, content, Delete / Edit Note)
- [ ] Selection handling in `.content-item-description.lesson-description`: show the floating "Add Note" button, save the anchor, and render a `<mark class="lp-note-hl" data-note-id>` highlight
- [ ] Clicking a highlight focuses its card in the panel, and clicking a card scrolls to its highlight
- [ ] Works when the lesson is loaded or switched via AJAX
- [ ] Backend "Student Notes" submenu with stats (total notes, students with notes, courses with notes), filters (student and course via Tom Select), server-side search, a sortable table, pagination and an "Open Lesson" link
- [ ] Clean up notes on `deleted_user` and on `before_delete_post` (course, lesson)
- [ ] GDPR personal data exporter and eraser
- [ ] Setting to enable or disable Notes (`AdminTemplate::html_toggle_enable()`)

## Acceptance Criteria
- [ ] An enrolled student can highlight lesson text, add, edit and delete notes, and the highlights persist after reload
- [ ] A non-enrolled user or guest gets no "Add Note" button, and the AJAX endpoint rejects their request
- [ ] A student cannot read, edit or delete another student's notes (verified via AJAX with a forged `note_id`)
- [ ] An instructor sees only the notes for their own courses in the backend, and an admin sees all notes
- [ ] Editing the lesson content does not break existing notes; non-matching highlights become orphaned but stay listed
- [ ] All output is escaped, and the code passes PHPCS and the PHPUnit tests

## Out of scope
- Quiz notes / highlights
- Rich text notes, note sharing between students, and REST API endpoints
- Instructor editing or deleting student notes

## References
- Mockups: learning page with the Notes panel, and the admin "Student Notes" page (provided in chat 2026-10-05)
- Pattern references: `WebhookDB`, `WebhookFilter`, `WebhookModel`, `CourseAIAssistantTemplate` (launcher hook), `TableListTemplate`
