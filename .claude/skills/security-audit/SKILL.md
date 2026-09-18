# Skill: Security Audit

## Description
Audit LearnPress code for security vulnerabilities following WordPress security best practices.

## When to use
- Before releases
- When handling user input
- When adding REST API endpoints
- When working with file operations

## Checklist
- [ ] SQL Injection: All queries use `$wpdb->prepare()`
- [ ] XSS: All output properly escaped (`esc_html`, `esc_attr`, `esc_url`)
- [ ] CSRF: Nonce verification on all forms and AJAX
- [ ] Authorization: `current_user_can()` checks present
- [ ] File inclusion: No user-controlled includes/requires
- [ ] Data validation: All input sanitized before use
- [ ] Request parameters: Use `LP_Request::get_param( $key, $default_value, $sanitize_type, $method )` instead of reading `$_GET`, `$_POST`, or `$_REQUEST` directly; pass `get` or `post` as `$method`, or leave it empty for `$_REQUEST`
- [ ] REST API: `permission_callback` set on all routes
- [ ] No sensitive data in logs or debug output

## Common Vulnerabilities in WordPress Plugins
1. Unescaped output in templates
2. Missing nonce checks in AJAX handlers
3. Direct database queries without prepare
4. Missing capability checks on admin actions
5. Object injection via unserialize
