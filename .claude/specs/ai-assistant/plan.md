# Plan — ai-assistant

## Steps
- [x] Step 1: Consolidate AI Assistant PHP rendering around the learning content bar.
  - Remove constructor hooks for the footer launcher wrapper, launcher, and standalone footer panel.
  - Remove `FOOTER_LAUNCHERS_HOOK`, `render_launcher_wrapper()`, `render_launcher()`, `html_toggle()`, and legacy widget assembly methods that exist only for the floating launcher/panel.
  - Keep the render-state gate, asset enqueue, localization, messages, quick actions, composer, and AJAX contract.
  - Make `layout_ai_assistant_on_learning_content_bar()` resolve the current render state and return an empty string when unavailable.
  - Render a complete AI root inside the `<template>` with the correct context-specific actions and free-chat setting instead of calling `html_panel_footer()` with defaults.
  - Localize `window.lpAIAssistant` without outputting a duplicate standalone panel in `wp_footer`.
- [x] Step 2: Define an explicit dynamic content-bar lifecycle.
  - Add stable item identity to the filterable right-sidebar item/template markup.
  - After `learning-content-bar.js` clones a template into `.lp-addon-content-bar`, dispatch a dedicated event with the inserted content root and item identity.
  - Keep content-bar open/close state independent from AI Assistant internals.
  - Ensure repeated clicks replace content safely and emit one lifecycle event per render.
- [x] Step 3: Refactor `ai-assistant.js` for dynamically inserted content-bar markup.
  - Replace the standalone panel root/toggle/close selectors with selectors scoped to the AI root inside `.lp-addon-content-bar`.
  - Remove toggle click handling, close handling, Escape-to-close logic, `openPanel()`, `closePanel()`, panel `hidden` state, and toggle ARIA state updates.
  - Initialize or refresh cached elements when the learning-bar lifecycle event reports newly cloned AI markup.
  - Bind delegated document events only once while allowing the active AI root to be replaced on every reopen.
  - Restore history, context state, quick-action visibility, quiz hooks, message scrolling, composer submission, clear history, AJAX loading, quiz actions, and error handling for each inserted root.
  - Fail safely when configuration or AI markup is absent.
- [x] Step 4: Remove legacy floating-panel styles and finalize content-bar styles.
  - Delete `.lp-ai-assistant__toggle`, toggle-label/icon, standalone fixed panel, close-button, hidden-panel, and launcher-only responsive rules.
  - Retain and scope message, quick-action, quiz-card, clear-history, composer, loading, and footer styles used in `.lp-addon-content-bar`.
  - Ensure the AI messages area grows and scrolls while the controls remain usable at the bottom of the 400px content bar.
  - Remove obsolete `:has()` rules where stable AI root classes can provide equivalent layout behavior.
- [x] Step 5: Update automated coverage for the content-bar-only contract.
  - Replace launcher/standalone panel assertions with learning-bar template assertions.
  - Verify disabled, unauthorized, unsupported, and incomplete quiz states return no AI learning-bar content.
  - Verify lesson and completed-quiz templates use the correct enabled actions and free-chat configuration.
  - Verify no `.lp-ai-assistant__toggle` markup remains.
  - Add focused JavaScript coverage if an existing frontend test harness supports this module; otherwise document runtime verification.
- [ ] Step 6: Format, build, and verify the complete interaction flow.
  - Run PHPCS and PHP syntax checks for modified PHP files.
  - Format and syntax-check modified JavaScript.
  - Compile SCSS and run the production Webpack build.
  - Run the focused `CourseAIAssistantTemplateTest` suite.
  - Verify opening, history rendering, send, quick actions, quiz options, clear history, close/reopen, repeated opens, disabled state, and responsive content-bar layout in a running course popup.

## Files to create
| File | Purpose |
|------|---------|
| None expected | Reuse the existing AI Assistant, learning-bar, and test architecture. |

## Files to modify
| File | Change |
|------|--------|
| `inc/TemplateHooks/Course/CourseAIAssistantTemplate.php` | Remove launcher/standalone panel rendering and produce state-aware AI content-bar template markup plus localization. |
| `templates/single-course/content-item/popup-right-sidebar.php` | Render a filterable AI item only when its template is available and expose stable item identity. |
| `assets/src/apps/js/frontend/single-curriculum/learning-content-bar.js` | Emit the inserted-content lifecycle event after cloning the AI template. |
| `assets/src/js/frontend/ai-assistant.js` | Remove toggle/panel lifecycle and initialize all existing AI interactions against dynamically inserted content-bar markup. |
| `assets/src/scss/frontend/ai-assistant.scss` | Remove legacy launcher/floating-panel styles and scope retained AI UI to the content bar. |
| `assets/src/scss/frontend/_learning_addons_bar.scss` | Adjust shared bar sizing/scrolling only where needed by the AI content root. |
| `tests/Unit/TemplateHooks/Course/CourseAIAssistantTemplateTest.php` | Replace old launcher/panel expectations with content-bar-only rendering and state assertions. |

## Files to inspect but not modify unless required
| File | Reason |
|------|--------|
| `inc/class-lp-assets.php` | Existing `lp-ai-assistant` handles and page conditions should remain valid if localization is preserved. |
| `webpack.config.js` | Existing AI Assistant entry already builds the target script. |
| `inc/TemplateHooks/Learning/LearningTemplate.php` | Shared content-bar shell should not need AI-specific behavior. |
| `assets/src/js/utils.js` | Reuse `eventHandlers()` and existing utilities without changing the shared API. |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Should the backward-compatible public methods `render_widget()` and `html_widget()` be removed, or retained as deprecated wrappers that return/render the new learning-bar template?
- Is the AI Assistant intentionally limited to `LP_PAGE_SINGLE_COURSE_CURRICULUM`, or must the completed-quiz flow on `LP_PAGE_QUIZ` continue despite the current asset registration page condition?
- Should opening a non-AI learning-bar item tear down an in-flight AI request, or may the request complete while the AI markup is detached?
