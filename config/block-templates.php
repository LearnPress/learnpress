<?php
$blog_template_path = LP_PLUGIN_PATH . 'inc/block-template/';
require_once $blog_template_path . 'class-block-template-archive-course.php';
require_once $blog_template_path . 'class-block-template-single-course.php';
require_once $blog_template_path . 'class-block-template-item-curriculum-course.php';
require_once $blog_template_path . 'single-course/class-block-template-title-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-description-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-categories-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-tags-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-image-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-instructor-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-tabs-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-price-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-feature-review-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-box-extra-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-level-single-course.php';
require_once $blog_template_path . 'single-course/class-block-template-student-single-course.php';

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
		new Block_Template_Box_Extra_Single_Course(),
		new Block_Template_Level_Single_Course(),
		new Block_Template_Student_Single_Course(),
		//new Block_Template_Item_Curriculum_Course(), // When handle item correct post type, uncomment this line, currently item show is post type course.
	)
);
