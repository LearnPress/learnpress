# Progress — refactor-question

**Status:** 🟡 In progress
**Started:** 2026-09-09
**Last updated:** 2026-09-09

## Done
1. Created spec folder and initial spec/plan/progress files.
2. Audited `LP_Question` / `learn_press_get_question()` usages in target files.
3. Finalized concrete implementation plan in `plan.md`.
4. Added `get_answer_options()` and `is_selected_option()` to `QuestionPostModel`.
5. Added `check()` method to `QuestionPostModel` and overrides in `QuestionPostTrueFalseModel`, `QuestionPostSingleChoiceModel`, `QuestionPostMultipleChoiceModel`, `QuestionPostFIBModel`.
6. Updated `QuestionPostModel::find()` to return the concrete type-specific subclass.
7. Replaced `LP_Question` usage in `UserQuizModel.php`.
8. Replaced `learn_press_get_question()` usage in `class-lp-rest-users-controller.php`.
9. Ran phpcs on all modified files — no new errors remain.

## In progress

## Next

## Status
🟢 Completed

## Decisions made
- Keep existing `QuestionPostModel::get_answer_option()` (singular) untouched for backward compatibility.
- Make `QuestionPostModel::find()` return the concrete type-specific subclass so `check()` can be overridden naturally by type.

## Blockers / Notes
- Fill-in-blanks question processing relies on shortcode parsing and `_blanks` answer meta; dedicated overrides were added in `QuestionPostFIBModel`.
