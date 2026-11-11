# select-suggest

> Reusable admin search-suggest input: type to search, results listed via TemplateAJAX, click to select one item (course / user).

**Branch:** `develop`
**Created:** 2026-11-11

## Goal
Admin screens can drop in a search input that suggests courses or users while typing (autocomplete, max 5 results) and stores the chosen item's ID in a hidden input. One base class holds the HTML structure; child classes only supply the AJAX result list.

## Requirements
- [ ] Base class `LearnPress\TemplateHooks\Admin\InputSuggest\AdminInputSearchSuggest` with `public function layout( array $data = [] ): string` returning:
  - `<div class="lp-wrap-input-search-suggest">`
    - `<input type="text" class="lp-input-search-suggest" autocomplete="off">` (placeholder from `$data`)
    - `<input type="hidden" class="lp-input-search-suggest-value" name="{$data['name']}" value="{$data['value']}">`
    - `<div class="lp-search-suggest-result">{$data['html_result']}</div>`
  - `$data` keys: `name`, `value`, `text` (label of preselected item), `placeholder`, `html_result` (output of `TemplateAJAX::load_content_via_ajax`), `class` (extra wrapper class). All attributes escaped; `html_result` is only set by child classes, never from request
- [ ] `AdminInputSearchCourseSuggest extends AdminInputSearchSuggest` (Singleton, `init()` registers `lp/rest/ajax/allow_callback`)
  - `layout()` builds `html_result` with `TemplateAJAX::load_content_via_ajax( [ 'id_url' => 'lp-search-course-suggest', 'html_no_load_ajax_first' => '', ... ], [ class, 'render_courses' ] )` (no request on page load) and calls `parent::layout()`
  - `public static function render_courses( array $data = [] ): stdClass` — `CourseJsonFilter` (`only_fields` ID + post_title, `post_title` = sanitized `search`, `limit` 5, page 1) → `CourseService::get_list_courses()`; returns `content` = `<ul class="lp-search-suggest-list"><li class="lp-search-suggest-item" data-id data-text>title</li></ul>`, or a "No results" message
- [ ] `AdminInputSearchUserSuggest extends AdminInputSearchSuggest`, same shape, `render_users()` uses `UserFilter` (`search`, `limit` 5, only ID / display_name / user_email) → `UserDB::getInstance()->get_users()`; li shows `display_name (user_email)`
- [ ] Permissions inside the callbacks (REST `lp/v1/load_content_via_ajax` only checks nonce + whitelist):
  - `render_courses`: administrator → all courses; instructor → only own courses (`post_author` = current user); others → error
  - `render_users`: administrator only
- [ ] `assets/src/scss/_input_search_suggest.scss` (wrapper relative, result as absolute dropdown under input, item hover/active, loading, hidden when empty), imported in `assets/src/scss/admin/admin.scss`
- [ ] `assets/src/js/lp-input-search-suggest.js` — vanilla class `LpInputSearchSuggest` following `reset-course-progress.js`:
  - events registered once via `lpUtils.eventHandlers` (`keyup`/`input` on `.lp-input-search-suggest`, `click` on `.lp-search-suggest-item`, click outside closes the list)
  - typing → `lpUtils.debounce` → `fetchSuggest( elInput )`: find `.lp-target` in the wrapper, `lpAJAXG.getDataSetCurrent` → set `args.search`, `args.paged = 1` → `setDataSetCurrent` → `showHideLoading` → `lpAJAXG.fetchAJAX` → `elLPTarget.innerHTML = data.content`; errors via `lpToastify`
  - empty text → clear list and hidden value, no request
  - click an item → input text = `data-text`, hidden value = `data-id`, hide list, dispatch `lp-search-suggest-selected` (bubbles, `detail: { id, text, elWrap }`)
- [ ] Own webpack entry `./assets/dist/js/admin/lp-input-search-suggest` and a registered admin script handle `lp-input-search-suggest` (deps `lp-load-ajax`) in `inc/admin/class-lp-admin-assets.php`; `layout()` enqueues it

## Acceptance Criteria
- [ ] `AdminInputSearchCourseSuggest::instance()->layout( [ 'name' => 'course_id' ] )` prints the structure above; typing ≥ 1 char shows ≤ 5 matching courses after the debounce; no AJAX request on page load
- [ ] Clicking a result fills the text input, sets the hidden input to the ID, closes the list, fires `lp-search-suggest-selected`
- [ ] Same for `AdminInputSearchUserSuggest` with users
- [ ] Instructor gets only own courses; non-admin cannot get users; guest/subscriber gets an error for both
- [ ] Two components on the same page work independently
- [ ] Code passes PHPCS and the PHPUnit tests (tests for `layout()` output/escaping, `render_courses`, `render_users` incl. permission branches)

## Scope
- `inc/TemplateHooks/Admin/InputSuggest/AdminInputSearchSuggest.php` — new, base class + `layout()`
- `inc/TemplateHooks/Admin/InputSuggest/AdminInputSearchCourseSuggest.php` — new, `layout()`, `allow_callback()`, `render_courses()`
- `inc/TemplateHooks/Admin/InputSuggest/AdminInputSearchUserSuggest.php` — new, `layout()`, `allow_callback()`, `render_users()`
- `learnpress.php` — instantiate the two child classes next to `AdminCourseTools::instance()` (L422) so the callbacks are whitelisted on REST requests
- `assets/src/scss/_input_search_suggest.scss` — new; `assets/src/scss/admin/admin.scss` — `@import "../input_search_suggest";`
- `assets/src/js/lp-input-search-suggest.js` — new
- `webpack.config.js` — new entry; `inc/admin/class-lp-admin-assets.php` — register handle
- `tests/Unit/TemplateHooks/Admin/InputSuggest/*Test.php` — new

## Out of scope
- Replacing existing search fields (Reset course progress, Students Enrolled, order screens) — later spec
- Multi-select / tags, keyboard arrow navigation
- Frontend (non-admin) usage
- Changes to `TemplateAJAX.php`, `CourseService`, `UserDB`, `UserFilter`

## Decisions
- Permissions: course suggest for admin + instructor (instructor limited to own courses); user suggest admin only (user choice)
- Selecting: single select, ID stored in a hidden input + custom event `lp-search-suggest-selected` (user choice)
- JS shipped as its own webpack entry + registered script handle, not bundled into `admin.js` (user choice)
- This spec only builds the component; no integration into an existing screen (user choice)
- `html_no_load_ajax_first` is used so nothing loads until the user types; limit fixed at 5

## Changes
<!-- Added by /spec-plan when requirements change after the spec was approved: YYYY-MM-DD — what changed and why -->

## References
- `assets/src/js/admin/tools/reset-course-progress.js` — `fetchCourses()` / debounce / `eventHandlers` pattern
- `assets/src/js/utils.js` — `debounce`, `eventHandlers`; `window.lpAJAXG` (`getDataSetCurrent`, `setDataSetCurrent`, `showHideLoading`, `fetchAJAX`)
- `inc/TemplateHooks/TemplateAJAX.php` — `load_content_via_ajax()` (do not edit)
- `inc/TemplateHooks/Admin/AdminListStudentsEnrolled.php` — Singleton + `allow_callback` + static render returning `stdClass{content}`
- `inc/Services/CourseService.php::get_list_courses()`, `inc/Filters/Course/CourseJsonFilter.php`
- `inc/Databases/UserDB.php::get_users()`, `inc/Filters/UserFilter.php`
