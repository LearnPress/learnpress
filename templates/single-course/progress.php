<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       3.0.0
 */

defined( 'ABSPATH' ) || exit();

$course            = LP_Global::course();
$user              = LP_Global::user();

$course_data       = $user->get_course_data( get_the_ID() );
$passing_condition = $course->get_passing_condition();
$course_results    = $course->evaluate_course_results();

if ( ! $course_results = $course_data->get_results( false ) ) {
	return;
}

$grade = $course_results['grade'];
?>
<div class="learn-press-course-results-progress">
    <div class="items-progress">
        <h4 class="lp-course-progress-heading"><?php esc_html_e( 'Items completed', 'learnpress' ); ?></h4>
        <span class="number"><?php printf( __( '%d of %d items', 'learnpress' ), $course_results['completed_items'], $course_results['count_items'] ); ?></span>
        <div class="lp-course-progress">
            <div class="lp-progress-bar">
                <div class="lp-progress-value"
                     style="left: <?php echo $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0; ?>%;">
                </div>
            </div>
        </div>

    </div>
    <div class="course-progress">
        <h4 class="lp-course-progress-heading">
			<?php esc_html_e( 'Course results', 'learnpress' ); ?>
			<?php if ( $tooltip = learn_press_get_course_results_tooltip( $course->get_id() ) ) { ?>
                <span class="learn-press-tooltip" data-content="<?php echo esc_html( $tooltip ); ?>"></span>
			<?php } ?>
        </h4>
        <div class="lp-course-status">
            <span class="number"><?php echo round( $course_results['result'], 2 ); ?><span
                        class="percentage-sign">%</span></span>
			<?php if ( $grade ) { ?>
                <span class="grade <?php echo esc_attr( $grade ); ?>">
				<?php learn_press_course_grade_html( $grade ); ?>
				</span>
			<?php } ?>
        </div>
        <div class="lp-course-progress <?php echo $grade == 'passed' ? ' passed' : ''; ?>"
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