<?php
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$class                = 'lp-archive-courses';
$layout_single_course = LP_Settings::get_option( 'layout_single_course', 'classic' );
if ( $layout_single_course === 'modern' ) {
	$class = 'lp-single-course';
}
?>
<div class="<?php echo esc_attr( $class ); ?>">
	<?php
	if ( ! empty( $inner_content ) ) {
		echo $inner_content;
	}
	?>
</div>