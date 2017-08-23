<?php
/**
 * Template for displaying the students of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$course = learn_press_get_course();

// Do not show if course is no require enrollment
if ( ! $course || ! $course->is_require_enrollment() ) {
	//return;
}
?>
<span class="course-students" title="<?php echo esc_html( $course->get_students_html() ); ?>">
	<?php
	$count = $course->count_users_enrolled( 'append' );
	echo $count > 1 ? sprintf(
		_n( '%d student', '%d students', $count, 'learnpress' ),
		$count
	) : sprintf( __( '%d student', 'learnpress' ), $count );
	?>
</span>
