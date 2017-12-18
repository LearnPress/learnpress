<?php
/**
 * Template for displaying progress of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/progress.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$user   = LP_Global::user();

if ( ! $course || ! $user ) {
	return;
}

if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
	return;
}

$course_data       = $user->get_course_data( $course->get_id() );
$course_results    = $course_data->get_results( false );
$passing_condition = $course->get_passing_condition();
?>

<div class="learn-press-course-results-progress">

    <div class="items-progress">

		<?php if ( false !== ( $heading = apply_filters( 'learn-press/course/items-completed-heading', __( 'Items completed', 'learnpress' ) ) ) ) { ?>
            <h4 class="lp-course-progress-heading"><?php esc_html( $heading ); ?></h4>
		<?php } ?>

        <span class="number"><?php printf( __( '%d of %d items', 'learnpress' ), $course_results['completed_items'], $course->count_items('', false) ); ?></span>

        <div class="lp-course-progress">
            <div class="lp-progress-bar">
                <div class="lp-progress-value"
                     style="left: <?php echo $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0; ?>%;">
                </div>
            </div>
        </div>

    </div>

    <div class="course-progress">

		<?php if ( false !== ( $heading = apply_filters( 'learn-press/course/result-heading', __( 'Course results', 'learnpress' ) ) ) ) { ?>
            <h4 class="lp-course-progress-heading">
				<?php esc_html( $heading ); ?>
				<?php if ( $tooltip = learn_press_get_course_results_tooltip( $course->get_id() ) ) { ?>
                    <span class="learn-press-tooltip" data-content="<?php echo esc_html( $tooltip ); ?>"></span>
				<?php } ?>
            </h4>
		<?php } ?>

        <div class="lp-course-status">
            <span class="number"><?php echo round( $course_results['result'], 2 ); ?><span
                        class="percentage-sign">%</span></span>
			<?php if ( $grade = $course_results['grade'] ) { ?>
                <span class="lp-label grade <?php echo esc_attr( $grade ); ?>">
				<?php learn_press_course_grade_html( $grade ); ?>
				</span>
			<?php } ?>
        </div>

        <div class="lp-course-progress <?php echo $course_data->is_passed() ? ' passed' : ''; ?>"
             data-value="<?php echo $course_results['result']; ?>"
             data-passing-condition="<?php echo $passing_condition; ?>">
            <div class="lp-progress-bar">
                <div class="lp-progress-value" style="left: <?php echo $course_results['result']; ?>%;">
                </div>
            </div>
            <div class="lp-passing-conditional"
                 data-content="<?php printf( esc_html__( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ); ?>"
                 style="left: <?php echo $passing_condition; ?>%;">
            </div>
        </div>
    </div>

</div>