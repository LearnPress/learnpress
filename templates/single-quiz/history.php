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

if ( !$quiz->retake_count || !LP()->user->has( 'completed-quiz', $quiz->id ) ) {
	return;
}
$limit   = 10;
$history = LP()->user->get_quiz_history( $quiz->id );
reset( $history );

$history_count = sizeof( $history );
$view_id       = !empty( $_REQUEST['history_id'] ) ? $_REQUEST['history_id'] : key( $history );
$heading       = sprintf( __( 'Other results (newest %d items)', 'learnpress' ), $limit );
$heading       = apply_filters( 'learn_press_list_questions_heading', $heading );
?>

<?php if ( $heading ) { ?>
	<h4><?php echo $heading; ?></h4>
<?php } ?>

<?php
if ( $history_count > 1 ) {
	$position = 0;
	?>
	<table class="quiz-history">
		<thead>
		<tr>
			<th width="50" align="right">#</th>
			<th><?php _e( 'Time', 'learnpress' ); ?></th>
			<th><?php _e( 'Result', 'learnpress' ); ?></th>
		</tr>
		</thead>
		<?php foreach ( $history as $item ) {
			if ( $item->history_id == $view_id ) continue;
			$position ++; ?>
			<tr>
				<td align="right"><?php echo $position; ?></td>
				<td>
					<?php echo date( get_option( 'date_format' ), $item->start ); ?>
					<div><?php echo date( get_option( 'time_format' ), $item->start ); ?></div>
				</td>
				<td>
					<?php if( $item->results['quiz_mark'] ) { ?>
					<?php printf( "%01.2f (%%)", ( $item->results['mark'] / $item->results['quiz_mark'] ) * 100 ); ?>
					<?php }else{ ?>
					<?php printf( "%01.2f (%%)", 0 ); ?>
					<?php }?>
					<!--
				<p class="quiz-history-actions">
					<a href="<?php echo add_query_arg( 'history_id', $item->history_id ); ?>"><?php _e( 'View', 'learnpress' ); ?></a>
					<a href=""><?php _e( 'Use as result', 'learnpress' ); ?></a>
				</p>
				-->
				</td>
			</tr>
			<?php if ( $position >= $limit ) break;
		} ?>
	</table>
	<?php

} else {
	learn_press_display_message( __( 'No history found!', 'learnpress' ) );
}