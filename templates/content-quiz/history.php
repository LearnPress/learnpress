<?php
/**
 * Template for displaying history of quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/history.php.
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

<?php
$user   = learn_press_get_current_user();
$course = learn_press_get_the_course();
$quiz   = LP()->global['course-item'];
?>

<?php if ( ! $quiz->retake_count || ! $user->has_completed_quiz( $quiz->id, $course->get_id() ) ) {
	return;
} ?>

<?php
$limit   = 10;
$history = $user->get_quiz_history( $quiz->id, $course->get_id() );
reset( $history );
$history_count = sizeof( $history );
$view_id       = ! empty( $_REQUEST['history_id'] ) ? $_REQUEST['history_id'] : key( $history );
$heading       = sprintf( __( 'Other results (newest %d items)', 'learnpress' ), $limit );
$heading       = apply_filters( 'learn_press_quiz_history_heading', $heading );
?>

<?php if ( $heading ) { ?>
    <h4 class="lp-group-heading-title toggle-off"
        onclick="LP.toggleGroupSection('#lp-quiz-history', this);"><?php echo $heading; ?>
        <span class="toggle-icon"></span></h4>
<?php } ?>

<?php if ( $history_count > 1 ) { ?>

    <?php $position = 0; ?>

    <div class="lp-group-content-wrap hide-if-js" id="lp-quiz-history">
        <table class="quiz-history">
            <thead>
            <tr>
                <th width="50" align="right">#</th>
                <th><?php _e( 'Time', 'learnpress' ); ?></th>
                <th><?php _e( 'Result', 'learnpress' ); ?></th>
            </tr>
            </thead>
			<?php foreach ( $history as $item ) {
				if ( $item->history_id == $view_id ) {
					continue;
				}
				$results = $user->evaluate_quiz_results( $quiz->id, $item );
				$position ++; ?>
                <tr>
                    <td align="right"><?php echo $position; ?></td>
                    <td>
						<?php echo date( get_option( 'date_format' ), strtotime( $item->start ) ); ?>
                        <div><?php echo date( get_option( 'time_format' ), strtotime( $item->start ) ); ?></div>
                    </td>
                    <td>
						<?php $mark_percent = ! empty( $results['mark_percent'] ) ? $results['mark_percent'] : 0; ?>
						<?php printf( "%d%%", $mark_percent ); ?>
                    </td>
                </tr>
				<?php if ( $position >= $limit ) {
					break;
				}
			} ?>
        </table>
    </div>
	<?php

} else {
	learn_press_display_message( __( 'No history found!', 'learnpress' ) );
}