# Security Rules — LearnPress

## Input Validation
- Use `LP_Request::get_param( $key, $default_value, $sanitize_type, $method )` instead of reading `$_GET`, `$_POST`, or `$_REQUEST` directly; pass `get` or `post` as `$method`, or leave it empty for `$_REQUEST`
- Always sanitize user input: `sanitize_text_field()`, `absint()`, `wp_kses_post()`
- Validate nonces for form submissions: `wp_verify_nonce()`
- Use `$wpdb->prepare()` for all SQL queries with user input

## Authorization
- Check capabilities: `current_user_can()`
- REST API: always set `permission_callback`
- Admin pages: verify `manage_options` or appropriate capability

## Output Escaping
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Use `wp_localize_script()` for passing data to JS

## Database
- Never use raw SQL without `$wpdb->prepare()`
- Use DB layer classes instead of direct `$wpdb` calls
- Validate/sanitize before insert/update operations

## File Operations
- Validate file types for uploads
- Use WordPress filesystem API
- Never include/require user-supplied paths
