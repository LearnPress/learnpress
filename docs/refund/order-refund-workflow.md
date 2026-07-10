# LearnPress Refund Workflow

## Overview
This document describes the current refund workflow in LearnPress:
- Only logged-in users can request refund (guest is not supported).
- Only gateways with callable `refund()` are treated as refund-capable.
- `refund_max_completion` is an eligibility gate:
  - `0`: no limit by learning progress.
  - `> 0`: reject when `completion_percent > threshold`.
- If gate passes, execution always calls full refund: `refund( $order_id )`.

## Main Flow (Customer + Admin)

```mermaid
flowchart TD
    A["Customer clicks Refund in Profile Orders"] --> B["AJAX request: lp-load-ajax=request_refund_order"]
    B --> C{"Eligibility pass?"}
    C -->|No| C1["Return error message"]
    C -->|Yes| D{"Auto Refund = yes?"}

    D -->|Yes| E["Execute refund immediately"]
    E --> F{"Gateway refund() success?"}
    F -->|No| F1["Keep status completed, add error note"]
    F -->|Yes| G["Order status -> refunded"]
    G --> H["Request status -> auto-approved"]
    H --> I["LP user items -> cancel"]

    D -->|No| J["Create pending refund request"]
    J --> K["Store request meta + history"]
    K --> L["Admin reviews on order edit screen"]
    L --> M{"Admin action"}
    M -->|Approve| N["Execute refund"]
    N --> O{"Gateway refund() success?"}
    O -->|No| O1["Keep status completed, add error note"]
    O -->|Yes| P["Order status -> refunded"]
    P --> Q["Request status -> approved"]
    Q --> R["LP user items -> cancel"]
    M -->|Deny| S["Request status -> denied"]
```

## Eligibility Gate Details
- `enable_refund_requests = yes`.
- Order is not guest and not already refunded.
- Order status must be `completed`.
- Payment method must exist in `learn_press_get_order_refund_supported_gateways()`.
- No duplicate pending request.
- Re-request follows `allow_refund_rerequest`.
- Time limit follows `refund_time_limit`.
- Completion gate:
  - Progress source: `UserCourseModel::find(...)->calculate_course_results(true)`.
  - Aggregation: max progress among courses in the order.
  - Reject only when `completion_percent > refund_max_completion` (if threshold > 0).

## Sequence Diagram (Manual Review Path)

```mermaid
sequenceDiagram
    participant U as Customer
    participant FE as Profile UI
    participant AJ as RefundOrderAjax
    participant AD as Admin
    participant GW as Payment Gateway

    U->>FE: Click Refund
    FE->>AJ: request_refund_order(order_id, reason)
    AJ->>AJ: Validate eligibility + settings
    AJ-->>FE: Pending request created

    AD->>AJ: admin_refund_order_process(approve)
    AJ->>AJ: Re-check permission + order status + ownership
    AJ->>GW: refund(order_id)
    GW-->>AJ: success
    AJ->>AJ: update order status, request status, notes, meta
    AJ-->>AD: admin notice approved
```

## Status and Meta Transition
- Request meta:
  - `_lp_refund_request_status`: `pending|approved|auto-approved|denied`
  - `_lp_refund_requested_by`, `_lp_refund_requested_at`
  - `_lp_refund_reviewed_by`, `_lp_refund_reviewed_at`
  - `_lp_refund_reason`, `_lp_refund_note`
- Audit meta:
  - `_lp_refund_amount` (full order total)
  - `_lp_refund_percent` (`100`)
  - `_lp_refund_completion` (captured completion percent)

## Admin UI Behavior
- Refund request info remains visible on order admin after approved/denied/refunded.
- `Approve Refund` and `Deny Refund` buttons are visible only when status is `pending`.
