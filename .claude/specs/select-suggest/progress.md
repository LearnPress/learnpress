# Progress — select-suggest

**Status:** ⚪ Not started
**Branch:** `develop`
**Started:** 2026-11-11
**Last updated:** 2026-11-11

## Resume here
<!-- Rewritten at the end of every session. Read this first. -->
- **Next action:** Step 1 — create abstract `AdminInputSearchSuggest` with `layout()` + its unit test
- **Half-done work:** none
- **Uncommitted / unverified:** none

## Decisions
- Base class is abstract and does not use the `Singleton` trait; each child class uses it. The trait's `private static $instance` would otherwise be shared through the parent, so the second child would get the first child's instance
- `html_no_load_ajax_first => ''` drops the `.lp-load-ajax-element` wrapper, so `loadAJAX.js` does not fetch on page load; JS triggers every request
- Do not edit `inc/TemplateHooks/TemplateAJAX.php`; `html_result` is set only by the child classes
- Script is only registered (`only_register` 1); `layout()` enqueues it, so it loads only where the component is printed

## Blockers / Notes
- Open questions in `plan.md` (course statuses, min characters, debounce wait) — defaults are assumed unless the user answers

## Log
<!-- Newest first. One entry per session or step: what was done + how it was verified. Keep each entry ≤ 5 lines. -->
- 2026-11-11 — Plan created
