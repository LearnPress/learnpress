Create a new Model class for LearnPress.

## Input
$ARGUMENTS = "<ClassName> [post-backed|data-only]"
- ClassName: PascalCase ending in `Model`, e.g. `MaterialModel`
- Type:
  - `post-backed` (default): model wraps a WP post → extend `CoursePostModel` or `PostModel`
  - `data-only`: plain data model with no WP post → extend nothing or a minimal base

## Rules

### File location
`inc/Models/<ClassName>.php`

### Mandatory: read these files first before writing any code
1. `inc/Models/PostModel.php` — base for post-backed models
2. `inc/Models/CoursePostModel.php` — example of a post-backed model
3. `inc/Models/UserItems/UserItemModel.php` — example of a data-only model

### Structure — post-backed model

```php
<?php

namespace LearnPress\Models;

use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class <ClassName>
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends PostModel {
    /**
     * @var string WP post type this model represents
     */
    public static string $post_type = '<lp_post_type>';

    /**
     * Find and return an instance, optionally from cache.
     *
     * @param int  $id
     * @param bool $force  Skip cache if true
     * @return static|false
     * @throws Exception
     */
    public static function find( int $id, bool $force = false ) {
        // Check cache first unless $force
        // Load from DB via DB class
        // Store in cache
        // Return model instance or false
    }
}
```

### Structure — data-only model

```php
<?php

namespace LearnPress\Models\<SubFolder>;

defined( 'ABSPATH' ) || exit();

/**
 * Class <ClassName>
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> {
    // Public typed properties matching DB columns
    public int    $id    = 0;
    public string $name  = '';

    /**
     * Populate from stdClass DB row.
     */
    public static function from_object( object $data ): self {
        $instance = new self();
        foreach ( get_object_vars( $data ) as $key => $value ) {
            if ( property_exists( $instance, $key ) ) {
                $instance->{$key} = $value;
            }
        }
        return $instance;
    }
}
```

### Conventions
- Namespace: `LearnPress\Models` (or sub-namespace matching folder, e.g. `LearnPress\Models\UserItems`)
- PSR-4: filename matches class name exactly
- Caching: use `LP_Object_Cache` if the model is frequently loaded
- Static `find( int $id, bool $force )` for single-record lookup
- No raw DB calls inside models — delegate to the appropriate DB class in `inc/Databases/`

### After creating the file
- Ask if a Unit test should be scaffolded (`tests/Unit/Models/<ClassName>Test.php`)