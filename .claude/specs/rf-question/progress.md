# Progress — rf-question

**Status:** 🟡 In progress
**Started:** 2026-10-20
**Last updated:** 2026-10-21

## Done
1. Spec scaffold created (spec.md, plan.md, progress.md).
2. Plan refined after code exploration (`/plan-spec`).
3. Step 1: `create_new_table_question_answer` step added to `LP_Upgrade_6` (CREATE TABLE with `question_id` KEY added).
4. Step 2: `sync_question_answers` step added — batch 50 questions/run, resumable via `NOT IN` skip, meta joined + `maybe_unserialize`, JSON into `answers` column. Old tables read-only.
5. Step 3: phpcs clean on `inc/updates/learnpress-upgrade-6.php`.
6. Step 4: Manual verification on dev DB — confirmed by user.
7. `question_answer_id` dropped from synced JSON (user-confirmed).
8. Phase 2A shared legacy-to-config synchronization implemented: DB-v6 gate, deterministic order, persisted meta decoding, existing/new config save, empty-set handling and cache clearing.
9. Phase 2A inventory items 1–7 wired: AJAX add/update/delete, default answers, MCP answer sync, sample answers and CURD duplicate answers/meta. Legacy IDs and public read paths unchanged.
10. Phase 2A static verification: PHP syntax passes for all five changed PHP files; four files phpcs clean. Legacy CURD retains the same pre-existing one error/two warnings (confirmed against HEAD). Isolated helper checks pass for DB < 6 gate, FIB/custom meta, omitted legacy IDs, existing/empty config and save failure propagation.

## In progress
- Phase 2A integration/manual verification for all seven entry points, permissions and failure injection remains pending.
- Run the Phase 2A manual verification checklist on a dev WordPress DB (v6 and below-v6 paths), including FIB, MCP, sample installation and duplication.
- After verification, obtain user direction for the new-first read switch and remaining inventory items 8–28.

## Next
- User confirms which write sites (legacy CURD, clone, sample data, MCP) get the v6 switch, then wire `LP_KEY_DB_VERSION >= 6` into read/write paths.

## Decisions made
- JSON shape: flat array `[{title, value, order, is_true, meta?}]` — `question_answer_id` intentionally dropped (user-confirmed); new table is read by `question_id`, answers identified within JSON. Meta only included when present.
- Re-run strategy: `LEFT JOIN ... IS NULL` selects next 50 unsynced `question_id`s — idempotent without offset paging.
- `KEY question_id` added to new-table DDL (not in tables-v5 definition) to index the join/lookup column.
- `tb_lp_question_answer_config` already registered in `DataBase.php` — no change needed there.
- Batch pattern follows `learnpress-upgrade-5.php::sync_table_courses()` (`page`/`total_pages`, `percent`).
- Old tables (`lp_question_answers`, `lp_question_answermeta`) are read-only during sync — never modified.
- Batch size: 50 questions per step run.
- Phase 2 (read/write switch, gated by `LP_KEY_DB_VERSION >= 6` with old-table fallback) requires explicit user confirmation before planning.
- Phase 2A preserves legacy writes and saves one complete new-table snapshot after each business operation. New-table writes are gated by DB v6 support.
- Approved `QuestionAnswerConfigModel` was reused unchanged; no permission bypass added.
- Duplication failures now return `WP_Error`; `LP_Question_CURD::duplicate()` forwards it before firing the success hook.
- Public answer readers remain legacy-based in this batch to retain AJAX/MCP answer identity.

## Blockers / Notes
- No live WordPress DB writes or UI integration tests were run in this session. Runtime permissions, especially sample installation and clone ownership, require verification.
- Dual-write is not transactional: a failed config save may leave completed legacy writes; errors surface, but no rollback/retry policy is implemented.
- Legacy CURD phpcs baseline: MultipleStatementAlignment warning, DisallowMultipleAssignments error, EmptyPHPStatement warning; unrelated code left unchanged.
- Existing meta helpers may return false for either unchanged data or failure; full failure-injection coverage remains pending.
- Open questions in plan.md (JSON shape, idempotent re-run strategy) to resolve at Step 2.
