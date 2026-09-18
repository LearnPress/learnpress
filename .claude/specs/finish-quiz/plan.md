# Plan — finish-quiz

## Steps
- [x] Step 1: Define and implement `UserQuizModel::finish_quiz( array $answered, int $time_spend ): array`; validate the related `UserModel`, `CourseModel`, `QuizPostModel`, and `UserCourseModel`, merge previously persisted instant-check answers, set `end_time`, calculate the result, set passed/failed graduation and completed status, then persist the user item and result through `UserItemResultModel`.
- [x] Step 2: Preserve completion integrations inside `finish_quiz()` by firing `learn-press/user/quiz-finished` with the existing four arguments and returning the REST-ready result fields: `status`, `attempts`, `answered`, and `results`.
- [x] Step 3: Refactor the enrolled-course branch of `LP_REST_Users_Controller::submit_quiz()` to resolve `UserModel`, `CourseModel`, `QuizPostModel`, `UserCourseModel`, and `UserQuizModel`, then delegate completion to `finish_quiz()`; remove enrolled-flow calls to `learn_press_get_user()`, `learn_press_get_course()`, `learn_press_get_quiz()`, `$user->get_course_data()`, and the legacy `$user_quiz` object.
- [x] Step 4: Keep the no-required-enrollment branch behavior-compatible, but detect it with `CourseModel::has_no_enroll_requirement()`; isolate any unavoidable legacy `LP_Course_No_Required_Enroll` adapter usage to that guest branch only.
- [ ] Step 5: Add focused unit coverage for successful pass/fail completion, persisted instant-check answer merging, invalid user/course/quiz/enrollment/user-quiz states, result persistence, and hook argument compatibility. Blocked: these database-backed static models have no existing focused harness, and the unit suite currently fatals in `RefundPolicyTest` due to redeclaring `learn_press_get_order()`.
- [x] Step 6: Run PHP syntax checks, focused tests, and PHPCS for each modified PHP file; fix only violations introduced by this refactor. PHP syntax and PHPCS pass; the unit suite command was run but is blocked by the unrelated existing fatal recorded in Step 5.

## Files to create
| File | Purpose |
|------|---------|
| Focused test file under `tests/Unit/` (exact location selected in Step 5 to match existing test organization) | Verify `UserQuizModel::finish_quiz()` and the refactored submission flow |

## Files to modify
| File | Change |
|------|--------|
| `inc/Models/UserItems/UserQuizModel.php` | Add `finish_quiz()`, use `UserItemResultModel` for current-attempt result read/write, and centralize completion state and hook dispatch |
| `inc/rest-api/v1/frontend/class-lp-rest-users-controller.php` | Resolve current model classes and delegate enrolled quiz submission to `UserQuizModel::finish_quiz()` |
| `.claude/specs/finish-quiz/plan.md` | Track completed implementation steps |
| `.claude/specs/finish-quiz/progress.md` | Track current status, decisions, and next actions after every completed step |

## Verification commands
- `php -l inc/Models/UserItems/UserQuizModel.php`
- `php -l inc/rest-api/v1/frontend/class-lp-rest-users-controller.php`
- Run the focused PHPUnit test command determined from the existing test setup.
- `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/Models/UserItems/UserQuizModel.php`
- `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/rest-api/v1/frontend/class-lp-rest-users-controller.php`

## Decisions
- `finish_quiz()` accepts submitted answers and elapsed seconds, owns the enrolled-user completion transaction, and returns the finalized result array.
- The latest `UserItemResultModel` for the user item is authoritative for instant-check answer merging and result persistence.
- The completion hook retains `( $item_id, $course_id, $user_id, $userQuizModel )`; the registered webhook consumer supports a model object as its fourth argument.
- `CourseModel::has_no_enroll_requirement()` replaces legacy course inspection for branch selection.
- Guest/no-required-enrollment completion remains outside `UserQuizModel::finish_quiz()` because it has no enrolled `UserQuizModel` record.

## Open questions
- None blocking implementation.
- The project root has no `CLAUDE.md`; planning therefore follows the existing model and controller patterns discovered in scope.
