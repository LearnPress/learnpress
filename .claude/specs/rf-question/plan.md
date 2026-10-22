# Plan — rf-question

## Steps
- [x] Step 1: Add step `create_new_table_question_answer` to `LP_Upgrade_6`
  - Register `LP_Step` in `$this->group_steps` (before `finish_upgrade`).
  - Method runs `CREATE TABLE IF NOT EXISTS` for `tb_lp_question_answer_config` (same DDL as `config/table/tables-v5.php:L58-L65`), then `check_execute_has_error()` + `finish_step()`.
  - No `DataBase.php` change needed — `tb_lp_question_answer_config` is already registered (lines 29, 66).
- [x] Step 2: Add step `sync_question_answers` to `LP_Upgrade_6`
  - Signature `sync_question_answers( array $data ): LP_Step` — follows `learnpress-upgrade-5.php::sync_table_courses()` batch pattern (`page`/`total_pages`, `percent`, `status='success'` to continue).
  - Batch: 50 **questions** per run (distinct `question_id` ordered ASC, paged).
  - Read: `SELECT` from `lp_question_answers` for the 50 `question_id`s + `SELECT` from `lp_question_answermeta` for those `question_answer_id`s. Group per question → array of answer objects `{question_answer_id, title, value, order, is_true, meta: {meta_key: meta_value}}`.
  - Write: one row per question into `lp_question_answer_config` (`question_id`, `answers` = `wp_json_encode`); skip `question_id`s already present (idempotent/resumable). Old tables are SELECT-only — never written.
  - `finish_step()` when the last page is done.
- [x] Step 3: phpcs — `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/updates/learnpress-upgrade-6.php`
- [x] Step 4: Manual verification — run upgrade on dev DB: per-question counts match, JSON decodes, `fill_in_blanks` `_blanks` meta present, old tables byte-identical.

## Phase 2A — implementation scope: inventory items 1–7
Plan changes for the first six business entry points plus `LP_Question_CURD::duplicate_answer()` (item 7). Items 8–28 are not implementation scope in this batch, except reusing existing persistence APIs. Implementation completed on 2026-10-21; integration/manual verification remains pending.

### Shared synchronization contract
- [x] ✅ Add one shared synchronization method on `QuestionPostModel` (proposed name: `sync_answers_config_from_legacy()`) for these seven callers.
  - Gate new-table access with `QuestionAnswerConfigModel::is_support()`; below DB v6, retain legacy-only behavior because the new table is not guaranteed to exist.
  - Query the complete persisted legacy answer set directly through `QuestionAnswersDB`, ordered by `order` and a deterministic ID tie-breaker; do not use the new-first public reader to rebuild JSON, since its config may be stale.
  - Load persisted answer meta, preserving all meta keys and unserializing values; include FIB `_blanks` after its final write.
  - Build `[{title, value, order, is_true, meta?}]`; do not include legacy `question_answer_id` in JSON. An empty set must save `[]`, not leave a stale config or remove the row and unintentionally enable fallback.
  - Find the existing config by `question_id`, or create a model with that question ID; call `set_answers()` and `save()` through the approved `QuestionAnswerConfigModel`.
  - Synchronize once after the complete business operation, not after every answer in a loop. Do not add automatic synchronization to `QuestionAnswerModel::save/delete/save_meta_value_by_key` in this batch.
  - Propagate synchronization failures: AJAX/MCP must not return success; default/sample creation must surface or log the failure through their existing error handling. Do not swallow failures or claim rollback; legacy writes may already have completed.
  - Verify execution identity/capabilities for every caller, especially sample creation; do not bypass permissions or modify the approved config model without confirmation.

### 1. AJAX add answer — `EditQuestionAjax::add_question_answer()`
- [x] ✅ Keep validation, legacy `QuestionAnswerModel::save()`, generated value and order calculation unchanged.
- [x] ✅ After the legacy insert succeeds, synchronize the whole question configuration once, before setting the success response.
- [x] ✅ Preserve the response's legacy `question_answer_id` and payload shape so existing JS keeps working.

### 2. AJAX update answers — `EditQuestionAjax::update_question_answers_config()`
- [x] ✅ Keep existing legacy saves for normal question types; synchronize once after the entire answer loop.
- [x] ✅ For FIB, synchronize only after both answer content and `save_meta_value_by_key( META_KEY_BLANKS, ... )` finish, so JSON contains the final `_blanks` meta.
- [x] ✅ Preserve sanitization, conversion and existing request/response contracts; report failure before returning success.

### 3. AJAX delete answer — `EditQuestionAjax::delete_question_answer()`
- [x] ✅ Keep lookup by legacy answer ID and the model's permission/minimum-answer checks.
- [x] ✅ Retain the question ID before deletion; after legacy deletion succeeds, synchronize all remaining persisted answers once before returning success.
- [x] ✅ Rejected deletions must not alter the new config. Preserve existing legacy deletion behavior; broader orphan-meta cleanup is not part of this batch.

### 4. Default answers — `QuestionPostModel::create_default_answers()`
- [x] ✅ Keep the legacy insert loop and returned answer IDs unchanged.
- [x] ✅ Synchronize once after all default answers have been persisted, not inside the loop; propagate failures to the caller.

### 5. MCP — `QuestionTools::sync_answers()`
- [x] ✅ Preserve existing validation, legacy answer-ID matching, insert/update/reorder operations and removal of omitted answers.
- [x] ✅ Synchronize once after all writes and deletions succeed; propagate synchronization errors through the MCP error path.

### 6. Sample data — `SampleDataService::create_question_answers()`
- [x] ✅ Preserve the existing generated answer data and legacy inserts; check their results before treating the operation as successful.
- [x] ✅ After the complete legacy insert loop, resolve the question model and invoke the shared synchronization method once.

### 7. Duplicate answers — `LP_Question_CURD::duplicate_answer()`
- [x] ✅ Preserve legacy answer inserts for `$question_id_clone` and copy all answer meta to the newly generated legacy answer IDs.
- [x] ✅ Check both answer and meta insert results; do not report a successful clone when either persistence or config synchronization fails.
- [x] ✅ After all destination answers and meta have been copied successfully, resolve the destination `QuestionPostModel` and invoke the shared synchronization method once for `$question_id_clone`.
- [x] ✅ Build destination JSON from the persisted destination legacy data, not the source config; preserve title, value, order, is_true and all meta, including FIB `_blanks`. Never modify the source question/config.
- [x] ✅ Reuse the DB-v6 gate; below v6 keep legacy duplication without querying the config table.
- [x] ✅ Duplication logs failures and returns `WP_Error`; `duplicate()` forwards it before its success hook. Success return behavior remains unchanged.

### Verification for items 1–7
- [x] ✅ PHP syntax passes for all five changed files; phpcs clean on four, legacy CURD has the same pre-existing one error/two warnings as HEAD. No new violations.
- [x] ✅ Isolated helper smoke checks: below-v6 gate, decoded FIB/custom meta, no legacy IDs in JSON, existing config reuse, empty array save and save error propagation. No live DB writes performed.

### Files planned for this batch
- `inc/Models/Question/QuestionPostModel.php` — shared legacy-to-config synchronization and default-answer call site.
- `inc/Ajax/EditQuestionAjax.php` — synchronize after successful add/update/delete operations.
- `inc/MCP/Domain/QuestionTools.php` — synchronize after the complete answer-set mutation.
- `inc/Services/SampleDataService.php` — synchronize after sample answer creation and check write failures.
- `inc/curds/class-lp-question-curd.php` — synchronize destination config after complete answer/meta duplication and review failure propagation.
- Reuse approved `QuestionAnswerConfigModel`, existing DB/filter APIs; no JS changes or new files planned.

## Phase 2 — read/write switch (draft for review)
- [ ] Step 5: Read path — `QuestionPostModel::get_answer_option()` / `get_answer_options()`
  - Gate: `get_option( LP_KEY_DB_VERSION ) >= 6`.
  - If `lp_question_answer_config` has row for `question_id` → `json_decode( answers )` → build `QuestionAnswerModel` objects (map `meta` → `meta_data`).
  - Else → existing old-table path (fallback, unchanged).
- [x] Step 6: Write path — `EditQuestionAjax` (implemented in Phase 2A; manual verification pending)
  - All operations must preserve legacy answer/meta writes and synchronize the complete JSON configuration through `QuestionAnswerConfigModel::save()`.
  - `update_question_answers_config()`: update legacy answers/meta and save the resulting whole `answers` JSON row per question. FIB case: single answer + `_blanks` inside `meta`.
  - `add_question_answer()`: create the legacy answer and synchronize the complete configuration, preserving answer ordering.
  - `delete_question_answer()`: retain legacy answer identification/deletion and the min-2-answers rule (`check_valid_before_delete`), then synchronize the remaining answers to the new configuration. Do not require per-answer legacy IDs inside JSON.
- [ ] Step 7: `QuestionAnswersDB::get_last_number_order()` — read from JSON row when new-format exists, else old query.
- [ ] Step 8: `QuestionAnswerModel::save/delete/find/save_meta_value_by_key` — preserve legacy persistence and ID-based compatibility; plan shared synchronization through `QuestionAnswerConfigModel` for answer/meta mutations, avoiding duplicate synchronization across callers.
- [ ] Step 9: phpcs + manual test: edit answers for all question types (single/multi/true-false/FIB), verify both legacy answers/meta and new JSON stay synchronized after create/update/delete/reorder/meta changes; verify new-row reads, missing-row fallback and legacy behavior below DB v6.
- **`QuestionAnswerModel::find($id)`** — retain legacy ID-based compatibility; determine how consumers using new-format reads obtain any required legacy identity without adding per-answer IDs to JSON.
- **Frontend (non-admin) JS** (`edit-question.js` vs quiz-taking templates) — which render paths consume `get_answer_options()`?
- **`question_answer_ids` filter field / `lp_question_answermeta` readers** elsewhere (CURD `class-lp-question-curd.php`, `class-lp-question.php`) — inventory consumers and apply new-first/fallback reads where in scope; legacy storage remains synchronized, not frozen.

## Open questions (phase 2)
- **Read-model identity:** determine how new-first public readers preserve legacy ID-based editing contracts without adding per-answer IDs to JSON.
- **Failure recovery:** define any retry/repair policy beyond visible errors for partial dual-writes before claiming transactional consistency.
- **Remaining scope:** inventory items 8–28 require separate user direction after items 1–7.

## Files to create
| File | Purpose |
|------|---------|
| (none) | |

## Files to modify
| File | Change |
|------|--------|
| `inc/updates/learnpress-upgrade-6.php` | Add `create_new_table_question_answer` + `sync_question_answers` steps; register in `group_steps` |
| (phase 2, after confirm) `inc/Models/Question/QuestionAnswerModel.php` | Read/write new table with old fallback |
| (phase 2, after confirm) `inc/Databases/QuestionAnswersDB.php` | Query new table |
| (phase 2, after confirm) `inc/Ajax/EditQuestionAjax.php`, `assets/src/js/admin/edit-question.js` | Save/load answer payloads |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Frontend readers and legacy CURD/meta readers outside items 1–7 remain for subsequent planning. Dual-write and new-first/missing-row fallback are mandatory, not open decisions.
