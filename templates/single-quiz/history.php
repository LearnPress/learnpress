<?php
/**
 * Template for displaying the history for the quiz
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $quiz;

if( ! $quiz->retake ) {
	return;
}
$heading = apply_filters( 'learn_press_list_questions_heading', __( 'History', 'learn_press' ) );

?>

<?php if ( $heading ) { ?>
	<h4><?php echo $heading;?></h4>
<?php } ?>

<?php
$history = LP()->user->get_quiz_history( $quiz->id );

if( $history ){
	$position = 0;
	?>
	<table class="quiz-history">
		<thead>
			<tr>
				<th width="50">#</th>
				<th><?php _e( 'Time', 'learn_press' );?></th>
				<th><?php _e( 'Result', 'learn_press' );?></th>
			</tr>
		</thead>
		<?php foreach( $history as $item ){ $position++; ?>
		<tr>
			<td><?php echo $position;?></td>
			<td>
				<?php echo date( get_option( 'date_format' ), $item->start );?>
				<?php echo date( get_option( 'time_format' ), $item->start );?>
			</td>
			<td>
				0%
				<p class="quiz-history-actions">
					<a href="<?php echo add_query_arg( 'history_id', $item->history_id );?>"><?php _e( 'View', 'learn_press' );?></a>
					<a href=""><?php _e( 'Use as result', 'learn_press' );?></a>
				</p>
			</td>
		</tr>
		<?php } ?>
	</table>
	<?php

}else{
	learn_press_display_message( __( 'No history found!', 'learn_press' ) );
}