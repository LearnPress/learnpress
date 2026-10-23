---
name: spec-new
description: Start a new LearnPress feature or fix spec at .claude/specs/<slug>/spec.md. Use when the user asks to create, write or start a spec.
argument-hint: <feature-slug> <short description>
disable-model-invocation: true
---

# Skill: New Spec

Arguments: `$ARGUMENTS` — first word is the feature slug (kebab-case), the rest is the description.

## Process
1. If `.claude/specs/<slug>/` already exists, stop: suggest `/spec-plan <slug> <change>` to add to it, or `/spec-resume <slug>` to continue
2. Explore the code the feature touches (follow `CLAUDE.md` and `.claude/rules/`) so Scope names real files, classes, methods and hooks
3. Ask the user about anything that changes the design (permissions, data model, scope); do not guess on those. Record each answer under "Decisions" in spec.md
4. Write `.claude/specs/<slug>/spec.md` from the template below; fill **Branch** with the current branch (or the branch the user names)
5. Stop and ask the user to review. Next step: `/spec-plan <slug>`
   - Small fix (≤ 3 steps, no design questions): offer to run the `/spec-plan` process right away so spec, plan and progress are created together

Do not write code in this step.

## Template — spec.md
```markdown
# <slug>

> One-line summary.

**Branch:** `<branch>`
**Created:** YYYY-MM-DD

## Goal
What the user can do after this ships, and why.

## Requirements
- [ ] ...

## Acceptance Criteria
- [ ] ...
- [ ] Code passes PHPCS and the PHPUnit tests

## Scope
- `path/to/file.php` — what changes

## Out of scope
- ...

## Decisions
- ...

## Changes
<!-- Added by /spec-plan when requirements change after the spec was approved: YYYY-MM-DD — what changed and why -->

## References
- Mockups, reports, pattern references (existing classes to follow)
```
