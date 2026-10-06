# Testing Rules — LearnPress

## Framework
- PHPUnit for unit/integration tests
- Config: `phpunit.xml`
- Tests location: `tests/`

## Standards
- Run phpcs before committing: `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml <file>`
- All new Model/Service methods should have corresponding tests
- Test file naming: `test-<class-name>.php`

## Guidelines
- Mock WordPress functions when unit testing
- Use `WP_UnitTestCase` for integration tests
- Test both success and error paths
- Verify database state changes in integration tests
