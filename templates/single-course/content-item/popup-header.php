<?php
/**
 * Template for displaying header of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/header.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$user   = learn_press_get_current_user();
$course = LP_Global::course();

if ( ! $course || ! $user ) {
	return;
}

$course_data    = $user->get_course_data( $course->get_id() );
$course_results = $course_data->calculate_course_results();
$percentage     = $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0;
?>

<div id="popup-header">
	<div class="popup-header__inner">
		<h2 class="course-title">
			<a href="<?php echo esc_url( $course->get_permalink() ); ?>"><?php echo $course->get_title(); ?></a>
		</h2>

		<?php if ( $user->has_enrolled_course( $course->get_id() ) ) : ?>
			<div class="items-progress">
				<span class="number"><?php printf( __( '%1$s of %2$d items', 'learnpress' ), '<span class="items-completed">' . $course_results['completed_items'] . '</span>', $course->count_items( '', true ) ); ?></span>
				<div class="learn-press-progress">
					<div class="learn-press-progress__active" data-value="<?php echo $percentage; ?>%;">
					</div>
				</div>
			</div>
		<?php endif; ?>
 	</div>
	<a href="<?php echo $course->get_permalink(); ?>" class="back-course"><i class="fa fa-times"></i></a>
 </div>
