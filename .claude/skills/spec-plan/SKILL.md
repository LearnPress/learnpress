---
name: spec-plan
description: Create the implementation plan (plan.md + progress.md) for a LearnPress spec, or add steps to an existing plan for a change request, bug or follow-up. Use when the user asks to plan a spec or add to its plan.
argument-hint: <feature-slug> [change to add]
disable-model-invocation: true
---

# Skill: Plan Spec

Arguments: `$ARGUMENTS` — first word is the feature slug; the rest (optional) is a change to add. If empty, list `.claude/specs/` and ask which one.

Read `.claude/specs/<slug>/spec.md` first; if it is missing, suggest `/spec-new <slug> ...`.

## Mode A — New plan (no `plan.md` yet)
1. Explore the code to find existing patterns to reuse (Model/DB/Filter/Service/Ajax/TemplateHooks, `AdminTemplate`/`Template` helpers, `.claude/rules/architecture.md`)
2. Write `plan.md` from the template: small, ordered steps that each end in a verifiable state; each step names its own verification (test file, phpcs, browser check) — not only at the end
3. Create `progress.md` from the template with today's date, the spec's branch and "Resume here" pointing at Step 1
4. Show the plan summary and open questions; wait for approval before implementing (`/spec-resume <slug>`)

## Mode B — Add to an existing plan (`plan.md` exists)
Use for change requests, bugs found after a step was done, or follow-up work.
1. Read `plan.md` and `progress.md` (respect "Decisions"); explore only the code the change touches
2. If the change alters requirements, append a dated line to "Changes" in `spec.md` and update Requirements / Acceptance Criteria
3. Append a new section to `plan.md` — never renumber or rewrite done steps:
   ```markdown
   ## Phase N — <short title> (YYYY-MM-DD)
   > Why: <change request / bug / follow-up>
   - [ ] Step <next number>: ... — verify: ...
   ```
   Add new files to the Files tables
4. In `progress.md`: set **Status** to 🟡 In progress, rewrite "Resume here" to the first new step, add a Log line
5. Show the added steps; wait for approval before implementing

## Template — plan.md
```markdown
# Plan — <slug>

## Steps
- [ ] Step 1: ... — verify: `tests/Unit/<Area>/<Class>Test.php`, phpcs
- [ ] Step 2: ... — verify: ...

## Files to create
| File | Purpose |
|------|---------|

## Files to modify
| File | Change |
|------|--------|

## Verification
- Each new/changed PHP file: `vendor/bin/phpcs --standard=phpcs.xml <file>` (auto-fix with `vendor/bin/phpcbf`)
- Tests: `vendor/bin/phpunit tests/Unit/<Area>/<ClassName>Test.php`

## Open questions
- ...
```

## Template — progress.md
```markdown
# Progress — <slug>

**Status:** ⚪ Not started
**Branch:** `<branch>`
**Started:** YYYY-MM-DD
**Last updated:** YYYY-MM-DD

## Resume here
<!-- Rewritten at the end of every session. Read this first. -->
- **Next action:** Step 1 — ...
- **Half-done work:** none
- **Uncommitted / unverified:** none

## Decisions
- ...

## Blockers / Notes
- ...

## Log
<!-- Newest first. One entry per session or step: what was done + how it was verified. Keep each entry ≤ 5 lines. -->
- YYYY-MM-DD — Plan created
```
`plan.md` checkboxes are the source of truth for which steps are done; do not repeat the step list in `progress.md`.
