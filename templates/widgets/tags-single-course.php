<?php

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$course_id            = $attributes['courseId'] ? (int) $attributes['courseId'] : get_the_ID();
$course               = learn_press_get_course( $course_id );
$singleCourseTemplate = SingleCourseTemplate::instance();

echo $singleCourseTemplate->html_tags( $course );
