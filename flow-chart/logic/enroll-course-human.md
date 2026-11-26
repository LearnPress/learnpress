# Enroll Course — Human-readable Logic

```mermaid
flowchart TD
    Click([User clicks Start Now]) --> CanEnroll{Can the user enroll in this course?}
    CanEnroll -->|No| Reason([Show the reason, e.g. out of stock or password protected])
    CanEnroll -->|Yes| Bought{Has the user already bought this course?}

    Bought -->|Yes| EnrollBought[Enroll the user in the course]
    Bought -->|No, free course| LoggedIn{Is the user logged in?}
    LoggedIn -->|Yes| EnrollFree[Enroll the user for free]
    LoggedIn -->|No, guest| Cart[Add the course to the cart]

    EnrollBought --> Notify[Send enrollment confirmation]
    EnrollFree --> Notify
    Notify --> HasLesson{Does the course have lessons?}
    HasLesson -->|Yes| FirstLesson([Go to the first lesson])
    HasLesson -->|No| CoursePage([Go back to the course page])

    Cart --> Checkout([Go to checkout to finish enrolling])
```

## Cases

| Case | Result | Go to |
|---|---|---|
| Cannot enroll (out of stock, password protected...) | Show the reason | Stay on the course page |
| Already bought the course | Enrolled, confirmation email sent | First lesson (or course page if no lessons) |
| Free course, logged in | Enrolled for free, confirmation email sent | First lesson (or course page if no lessons) |
| Free course, guest | Course added to the cart | Checkout page |

See `enroll-course-code.md` for the code flow.
