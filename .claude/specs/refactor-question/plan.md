# Plan — refactor-question

## Steps
- [x] Step 1: Add `get_answer_options( $args = [] )` and `is_selected_option()` to `QuestionPostModel` so output matches `learn_press_get_question_options_for_js()`.
- [x] Step 2: Add `check( $user_answer )` to `QuestionPostModel` and override it in `QuestionPostTrueFalseModel`, `QuestionPostSingleChoiceModel`, `QuestionPostMultipleChoiceModel`, and `QuestionPostFIBModel`.
- [x] Step 3: Update `QuestionPostModel::find()` to instantiate and return the correct type-specific subclass.
- [x] Step 4: Replace `LP_Question` usage in `inc/Models/UserItems/UserQuizModel.php`.
- [x] Step 5: Replace `learn_press_get_question()` usage in `inc/rest-api/v1/frontend/class-lp-rest-users-controller.php`.
- [x] Step 6: Run phpcs on modified files and fix any new issues.

## Files to create
| File | Purpose |
|------|---------|
| (none) | |

## Files to modify
| File | Change |
|------|--------|
| `inc/Models/Question/QuestionPostModel.php` | Add `get_answer_options()`, `check()`, `is_selected_option()`; update `find()` to return concrete subclass. |
| `inc/Models/Question/QuestionPostTrueFalseModel.php` | Override `check()` for true/false logic. |
| `inc/Models/Question/QuestionPostSingleChoiceModel.php` | Override `check()` for single-choice logic. |
| `inc/Models/Question/QuestionPostMultipleChoiceModel.php` | Override `check()` for multiple-choice logic. |
| `inc/Models/Question/QuestionPostFIBModel.php` | Override `check()` and/or `get_answer_options()` for fill-in-blanks. |
| `inc/Models/UserItems/UserQuizModel.php` | Replace `LP_Question::get_question()` / `->check()` / `->get_explanation()` calls with `QuestionPostModel::find()`; update imports. |
| `inc/rest-api/v1/frontend/class-lp-rest-users-controller.php` | Replace `learn_press_get_question()` calls in `check_answer()` with `QuestionPostModel::find()`; update imports. |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Should `QuestionPostModel::find()` always return the type-specific subclass, or only inside this refactor scope?
- How should fill-in-blanks title processing be replicated in the model layer (shortcode regex, blanks data)?
