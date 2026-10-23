---
name: test-writer
description: Write PHPUnit unit tests (Brain Monkey + Mockery) for LearnPress classes and functions. Use when creating Model/DB/Service classes, fixing bugs (regression tests), or refactoring.
argument-hint: "<class | file | method>"
context: fork
agent: test-writer
---

Write PHPUnit unit tests for: $ARGUMENTS

If no target is given above, cover the Model/DB/Service methods added or changed in `git diff`.
Follow your process, run the tests and phpcs, and report the tests added with the final result.
