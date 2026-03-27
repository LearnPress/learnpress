Scaffold a new feature spec workspace for LearnPress.

## Input
$ARGUMENTS = "<feature-slug> [short description]"
- feature-slug: kebab-case, e.g. `refactor-material-db`
- short description (optional): one-line summary

## What to do

1. Create folder: `.claude/specs/<feature-slug>/`

2. Create `.claude/specs/<feature-slug>/spec.md` with this template:

```markdown
# <feature-slug>

> <short description>

## Goal
<!-- What problem does this solve? What is the expected outcome? -->

## Requirements
<!-- Functional requirements — what must work when this is done -->
- [ ]

## Acceptance Criteria
<!-- How to verify it's done correctly -->
- [ ]

## Scope
<!-- Files / areas of code this touches. List known files if any. -->
- `inc/...`

## Out of scope
<!-- What is explicitly NOT part of this feature -->

## References
<!-- Related files to read, issues, prior art -->
```

3. Create `.claude/specs/<feature-slug>/plan.md` with this template:

```markdown
# Plan — <feature-slug>

## Steps

- [ ] Step 1: ...
- [ ] Step 2: ...

## Files to create
| File | Purpose |
|------|---------|
| | |

## Files to modify
| File | Change |
|------|--------|
| | |

## Open questions
<!-- Things to clarify before or during implementation -->
-
```

4. Create `.claude/specs/<feature-slug>/progress.md` with this template:

```markdown
# Progress — <feature-slug>

**Status:** 🟡 In progress
**Started:** <today's date>
**Last updated:** <today's date>

## Done
<!-- Completed steps — move items here from Next when done -->

## In progress
<!-- What is currently being worked on -->

## Next
<!-- Immediate next actions -->
- Start with step 1 from plan.md

## Decisions made
<!-- Key decisions and why — so future sessions don't relitigate them -->

## Blockers / Notes
<!-- Anything that slowed down or needs attention -->
```

5. After creating all files, print:
```
Spec created at .claude/specs/<feature-slug>/

Next steps:
1. Fill in spec.md — define requirements and acceptance criteria
2. Run /plan-spec <feature-slug> to generate the implementation plan
3. Run /resume <feature-slug> when ready to start coding
```