# Plan — quiz-question-count

## Steps
- [x] Step 1: In `question.php` `posts_pre_query()`, add quiz_count subquery field: `(SELECT COUNT(*) FROM {prefix}learnpress_quiz_questions qq_count WHERE qq_count.question_id = p.ID) AS quiz_count` to `$filter->only_fields[]`
- [x] Step 2: In `question.php` `posts_pre_query()`, add `orderby === 'quiz-count'` branch → `$filter->order_by = 'quiz_count'`; also set `$filter->field_count = 'p.ID'` to prevent ambiguous COUNT in total query
- [x] Step 3: In `QuestionsTable`, add `quiz_count` column to `get_columns()`, add `quiz-count` to `get_sortable_columns()`, add `column_quiz_count()` renderer
- [ ] Step 4: Verify no SQL errors — test with sorting, filtering by quiz, unassigned, search

## Files to create
| File | Purpose |
|------|---------|
| (none) | |

## Files to modify
| File | Change |
|------|--------|
| `inc/custom-post-types/question.php` | Add quiz_count subquery field + orderby handling |
| `inc/Models/WPTables/QuestionsTable.php` | Add column, sortable, renderer |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- None
