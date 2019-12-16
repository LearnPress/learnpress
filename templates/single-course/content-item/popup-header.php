<?php
/**
 * Template for displaying header of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/header.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user   = learn_press_get_current_user();
$course = LP_Global::course();
if ( ! $course || ! $user ) {
	return;
}

/*if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
	return;
}*/

$course_data    = $user->get_course_data( $course->get_id() );
$course_results = $course_data->get_results( false );
$percentage     = $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0;
?>

<div id="popup-header">

    <div class="popup-header__inner">

        <h2 class="course-title">
            <a href="<?php echo esc_url( $course->get_permalink() ) ?>"><?php echo $course->get_title(); ?></a>
        </h2>

        <?php
            if($user->has_enrolled_course( $course->get_id())) :
        ?>
            <div class="items-progress">

                <span class="number"><?php printf( __( '%d of %d items', 'learnpress' ), $course_results['completed_items'], $course->count_items( '', true ) ); ?></span>

                <div class="learn-press-progress">
                    <div class="learn-press-progress__active" data-value="<?php echo $percentage; ?>%;">
                    </div>
                </div>

            </div>
        <?php endif; ?>

		<?php if ( $user->can_finish_course( $course->get_id() ) ) {
			LP()->template( 'course' )->course_finish_button();
		} ?>
    </div>
</div>
