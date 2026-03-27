Read the spec for a feature and generate a detailed implementation plan.

## Input
$ARGUMENTS = "<feature-slug>"

## What to do

1. Read `.claude/specs/<feature-slug>/spec.md` — understand goals and requirements
2. Read `CLAUDE.md` — refresh understanding of architecture, patterns, directory roles
3. Explore the relevant files listed in spec.md `## Scope` section
4. For each file that needs to be created, identify which slash command applies:
   - DB class → `/new-db-class`
   - Filter → `/new-filter`
   - Model → `/new-model`
   - AJAX handler → `/new-ajax-handler`
   - REST controller → `/new-rest-controller`
   - TemplateHook → `/new-template-hook`
   - Shortcode → `/new-shortcode`
   - Widget → `/new-widget`

5. Write the updated `plan.md`:
   - Break work into concrete, ordered steps (each step = one focused task)
   - Each step should be doable in a single conversation session
   - List every file to create and modify with clear purpose
   - Flag any open questions or decisions needed before starting

6. Update `progress.md`:
   - Set **Status** to 🟡 In progress
   - Populate **Next** with the first 2-3 actionable steps from plan.md

7. Ask the user: "Plan is ready. Want to start now with Step 1?"