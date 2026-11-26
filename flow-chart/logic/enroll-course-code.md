# Enroll Course — Business Logic

```mermaid
flowchart TD
    Start([Single course page]) --> Render[SingleCourseTemplate::html_btn_enroll_course]
    Render --> CanEnroll{CourseModel::can_enroll?}
    CanEnroll -->|No| NoBtn([Warning message or no button])
    CanEnroll -->|Yes| StartNow[Start Now button with data-send]

    StartNow --> Submit[SingleCourse::enrollCourse]
    Submit --> Ajax[AJAX UserCourseAjax::user_enroll_course]
    App([App: JWT learnpress/v1/courses/enroll, login required]) --> Service
    Ajax --> Service[UserCourseService::enroll]

    Service --> Valid{Course valid, not password protected, can_enroll?}
    Valid -->|No| Error([Show error])
    Valid -->|Yes| Purchased{User bought this course?}

    Purchased -->|Yes| SetEnrolled[Set user course enrolled, fire learnpress/user/course-enrolled, send email]
    Purchased -->|No, free course| AddCart[Add course to cart]
    AddCart --> LoggedIn{User logged in?}
    LoggedIn -->|Yes| FreeOrder[Create order, total must be 0, LP_Order::payment_complete]
    FreeOrder --> Factory[LP_User_Factory creates enrolled user course]
    LoggedIn -->|No, guest| Checkout([Redirect to checkout page, see purchase-course-code.md])

    SetEnrolled --> Learn([Redirect to first item or course page])
    Factory --> Learn
```

## Entry points

- `SingleCourseTemplate::html_btn_enroll_course()`: renders the Start Now button.
- `SingleCourse::enrollCourse()` (`assets/src/js/frontend/single-course.js`): sends AJAX, redirects on success.
- `UserCourseAjax::user_enroll_course()` (web) and `LP_REST_Courses_V1_Controller::enroll_course()` (app) → `UserCourseService::enroll()`: validates and enrolls.
- `LP_Order::payment_complete()` → `LP_User_Factory` on `learn-press/order/status-changed`: creates the enrolled user course for a free course.

Since 4.5.1 REST `lp/v1/courses/enroll-course` is deprecated (replaced by AJAX `user_enroll_course`).
