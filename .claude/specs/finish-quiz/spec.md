# finish-quiz

> Refactor quiz submission to finish quizzes through the new LearnPress models.

## Goal
Move quiz-finishing business logic from the REST controller into `UserQuizModel::finish_quiz()` and replace legacy user, course, quiz, and user-item objects with the current model classes.

## Requirements
- [ ] Add `UserQuizModel::finish_quiz()` following the model-oriented pattern used by `UserQuizModel::start_quiz()`.
- [ ] Make `finish_quiz()` calculate and persist the quiz result, set completion state and graduation, and dispatch the quiz-finished action.
- [ ] Refactor `LP_REST_Users_Controller::submit_quiz()` to call `UserQuizModel::finish_quiz()`.
- [ ] Replace `learn_press_get_user()`, `learn_press_get_course()`, `learn_press_get_quiz()`, `LP_User_Course`, and `LP_User_Item_Quiz` usage in the enrolled-course flow with `UserModel`, `CourseModel`, `QuizPostModel`, `UserCourseModel`, and `UserQuizModel`.
- [ ] Preserve the existing no-required-enrollment flow unless equivalent model support is available and required.
- [ ] Preserve instant-check answers, submitted time spent, REST response shape, attempts, completion status, and existing hooks.
- [ ] Log caught internal errors through `LP_Debug` where appropriate.

## Acceptance Criteria
- [ ] Submitting an enrolled-course quiz uses the new model classes only.
- [ ] `submit_quiz()` delegates quiz completion business logic to `UserQuizModel::finish_quiz()`.
- [ ] Quiz results are calculated and saved against the correct user item.
- [ ] Passed and failed graduation values remain correct.
- [ ] The user quiz is marked completed and the existing `learn-press/user/quiz-finished` action still fires with compatible arguments.
- [ ] The REST success response remains compatible, including `status`, `attempts`, `answered`, `results`, and nested result data.
- [ ] The no-required-enrollment behavior remains functional.
- [ ] Modified PHP files pass the project PHPCS standard without new violations.

## Scope
- `inc/Models/UserItems/UserQuizModel.php`
- `inc/rest-api/v1/frontend/class-lp-rest-users-controller.php`
- Related model APIs and tests needed to verify quiz submission behavior.

## Out of scope
- Changing quiz scoring rules.
- Changing REST routes or response contracts.
- Refactoring unrelated REST controller methods.
- Removing legacy classes outside the quiz submission flow.

## References
- `UserQuizModel::start_quiz()`
- `LP_REST_Users_Controller::submit_quiz()`
- `UserModel`
- `CourseModel`
- `QuizPostModel`
- `UserCourseModel`
- `UserQuizModel`
