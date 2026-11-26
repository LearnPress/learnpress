# Progress — flow-diagram-purchase-course

**Status:** ✅ Complete
**Started:** 2026-09-18
**Last updated:** 2026-11-26

## Done
1. Step 1: Built the authoritative flow inventory for button visibility, REST cart insertion, repurchase, checkout validation, order/payment completion, and order-status-driven user-course updates.
2. Step 2: Created `flow-chart/logic/purchase-course.md` with the end-to-end Mermaid business logic flowchart.
3. Step 3: Created `flow-chart/diagram/purchase-course.md` with the component-level Mermaid sequence diagram.
4. Step 4: Cross-checked transitions against the scoped implementation and validated Markdown fences, Mermaid declarations, and sequence block nesting.

## In progress

## Next
- None.

## Decisions made
- Use Mermaid in Markdown, following `flow-chart/README.md`.
- Create one business logic flowchart and one system sequence diagram with matching `purchase-course.md` filenames.
- Keep this task documentation-only; no production PHP or JavaScript changes are in scope.
- Include both completed-order course access and reversal from completed to non-completed order statuses.
- Represent payment processing at the `LP_Gateway_Abstract::process_payment()` boundary; gateway-specific callbacks and internals remain out of scope.

## Blockers / Notes
- None. Implementation and structural validation are complete.

## Resume here
- Nothing pending. Flowchart `flow-chart/logic/purchase-course-code.md` follows the AJAX purchase flow (since 4.5.1).

## Log
- 2026-11-26: Updated `flow-chart/logic/purchase-course-code.md` for the new purchase flow: REST `lp/v1/courses/purchase-course` and `purchaseCourse()` (apps single-course.js) are deprecated; request now goes `SingleCourse::purchaseCourse` (assets/src/js/frontend/single-course.js) -> `lp-load-ajax=user_purchase_course` -> `AbstractAjax::catch_lp_ajax` (wp_rest nonce, action nonce for logged-in only) -> `UserCourseAjax::user_purchase_course` -> `UserCourseService::purchase`; repurchase popup is SweetAlert. Checkout/order part unchanged. Verified structurally (balanced brackets, no undefined node ids, fences); not rendered with Mermaid (no local renderer).

