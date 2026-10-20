# Progress — tweak-ai

**Status:** 🟢 Done
**Started:** 2026-10-14
**Last updated:** 2026-10-14

## Done
1. Step 1: Replaced language Tom Select with text input in `inc/TemplateHooks/Admin/AI` templates.
2. Step 2: Removed hardcoded `language` option list from `config/settings/open-ai-modal.php`.
3. Step 3: Updated AI JavaScript to handle the new text input.
4. Step 4: Appended language constraint to `<output_format>` blocks in relevant prompt configs.
5. Step 5: `npm run build` succeeded; phpcs binary is not installed in this environment.

## In progress

## Next

## Decisions made
- Kept prompt-create-image-course.php unchanged because it has no `<output_format>` block.

## Blockers / Notes
- Run phpcs manually when available:
  `php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml inc/TemplateHooks/Admin/AI/AdminCreateCourseAITemplate.php inc/TemplateHooks/Admin/AI/AdminEditCourseCurriculumWithAITemplate.php inc/TemplateHooks/Admin/AI/AdminEditWithAITemplate.php config/settings/open-ai-modal.php config/settings/openAi/prompt-curriculum-course.php config/settings/openAi/prompt-create-course.php config/settings/openAi/prompt-create-description-course.php config/settings/openAi/prompt-create-title-course.php`
