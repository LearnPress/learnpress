# modify-material-db

> Add get_files method to MaterialFilesDB using MaterialFilter

## Goal
<!-- What problem does this solve? What is the expected outcome? -->
Add a `get_files` method to the MaterialFilesDB class that uses MaterialFilter to query material files, following the pattern of CourseJsonDB.php `get_courses` method.

## Requirements
<!-- Functional requirements — what must work when this is done -->
- [ ] Implement `get_files` method in MaterialFilesDB.php
- [ ] Use MaterialFilter as a parameter for the `get_files` method
- [ ] Follow the implementation pattern of CourseJsonDB's `get_courses` method
- [ ] Ensure the method adheres to LearnPress's database layer architecture

## Acceptance Criteria
<!-- How to verify it's done correctly -->
- [ ] `get_files` method accepts MaterialFilter and returns filtered material file results
- [ ] Method behavior matches CourseJsonDB's `get_courses` method structure
- [ ] No breaking changes to existing MaterialFilesDB functionality

## Scope
<!-- Files / areas of code this touches. List known files if any. -->
- `inc/Databases/MaterialFilesDB.php`
- `inc/Filters/MaterialFilter.php`
- `inc/Databases/Material/` (if relevant)

## Out of scope
<!-- What is explicitly NOT part of this feature -->
- Modifying other MaterialFilesDB methods
- Adding new filter properties to MaterialFilter
- Changing CourseJsonDB's `get_courses` method

## References
<!-- Related files to read, issues, prior art -->
- CourseJsonDB's `get_courses` method (reference implementation)
- MaterialFilter class (parameter usage)
- LearnPress database layer architecture documentation