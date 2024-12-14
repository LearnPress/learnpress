<?php
$blog_template_path = LP_PLUGIN_PATH . 'inc/block-template/';
require_once $blog_template_path . 'class-block-template-archive-course.php';
require_once $blog_template_path . 'class-block-template-single-course.php';

// Single Course
require_once $blog_template_path . 'single-course/class-block-template-title-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-description-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-categories-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-tags-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-image-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-instructor-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-tabs-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-price-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-feature-review-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-level-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-student-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-btn-purchase-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-duration-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-breadcrumb.php';
require_once $blog_template_path . 'single-course/class-block-template-comment.php';
require_once $blog_template_path . 'single-course/class-block-template-requirements-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-features-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-target-audiences-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-lesson-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-quiz-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-time-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-progress-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-item-curriculum-course.php';

// Archive course
require_once $blog_template_path . 'archive-course/class-block-template-search-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-order-by-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-switch-layout-archive-courses.php';
require_once $blog_template_path . 'archive-course/layout/class-block-layout-list-course-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-pagination-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-info-course-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-instructor-category-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-meta-course-archive-courses.php';
require_once $blog_template_path . 'archive-course/class-block-template-title-course-archive-courses.php';
require_once $blog_template_path . 'archive-course/layout/class-block-layout-group-courses.php';

//Layout single course
require_once $blog_template_path . 'single-course/layout/class-block-layout-archive-course.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-content-area.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-content-left.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-course-summary.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-detail-info.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-meta-primary.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-meta-secondary.php';
require_once $blog_template_path . 'single-course/layout/class-block-layout-sidebar.php';

return apply_filters(
	'learn-press/config/block-templates',
	array(
		new Block_Template_Archive_Course(),
		new Block_Template_Single_Course(),
		new Block_Template_Title_Single_Course(),
		new Block_Template_Description_Single_Course(),
		new Block_Template_Categories_Single_Course(),
		new Block_Template_Tags_Single_Course(),
		new Block_Template_Image_Single_Course(),
		new Block_Template_Instructor_Single_Course(),
		new Block_Template_Tabs_Single_Course(),
		new Block_Template_Price_Single_Course(),
		new Block_Template_Feature_Review_Single_Course(),
		new Block_Template_Requirements_Single_Course(),
		new Block_Template_Features_Single_Course(),
		new Block_Template_Target_Audiences_Single_Course(),
		new Block_Template_Lesson_Single_Course(),
		new Block_Template_Quiz_Single_Course(),
		new Block_Template_Level_Single_Course(),
		new Block_Template_Student_Single_Course(),
		new Block_Template_Btn_Purchase_Single_Course(),
		new Block_Template_Duration_Single_Course(),
		new Block_Template_Progress_Single_Course(),
		new Block_Template_Time_Single_Course(),
		new Block_Template_Breadcrumb(),
		new Block_Template_Comment(),
		new Block_Layout_Archive_Course(),
		new Block_Layout_Content_Area(),
		new Block_Layout_Content_Left(),
		new Block_Layout_Course_Summary(),
		new Block_Layout_Detail_Info(),
		new Block_Layout_Meta_Primary(),
		new Block_Layout_Meta_Secondary(),
		new Block_Layout_Sidebar(),
		new Block_Template_Search_Archive_Courses(),
		new Block_Template_Order_By_Archive_Courses(),
		new Block_Template_Switch_Layout_Archive_Courses(),
		new Block_Layout_List_Course_Archive_Courses(),
		new Block_Template_Pagination_Archive_Courses(),
		new Block_Layout_Group_Courses_Archive_Course(),
		new Block_Template_Info_Course_Archive_Courses(),
		new Block_Template_Instructor_Category_Archive_Courses(),
		new Block_Template_Meta_Course_Archive_Courses(),
		new Block_Template_Title_Course_Archive_Courses(),
		new Block_Template_Item_Curriculum_Course(),
	)
);
