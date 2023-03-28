<?php
$blog_template_path = LP_PLUGIN_PATH . 'inc/block-template/';
require_once $blog_template_path . 'class-block-template-archive-course.php';
require_once $blog_template_path . 'class-block-template-single-course.php';
require_once $blog_template_path . 'class-block-template-item-curriculum-course.php';

return apply_filters(
	'learn-press/config/block-templates',
	array(
		new Block_Template_Archive_Course(),
		new Block_Template_Single_Course(),
		//new Block_Template_Item_Curriculum_Course(), // When handle item correct post type, uncomment this line, currently item show is post type course.
	)
);
