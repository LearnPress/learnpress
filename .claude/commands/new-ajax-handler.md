Create a new AJAX handler class for LearnPress.

## Input
$ARGUMENTS = "<ClassName>"
- ClassName: PascalCase ending in `Ajax`, e.g. `MaterialAjax`

## Rules

### File location
`inc/Ajax/<ClassName>.php`

### Mandatory: read these files first before writing any code
1. `inc/Ajax/AbstractAjax.php` — base class, hook registration pattern
2. `inc/Ajax/LessonAjax.php` — canonical handler pattern (try/catch, LP_Request, response)

### Structure to produce

```php
<?php

namespace LearnPress\Ajax;

use Exception;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * Class <ClassName>
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends AbstractAjax {

    /**
     * <Action description>
     *
     * @since 4.x.x
     * @version 1.0.0
     */
    public function <action_name>() {
        $response = new LP_REST_Response();

        try {
            // 1. Read params via LP_Request::get_param()
            // 2. Validate — throw Exception on failure
            // 3. Business logic via Model classes
            // 4. Set $response->data and $response->message

            $response->status = 'success';
        } catch ( Throwable $e ) {
            $response->message = $e->getMessage();
        }

        wp_send_json( $response );
    }
}
```

### Conventions
- Namespace: `LearnPress\Ajax`
- Extend: `AbstractAjax`
- Read input with `LP_Request::get_param( 'key', default, 'type', 'method' )`
- Always wrap logic in `try { } catch ( Throwable $e ) { }`
- Use Model classes for business logic — no raw DB calls in AJAX handlers
- End with `wp_send_json( $response )` (JSON responses) or `wp_safe_redirect()` + `die()` (form redirects)
- Register the handler by adding `<ClassName>::catch_lp_ajax();` inside `lp_main_handle()` in `learnpress.php` (or the appropriate init hook at priority 11)

### After creating the file
- Show the registration line the user needs to add
- Ask if a REST endpoint alternative is also needed