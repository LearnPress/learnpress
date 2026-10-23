---
name: spec-resume
description: Continue work on a planned LearnPress spec — the next plan step, or a fix/change the user describes — and keep plan.md and progress.md up to date for the next session. Use when the user asks to resume, continue or fix something in a spec.
argument-hint: <feature-slug> [what to fix or do]
---

# Skill: Resume Spec

Arguments: `$ARGUMENTS` — first word is the feature slug; the rest (optional) is a specific fix or task. If no slug: pick the only spec with status 🟡 In progress, otherwise list them and ask.

## 1. Restore context
1. Read `progress.md` first ("Resume here", "Decisions", latest Log entries), then `plan.md`, then only the parts of `spec.md` the next step needs. If `plan.md` is missing, suggest `/spec-plan <slug>`
2. Check the workspace:
   - `git branch --show-current` — if it differs from **Branch**, stop and ask before editing
   - `git status --short` — compare with "Uncommitted / unverified"; mention unexpected changes
   - `git log --oneline --since=<Last updated>` — commits made since the last session
3. Respect everything under "Decisions" — do not re-open those decisions
4. Summarize in ≤ 5 lines: done steps / total, where the last session stopped, what you will do now

## 2. Pick the work
- With a task in the arguments: if it fits an unchecked step, do that step. If it is new (bug, change request, follow-up), add it to `plan.md` as described in `/spec-plan` Mode B (new Phase, next step number; update spec.md "Changes" if requirements change), then do it. Ask first only when the change contradicts "Decisions" or the spec
- Without a task: do the "Next action" from "Resume here", otherwise the first unchecked step

## 3. Implement
Follow `CLAUDE.md` and `.claude/rules/`:
- phpcs on each new/changed PHP file: `vendor/bin/phpcs --standard=phpcs.xml <file>`
- Add or update tests (`.claude/rules/testing.md`) and run them by file
- `composer dump-autoload` after adding PSR-4 classes

## 4. Record progress (after each step, and always before ending your turn)
- Tick finished steps in `plan.md` (`- [x]`)
- `progress.md`:
  - Rewrite "Resume here": exact next action, half-done work (file + what is left), uncommitted or unverified changes
  - Add a Log entry (newest first, ≤ 5 lines): what was done and how it was verified (tests, phpcs, browser)
  - Add new decisions and user requests to "Decisions"; blockers to "Blockers / Notes"
  - Set **Status** (🟡 In progress / ✅ Done / 🔴 Blocked) and **Last updated** to today
- Continue with the next step unless it needs a user decision; ask in that case

## 5. Finish
When all steps are done: verify the Acceptance Criteria in `spec.md`, tick the ones that are verified, update `spec.md` where the implementation deliberately differs, and set Status ✅ Done. Later fixes reopen the spec through step 2.

Older progress files with a "Done / In progress / Next" layout: keep their history, but add "Resume here" and "Log" on the first update instead of extending "Done".
