# Plan — tweak-ai

## Steps
- [x] Step 1: Replace the language Tom Select dropdown with a plain text input defaulting to "English" in the three AI template classes under `inc/TemplateHooks/Admin/AI`.
- [x] Step 2: Remove the hardcoded `language` option array from `config/settings/open-ai-modal.php` and drop references to `$options['language']` in the templates.
- [x] Step 3: Update AI-related JavaScript to handle the new text input (`input[name="language"]`), storing the value as a plain string in `lp_course_ai_setting` instead of an array.
- [x] Step 4: Append the constraint `- Must language output is $language` to the `<output_format>` block of every relevant prompt config in `config/settings/openAi/`.
- [x] Step 5: Run `npm run build` to regenerate bundled JS, then run phpcs on all modified PHP/JS files. (Build OK; phpcs not installed locally.)

## Files to create
| File | Purpose |
|------|---------|
| | |

## Files to modify
| File | Change |
|------|---------|
| `inc/TemplateHooks/Admin/AI/AdminCreateCourseAITemplate.php` | Replace language `html_tom_select` with `<input type="text" name="language" value="English">` |
| `inc/TemplateHooks/Admin/AI/AdminEditCourseCurriculumWithAITemplate.php` | Replace language `html_tom_select` with `<input type="text" name="language" value="English">` |
| `inc/TemplateHooks/Admin/AI/AdminEditWithAITemplate.php` | Replace both language `html_tom_select` calls with `<input type="text" name="language" value="English">` |
| `config/settings/open-ai-modal.php` | Remove the `language` option list (lines ~5–38) |
| `assets/src/js/admin/generate-with-openai.js` | Query `input[name="language"]`, set string value, listen to `input` event |
| `assets/src/js/admin/courses/generate-with-ai.js` | Query `input[name="language"]`, set string value, listen to `input` event |
| `config/settings/openAi/prompt-curriculum-course.php` | Append language constraint to `<output_format>` |
| `config/settings/openAi/prompt-create-course.php` | Append language constraint to `<output_format>` |
| `config/settings/openAi/prompt-create-description-course.php` | Append language constraint to `<output_format>` |
| `config/settings/openAi/prompt-create-title-course.php` | Append language constraint to `<output_format>` |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

## Open questions
- Should the image prompt (`prompt-create-image-course.php`) also receive a language constraint even though it has no `<output_format>` block?
