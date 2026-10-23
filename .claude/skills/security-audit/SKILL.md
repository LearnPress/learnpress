---
name: security-audit
description: Audit LearnPress code for security vulnerabilities following WordPress security best practices. Use before releases, and when code handles user input, AJAX, REST endpoints or file operations.
argument-hint: "[files | directory]"
context: fork
agent: security-reviewer
---

Audit for security vulnerabilities: $ARGUMENTS

If no target is given above, audit the uncommitted changes (`git diff --name-only` and `git diff --name-only --cached`).
Follow your process and checklist, and return the report in your output format.
