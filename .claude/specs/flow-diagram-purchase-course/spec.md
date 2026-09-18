# flow-diagram-purchase-course

> Document the complete LearnPress purchase-course flow with a business logic flowchart and a system sequence diagram.

## Goal
Describe how a course purchase moves from rendering the Buy Now button, through the purchase REST API and checkout AJAX request, to order-status handling and course access creation or update.

## Requirements
- [x] Add a Mermaid business logic flowchart under `flow-chart/logic/`.
- [x] Add a Mermaid sequence diagram under `flow-chart/diagram/`.
- [x] Cover purchase-button visibility and `CourseModel::can_purchase()` outcomes.
- [x] Cover the `form.purchase-course` submit event and `POST lp/v1/courses/purchase-course` request.
- [x] Cover validation, repurchase choices, cart updates, and checkout redirection in the purchase REST endpoint.
- [x] Cover checkout submission from `checkout.js` through the `checkout` AJAX event to `LP_Checkout::process_checkout_handler()`.
- [x] Cover checkout validation, order creation/payment processing, and success or failure outcomes.
- [x] Cover order-status listeners in `LP_User_Factory` and creation or update of the purchased course user item.
- [x] Show important branches for guest checkout, first purchase, repurchase with keep/reset, free or no-enrollment-requirement courses, and errors.
- [x] Link diagram nodes or notes to the authoritative classes, methods, hooks, and endpoints where practical.

## Acceptance Criteria
- [x] Both documents render successfully as Mermaid diagrams in Markdown.
- [x] The logic chart communicates the main business decisions and success/error paths from Buy Now to course access.
- [x] The sequence diagram identifies the browser, REST API, cart, checkout AJAX handler, checkout/order services, payment gateway, and user-course handler.
- [x] The documented flow matches the current implementation and does not describe speculative behavior.
- [x] Filenames and terminology are consistent between the two documents.

## Scope
- `flow-chart/logic/`
- `flow-chart/diagram/`
- `inc/TemplateHooks/Course/SingleCourseTemplate.php`
- `assets/src/apps/js/frontend/single-course.js`
- `inc/rest-api/v1/frontend/class-lp-rest-courses-controller.php`
- `assets/src/js/frontend/checkout.js`
- `inc/class-lp-ajax.php`
- `inc/class-lp-checkout.php`
- `inc/user/class-lp-user-factory.php`

## Out of scope
- Changing purchase, checkout, payment, order, or enrollment behavior.
- Changing PHP, JavaScript, CSS, database schemas, or REST contracts.
- Documenting gateway-specific internals beyond their role in the core purchase flow.

## References
- `flow-chart/README.md`
- `SingleCourseTemplate::html_btn_purchase_course()`
- `purchaseCourse()` in `single-course.js`
- `LP_REST_Courses_Controller::purchase_course()`
- Checkout handling in `checkout.js`
- `LP_AJAX::checkout()`
- `LP_Checkout::process_checkout_handler()` and `LP_Checkout::process_checkout()`
- Order-status handling and `LP_User_Factory::handle_item_order_completed()`
