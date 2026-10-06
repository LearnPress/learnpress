# Purchase Course — Human-readable Logic

```mermaid
flowchart TD
    Start([Open the course page]) --> CanBuy{Can the course be purchased?}
    CanBuy -->|No| Message[Show why the course cannot be purchased]
    CanBuy -->|Yes| BuyCourse[Show the Buy Course button]

    BuyCourse --> Request[User requests to purchase]
    Request --> Repurchase{Has the user owned this course before?}
    Repurchase -->|Yes| Choice[Choose to keep or reset progress]
    Repurchase -->|No| Cart[Add the course to the cart]
    Choice --> Cart

    Cart --> Checkout[Go to checkout]
    Checkout --> CheckoutValid{Is the checkout information valid?}
    CheckoutValid -->|No| Error[Show an error]
    CheckoutValid -->|Yes| Payment{Is payment required?}
    Payment -->|Yes| Pay[Pay with the selected payment method]
    Pay --> PaySuccess{Was the payment successful?}
    PaySuccess -->|No| Error
    PaySuccess -->|Yes| Complete[Complete the order]
    Payment -->|No| Complete

    Complete --> Access{How should course access be granted?}
    Access -->|First purchase| Create[Grant course access]
    Access -->|Repurchase and keep progress| Keep[Renew access and keep progress]
    Access -->|Repurchase and reset| Reset[Reset progress and grant access]
    Access -->|Free course| Enroll[Enroll in the course]
    Access -->|Not eligible| NoChange[Do not change course access]

    Create --> Notify[Send enrollment confirmation]
    Keep --> Notify
    Reset --> Notify
    Enroll --> Notify
    Notify --> Done([Complete])
    NoChange --> Done
    Error --> EndError([End with an error])
```
