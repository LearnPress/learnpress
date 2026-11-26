# Purchase Course — Business Logic

```mermaid
flowchart TD
    Start([Single course page]) --> Render[SingleCourseTemplate::html_btn_purchase_course]
    Render --> CanBuy{CourseModel::can_purchase?}
    CanBuy -->|No| NoBtn([Warning message or no button])
    CanBuy -->|Yes| BuyNow[Buy Now button with data-send]

    BuyNow --> Submit[SingleCourse::purchaseCourse]
    Submit --> Ajax[AJAX UserCourseAjax::user_purchase_course]
    Ajax --> Valid{Nonce, course and can_purchase valid?}
    Valid -->|No| Error([Show error, enable button])
    Valid -->|Yes| Repurchase{Purchased before, repurchase type popup, no choice yet?}
    Repurchase -->|Yes| Popup[SweetAlert popup keep or reset]
    Popup -->|Confirm| Ajax
    Popup -->|Cancel| Stop([Nothing sent])
    Repurchase -->|No| AddCart[UserCourseService::purchase adds course to cart, saves repurchase choice]
    AddCart --> Added{Added to cart and checkout page set?}
    Added -->|No| Error
    Added -->|Yes| GoCheckout[Redirect to checkout page]

    GoCheckout --> PlaceOrder[Place order: LP_Checkout::process_checkout]
    PlaceOrder --> CheckoutValid{Cart, fields and payment method valid?}
    CheckoutValid -->|No| CheckoutError([Checkout shows error])
    CheckoutValid -->|Yes| Order[Create or reuse pending order]
    Order --> NeedPay{Order needs payment?}
    NeedPay -->|Yes| Pay[Payment gateway process_payment]
    Pay -->|Fail| CheckoutError
    Pay -->|Success| Paid[Gateway completes order]
    NeedPay -->|No, free| Complete[LP_Order::payment_complete]

    Paid --> Status[learn-press/order/status-changed]
    Complete --> Status
    Status --> IsCompleted{Order completed?}
    IsCompleted -->|Yes| Access[LP_User_Factory creates or updates user course: first purchase, repurchase keep or reset, free, guest]
    Access --> Enrolled([Enrolled: fire learnpress/user/course-enrolled, send emails])
    IsCompleted -->|No, was completed before| Cancel([Set user course to Cancel])
```

## Entry points

- `SingleCourseTemplate::html_btn_purchase_course()`: renders the Buy Now button.
- `SingleCourse::purchaseCourse()` (`assets/src/js/frontend/single-course.js`): sends AJAX, shows the repurchase popup.
- `UserCourseAjax::user_purchase_course()` → `UserCourseService::purchase()`: validates, adds to cart, returns checkout URL.
- `LP_Checkout::process_checkout()`: validates checkout, creates the order, starts payment or completes a free order.
- `LP_User_Factory`: on `learn-press/order/status-changed`, grants or cancels course access.

Since 4.5.1 REST `lp/v1/courses/purchase-course` is deprecated (replaced by AJAX `user_purchase_course`).
