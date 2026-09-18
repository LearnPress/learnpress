# refactor-question

> Replace legacy `LP_Question` usage in `UserQuizModel` and the frontend users REST controller with `QuestionPostModel`, adding any missing model methods following the existing model standard.

## Goal

Move quiz/question runtime logic away from the legacy `LP_Question` class toward the new `QuestionPostModel` hierarchy (`inc/Models/Question/`), so `UserQuizModel::calculate_quiz_result()` and `LP_REST_Users_Controller::check_answer()` use model objects instead of the old class.

## Requirements

- [ ] Find all `LP_Question` / `learn_press_get_question()` usages in the target files.
- [ ] Replace them with `QuestionPostModel::find()` (returning the correct type-specific subclass).
- [ ] Add missing methods to `QuestionPostModel` and subclasses so the replacement code has equivalent behaviour:
  - `get_answer_options( $args = [] )` compatible with `learn_press_get_question_options_for_js()`.
  - `check( $user_answer )` returning `['correct' => bool, 'mark' => float]`.
  - Helper `is_selected_option()` if needed.
- [ ] Update `QuestionPostModel::find()` so it returns the concrete subclass (`QuestionPostTrueFalseModel`, `QuestionPostSingleChoiceModel`, `QuestionPostMultipleChoiceModel`, `QuestionPostFIBModel`, or filtered custom type).
- [ ] Keep existing `get_answer_option()` (singular) behaviour intact.

## Acceptance Criteria

- [ ] `UserQuizModel.php` no longer imports or calls `LP_Question`.
- [ ] `class-lp-rest-users-controller.php` no longer calls `learn_press_get_question()` for answer checking.
- [ ] Quiz result calculation and instant answer checking still work for `true_or_false`, `single_choice`, `multi_choice`, and `fill_in_blanks`.
- [ ] `learn_press_get_question_options_for_js()` continues to receive compatible answer option data from the model.
- [ ] `phpcs --standard=phpcs.xml` reports no new errors in modified files.

## Scope

- `inc/Models/UserItems/UserQuizModel.php`
- `inc/rest-api/v1/frontend/class-lp-rest-users-controller.php`
- `inc/Models/Question/QuestionPostModel.php`
- `inc/Models/Question/QuestionPostTrueFalseModel.php`
- `inc/Models/Question/QuestionPostSingleChoiceModel.php`
- `inc/Models/Question/QuestionPostMultipleChoiceModel.php`
- `inc/Models/Question/QuestionPostFIBModel.php`

## Out of scope

- Refactoring frontend templates that render questions.
- Changing the database schema or the legacy `LP_Question` class itself.
- Other controllers still using `LP_Question`.

## References

- `inc/question/class-lp-question.php`
- `inc/question/class-lp-question-*.php`
- `inc/lp-template-functions.php` (`learn_press_get_question_options_for_js`)
