# Skill: Test Writer

## Description
Write PHPUnit tests for LearnPress plugin classes and functions.

## When to use
- When creating new Model/Service classes
- When fixing bugs (regression tests)
- When refactoring existing code

## Guidelines
- Use `WP_UnitTestCase` for integration tests
- Mock external dependencies
- Test file: `tests/test-<class-name>.php`
- Cover success, error, and edge cases
- Assert database state changes for DB operations

## Template
```php
class Test_ClassName extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        // Setup
    }

    public function test_method_name_success() {
        // Arrange, Act, Assert
    }

    public function test_method_name_failure() {
        // Arrange, Act, Assert
    }
}
```
