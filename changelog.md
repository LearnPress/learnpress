**2.1.9**
~ Fixed lesons lost when press preview button in edit course page.
~ Fixed the "Buy this course" button display after course is "finished" 
~ Fixed Courses and Instructor Tab from Profile page not shows courses
~ Fixed Profile does not show courses created by himself
~ Fixed Notify email not send when cousre auto finished

**2.1.8**

**2.1.7.3**
~ Fixed can not save curriculum with SiteOrigin.
~ Fixed instructor can select lessons/quizzes of an another user when editing course.
~ Fixed get request var does not exists while updating course.
~ Fixed can not load lesson with Yoast Seo enabled.
~ Fixed become a teacher page with page builder.
~ Fixed eye icon does not show when opening a lesson.

**2.1.7.2**
~ Fixed can add a question to multiple quizzes

**2.1.7**
+ Added link to navigate to course in checkout page
+ Updated meta-box lib to latest version
+ Fixed prevent adding duplication course in one order
+ Fixed LP widgets does not work with SO
+ Fixed settings page is blank in some languages
+ Fixed some issue with WC 3.x
+ Fixed some other issues

**2.1.6.2**
+ Fixed course does not finish automatically
+ Fixed issue with WC 3.x

**2.1.6.1**
+ Removed cache-flush param
+ Added some filters for evaluating course results
+ Fixed js unreachable code
+ Fixed warning message on update post

**2.1.6**
+ Add more options for course results
+ Made 'Show correct answer' option depending on 'Show/Hide questions'
+ Fixed process fields are added by filter for become a teacher form
+ Fixed wrong user profile url
+ Fixed user avatar can not save in profile
+ Remove related data after removing posts

**2.1.5.5**
+ Fixed issue with slug of course page is the same with slug of course tab in profile
+ Fixed issue with metabox show/hide field

**2.1.5.4**
+ Fixed code with old version of PHP

**2.1.5.3**
+ Fixed a bug when using template_include filter

**2.1.5.2**
+ Fixed some warning messaages
+ Fixed orders display missing in user profile

**2.1.5**
+ Added feature allow creating an order for multi users
+ Added option to force an action can be triggered after updated order
+ Added hook for logout redirection
+ Improved emails system ( add emails: order status changed for user, course enrolled for admin, course updated for admin )
+ Improved sql queries performance
+ Fixed 'Preview' label can not click-able
+ Fixed option 'Show correct answer' does not work correctly
+ Re-added Recent/Popular/Featured widgets and shortcodes

**2.1.4.2**
+ Fixed issue can not view lesson
+ Fixed "sale price" option does not work correctly

**2.1.4.1**
+ Fixed warning empty object
+ Fixed 404 page with custom slug for lesson or quiz

**2.1.4**
+ Added option for external link of "Buy this course"
+ Improved user roles while edit a course and it's items
+ Removed "Show/Hide" questions option of quiz in Global Settings
+ Removed option "Show/Hide" explanation for quiz
+ Removed "Preview" label of course items if course is no required enroll
+ Fixed guest user can not start quiz on wpengine site
+ Fixed "Start quiz" does not show for "No require enrollment" course
+ Fixed course id is missing after duplicating course
+ Fixed course results is incorrect with 'Evaluate lessons' option
+ Fixed wrong review course before publish
+ And more...

**2.1.3**
+ Fixed wrong notice outdated templates
+ Fixed issue when viewing order details in profile
+ Improved admin course tabs
+ Fixed course does not finish automatically when expired
+ Fixed translation issue with failed/passed strings

**2.1.2**
+ Fixed Assign course's items to user when assigning course
+ Fixed Options to change key 'lessons' and 'quizzes' when viewing a lesson/quiz in a course.
+ Fixed Course pagination issue in some case
+ Fixed Can not add to cart for non-loggedin user (woocommerce addon)
+ Fixed Broken cert when previewing to print in single course (Certificate)
+ Fixed Paid memberships show user as deleted after buying course (Paid membership)
+ Fixed overwrite templates issue (Paid membership)
+ Fixed "page isn’t working" when creating a new post type (conflict with metabox in the-7 theme)

**2.1.1**
+ Added options to change value of lessons/quizzes in course item permalink
+ Improved edit profile page
+ Improved permalink for lesson/quiz
+ Improved some options
+ Improved some sections in admin
+ Fixed "Preview change" button show 404 page
+ Fixed question show randomly when starting quiz
+ Fixed username contains spacing

**2.1.0**
+ Fixed bugs related to AJAX calling
+ Fixed bugs related to updating user profile
+ Fixed open question to new tab in quiz editor

**2.0.9**
+ Improved some sections in admin
+ Added tab 'Related Themes'
+ Fixed error with PHP version before 5.3.x
+ Fixed bug get order incorrect
+ Added option to switch WP Metaboxes into tabs style
+ And more

= 2.0.8.2**
+ Fixed outdated templates notice

= 2.0.8.1**
+ Fixed some functions does not support in PHP < 5.5

**2.0.8**
+ Fixed loop redirecting while saving course for instructor
+ Fixed "Place Order" is empty if Paypal is selected
+ Improved some admin sections
+ Improved uploading user profile picture
+ Enabled VC load lesson for building page

**2.0.7.2**
+ Added Messaging between admin and instructors for reviewing and submitting course
+ Fixed loop redirect if a page is used for both home page and course page
+ Fixed warning notice while instructor submitting a course
+ Fixed several other bugs

**2.0.7.1**
+ Fixed start quiz load infinite
+ Fixed version number does not update with WP 4.7
+ Fixed issue with course's item content
+ Fixed styles can not load in some cases

**2.0.6.1**
+ Improved checking templates are outdated
+ Fixed bug avatar cannot change in user profile
+ Fixed several other bugs

**2.0.6**
+ Added tool to check the templates are outdated in theme
+ Added avatar option of LP profile into WP profile
+ Added comment features for lesson
+ Removed unnecessary fields in LP profile page
+ Removed 'Preview' label for lesson if user is enrolled course
+ Fixed issue with WooSidebars
+ Fixed 'Tick' icon beside lesson for it's statuses
+ Fixed course's price does not show decimal numbers
+ Fixed user profile link is 404
+ Fixed issues with page builder and Yoast SEO plugins
+ Fixed division by zero for course pagination
+ Fixed message show in course and user can not click any where to buy

**2.0.5.2**
+ Fixed static pages are duplicated

**2.0.5.1**
+ Removed prints SQL in code

**2.0.5**
+ Added "Coming Soon" courses
+ Added duration for questions
+ Improved lightbox in order editor to add items into the order
+ Fixed bug can not do anything in admin after activating LP
+ Fixed lesson 404 in course popup
+ Fixed issue with duration of quiz larger than 10 hours
+ Fixed quiz finish immediately after starting
+ Fixed js error in global.js
+ Fixed lesson does not load in popup
+ Fixed some functions/keywords does not support in PHP < 5.3
+ Fixed conflict with WPML make course become 404
+ Fixed PHP notice in multisites by using a property has deprecated
+ Removed hardcode wp-content
+ Removed heading title in tabs overview and curriculum

**2.0.4**
+ Improved LearnPress statistic
+ Fixed "Duplicate course" link is gone
+ Fixed SQL error while sorting lessons by date or title
+ Fixed instructor role issues
+ Added "no distraction mode" for lesson and quiz
+ Restyle layout of widget/shortcode for recent/popular courses
+ Fixed "course suggestion price" does not show for admin
+ Added register/forgot password link into user's profile
+ Added validation Paypal settings before user can placing order
+ Added option to assign a course to an instructor
+ Added option to turn on/off a course is featured

**2.0.3**
+ Fixed youtube/vimeo video does not show fullscreen button in lesson content
+ Fixed search does not work while searching in a course category page
+ Fixed error while searching in course category page
+ Fixed layout broken if course item title is long
+ Fixed some bugs related to style

**2.0.2**
+ Fixed 'Course Overview' does not show
+ Fixed single course permalink does not work with category inside
+ Fixed course's author data is empty

**2.0.1**
+ Added duplication quiz/question/lesson in admin
+ Added crop user's avatar in profile
+ Fixed conflict with Yoast SEO make course content does not show correctly
+ Fixed some errors happen with older PHP version
+ Fixed progress bar does not update after completing an item
+ Fixed setting of some page lost after reactive
+ Fixed items can not drag and drop in course's curriculum
+ Fixed courses name is always show as "Auto Draft"

**2.0**
+ Updated database structure for new functions
+ Added view quiz inside a course with sub-permalink
+ Added allow add course's section without a name
+ Added email system to send it to user after buying a course
+ Added popup lightbox to view course's item content in full-screen mode
+ Added option to show/hide list of questions in quiz
+ Added 'Sale Price' for course
+ Added option to combine all scripts/styles enqueued into one file
+ Added option to evaluate course's results by average results of quizzes
+ Added "Passing Grade" to quiz allow evaluate result of quiz is passed/failed
+ Added option to show name of user in profile
+ Added duplicate a question inside quiz
+ Added preview mode of course for instructor or admin
+ Added memorize question type is the most used 
+ Added new tab to edit user information in profile page
+ Improved admin course editor
+ Removed "Cart" outside LearnPress core and separated to addon
+ Fixed show answer's explanation right away after user checking question's answer
+ Fixed page does not load after logging in profile
+ Fixed quiz finish automatically right away after starting with duration is zero
+ Fixed displays shortcodes inside content of quiz/lesson


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
**0.9.2**  
We have changed directory structure and separated all out core add-ons to become independence plugins, so after upgrading version, if you face any add-ons problems, please completely delete the old version and re-install LearnPress. Thank!