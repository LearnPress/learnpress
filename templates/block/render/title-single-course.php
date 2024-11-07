<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$course_id            = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
$tag            = ! empty( $attributes['tag'] ) ? $attributes['tag'] : 'span';
$course               = CourseModel::find( $course_id, true );
$singleCourseTemplate = SingleCourseTemplate::instance();

echo $singleCourseTemplate->html_title( $course, $tag );
