Create a new Widget class for LearnPress.

## Input
$ARGUMENTS = "<ClassName>"
- ClassName: PascalCase ending in `Widget`, e.g. `MaterialWidget`

## Rules

### File location
`inc/Widgets/<ClassName>.php`
(Sub-group e.g. Course → `inc/Widgets/Course/<ClassName>.php`)

### Mandatory: read these files first before writing any code
1. `inc/Widgets/LPWidgetBase.php` — new-style base class (PSR-4, properties-based settings)
2. `inc/Widgets/LPRegisterWidget.php` — how widgets are registered via `learn-press/widgets/register` filter
3. `inc/Widgets/course-info.php` — legacy example showing settings array structure and `lp_rest_api_content()`

### Structure to produce

```php
<?php

namespace LearnPress\Widgets;

use LearnPress\Helpers\Singleton;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class <ClassName>
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends LPWidgetBase {
    use Singleton;

    protected $lp_widget_id          = '<slug>';           // e.g. 'material'
    protected $lp_widget_name        = '';                  // translated label
    protected $lp_widget_description = '';                  // translated description
    protected $lp_widget_class       = 'learnpress widget_<slug>';

    /**
     * Widget settings fields shown in the Customizer / Widgets admin.
     * Each entry: 'key' => [ 'label', 'type', 'std' ]
     * Types: text | textarea | checkbox | select | autocomplete
     */
    protected $lp_widget_setting = [
        'title' => [
            'label' => '',   // esc_html__( 'Title', 'learnpress' )
            'type'  => 'text',
            'std'   => '',
        ],
        // add more fields as needed
    ];

    /**
     * Render widget on the frontend.
     *
     * @param array $args    Sidebar wrapper args (before_widget, after_widget, etc.)
     * @param array $instance Saved widget settings
     */
    public function widget( $args, $instance ) {
        try {
            echo $args['before_widget'];

            if ( ! empty( $instance['title'] ) ) {
                echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
            }

            // Build and echo widget HTML
            // Delegate to a TemplateHook class where possible

            echo $args['after_widget'];
        } catch ( Throwable $e ) {
            error_log( $e->getMessage() );
        }
    }

    /**
     * Save / sanitize widget settings.
     *
     * @param array $new_instance
     * @param array $old_instance
     *
     * @return array
     */
    public function update( $new_instance, $old_instance ): array {
        $instance          = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
        // sanitize remaining fields
        return $instance;
    }
}
```

### Conventions
- Namespace: `LearnPress\Widgets`
- Extend: `LPWidgetBase` (PSR-4, not legacy `LP_Widget`)
- Use `Singleton` trait
- Widget ID: `learnpress_<slug>` (prefix is added automatically by `LPWidgetBase::__construct()`)
- `$lp_widget_setting` drives the admin form — rendered automatically by `LPWidgetBase::form()`
- `widget()` renders frontend output; wrap in `try/catch( Throwable )`
- `update()` must sanitize every field
- Delegate HTML building to a TemplateHook class where possible; avoid inline HTML

### Registration
Add the class to the `learn-press/widgets/register` filter in `LPRegisterWidget::register_widgets()`:

```php
// inc/Widgets/LPRegisterWidget.php
$widgets = apply_filters(
    'learn-press/widgets/register',
    [
        <ClassName>::class,   // ← add this line
    ]
);
```

Show the user the exact line to add after creating the file.