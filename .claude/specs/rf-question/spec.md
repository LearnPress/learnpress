# rf-question

> Refactor where question answers are stored and how they are read — migrate from `lp_question_answers`/`lp_question_answermeta` to the new JSON-based `lp_question_answer_config` table.

## Goal
Question answers are currently stored one row per answer in `lp_question_answers` (plus meta rows in `lp_question_answermeta`). Move to a single row per question in `lp_question_answer_config.answers` (JSON). Old data and old tables must remain untouched (bất di bất dịch — data must not be altered or lost). After a full, verified sync, code switches to read from the new table (only when `LP_KEY_DB_VERSION >= 6`), with fallback to the old table when new data is absent.

## Requirements
- [ ] New storage: question answers written/read in `lp_question_answer_config` (see `config/table/tables-v5.php:L58-L65`) instead of `lp_question_answers` (`tables-v5.php:L46-L57`) + `lp_question_answermeta` (`L66-L75`).
- [ ] Upgrade step `create_new_table_question_answer` in `inc/updates/learnpress-upgrade-6.php` creates `lp_question_answer_config`.
- [ ] Upgrade step `sync_question_answers` migrates data old → new:
  - [ ] Reads `lp_question_answers` joined with `lp_question_answermeta`, groups rows per question into an object/array.
  - [ ] Processes **50 questions per request/step run** (batch/paged, resumable).
  - [ ] Writes one row per question into `lp_question_answer_config` with `answers` = JSON.
  - [ ] Must NOT modify/delete/truncate old tables (`lp_question_answers`, `lp_question_answermeta`).
- [ ] Read logic switch (phase 2, requires explicit user confirmation before planning):
  - [ ] Only active when `LP_KEY_DB_VERSION >= 6`.
  - [ ] Read from `lp_question_answer_config`; if a question has no new-format row, fall back to old table.
  - [ ] Only started after old→new sync is complete and verified.

## Acceptance Criteria
- [ ] Running upgrade to DB v6 creates `lp_question_answer_config` and fully syncs all existing question answers into JSON rows.
- [ ] Old tables' row counts and contents are byte-identical before/after sync.
- [ ] After sync, questions (all types incl. `fill_in_blanks` `_blanks` meta) render identical answers from new table.
- [ ] With `LP_KEY_DB_VERSION < 6` or missing new row → old read path still works.
- [ ] phpcs (`--standard=phpcs.xml`) reports no new errors on changed files.

## Scope
- `inc/updates/learnpress-upgrade-6.php` — new steps
- `config/table/tables-v5.php` — table name/property for new table (verify `tb_lp_question_answer_config` exists in `DataBase`)
- `inc/Databases/DataBase.php` — register `tb_lp_question_answer_config` if missing
- `inc/Models/Question/QuestionAnswerModel.php`, `inc/Databases/QuestionAnswersDB.php` — read/write path (phase 2)
- `inc/Ajax/EditQuestionAjax.php`, `assets/src/js/admin/edit-question.js` — save/load answers (phase 2)

## Out of scope
- Dropping or altering `lp_question_answers` / `lp_question_answermeta` tables.
- Changing answer JSON payload shape beyond what old data requires (title, value, order, is_true, meta e.g. `_blanks`).
- Phase-2 read/write refactor — separate plan after user confirmation.

## References
- `config/table/tables-v5.php:L46-L75`
- `inc/updates/learnpress-upgrade-6.php` (existing step pattern: `LP_Step`, `finish_step`, `error_step`)
- `inc/Models/Question/QuestionAnswerModel.php`
- Prior batched-upgrade patterns in `inc/updates/learnpress-upgrade-*.php`
