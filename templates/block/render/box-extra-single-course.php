<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$course_id = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
$meta_key  = ! empty( $attributes['metaKey'] ) ? '_lp_' . $attributes['metaKey'] : '';
$title     = ! empty( $attributes['title'] ) ? esc_html( $attributes['title'], 'learnpress' ) : esc_html( 'Title', 'learnpress' );
$course    = CourseModel::find( $course_id, true );

if ( empty( $meta_key ) || empty( $course ) ) {
	return;
}

$singleCourseTemplate = SingleCourseTemplate::instance();
$items                = $course->get_meta_value_by_key( $meta_key );

$html_list = '';
if ( ! empty( $items ) && is_array( $items ) ) :
	ob_start();
	?>
	<ul>
		<?php foreach ( $items as $item ) : ?>
			<li><?php echo wp_kses_post( $item ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php
	$html_list = ob_get_clean();
endif;

echo $singleCourseTemplate->html_course_box_extra( $course, $title, $html_list );
