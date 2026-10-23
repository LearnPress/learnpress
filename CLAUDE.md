# LearnPress — AI Project Instructions

## Overview
LearnPress is a WordPress LMS (Learning Management System) plugin.
- **Language:** PHP 7.4+, WordPress
- **JS Build:** `@wordpress/scripts` (Webpack + Babel); vanilla JS in `assets/src/js/`
- **CSS:** SCSS → compiled CSS (gulp)
- **Testing:** PHPUnit (Brain Monkey), phpcs (WordPress Coding Standards)
- Entry point: `learnpress.php`; PSR-4 maps `LearnPress\` → `inc/`

## Workflow when changing code (MUST)
1. Before editing, follow `.claude/rules/architecture.md` and `.claude/rules/security.md` (always loaded). Before touching JS, read `.claude/rules/javascript.md`; before writing tests, read `.claude/rules/testing.md`
2. Reuse existing classes/helpers first — search `inc/` before creating anything new
3. After adding or moving a PSR-4 class: `composer dump-autoload`
4. Every new/changed PHP file must pass `vendor/bin/phpcs --standard=phpcs.xml <file>` (issues on changed lines)
5. Every new Model/DB/Service method must have a unit test, run by file
6. If the work belongs to a spec in `.claude/specs/<slug>/` (even outside `/spec-resume`): tick `plan.md`, and before ending the turn rewrite "Resume here" and add a Log entry in `progress.md`
7. Report what was verified (phpcs, tests) — and what was not

Hard rules (never violate): no raw `$_GET`/`$_POST`/`$_REQUEST` (use `LP_Request::get_param()`); no SQL without `$wpdb->prepare()`; no unescaped output; nonce + capability check on every AJAX/form/admin action; no raw `$wpdb` in Models; do not edit compiled files in `assets/js/`, `assets/css/`, `assets/dist/`.

## Key Conventions
- Post types: `lp_course`, `lp_lesson`, `lp_quiz`, `lp_question`, `lp_order`
- Custom tables: `learnpress_*` prefix
- Hooks: `learn-press/*` or `learnpress/*` filter/action naming

## Commands
- PHPCS: `vendor/bin/phpcs --standard=phpcs.xml <file>` (fix: `vendor/bin/phpcbf --standard=phpcs.xml <file>`)
- PHPUnit (one file): `vendor/bin/phpunit tests/Unit/<Area>/<ClassName>Test.php`
- Frontend JS tests: `node --test tests/Frontend/<name>.test.cjs`
- Autoload: `composer dump-autoload` after adding or moving PSR-4 classes
- `npm run build` — production build; `npm run start` — development watch
- `gulp styles && gulp mincss` — compile SCSS

## Specs & Planning
- Feature specs: `.claude/specs/<feature-slug>/` (`spec.md`, `plan.md`, `progress.md`)
- Workflow: `/spec-new <slug> <desc>` → `/spec-plan <slug>` → `/spec-resume <slug> [task]`
- Change request / bug / follow-up on an existing spec: `/spec-plan <slug> <change>` (adds a Phase) or directly `/spec-resume <slug> <fix>`
- `/spec-status` — overview of all specs and what to do next

## Claude Code setup
- Skills: `/lp-code-review`, `/security-audit`, `/test-writer` (each runs in its matching subagent)
- Subagents: `code-reviewer`, `security-reviewer`, `test-writer`
