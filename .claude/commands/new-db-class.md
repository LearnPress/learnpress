Create a new PSR-4 Database class for LearnPress.

## Input
$ARGUMENTS = "<ClassName> [table_property]"
- ClassName: PascalCase, e.g. `MaterialDB`
- table_property (optional): property from DataBase.php, e.g. `tb_lp_files`. If omitted, ask the user.

## Rules

### File location
`inc/Databases/<ClassName>.php`

### Mandatory: read these files first before writing any code
1. `inc/Databases/DataBase.php` — to know available `$this->tb_*` table properties and the `execute()` method signature
2. `inc/Databases/UserItemsDB.php` — canonical pattern to follow

### Structure to produce

```php
<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\<ClassName matching Filter, e.g. MaterialFilter>;

defined( 'ABSPATH' ) || exit();

/**
 * Class <ClassName>
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends DataBase {
    private static $_instance;

    protected function __construct() {
        parent::__construct();
    }

    public static function getInstance(): self {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // query methods here — each accepts a Filter object + optional int &$total_rows
    // build $filter->where[] with $this->wpdb->prepare()
    // always end with: return $this->execute( $filter, $total_rows );
}
```

### Conventions
- Namespace: `LearnPress\Databases`
- Extend: `DataBase` (not `LP_Database`)
- Singleton via `private static $_instance` + `getInstance()`
- Each query method receives a typed Filter object (e.g. `MaterialFilter $filter`) and optional `int &$total_rows = 0`
- Build WHERE clauses with `$this->wpdb->prepare()`, push to `$filter->where[]`
- Use `LP_Helper::db_format_array()` for IN clauses
- Apply a WP filter before executing: `$filter = apply_filters( 'lp/<entity>/query/filter', $filter );`
- Return: `$this->execute( $filter, $total_rows )`
- Do NOT call `getInstance()` at the bottom of the file (unlike old `class-lp-*` style)
- Do NOT use `class_exists()` guard (PSR-4 autoloader handles it)

### After creating the file
- Ask the user if a corresponding Filter class is also needed (offer to run `/new-filter`)
- Remind to update `CLAUDE.md` → Databases section if the table is new