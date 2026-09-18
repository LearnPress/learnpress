# Skill: Code Review

## Description
Review PHP/JS code changes for LearnPress plugin following WordPress Coding Standards.

## When to use
- Before merging PRs
- After implementing new features
- When refactoring existing code

## Checklist
- [ ] Follows WordPress Coding Standards (phpcs)
- [ ] Uses Model/DB pattern correctly
- [ ] Input sanitization and output escaping
- [ ] Proper nonce verification
- [ ] No raw SQL without `$wpdb->prepare()`
- [ ] Proper hook naming (`learn-press/*`)
- [ ] No hardcoded values — use constants/config
- [ ] Error handling present
