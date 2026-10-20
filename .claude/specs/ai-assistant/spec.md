# ai-assistant

> Move the AI Assistant interaction fully into the learning addon content bar and remove the legacy standalone toggle flow.

## Goal
Make the AI Assistant work entirely inside `.lp-addon-content-bar`, using the learning bar item/template flow for rendering and interaction instead of the legacy `.lp-ai-assistant__toggle` and standalone panel entry behavior.

## Requirements
- [ ] Remove PHP, JavaScript, SCSS, selectors, and accessibility state handling used only by `.lp-ai-assistant__toggle`.
- [ ] Keep AI Assistant rendering available through the learning content bar item.
- [ ] Adapt `assets/src/js/frontend/ai-assistant.js` to initialize against AI Assistant markup cloned into `.lp-addon-content-bar`.
- [ ] Ensure message loading, prompt submission, actions, clear history, loading state, scrolling, and error handling work inside the content bar.
- [ ] Handle markup inserted after page load when a `.popup-right-sidebar__item` opens the learning content bar.
- [ ] Avoid duplicate event listeners when the content bar is opened repeatedly.
- [ ] Preserve LearnPress AJAX, nonce, and existing AI Assistant API behavior.
- [ ] Preserve the content bar's independent open/close behavior.

## Acceptance Criteria
- [ ] No `.lp-ai-assistant__toggle` markup or runtime dependency remains.
- [ ] Clicking the AI Assistant learning-bar item opens `.lp-addon-content-bar` and renders the AI Assistant interface.
- [ ] Existing AI Assistant messages render correctly inside the content bar.
- [ ] Users can submit prompts and receive responses without opening a separate dialog/panel.
- [ ] Clear history and other available AI actions work inside the content bar.
- [ ] Closing and reopening the content bar does not duplicate handlers or break the interface.
- [ ] No JavaScript errors occur when AI Assistant is disabled or its markup is absent.
- [ ] PHP, JavaScript, and SCSS validation/build checks pass.

## Scope
- `assets/src/js/frontend/ai-assistant.js`
- `assets/src/scss/frontend/ai-assistant.scss`
- `assets/src/apps/js/frontend/single-curriculum/learning-content-bar.js`
- `inc/TemplateHooks/Course/CourseAIAssistantTemplate.php`
- `inc/TemplateHooks/Learning/LearningTemplate.php`
- `templates/single-course/content-item/popup-right-sidebar.php`
- AI Assistant script enqueue/localization code if selector or initialization changes are required

## Out of scope
- Changing AI response generation, provider selection, or prompt semantics.
- Adding new AI Assistant capabilities.
- Redesigning unrelated learning-bar items.
- Persisting learning content bar visibility between page loads.

## References
- `.lp-addon-content-bar`
- `.lp-learning-bar-item-head`
- `.lp-learning-bar-item-content`
- `.lp-ai-assistant__toggle`
- `CourseAIAssistantTemplate::layout_ai_assistant_on_learning_content_bar()`
