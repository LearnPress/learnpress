<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$course_id            = $attributes['courseId'] ? (int) $attributes['courseId'] : 0;
$course               = CourseModel::find( $course_id, true );
$singleCourseTemplate = SingleCourseTemplate::instance();

echo $singleCourseTemplate->html_title( $course );
