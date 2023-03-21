<?php
require_once LP_PLUGIN_PATH . 'inc/block-template/class-block-template-archive-course.php';
require_once LP_PLUGIN_PATH . 'inc/block-template/class-block-template-single-course.php';

return array(
    new Block_Template_Archive_Course(),
    new Block_Template_Single_Course(),
);
