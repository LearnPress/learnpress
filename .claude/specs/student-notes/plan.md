# Plan — student-notes

## Steps
- [x] Step 1: Table — `tb_lp_notes` in `DataBase.php`, `create_table_notes()` + `LP_Settings::is_created_tb_notes()` guard in `class-lp-install.php`
- [x] Step 2: `NoteFilter`, `NoteDB` (get_notes, stats counts), `NoteModel` (find / save / delete / get_anchor) + tests
- [x] Step 3: `NoteService` — can_create, can_manage (owner), can_view (owner/admin/course author), validate payload + tests
- [x] Step 4: `NoteAjax` — note_list / note_save / note_delete; register in `learnpress.php` AJAX catch list
- [x] Step 5: `CourseNoteTemplate` — launcher icon, panel markup on `wp_footer`, localize data; only on lesson items, setting enabled
- [x] Step 6: JS panel — list / add text note / edit / delete via `window.lpAJAXG`, Toastify messages
- [x] Step 7: JS highlight — selection → floating "Add Note", build anchor, re-anchor + render `<mark>`, orphan handling, AJAX item switch
- [x] Step 8: Read-only view for admin/instructor via `?lp_note_user=`
- [ ] Step 9: Backend "Student Notes" page — stats, `html_form_filter()` + `html_tom_select()`, `TableListTemplate`, `html_pagination()`
- [ ] Step 10: Cleanup hooks, GDPR exporter/eraser, enable setting
- [ ] Step 11: SCSS, `npm run build`, PHPCS, PHPUnit, manual test (classic + modern layout)

## Files to create
| File | Purpose |
|------|---------|
| `inc/Databases/NoteDB.php` | Queries for notes table |
| `inc/Filters/NoteFilter.php` | Query criteria |
| `inc/Models/Note/NoteModel.php` | Note entity |
| `inc/Services/NoteService.php` | Permissions + business rules |
| `inc/Ajax/NoteAjax.php` | Frontend AJAX handlers |
| `inc/TemplateHooks/Course/CourseNoteTemplate.php` | Launcher + panel |
| `inc/TemplateHooks/Admin/AdminStudentNotesTemplate.php` | Backend page |
| `assets/src/js/frontend/course-notes.js` | Panel + highlight logic |
| `assets/src/scss/frontend/course-notes.scss` | Styles (compiled to `assets/css/frontend/course-notes.css`) |
| `assets/src/js/frontend/course-notes-anchor.js` | Anchor create/locate/wrap helpers |
| `assets/src/scss/frontend/_footer-launchers.scss` | Shared launcher wrapper styles (moved from ai-assistant.scss) |
| `tests/Unit/Databases/NoteDBTest.php`, `tests/Unit/Models/NoteModelTest.php`, `tests/Unit/Services/NoteServiceTest.php` | Tests (repo uses `*Test.php` — PHPUnit only discovers that suffix) |

## Files to modify
| File | Change |
|------|--------|
| `inc/Databases/DataBase.php` | Add `$tb_lp_notes` |
| `inc/class-lp-install.php` | Create table |
| `inc/Databases/class-lp-db.php` | Add `$tb_lp_notes` (used by LP_Install) |
| `config/table/tables-v4.php` | Notes table schema |
| `inc/class-lp-settings.php` | `is_created_tb_notes()` |
| `learnpress.php` | Register `NoteAjax::catch_lp_ajax()`, init templates |
| `inc/admin/class-lp-admin-menu.php` | "Student Notes" submenu |
| `webpack.config.js` | New JS entry `assets/js/dist/frontend/course-notes` |
| `inc/class-lp-assets.php` | Register `lp-course-notes` style + script |
| `assets/src/js/frontend/ai-assistant.js` | `lp-footer-panel:open` event so only one panel is open |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Instructor submenu capability: which cap gates the page for `lp_teacher` role? (check existing instructor-accessible admin pages)
