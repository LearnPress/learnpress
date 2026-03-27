Create a new PSR-4 Filter class for LearnPress.

## Input
$ARGUMENTS = "<ClassName> [table_columns...]"
- ClassName: PascalCase ending in `Filter`, e.g. `MaterialFilter`
- table_columns (optional): comma-separated column names of the target table. If omitted, ask the user for the table name then read the schema from `config/table/` or the relevant DB class.

## Rules

### File location
`inc/Filters/<ClassName>.php`

### Mandatory: read these files first before writing any code
1. `inc/Filters/FilterBase.php` — base class, all inherited properties
2. `inc/Filters/UserItemsFilter.php` — canonical pattern to follow

### Structure to produce

```php
<?php

namespace LearnPress\Filters;

/**
 * Class <ClassName>
 *
 * Filter query for <wp_table_name> table
 *
 * @since 4.x.x
 * @version 1.0.0
 */
class <ClassName> extends FilterBase {
    // 1. COL_* constants — one per column
    const COL_<COLUMN> = '<column>';

    // 2. $all_fields — all column names as array of COL_* constants
    public array $all_fields = [
        self::COL_<COLUMN>,
        // ...
    ];

    // 3. Public properties — one per filterable field
    // Single value: public $<column>;
    // Multiple values (IN): public $<column>s = [];
    // Always add @var type docblock

    // 4. $field_count — primary key column used for COUNT(*)
    public $field_count = self::COL_<PRIMARY_KEY>;
}
```

### Conventions
- Namespace: `LearnPress\Filters`
- Extend: `FilterBase`
- No constructor needed unless custom default values required
- Constants: `COL_` prefix + UPPER_SNAKE column name
- Properties: snake_case, matches column name; plural array variant for IN queries (e.g. `$status` + `$statues = []`)
- `$all_fields` must list every column of the target table
- `$field_count` must point to the primary key column constant
- No `defined('ABSPATH')` guard needed (FilterBase has none; consistent style)

### After creating the file
- Ask if a corresponding DB class needs to be created or updated to use this filter