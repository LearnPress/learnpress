<?php
/**
 * Template for displaying students of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/students.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $course = learn_press_get_course(); ?>

<?php
// Do not show if course is no require enrollment
if ( ! $course || ! $course->is_require_enrollment() ) {
	return;
} ?>

<span class="course-students" title="<?php echo esc_html( $course->get_students_html() ); ?>">

    <?php $count = $course->count_users_enrolled( 'append' );

    echo $count > 1 ? sprintf( _n( '%d student', '%d students', $count, 'learnpress' ), $count ) : sprintf( __( '%d student', 'learnpress' ), $count ); ?>

</span>
