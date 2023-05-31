<?php
/**
 * Template for displaying item course content of single Instructor.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $courses ) ) {
	return;
}

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
			<?php echo $course->get_course_price_html(); ?>
		</div>
		<div>
			<?php echo get_the_term_list( $course->get_id(), 'course_category', '', '<span>|</span>' ); ?>
		</div>
		<div>
			<?php echo "<h3>{$course->get_title()}</h3>"; ?>
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
