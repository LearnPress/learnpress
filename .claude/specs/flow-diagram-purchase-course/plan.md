# Plan — flow-diagram-purchase-course

## Steps
- [x] Step 1: Build the authoritative flow inventory from the scoped code, recording actors, method/hook transitions, and branch outcomes for button visibility, repurchase, checkout, payment, and order status changes.
- [x] Step 2: Create `flow-chart/logic/purchase-course.md` with a Mermaid `flowchart TD` covering:
  - `SingleCourseTemplate::html_btn_purchase_course()` and `CourseModel::can_purchase()` success, hidden, and warning outcomes.
  - `purchaseCourse()` form submission, REST errors, repurchase popup with `keep`/`reset`, cart insertion, and checkout redirect.
  - Checkout submission, cart/item/nonce/payment validation, user or guest order creation, gateway versus free-course completion, and response redirect/error.
  - `learn-press/order/status-changed` branches for completed access creation/update and reversal from completed to pending, processing, cancelled, failed, or refunded.
- [x] Step 3: Create `flow-chart/diagram/purchase-course.md` with a Mermaid `sequenceDiagram` using participants for user/browser, template, course frontend JS, REST controller, cart, checkout page JS, `LP_AJAX`, `LP_Checkout`, order/payment gateway, and `LP_User_Factory`; use `alt`/`opt` blocks for errors, repurchase, guest checkout, paid/free completion, and user-course updates.
- [x] Step 4: Cross-check every diagram transition against the scoped methods and hooks, ensure both documents use matching terminology, and validate Mermaid syntax and Markdown rendering without changing runtime code.

## Files to create
| File | Purpose |
|------|---------|
| `flow-chart/logic/purchase-course.md` | End-to-end business decisions and success/error paths from Buy Now to course access or cancellation. |
| `flow-chart/diagram/purchase-course.md` | Component-level sequence from rendering and REST cart insertion through checkout, payment, order status, and user-course handling. |

## Files to modify
| File | Change |
|------|--------|
| `.claude/specs/flow-diagram-purchase-course/plan.md` | Track ordered implementation steps and verification scope. |
| `.claude/specs/flow-diagram-purchase-course/progress.md` | Track completed work, next actions, and decisions. |

## Format code when create file done run > php vendor/squizlabs/php_codesniffer/bin/phpcs --standard=phpcs.xml [file-name]

Not applicable to Mermaid Markdown files. Validate Mermaid syntax and Markdown rendering instead.

## Open questions
- None. Represent payment gateways at the `LP_Gateway_Abstract::process_payment()` boundary because gateway-specific internals are out of scope.
