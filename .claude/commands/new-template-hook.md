Create a new TemplateHook class for LearnPress.

## Input
$ARGUMENTS = "<ClassName> [SubFolder]"
- ClassName: PascalCase ending in `Template`, e.g. `SingleMaterialTemplate`
- SubFolder (optional): e.g. `Course`, `Profile`, `Admin`, `Instructor`, `Order`. If omitted, place directly in `inc/TemplateHooks/`.

## Rules

### File location
- With subfolder: `inc/TemplateHooks/<SubFolder>/<ClassName>.php`
- Without subfolder: `inc/TemplateHooks/<ClassName>.php`

### Mandatory: read these files first before writing any code
1. `inc/TemplateHooks/Course/SingleCourseTemplate.php` — canonical pattern (Singleton, init(), html_* methods)
2. `inc/TemplateHooks/Profile/ProfileCoursesTemplate.php` — simpler example
3. `inc/Helpers/Template.php` — to know available Template helper methods (`nest_elements`, `instance`, etc.)

### Structure to produce

```php
<?php
/**
 * Template hooks <description>.
 *
 * @since 4.x.x
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\<SubFolder>;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use Throwable;

defined( 'ABSPATH' ) || exit();

class <ClassName> {
    use Singleton;

    public function init() {
        // Register WP actions/filters for this context
        // e.g. add_action( 'learn-press/<context>/layout', [ $this, 'layout' ] );
    }

    /**
     * Render full layout for this context.
     *
     * @param array $args
     */
    public function layout( array $args = [] ) {
        ob_start();

        try {
            // Build HTML sections
            // Use Template::instance()->nest_elements( $html_wrapper, $content )
        } catch ( Throwable $e ) {
            error_log( $e->getMessage() );
        }

        echo ob_get_clean();
    }

    /**
     * Return HTML for a single element.
     *
     * @return string
     */
    public function html_<element>( /* typed args */ ): string {
        $html_wrapper = apply_filters(
            'learn-press/<context>/html-<element>',
            [ '<opening-tag>' => '</closing-tag>' ]
        );

        return Template::instance()->nest_elements( $html_wrapper, /* content */ '' );
    }
}
```

### Conventions
- Namespace: `LearnPress\TemplateHooks\<SubFolder>` (omit subfolder segment if none)
- Use `Singleton` trait — no manual `getInstance()` needed; access via `<ClassName>::instance()`
- `init()` is called automatically by the Singleton trait — register all hooks here
- Hook names follow pattern: `learn-press/<context>/<action>` (kebab-case)
- HTML builder methods: named `html_<element>()`, return `string`, never echo directly
- Use `Template::instance()->nest_elements( $html_wrapper, $content )` for wrapping HTML
- Wrap rendering logic in `try/catch( Throwable $e )` with `error_log`
- Apply a WP filter on every `$html_wrapper` so themes/add-ons can override markup
- Use `ob_start()` / `ob_get_clean()` in `layout()` methods that call template files

### Registration
- The class is instantiated (and `init()` called) by calling `<ClassName>::instance()` in the appropriate bootstrap location (e.g., `lp_main_handle()` or the relevant page controller)
- Ask the user where to add the instantiation call if not obvious