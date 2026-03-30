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
- Test files: `tests/Unit/Models/CourseModelTest.php`, `UserModelTest.php`
- Helper: `tests/Helpers/BrainMonkeyTestCase.php` — base test case
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

| Path | Purpose                                                                                                   |
|------|-----------------------------------------------------------------------------------------------------------|
| `inc/Databases/` | Repository layer — all raw DB queries per entity                                                          |
| `inc/Models/` | Business logic and data models — see breakdown below                                                      |
| `inc/Filters/` | Data transformation applied before/after DB operations, map columns of table to same properties of object |
| `inc/Services/` | Service layer — orchestrates Model + DB operations; use `Singleton` trait; namespace `LearnPress\Services` |
| `inc/curds/` | CRUD implementations (implements `inc/interfaces/interface-curd.php`)                                     |
| `inc/Ajax/` | AJAX handlers — each extends `AbstractAjax`, implements `catch_lp_ajax()`                                 |
| `inc/TemplateHooks/` | Template hook registrars for specific pages/contexts                                                      |
| `inc/abstracts/` | Base classes: `LP_Addon`, `LP_Post_Data`, `LP_Object_Data`                                                |
| `inc/Admin/` | Admin pages, menus, settings UI (188 files)                                                               |
| `inc/Cache/` | Object caching layer (courses, quizzes, sessions)                                                         |
| `inc/Background/` | Async/background task processors                                                                          |
| `inc/Gutenberg/` | Gutenberg block system (80+ files)                                                                        |
| `inc/Shortcodes/` | Shortcode implementations (17 files)                                                                      |
| `inc/Widgets/` | Frontend widget classes (10 files)                                                                        |
| `inc/ExternalPlugin/` | 3rd-party integrations (Elementor, Yoast SEO, Rank Math, Polylang)                                        |
| `inc/gateways/` | Payment gateways (PayPal, offline)                                                                        |
| `inc/WPGDPR/` | GDPR data export/erasure handlers                                                                         |
| `inc/rest-api/` | REST API controllers — see breakdown below                                                                |
| `templates/` | Frontend PHP templates                                                                                    |
| `assets/src/scss/` | SCSS source (compiled to `assets/dist/css/`)                                                              |
| `assets/src/apps/js/` | React apps (Gutenberg blocks, admin editors, frontend)                                                    |
| `assets/dist/js/` | Compiled JS output with `.asset.php` dependency manifests                                                 |
| `config/` | Static config (settings, fields, delivery types, Elementor widgets)                                       |

### Models Breakdown (`inc/Models/`)

| Model file | Purpose |
|-----------|---------|
| `PostModel.php` | Base model for WP post-backed entities |
| `CourseModel.php` | Course business logic |
| `CoursePostModel.php` | Course post data (post meta, author, etc.) |
| `CourseSectionModel.php` | Section within a course |
| `CourseSectionItemModel.php` | Item (lesson/quiz) within a section |
| `LessonPostModel.php` | Lesson post data |
| `QuizPostModel.php` | Quiz post data |
| `UserModel.php` | Enrolled user data & course progress |
| `UserItems/UserItemModel.php` | Base model for a user's enrollment item |
| `UserItems/UserCourseModel.php` | User ↔ course enrollment |
| `UserItems/UserLessonModel.php` | User ↔ lesson progress |
| `UserItems/UserQuizModel.php` | User ↔ quiz attempt |
| `UserItemMeta/UserItemMetaModel.php` | Generic metadata for user items |
| `UserItemMeta/UserQuizMetaModel.php` | Quiz-specific metadata (answers, results) |
| `Question/QuestionPostModel.php` | Base question post model |
| `Question/QuestionPostSingleChoiceModel.php` | Single-choice question |
| `Question/QuestionPostMultipleChoiceModel.php` | Multiple-choice question |
| `Question/QuestionPostTrueFalseModel.php` | True/false question |
| `Question/QuestionPostFIBModel.php` | Fill-in-the-blank question |
| `Question/QuestionSortingChoiceModel.php` | Sorting/ordering question |
| `Question/QuestionAnswerModel.php` | A single answer option |
| `Quiz/QuizQuestionModel.php` | Quiz ↔ question relationship |
| `WPTables/CoursesTable.php` | WP_List_Table for courses admin list |
| `steps/class-lp-step.php` | Single step in a course flow |
| `steps/class-lp-group-step.php` | Grouped steps |
| `Courses.php` | Collection/query helper for courses |
| `ListCourseCategories.php` | Course category query helper |
| `class-lp-course-extra-info-fast-query-model.php` | Fast query optimization model |
| `class-lp-rest-response.php` | REST API response wrapper |

### REST API (`inc/rest-api/`)

**Namespace:** `LearnPress\RestApi\`
**Base URL:** `/wp-json/learnpress/v1/`
**Routers:** `class-lp-core-api.php` (frontend), `class-lp-admin-core-api.php` (admin)

**Frontend Controllers (`v1/frontend/`):**

| Controller | Endpoint area |
|-----------|---------------|
| `class-lp-rest-courses-controller.php` | Course listing & filtering |
| `class-lp-rest-student-controller.php` | Student data |
| `class-lp-rest-profile-controller.php` | User profile |
| `class-lp-rest-instructor-controller.php` | Instructor data |
| `class-lp-rest-users-controller.php` | User management |
| `class-lp-rest-settings-controller.php` | Frontend settings |
| `class-lp-rest-lazy-load-controller.php` | Lazy loading content |
| `class-lp-rest-ajax-controller.php` | AJAX proxy endpoints |
| `class-lp-rest-addon-controller.php` | Add-on API |
| `class-lp-rest-material-controller.php` | Course materials |
| `class-lp-rest-widgets-controller.php` | Widget data |

**Admin Controllers (`v1/admin/`):**

| Controller | Endpoint area |
|-----------|---------------|
| `class-lp-admin-rest-course-controller.php` | Course management |
| `class-lp-admin-rest-database-controller.php` | Database queries |
| `class-lp-admin-rest-reset-data-controller.php` | Data reset |
| `class-lp-admin-rest-statistics-controller.php` | Statistics |
| `class-lp-admin-rest-tools-controller.php` | Tools & utilities |

**Abstract base:** `inc/abstracts/abstract-rest-controller.php`

### AJAX Handlers (`inc/Ajax/`)

All handlers extend `AbstractAjax` and implement the static `catch_lp_ajax()` method, called on `init` at priority `11`.

| Handler | Purpose |
|---------|---------|
| `LoadContentViaAjax.php` | Load lesson/quiz content dynamically |
| `LessonAjax.php` | Lesson actions (start, mark complete) |
| `EditCurriculumAjax.php` | Curriculum editor operations |
| `EditQuizAjax.php` | Quiz editing |
| `EditQuestionAjax.php` | Question editing |
| `SendEmailAjax.php` | Email sending from admin |
| `ExportOrderCSVAjax.php` | Export orders to CSV |
| `AI/OpenAiAjax.php` | OpenAI course/content generation |

### Data Layer Pattern

All data access follows: **Model → Database class → Filter → WP DB**

- `inc/Databases/DataBase.php` is the global DB singleton
- Each entity has its own DB class (e.g., `LP_Course_DB`, `LP_User_Items_DB`)
- Filters in `inc/Filters/` shape query args before execution
- Models handle caching via `LP_Object_Cache`

### Add-on Architecture

- All add-ons extend `inc/abstracts/abstract-addon.php` (`LP_Addon`)
- Add-ons register their AJAX handlers via `learn-press/register-ajax-handlers` hook
- `LP_Manager_Addons` manages activation/deactivation and license validation
- `purchase-code.txt` stores site license key

### Asset Build Pipeline

**JS**: Webpack (`webpack.config.js`) extends `@wordpress/scripts` config with ~123 entry points. Outputs minified bundles + `.asset.php` dependency manifests to `assets/dist/js/`.

Entry point categories:
- Admin pages (~14): admin, learnpress, admin-order, admin-tools, admin-statistic, edit-course, edit-quiz, edit-question, etc.
- Frontend pages (~10): courses, profile, instructors, checkout, single-course, lesson, quiz, course-filter, etc.
- Gutenberg blocks (~95): archive/single course, course elements, instructor elements, filter elements, breadcrumb

**CSS**: Gulp compiles SCSS with PostCSS, auto-generates RTL variants (`-rtl.css` suffix).

### Custom Post Types

Registered in `inc/custom-post-types/`: `lp_course`, `lp_lesson`, `lp_quiz`, `lp_question`, `lp_order`

### Settings

`LP_Settings` singleton (`inc/class-lp-settings.php`) — settings cached in WordPress options. Admin UI in `inc/admin/settings/`.

Config files in `config/settings/`: `general.php`, `course.php`, `profile.php`, `permalink.php`, `advanced.php`, `currencies.php`, plus OpenAI prompt templates.

### External Integrations (`inc/ExternalPlugin/`)

- **Elementor** (44 files): Full widget library mirroring Gutenberg blocks, with a skin system for list variations
- **Yoast SEO**: `LPYoastSeo.php`
- **Rank Math**: `LPRankMath.php`
- **Polylang**: `class-lp-polylang.php`

### Background Processing (`inc/Background/`)

Custom async pattern — does **not** use WP Background Processing library.

- `LPBackgroundTrigger.php` — Background job orchestrator
- `LPBackgroundAjax.php` — AJAX-triggered background tasks
- `LPAsyncRequest.php` — Async HTTP request handler

### GDPR (`inc/WPGDPR/`)

- `ExportPersonalData.php` — Handles WP personal data export requests
- `ErasePersonalData.php` — Handles WP personal data erasure requests

### Templates (`templates/`)

| Directory | Purpose |
|-----------|---------|
| `block/` | Gutenberg block templates |
| `checkout/` | Checkout page |
| `content-lesson/` | Lesson content wrappers |
| `content-quiz/` | Quiz content wrappers |
| `emails/` | Email templates |
| `global/` | Shared global components |
| `loop/` | Course archive/loop |
| `order/` | Order display |
| `pages/` | Page-level (courses list, profile, etc.) |
| `profile/` | User profile tabs |
| `shared/` | Shared component partials |
| `shortcode/` | Shortcode output |
| `single-course/` | Single course page |
| `widgets/` | Widget output |

## Code Standards

- PHP: WordPress Coding Standards (`phpcs.xml`) — applies to `inc/`, `templates/`, `config/`, `learnpress.php`; excludes `libraries/`, `vendor/`, `tests/`, `assets/`, `languages/`
- JS: ESLint with WordPress standards (`.eslintrc.js`)
- CSS: StyleLint with property order rules (`stylelint.config.js`)
- Editor: `.editorconfig` defines tabs, Unix line endings for PHP; spaces for JS/CSS
- Notable relaxed rules: short array syntax allowed, Yoda conditions not required, camelCase/snake_case both acceptable
