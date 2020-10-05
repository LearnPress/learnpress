**3.2.7.8**
~ Fix save author id when add item when edit course
~ Fix title, description item course when install yoast seo plugin
~ Fix Retake when not enable duration expire
~ Fix function 'Instructors Registration'
~ Fix function Export order invoice PDF

**3.2.7.5**
+ Fix run Elementor with question
+ Fix lesson preview not show button complete when user enrolled
+ Add tag apply_filter 'learn-press/order-item-not-course-id' on received-order
+ Add tag apply_filter 'learn-press/tmpl-button-purchase-course' before return button purchase course
+ Optimize (permalink of items course)
+ Show finish course button when items of course completed but course not passed.
+ Show explanation when user completed quiz
+ Allow re-viewing questions after completing the quiz. Unless otherwise have Retake

** 3.0.0 **
+ Reset courses data for an user has enrolled course
+ Reset course data for users has enrolled course
+ Reset data of a quiz or lesson for an user
+ Enable a Guest user can buy and checkout
+ Option to show/hide login form in user profile
+ Option to show/hide register form in user profile
+ Option to show/hide login form in checkout page
+ Option to show/hide register form in Checkout page
+ Enable sort the payment gateways to show in frontend
+ Quick turn a payment gateway on/of in a list
+ Support plugins Mathjax
+ Widget to display course info
+ Widget to display current progress of a course
+ Custom frontend colors
+ Group emails to related action
+ Run action to send the emails in background
+ Quick edit question settings in it's quiz
+ Preview mode for course/lesson/quiz
+ Option to show list of questions as numbers below quiz while doing or reviewing
+ Display duration of lesson or quiz in curriculum
+ Display number questions of quiz
~ Improves popup for searching courses to add to an order
~ Improves Emails system
~ Improves Multi users order
~ Admin settings pages
~ No distraction mode
~ New course editor
~ New quiz editor
~ User profile
~ Improve cache for speed
~ Improve UI/Ux for both backend and frontend

**2.0 Beta 1**
- Updated database structure for new functions
- Removed "Cart" outside LearnPress core and separated to addon
- Changed permalink of quiz and made it become a part of course's permalink
- Allowed add course's section without a name
- Added email system to send it to user after buying a course
- Added popup lightbox to view course's item content in full-screen mode
- Added option to show/hide list of questions in quiz
- Added 'Sale Price' for course
- Added option to randomize question's answer
- Added option to combine all scripts/styles enqueued into one file
- Added option to evaluate course's results by average results of quizzes
- Added "Passing Grade" to quiz allow evaluate result of quiz is passed/failed
- Added option to show name of user in profile
- Fixed page does not load after logging in profile
- Fixed quiz finish automatically right away after starting with duration is zero
- Improved admin course editor
- Show answer's explanation right away after user checking question's answer
- Added tab Edit in user profile to edit their information
- Supported shortcode inside content of lesson/quiz

**0.9.14**
- Fixed can not start a quiz for guest.
- Fixed course evaluation error and shows wrong message
- Added "preload icon" for quiz actions and question navigation
- Added option for instructors registration
- Updated default language file.

**0.9.13.1**
- Added lacked files.

**0.9.13**
- Fixed profile link problem.
- Fixed conflict with Woo payments add-on.
- Added shortcodes to display free courses, paid courses and newest courses.
- Added shortcodes to display course summary.
- Provided premium add-on assignments.
- Provided premium add-on gradebook.

**0.9.12**  
- Support languages: Italian, Indonesian, German.

**0.9.11**
- Fixed resets all user settings back to default when re-active LearnPress.
- Fixed certificates trigger error "header already sent" when saving templates.
- Made translatable strings in javascript code.
- Fixed course review add-on error on PHP version 5.3.x.
- Fixed text domain error on translating plugin.

**0.9.10**
- Fixed bug: Template loader missing header and footer.
- Updated default language file.

**0.9.9**  
- Fixed bug: Course price always showing "FREE" on category page.
- Updated language Polish.

**0.9.8**    
- Fixed wrong placeholder in email settings.  
- Updated addon Certificates for GUI and functions with more options  
  - Used Google Fonts instead of True Type Font (*.ttf)  
  - Color picker  
  - Text align (vertical and horizontal)  
  - Text rotation  
  - Text transform ( scale X and scale Y)  
  - More options to display the name of a user:  
    + User login  
    + User nice name  
    + Nickname  
    + First name  
    + Last name  
    + First name then Last name  
    + Last name then First name  
- Support Dutch and French (special thank to Bart Kleijheeg and fxbenard)  

**0.9.7**
Fixed wrong query of quizzes result in Profile.

**0.9.6**
- Fix translation textdomain error - Thank polischmen for your concern.
- Updated question hint
- Option to set course is public or enrolled require  
- Certificates add-on:  
 - Add more fonts  
 - Option to display the full name with {firstname}=User first name and {lastname}=User last name  
 - Option to format date  
 - Created a sample Certificate when plugin is activated  

**0.9.5**
- Added showing question answer and explaination feature
- Fixed bugs for addon Fill In Blank question 
- Fixed bugs for addon Sorting Choice question
- Fixed bugs for addon Certificates
- Fixed bugs for addon WooCommerce Payment
- Prevent access to lesson directly by using permalink
- Random quiz questions addon (premium add-on)
- Content Drip addon (premium add-on)
- Support multi language

**0.9.4**
- Set up sample data for LearnPress    
- Provided sorting question type (you can find it in premium add-on list)  
- Provided fill in blank question type (we are uploading it to WordPress.org so if you cannot find it, wait for it to be available soon)  
- Fixed bug profile template  
- Updated LearnPress profile method  

**0.9.3**
- Fix menu position problem (3.14).  
- Fix bug shortcut key @l to insert lesson link when writing a lesson.  
- Fix bug when add Lesson/Quiz into a section but it is not assigned to the course.  
- List the addons from wordpress.org in addons page.  
- Add new option into settings page lets to choose the page to display form "Become a teacher".  
- Add shortcode to insert form "Become a teacher".  
- Update lesson/quiz title also update its slug.  
- Align review form to center of the page.  
- Course review pagination.  
- Provide related courses function.  
- Support full width embed video. 

**0.9.2**  
- Update add-on management page.  
- Add auto next lesson after complete a quiz feature.  
- Fix course-review bugs.  
- Fix bug on curriculum when use "shift + (" or "shift+&" ..... to edit Lesson title or Section title.  
- Fix course result bug.  
- Fix export/import addon - sometime the image is not imported.  
- Fix bug show certificate after user finished a course.  

**0.9.1**  
The first beta release.  

**Upgrade Notice**  
*0.9.2*  
We have changed directory structure and separated all out core add-ons to become independence plugins, so after upgrading version, if you face any add-ons problems, please completely delete the old version and re-install LearnPress. Thank!