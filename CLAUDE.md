# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

LearnPress is a WordPress LMS (Learning Management System) plugin. It manages courses, lessons, quizzes, questions, orders, and student enrollments via custom post types and a custom database layer.

- **Version**: 4.x.x (requires WordPress 6.0+, PHP 7.4+)
- **Node.js**: v17+ required for asset development
- **PSR-4 namespace**: `LearnPress\` → `inc/`

## Commands

### JavaScript / CSS

```bash
npm run start        # Watch and compile JS/CSS (webpack + @wordpress/scripts)
npm run build        # Production build (minified JS)
npm run dev-build    # Full build: JS + CSS (webpack + gulp)
npm run watchCss     # Watch SCSS files only (gulp)
npm run format-js    # Format JS files
npm run release      # Create folder releases, file ZIP in `releases/`
```

Gulp tasks (run directly if needed):
```bash
npx gulp styles      # Compile SCSS → CSS with RTL variants
npx gulp release     # Create release ZIP
```

### PHP Code Quality

```bash
composer lint              # Check PHP code standards (phpcs)
composer format            # Auto-fix PHP code style (phpcbf)
composer format-a-file     # Fix a single file
```

### Tests

```bash
composer test              # Run all PHPUnit tests
composer test:unit         # Run unit tests only (tests/Unit/)
composer test:filter UserModel   # Run tests matching name "UserModelTest"
```

- Framework: **PHPUnit 10.5** with **Brain Monkey** (stubs WordPress functions — no WP core needed)
- Mocking: **Mockery** for class mocks
- Bootstrap: `tests/bootstrap.php` — defines WP/LP constants and stub classes
- Helper: `tests/Helpers/BrainMonkeyTestCase.php` — base test case (extend this for new tests)
- Test files live in `tests/Unit/` mirroring `inc/` structure
- Coverage source: `inc/` (excludes `inc/libraries/`)

### Translations

```bash
npm run makepot      # Extract all translatable strings (PHP + JS)
```

## Architecture

### Bootstrap Flow

Entry point: `learnpress.php` → `LearnPress` singleton class

1. Constants defined in `inc/lp-constants.php`
2. `prepare_before_handle()` loads includes and checks version
3. `lp_main_handle()` fires on `init` at priority `-1000` (very early, before admin init)
4. AJAX handlers register on `init` at priority `11`
5. `learn-press/ready` fires when plugin is fully initialized

Key custom hook prefixes: `learn-press/` (core), `lp_` (legacy)

### Key Directory Roles

| Path | Purpose |
|------|---------|
| `inc/Models/` | Business logic and data models (Course, Lesson, Quiz, Question, User, UserItems) |
| `inc/Databases/` | Repository layer — all raw DB queries per entity |
| `inc/Filters/` | Query filter objects — map table columns to properties, shape queries before execution |
| `inc/curds/` | Legacy CRUD implementations (implements `inc/interfaces/interface-curd.php`) |
| `inc/Ajax/` | AJAX handlers — each extends `AbstractAjax`, implements `catch_lp_ajax()` |
| `inc/TemplateHooks/` | Template hook registrars for specific pages/contexts |
| `inc/rest-api/` | REST API controllers (namespace `LearnPress\RestApi\`, base URL `/wp-json/learnpress/v1/`) |
| `inc/abstracts/` | Base classes: `LP_Addon`, `LP_Post_Data`, `LP_Object_Data`, `abstract-rest-controller.php` |
| `inc/Admin/` | Admin pages, menus, settings UI |
| `inc/Cache/` | Object caching layer (courses, quizzes, sessions) |
| `inc/Background/` | Custom async/background task processors (not WP Background Processing library) |
| `inc/Gutenberg/` | Gutenberg block system (80+ blocks) |
| `inc/Shortcodes/` | Shortcode implementations |
| `inc/Widgets/` | Frontend widget classes |
| `inc/ExternalPlugin/` | 3rd-party integrations (Elementor, Yoast SEO, Rank Math, Polylang) |
| `inc/gateways/` | Payment gateways (PayPal, offline) |
| `templates/` | Frontend PHP templates (overridable by themes) |
| `assets/src/apps/js/` | React apps (Gutenberg blocks, admin editors, frontend) |
| `assets/src/scss/` | SCSS source (compiled to `assets/dist/css/`) |
| `config/` | Static config (settings fields, delivery types, Elementor widgets, DB table definitions) |

### Data Layer Pattern

All data access follows: **Model → Database class → Filter → `$wpdb`**

1. **Model** (`inc/Models/`) — business logic, caching via `LP_Object_Cache`
2. **Database class** (`inc/Databases/`) — extends `DataBase` singleton, executes queries
3. **Filter** (`inc/Filters/`) — typed data objects extending `FilterBase` that define columns, where clauses, joins, pagination
4. **`$wpdb`** — WordPress database abstraction

`DataBase` base class (`inc/Databases/DataBase.php`) provides:
- `execute(&$filter, &$total_rows)` — SELECT queries
- `update_execute($filter)` — UPDATE queries
- `delete_execute($filter)` — DELETE queries
- `insert_data(array $args)` — INSERT with validation
- Table management: `check_table_exists()`, `add_col_table()`, `drop_col_table()`

Each entity DB class exposes table name properties (e.g., `$tb_lp_courses`, `$tb_lp_user_items`).

### Filter Pattern

Filters in `inc/Filters/` are plain data-holder classes that extend `FilterBase`:
- Define column constants (`const COL_ID = 'ID'`)
- Declare typed public properties matching table columns
- `all_fields` array lists available columns
- Inherited properties: `$limit`, `$page`, `$order_by`, `$order`, `$where`, `$join`, `$fields`, `$group_by`
- Used for both read and write operations

### Custom Database Tables

All tables prefixed with `{wp_prefix}learnpress_` (constant `LP_TABLE_PREFIX`). Defined in `config/table/tables-v4.php`.

| Table | Purpose |
|-------|---------|
| `learnpress_courses` | Course data |
| `learnpress_sections` | Course sections |
| `learnpress_section_items` | Items (lessons/quizzes) within sections |
| `learnpress_quiz_questions` | Quiz ↔ question relationships |
| `learnpress_question_answers` | Answer options for questions |
| `learnpress_question_answermeta` | Answer metadata |
| `learnpress_user_items` | User enrollments and progress |
| `learnpress_user_itemmeta` | User item metadata |
| `learnpress_user_item_results` | Quiz/lesson results |
| `learnpress_order_items` | Order line items |
| `learnpress_order_itemmeta` | Order item metadata |
| `learnpress_sessions` | Session storage |
| `learnpress_files` | File attachments |

### Key Constants (`inc/lp-constants.php`)

**Post Types:** `LP_COURSE_CPT` (`lp_course`), `LP_LESSON_CPT` (`lp_lesson`), `LP_QUIZ_CPT` (`lp_quiz`), `LP_QUESTION_CPT` (`lp_question`), `LP_ORDER_CPT` (`lp_order`)

**Taxonomies:** `LP_COURSE_CATEGORY_TAX` (`course_category`), `LP_COURSE_TAXONOMY_TAG` (`course_tag`)

**User Roles:** `LP_TEACHER_ROLE` (`lp_teacher`), `ADMIN_ROLE` (`administrator`)

**Status Constants:**
- Course progress: `LP_COURSE_ENROLLED`, `LP_COURSE_FINISHED`, `LP_COURSE_PURCHASED`
- Item progress: `LP_ITEM_COMPLETED`, `LP_ITEM_STARTED`
- Graduation: `LP_GRADUATION_IN_PROGRESS`, `LP_GRADUATION_PASSED`, `LP_GRADUATION_FAILED`
- Orders: `LP_ORDER_COMPLETED`, `LP_ORDER_PENDING`, `LP_ORDER_PROCESSING`, `LP_ORDER_CANCELLED`, `LP_ORDER_FAILED`

**Page Contexts:** `LP_PAGE_CHECKOUT`, `LP_PAGE_COURSES`, `LP_PAGE_QUIZ`, `LP_PAGE_PROFILE`, `LP_PAGE_INSTRUCTORS`, etc.

### REST API

**Namespace:** `LearnPress\RestApi\`
**Base URL:** `/wp-json/learnpress/v1/`
**Routers:** `class-lp-core-api.php` (frontend), `class-lp-admin-core-api.php` (admin)
**Abstract base:** `inc/abstracts/abstract-rest-controller.php`

Frontend controllers in `inc/rest-api/v1/frontend/`, admin controllers in `inc/rest-api/v1/admin/`.

### AJAX Handlers

All handlers in `inc/Ajax/` extend `AbstractAjax` and implement the static `catch_lp_ajax()` method, called on `init` at priority `11`. Add-ons register handlers via `learn-press/register-ajax-handlers` hook.

### Add-on Architecture

- All add-ons extend `inc/abstracts/abstract-addon.php` (`LP_Addon`)
- `LP_Manager_Addons` manages activation/deactivation and license validation
- `purchase-code.txt` stores site license key

### Asset Build Pipeline

**JS**: Webpack (`webpack.config.js`) extends `@wordpress/scripts` config with ~123 entry points. Outputs minified bundles + `.asset.php` dependency manifests to `assets/dist/js/`. Note: requires `NODE_OPTIONS=--openssl-legacy-provider` (handled by `cross-env` in npm scripts).

**CSS**: Gulp compiles SCSS with PostCSS, auto-generates RTL variants (`-rtl.css` suffix).

### Settings

`LP_Settings` singleton (`inc/class-lp-settings.php`) — cached in WordPress options. Config files in `config/settings/`: `general.php`, `course.php`, `profile.php`, `permalink.php`, `advanced.php`, `currencies.php`.

## Code Standards

- PHP: WordPress Coding Standards (`phpcs.xml`) — applies to `inc/`, `templates/`, `config/`, `learnpress.php`; excludes `libraries/`, `vendor/`, `tests/`, `assets/`, `languages/`
- JS: ESLint with WordPress standards (`.eslintrc.js`)
- CSS: StyleLint with property order rules (`stylelint.config.js`)
- Editor: `.editorconfig` defines tabs, Unix line endings for PHP; spaces for JS/CSS
- Notable relaxed rules: short array syntax allowed, Yoda conditions not required, camelCase/snake_case both acceptable
