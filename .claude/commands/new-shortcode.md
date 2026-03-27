Create a new Shortcode class for LearnPress.

## Input
$ARGUMENTS = "<ClassName> <shortcode_name>"
- ClassName: PascalCase ending in `Shortcode`, e.g. `MaterialListShortcode`
- shortcode_name: snake_case tag (without `learn_press_` prefix), e.g. `material_list`
  → registered as `[learn_press_material_list]`

## Rules

### File location
`inc/Shortcodes/<ClassName>.php`
(If it belongs to a sub-group like Course, place in `inc/Shortcodes/Course/<ClassName>.php`)

### Mandatory: read these files first before writing any code
1. `inc/Shortcodes/AbstractShortcode.php` — base class contract
2. `inc/Shortcodes/CourseButtonShortcode.php` — canonical pattern (Singleton, render, try/catch)

### Structure to produce

```php
<?php

namespace LearnPress\Shortcodes;

use LearnPress\Helpers\Singleton;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class <ClassName>
 *
 * Shortcode: [learn_press_<shortcode_name>]
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends AbstractShortcode {
    use Singleton;

    protected $shortcode_name = '<shortcode_name>';

    /**
     * Render shortcode output.
     *
     * @param string|array $atts  Shortcode attributes (empty string when none provided)
     *
     * @return string
     */
    public function render( $atts ): string {
        $html = '';

        $atts = shortcode_atts(
            [
                // default attribute => default value
                'id' => 0,
            ],
            $atts,
            $this->prefix . $this->shortcode_name
        );

        try {
            // Enqueue required assets
            wp_enqueue_style( 'learnpress' );

            // Business logic via Model / TemplateHook classes
            // Build $html string

        } catch ( Throwable $e ) {
            error_log( $e->getMessage() );
        }

        return $html;
    }
}
```

### Conventions
- Namespace: `LearnPress\Shortcodes`
- Extend: `AbstractShortcode`
- Use `Singleton` trait — `init()` (from `AbstractShortcode`) is called automatically
- `$shortcode_name` must be snake_case; the full tag becomes `learn_press_<shortcode_name>`
- `render()` must return a string — never echo
- Always call `shortcode_atts()` to merge defaults and sanitize
- Wrap logic in `try/catch( Throwable $e )` + `error_log`
- No raw DB calls — delegate to Model or TemplateHook classes
- Enqueue styles/scripts only when the shortcode actually renders (inside `render()`)

### Registration
- Add `<ClassName>::instance();` to the shortcodes registration location in `inc/class-lp-shortcodes.php`
- Show the user the exact line to add after creating the file