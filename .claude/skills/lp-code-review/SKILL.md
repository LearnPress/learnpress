---
name: lp-code-review
description: Review LearnPress PHP/JS changes against WordPress Coding Standards and the project's architecture and security rules. Use before merging, after implementing a feature, or when refactoring.
argument-hint: "[files | branch]"
context: fork
agent: code-reviewer
---

Review the LearnPress changes in: $ARGUMENTS

If no target is given above, review the uncommitted changes (`git diff` and `git diff --cached`).
Follow your process and checklist, and return the report in your output format.
