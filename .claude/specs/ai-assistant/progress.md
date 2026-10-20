# Progress — ai-assistant

**Status:** 🟡 In progress
**Started:** 2026-10-06
**Last updated:** 2026-10-06

## Done
1. Created the initial AI Assistant content-bar feature specification.
2. Inspected the full AI JavaScript widget, PHP render-state/template flow, dynamic learning-bar cloning, SCSS selectors, asset registration, and existing unit-test coverage.
3. Produced the ordered content-bar-only implementation plan.
4. Completed Step 1: removed footer launcher/standalone panel hooks and rendering methods, moved localization into asset enqueue, and made the learning-bar template state-aware with the complete AI root and context-specific actions/free-chat controls.
5. Completed Step 2: added stable `data-learning-bar-item` identity and dispatches `lp-learning-content-bar-rendered` after cloned head/content markup is inserted.
6. Completed Step 3: refactored `ai-assistant.js` to boot once, listen for `lp-learning-content-bar-rendered`, refresh its active root/elements after every AI template insertion, and retain history/chat/quiz/AJAX behavior without standalone toggle/panel controls.
7. Completed Step 4: removed footer-launcher, toggle, fixed-panel, panel-header/body, hidden-panel, animation, close-control, and launcher responsive SCSS; converted `.lp-ai-assistant` to the content-bar flex root and synchronized content height with the 64px bar header.
8. Completed Step 5: replaced legacy widget/launcher/panel calls with learning-bar template and footer assertions, added no-toggle/template identity checks, and retained disabled, denied, lesson, incomplete-quiz, completed-quiz, action, free-chat, asset, and localization coverage.
9. Completed Step 6 automated verification: removed remaining toggle dependencies, suppressed unavailable AI sidebar items, formatted JavaScript, passed PHP/JS syntax, PHPCS, focused PHPUnit, Sass compilation, production build, and whitespace checks.
10. Fixed runtime AI controls initialization: delegated handlers and the learning-bar lifecycle listener now bind regardless of initial config/template timing, each inserted root validates current localized config, and an already-rendered root is initialized as a fallback.
11. Changed AI initialization to run directly from `LearningContentBar.open()` after the AI template root is cloned; the AI bundle now exposes one widget instance and no longer auto-initializes through document boot, MutationObserver, or the custom event.

## In progress
- Step 6 runtime verification in a running course learning popup.

## Next
- Verify opening AI Assistant, restored history, prompt submission, quick actions, quiz interaction, clear history, close/reopen, repeated opens, disabled state, and responsive layout in the browser.

## Decisions made
- The AI Assistant user interface will live entirely inside `.lp-addon-content-bar`.
- `.lp-ai-assistant__toggle` and its runtime/style dependencies will be removed.
- Existing AI AJAX transport, localized configuration, history, quick actions, quiz state, and security contract must remain unchanged.
- Dynamic template cloning requires explicit reinitialization because the current script boots once against `#lp-ai-assistant` in the footer.
- Delegated document handlers should bind once, while cached AI DOM references must refresh after every template insertion.
- Legacy public launcher/panel methods were removed rather than retained as compatibility wrappers because the feature explicitly removes the standalone toggle flow.
- Script localization now occurs in `enqueue_assets()` after the render state is validated.
- The learning-bar template now uses the resolved `free_chat_enabled` and `enabled_actions` values instead of permissive defaults.
- The lifecycle event detail exposes `item`, `contentBar`, and `contentRoot`.
- The event is emitted once per successful template render and not emitted for sidebar items without a valid template/head/content target.
- AI delegated click/keydown handlers bind once; current DOM references are replaced on every lifecycle event.
- Clear-history is resolved from the containing content bar because its control lives in the cloned header outside `#lp-ai-assistant`.
- Toggle, close, Escape, panel hidden state, and panel ARIA mutation logic were removed.
- An in-flight request is allowed to complete; reopening while it runs keeps the singleton request guard active rather than creating a duplicate request.
- The AI sidebar item is omitted when `layout_ai_assistant_on_learning_content_bar()` returns no authorized renderable template.

## Blockers / Notes
- Decide whether public legacy methods remain as deprecated compatibility wrappers.
- Confirm completed-quiz support and asset loading expectations on `LP_PAGE_QUIZ`.
- Decide how an in-flight request behaves if another learning-bar item replaces the AI markup.
- Automated validation passes: PHPCS, 8 PHPUnit tests with 37 assertions, Sass compilation, production Webpack build, JavaScript/PHP syntax, and `git diff --check`.
- No `.lp-ai-assistant__toggle` dependency remains in PHP, JavaScript, or SCSS source.
- Webpack reports only existing asset-size warnings.
- Runtime browser verification requires an accessible course popup URL/session.
- `http://lp.local/course/sdfsd/lessons/lesson-9-28` is reachable, but the available session is unauthenticated and only exposes login/enroll links, so the protected learning popup and AI controls cannot be verified.
- Retained AI styles are limited to title/clear, messages, quick actions, composer, loading, quiz cards, and content-bar layout.
- The AI root fills available content-bar height; messages own the scroll area and footer controls remain in the flex flow.
- The production build reports only existing asset-size warnings.
- Focused PHP coverage now treats `layout_ai_assistant_on_learning_content_bar()` as the authoritative rendering contract.
- The runtime initialization fix passes JavaScript formatting, syntax, production Webpack build, and `git diff --check`.
- Direct click-flow initialization passes JavaScript formatting, syntax, production Webpack build, and `git diff --check`.
