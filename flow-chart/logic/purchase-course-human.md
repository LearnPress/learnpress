# Purchase Course — Human-readable Logic

```mermaid
flowchart TD
    Click([User clicks Buy Now]) --> CanBuy{Can the user buy this course?}
    CanBuy -->|No| Reason([Show the reason, e.g. out of stock or already enrolled])
    CanBuy -->|Yes| Owned{Has the user owned this course before?}

    Owned -->|No, first purchase| Cart[Add the course to the cart]
    Owned -->|Yes, repurchase| AskChoice{Does the course ask the user to choose?}
    AskChoice -->|Yes| Choice{User chooses keep or reset progress}
    Choice -->|Cancel| Stay([Stay on the course page])
    Choice -->|Keep or reset| Cart
    AskChoice -->|No, use course setting| Cart

    Cart --> Checkout[Go to checkout]
    Checkout --> Payment{Is payment required?}
    Payment -->|Yes| Pay{Was the payment successful?}
    Pay -->|No| Error([Show an error on checkout])
    Pay -->|Yes| Access[Grant course access, keep or reset progress if repurchase]
    Payment -->|No, free| Access
    Access --> Notify[Send enrollment confirmation]
    Notify --> Received([Go to the order received page])
```

## Cases

| Case | Result | Go to |
|---|---|---|
| Cannot buy (out of stock, already enrolled...) | Show the reason | Stay on the course page |
| First purchase | Course added to the cart | Checkout page |
| Repurchase, course asks to choose | User chooses keep or reset progress, course added to the cart | Checkout page (cancel: stay on the course page) |
| Repurchase, course setting decides | Course added to the cart with the setting keep or reset | Checkout page |
| Checkout, payment successful or free | Course access granted, confirmation email sent | Order received page |
| Checkout, payment failed | Show an error | Stay on checkout |

See `purchase-course-code.md` for the code flow.
