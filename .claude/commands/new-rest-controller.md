Create a new REST API controller for LearnPress.

## Input
$ARGUMENTS = "<ClassName> [frontend|admin]"
- ClassName: PascalCase, e.g. `LP_REST_Material_Controller`
- Scope: `frontend` (default) or `admin`

## Rules

### File location
- Frontend: `inc/rest-api/v1/frontend/class-lp-rest-<slug>-controller.php`
- Admin: `inc/rest-api/v1/admin/class-lp-admin-rest-<slug>-controller.php`

### Mandatory: read these files first before writing any code
1. `inc/abstracts/abstract-rest-controller.php` — base class, `register_routes()` contract
2. `inc/rest-api/v1/frontend/class-lp-rest-courses-controller.php` — canonical pattern

### Structure to produce

```php
<?php

/**
 * Class <ClassName>
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends LP_Abstract_REST_Controller {

    public function __construct() {
        $this->namespace = 'lp/v1';
        $this->rest_base = '<slug>';  // e.g. 'materials'
        parent::__construct();
    }

    public function register_routes() {
        $this->routes = array(
            '' => array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(),
                ),
            ),
            '(?P<id>[\d]+)' => array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => '__return_true',
                ),
            ),
        );

        parent::register_routes();
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_items( $request ) {
        $response = new LP_REST_Response();

        try {
            // Business logic here
            $response->status = 'success';
        } catch ( Throwable $e ) {
            $response->message = $e->getMessage();
        }

        return rest_ensure_response( $response );
    }
}
```

### Conventions
- No PSR-4 namespace (legacy global class name style — match existing frontend controllers)
- Extend `LP_Abstract_REST_Controller`
- `$this->namespace = 'lp/v1'` always
- `$this->rest_base` = plural slug in kebab-case
- Always call `parent::register_routes()` at the end of `register_routes()`
- Use `LP_REST_Response` for response objects
- Wrap logic in `try/catch( Throwable $e )`
- Return `rest_ensure_response( $response )`
- Use `current_user_can()` inside `permission_callback` for protected endpoints

### After creating the file
- Show the registration line to add in `class-lp-core-api.php` (frontend) or `class-lp-admin-core-api.php` (admin)