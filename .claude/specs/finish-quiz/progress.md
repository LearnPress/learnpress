# Progress — finish-quiz

**Status:** 🟢 Done
**Started:** 2026-09-09
**Last updated:** 2026-09-09

## Done
1. Created the feature specification.
2. Inspected the scoped controller, user-item models, result persistence model, no-required-enrollment flow, and quiz-finished hook consumer.
3. Produced the concrete implementation and verification plan.
4. Implemented `UserQuizModel::finish_quiz()` with model validation, instant-check answer merging, elapsed-time handling, completion state, and `UserItemResultModel` persistence.
5. Preserved the four-argument quiz-finished hook and REST-compatible result payload.
6. Refactored the enrolled `submit_quiz()` flow to resolve current models and delegate to `UserQuizModel::finish_quiz()`.
7. Preserved the no-required-enrollment flow while switching branch detection to `CourseModel::has_no_enroll_requirement()`.
8. Verified both modified PHP files with `php -l` and project PHPCS; both pass.
9. Added `UserQuizModel::check_can_finish()` and updated `finish_quiz()` to use its filterable `WP_Error` validation result.

## In progress
- Step 5: Focused automated coverage remains pending.

## Next
- Add a database-backed test harness for `UserQuizModel::finish_quiz()` once the unit-suite bootstrap conflict is resolved.

## Decisions made
- `finish_quiz()` accepts answers and elapsed seconds and returns the finalized result array.
- `UserItemResultModel` replaces direct `LP_User_Items_Result_DB` result access in the enrolled completion flow.
- The `learn-press/user/quiz-finished` hook keeps its existing four-argument shape with `UserQuizModel` as the fourth argument.
- `CourseModel::has_no_enroll_requirement()` selects the guest flow; the legacy guest adapter remains isolated there because no enrolled user-quiz record exists.
- `check_can_finish()` validates user, course, quiz, enrollment, and current result record before quiz completion.
- Consumers can customize finish permission through `learn-press/user/can-finish-quiz`.
- No project-root `CLAUDE.md` exists; the plan follows patterns found in the scoped source files.

## Blockers / Notes
- `php vendor/bin/phpunit --testsuite unit` exits 255 in the existing `RefundPolicyTest`: `learn_press_get_order()` is redeclared between Brain Monkey's function stub and `inc/order/lp-order-functions.php`.
- `git diff --check` passes.
