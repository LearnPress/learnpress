<?php
$blog_template_path = LP_PLUGIN_PATH . 'inc/block-template/';
require_once $blog_template_path . 'class-block-template-archive-course.php';
require_once $blog_template_path . 'class-block-template-single-course.php';

return array(
	new Block_Template_Archive_Course(),
	new Block_Template_Single_Course(),
);
