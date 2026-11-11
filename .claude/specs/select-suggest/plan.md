# Plan — select-suggest

## Steps
- [ ] Step 1: Base class `AdminInputSearchSuggest` (abstract, **no** `Singleton` trait) with `layout( array $data = [] ): string` — builds wrapper / text input / hidden input / `.lp-search-suggest-result` via `Template::combine_components()`, escapes `name`, `value`, `text`, `placeholder`, `class`; prints `html_result` as is; calls `wp_enqueue_script( 'lp-input-search-suggest' )`. `composer dump-autoload` — verify: `tests/Unit/TemplateHooks/Admin/InputSuggest/AdminInputSearchSuggestTest.php` (structure, escaping of each attribute, empty `$data`), phpcs
- [ ] Step 2: `AdminInputSearchCourseSuggest extends AdminInputSearchSuggest` with `use Singleton`, `init()` → `add_filter( 'lp/rest/ajax/allow_callback', ... )`, `allow_callback()`, `layout()` (sets `html_result` = `TemplateAJAX::load_content_via_ajax()` with `id_url` `lp-search-course-suggest`, `html_no_load_ajax_first` `''`, `enableScrollToView` false, `enableUpdateParamsUrl` false; callback `render_courses`; default placeholder; then `parent::layout()`), and `public static render_courses( array $data = [] ): stdClass`:
  - permission: `current_user_can( UserModel::ROLE_ADMINISTRATOR )` → all; else `current_user_can( UserModel::ROLE_INSTRUCTOR )` → `$filter->post_author = get_current_user_id()`; else throw
  - empty `search` → empty content, no query
  - `CourseJsonFilter`: `only_fields` `[ 'ID', 'post_title' ]`, `post_title` = `LP_Helper::sanitize_params_submitted( trim( $data['search'] ?? '' ) )`, `limit` 5, `page` 1 → `CourseService::get_list_courses()`
  - output `<ul class="lp-search-suggest-list">` + `<li class="lp-search-suggest-item" data-id data-text>`; no rows → `<li class="lp-search-suggest-empty">` with "No results" text; exceptions → `Template::print_message()` into content
  - verify: `tests/Unit/TemplateHooks/Admin/InputSuggest/AdminInputSearchCourseSuggestTest.php` (admin, instructor sets `post_author`, no permission, empty search, title escaping, `allow_callback`), phpcs
- [ ] Step 3: `AdminInputSearchUserSuggest` — same shape, `id_url` `lp-search-user-suggest`, `render_users()`: administrator only; `UserFilter` with `search`, `limit` 5, `page` 1, `only_fields` `ID`, `display_name`, `user_email` → `UserDB::getInstance()->get_users()`; li text `display_name (user_email)`, `data-text` = display_name — verify: `tests/Unit/TemplateHooks/Admin/InputSuggest/AdminInputSearchUserSuggestTest.php` (admin, instructor denied, guest denied, empty search, escaping), phpcs
- [ ] Step 4: Boot both child classes in `learnpress.php` after `AdminCourseTools::instance();` (plus `use` imports) so the callbacks are whitelisted on REST requests — verify: phpcs on changed lines; `php -l learnpress.php`
- [ ] Step 5: `assets/src/js/lp-input-search-suggest.js` — class `LpInputSearchSuggest` (selectors static, events attached once with `lpUtils.eventHandlers`: `input` on `.lp-input-search-suggest`, `click` on `.lp-search-suggest-item`, document click outside wrapper closes list; `lpUtils.debounce` → `fetchSuggest( elInput )` as in `fetchCourses()`; empty text clears list + hidden value without request; stale responses ignored by comparing the input's current value; select fills input / hidden value, closes list, dispatches `lp-search-suggest-selected`); file ends with `new LpInputSearchSuggest().init()`. Add webpack entry `./assets/dist/js/admin/lp-input-search-suggest` — verify: `tests/Frontend/lp-input-search-suggest.test.cjs` (`node --test`), `npm run build` succeeds
- [ ] Step 6: Register handle `lp-input-search-suggest` in `inc/admin/class-lp-admin-assets.php` (`dist/js/admin/lp-input-search-suggest`, deps `lp-load-ajax`, no screens, `only_register` 1, defer) — verify: phpcs on changed lines
- [ ] Step 7: `assets/src/scss/_input_search_suggest.scss` (wrapper `position: relative`; result absolute dropdown below input, border/shadow, `z-index`, max-height + scroll; `:empty`/closed state hidden; item hover; empty-state row; loading) and `@import "../input_search_suggest";` in `assets/src/scss/admin/admin.scss` after `"../toggleEnable"` — verify: `gulp styles && gulp mincss` succeeds
- [ ] Step 8: Manual browser check (component is not placed on any screen by this spec): temporarily echo both `layout()` outputs on LP → Tools, check acceptance criteria (no request on load, ≤ 5 results, select fills hidden input + event, two instances independent, instructor sees own courses only, instructor gets error for users), then remove the temporary output — verify: `git diff` shows no leftover test markup
- [ ] Step 9: `/lp-code-review` + `/security-audit` on the new files; fix findings — verify: all three test files + phpcs pass again

## Files to create
| File | Purpose |
|------|---------|
| `inc/TemplateHooks/Admin/InputSuggest/AdminInputSearchSuggest.php` | Abstract base: HTML structure, `layout()` |
| `inc/TemplateHooks/Admin/InputSuggest/AdminInputSearchCourseSuggest.php` | Course suggest: `layout()`, `allow_callback()`, `render_courses()` |
| `inc/TemplateHooks/Admin/InputSuggest/AdminInputSearchUserSuggest.php` | User suggest: `layout()`, `allow_callback()`, `render_users()` |
| `assets/src/js/lp-input-search-suggest.js` | Debounced autocomplete + select handling |
| `assets/src/scss/_input_search_suggest.scss` | Component styles |
| `tests/Unit/TemplateHooks/Admin/InputSuggest/AdminInputSearchSuggestTest.php` | `layout()` output / escaping |
| `tests/Unit/TemplateHooks/Admin/InputSuggest/AdminInputSearchCourseSuggestTest.php` | `render_courses()` + permissions |
| `tests/Unit/TemplateHooks/Admin/InputSuggest/AdminInputSearchUserSuggestTest.php` | `render_users()` + permissions |
| `tests/Frontend/lp-input-search-suggest.test.cjs` | JS behaviour (debounce → fetch, select, clear) |

## Files to modify
| File | Change |
|------|--------|
| `learnpress.php` | Instantiate the two child classes (~L422) |
| `webpack.config.js` | New entry `./assets/dist/js/admin/lp-input-search-suggest` |
| `inc/admin/class-lp-admin-assets.php` | Register script handle `lp-input-search-suggest` |
| `assets/src/scss/admin/admin.scss` | Import the new partial |

## Verification
- Each new/changed PHP file: `vendor/bin/phpcs --standard=phpcs.xml <file>` (auto-fix with `vendor/bin/phpcbf`)
- Tests: `vendor/bin/phpunit tests/Unit/TemplateHooks/Admin/InputSuggest/<ClassName>Test.php`
- JS: `node --test tests/Frontend/lp-input-search-suggest.test.cjs`, `npm run build`
- SCSS: `gulp styles && gulp mincss`
- Compiled files in `assets/js/`, `assets/css/`, `assets/dist/` are build output only — never edited by hand

## Open questions
- Course statuses: `render_courses` leaves `post_status` unset, so `CourseJsonDB` returns every status except `auto-draft` (draft, pending, private, trash included). Restrict to `publish` only, or keep all for admin screens?
- Minimum characters before searching: plan assumes 1 (spec AC). Raise to 2 to cut requests?
- Debounce wait: plan assumes 500 ms (`debounce` default); `reset-course-progress.js` uses 800 ms
