---
paths:
  - "tests/**"
  - "phpunit.xml"
---

# Testing Rules — LearnPress

## Framework
- PHPUnit with Brain Monkey + Mockery (no WordPress test install needed)
- Config: `phpunit.xml` (suite `unit` → `tests/Unit`), bootstrap `tests/bootstrap.php`
- Base class: `LearnPress\Tests\Helpers\BrainMonkeyTestCase`
- Reference example: `tests/Unit/Databases/NoteDBTest.php`

## Standards
- File: `tests/Unit/<Area>/<ClassName>Test.php` mirroring the `inc/` subdirectory — PHPUnit only discovers the `*Test.php` suffix
- Namespace: `LearnPress\Tests\Unit\<Area>`; method names `test_<method>_<scenario>`
- All new Model/Service methods should have corresponding tests
- Run phpcs on new test files: `vendor/bin/phpcs --standard=phpcs.xml <file>`

## Guidelines
- Stub WordPress functions with `Brain\Monkey\Functions` (translation and escape functions are already stubbed by the base class)
- Mock dependencies with Mockery
- Test success, error and edge paths
- For DB classes, assert queries against a fake `wpdb` instead of a real database
- Run tests by file (`vendor/bin/phpunit tests/Unit/<Area>/<ClassName>Test.php`); the full suite currently has unrelated failures
