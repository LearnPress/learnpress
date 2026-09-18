# quiz-question-count

> Add "Quiz Count" column and filter/sort by quiz count for Questions admin list

## Goal
In the admin Questions list (`edit-lp_question`), show how many quizzes each question belongs to, allow sorting by that count, and optionally filter questions by a minimum quiz count. Uses JOIN to `learnpress_quiz_questions` with `COUNT(question_id) GROUP BY question_id`.

## Requirements
- [ ] Add a `quiz_count` computed field via subquery or JOIN + GROUP BY to `posts_pre_query()` in `question.php`
- [ ] Add `quiz_count` column in `QuestionsTable::get_columns()`
- [ ] Add `column_quiz_count()` renderer in `QuestionsTable`
- [ ] Register `quiz_count` as sortable in `QuestionsTable::get_sortable_columns()`
- [ ] Handle `orderby=quiz-count` in `posts_pre_query()` to ORDER BY the count value
- [ ] Set `$filter->field_count = 'p.ID'` and `$filter->group_by = 'p.ID'` when using GROUP BY to avoid ambiguous column errors

## Acceptance Criteria
- [ ] Questions list shows a "Quiz Count" column with correct count per question
- [ ] Clicking "Quiz Count" header sorts questions by number of quizzes ASC/DESC
- [ ] Questions with 0 quizzes show "0" (use LEFT JOIN so unassigned questions are included)
- [ ] No SQL errors in debug log
- [ ] Existing filters (quiz, unassigned, search, author, month) still work correctly

## Scope
- `inc/custom-post-types/question.php` — `posts_pre_query()` method
- `inc/Models/WPTables/QuestionsTable.php` — columns, sortable, renderer

## Out of scope
- Quiz list question count (already exists)
- Filter by exact quiz count value (dropdown) — only sort for now

## References
- `inc/custom-post-types/quiz.php` — reference implementation with `question_count` subquery
- `learnpress_quiz_questions` table — `quiz_id`, `question_id` columns
- `inc/Databases/DataBase.php` — `field_count`, `group_by` in query builder
