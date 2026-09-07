# Setup Finish and Demo Course Import Design

## Goal

Complete the LearnPress setup wizard Finish step and replace its simulated demo-course animation with a secure, data-driven import flow. The importer will be ready before the final `demo-data-courses.json` content is supplied.

## Finish screen

The screen keeps the approved two-card layout and three UI states: idle, importing, and complete. Configuration Summary values come from current LearnPress settings instead of placeholder strings.

`Create Your First Course` links to `post-new.php?post_type=lp_course` in the current tab. `Advanced Settings`, documentation, course-page, and profile-page links retain their intended destinations.

The demo import UI derives its course title, current item, total count, and percentage from the server response. No count such as five or six is hardcoded.

## JSON contract

The importer reads `inc/admin/setup/demo-data-courses.json`. Its root contains a `courses` array. Each course has:

- `slug`: stable identifier used to prevent duplicates.
- `title`: course title.
- `content`: course description.
- `excerpt`: optional short description.
- `status`: optional `draft` or `publish`, defaulting to `publish`.
- `section`: one object containing `title`, optional `description`, and a `lessons` array.

Each lesson has `title`, `content`, and an optional `duration`. Quiz data is outside this contract. The final data file will contain five courses, each with exactly one section and five lessons.

The JSON reader validates the complete document before inserting anything. It rejects missing files, malformed JSON, an empty course list, duplicate or invalid slugs, missing titles, missing section data, and invalid lesson lists.

## Server flow

A setup-wizard AJAX action accepts an authenticated administrator request with a dedicated nonce. It verifies `manage_options`, loads and validates the JSON file, then imports one requested course per call. Small requests allow the UI to report real progress and avoid a long PHP request.

Course, section, and lesson creation uses LearnPress models/services and their established relationships. Every imported course stores its JSON slug in dedicated post meta. If that slug already exists, the endpoint reports the course as already imported instead of creating a duplicate.

Each response contains the processed index, total courses, course title, percentage, result (`created` or `existing`), and relevant URLs. The client requests the next course until the server reports completion.

## Errors and recovery

Validation happens before the first import call creates content. Runtime failures return a translated message and leave the Install button available for retry. A retry skips courses already marked with the source slug and continues with the remaining courses.

The page shows an error state inside the action card. Buttons are disabled only while a request is running. Network, nonce, permission, invalid-data, and persistence failures use the same response structure.

## Completion behavior

After all JSON entries are created or found, the card reports the actual total and links to the LearnPress course list. The wizard remains completed. The final footer action leads to the WordPress dashboard.

## Testing

PHP tests cover JSON validation, permission and nonce rejection, course/section/lesson creation, duplicate prevention, progress responses, and failure responses. Rendering tests cover settings-backed summary values and same-tab course creation. JavaScript tests cover idle, importing, error, retry, and complete state transitions without opening a browser.
