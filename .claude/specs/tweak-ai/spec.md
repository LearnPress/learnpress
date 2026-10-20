# tweak-ai

> Adjust AI curriculum generation language input and output prompt formatting.

## Goal
Replace the fixed language dropdown with a free-text language input (default English) and ensure AI prompts include a mandatory language constraint using the submitted value.

## Requirements
- [ ] Change the language field from a select dropdown to a text input with default value "English" in `inc/TemplateHooks/Admin/AI`.
- [ ] Remove the hardcoded `language` options from `config/settings/open-ai-modal.php` and any related references in the plugin.
- [ ] Append the instruction `Must language output is $language` (where `$language` is the submitted input value) to all relevant AI output format prompts.

## Acceptance Criteria
- [ ] The AI modal/settings language control renders as a text input with placeholder/default "English".
- [ ] No hardcoded language dropdown list remains in `config/settings/open-ai-modal.php` or elsewhere.
- [ ] Submitted AI requests include the language constraint text derived from the input value.

## Scope
- `inc/TemplateHooks/Admin/AI`
- `config/settings/open-ai-modal.php`
- Related AI prompt builders or output-format handlers in the plugin.

## Out of scope
- Backend AI provider or model changes.
- Translations of the new constraint string.

## References
- `config/settings/open-ai-modal.php:L5-L38`
