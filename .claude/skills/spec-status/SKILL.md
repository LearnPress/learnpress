---
name: spec-status
description: Show the status of LearnPress specs in .claude/specs/ (done steps, next step, blockers). Use when the user asks about spec or feature progress, or what to work on next.
argument-hint: "[feature-slug]"
allowed-tools: Read, Glob, Grep, Bash(git branch:*), Bash(git status:*), Bash(git log:*)
---

# Skill: Spec Status

Arguments: `$ARGUMENTS` — optional feature slug. Read-only: do not edit any file.

## Without a slug
One row per folder in `.claude/specs/`, most recently updated first:

| Spec | Status | Steps done | Branch | Last updated | Next action |
|------|--------|------------|--------|--------------|-------------|

- Steps done = checked / total checkboxes under the step lists of `plan.md`
- Next action = "Resume here" in `progress.md`, otherwise the first unchecked step
- Below the table: the current git branch, and the suggested command (`/spec-resume <slug>`, `/spec-plan <slug>` when `plan.md` is missing)

## With a slug
Report:
- Status, branch, last updated date
- Steps done / total, and the remaining steps
- "Resume here" (next action, half-done work, uncommitted changes) and recent Log entries
- Open questions, blockers, Acceptance Criteria still unchecked
- Whether the current branch matches the spec's branch

## Consistency checks
Point out: a spec without `plan.md` or `progress.md`; status ✅ with unchecked steps (or the reverse); progress files without "Resume here"; **Last updated** older than 14 days while 🟡 In progress.
