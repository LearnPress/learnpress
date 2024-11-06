<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$course_id            = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
$item_type            = ! empty( $attributes['itemType'] ) ? $attributes['itemType'] : '';
$show_only_number     = ! empty( $attributes['showOnlyNumber'] ) ? $attributes['showOnlyNumber'] : false;
$course               = CourseModel::find( $course_id, true );
$singleCourseTemplate = SingleCourseTemplate::instance();

echo $singleCourseTemplate->html_count_item( $course, $item_type, $show_only_number );
