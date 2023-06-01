<?php
/**
 * Template for displaying item course content of single Instructor.
 */

use LearnPress\TemplateHooks\SingleCourse;

defined( 'ABSPATH' ) || exit;

if ( ! isset( $courses ) ) {
	return;
}

$singleCourseTemplate = SingleCourse::instance();

foreach ( $courses as $course_obj ) {
	$course = LP_Course::get_course( $course_obj->ID );
	?>
	<li class="item-course">
		<div class="img">
			<a href="<?php echo esc_url( $course->get_permalink() ); ?>">
				<?php echo $course->get_image(); ?>
			</a>
		</div>
		<div>
			<?php echo $course->get_course_price_html(); ?> |
			<?php echo $singleCourseTemplate->html_categories( $course ); ?>
		</div>
		<div>
			<?php echo sprintf( '<h3><a href="%s">%s</a></h3>', $course->get_permalink(), $singleCourseTemplate->html_title( $course ) ); ?>
		</div>
		<div>
			<?php
			echo sprintf(
				'%s %s',
				$course->get_total_user_enrolled_or_purchased(),
				_n( 'student', 'students', $course->get_total_user_enrolled_or_purchased(), 'learnpress' )
			);
			?>
		</div>
	</li>
	<?php
}
?>
