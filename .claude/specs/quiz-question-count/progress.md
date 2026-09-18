# Progress — quiz-question-count

**Status:** ✅ Done
**Started:** 2026-09-18
**Last updated:** 2026-09-18

## Done
1. Step 1: Added `quiz_count` subquery to `$filter->only_fields` + `field_count = 'p.ID'`
2. Step 2: Added `orderby === 'quiz-count'` branch in `posts_pre_query()`
3. Step 3: Added `quiz_count` column, sortable, and `column_quiz_count()` renderer in `QuestionsTable`

## In progress

## Next
- Step 4: Manual verification — test sort/filter in admin

## Decisions made
- Used subquery pattern (same as quiz's `question_count`) instead of JOIN + GROUP BY — simpler, no GROUP BY needed, existing filters unaffected

## Blockers / Notes
