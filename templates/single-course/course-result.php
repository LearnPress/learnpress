<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();

if ( LP()->user->is( 'guest' ) ) return;

$course_id = get_the_ID();
$passed    = learn_press_user_has_passed_course( $course_id );
$result    = learn_press_get_quiz_result( null, $quiz_id );
$result    = learn_press_get_course_result( $course_id );

?>
	<div class="clearfix"></div>
<?php if ( $passed ): ?>
	<?php learn_press_message( sprintf( __( 'You have passed this course with %.2f%% of total', 'learnpress' ), $result ) ); ?>
<?php else: ?>
	<?php
	$passing_condition = learn_press_get_course_passing_condition( $course_id );
	?>
	<?php learn_press_message( sprintf( __( 'Sorry, you have not passed this course. This course required you pass %.2f%% but your result is only %.2f%%', 'learnpress' ), $passing_condition, $result ), 'error' ); ?>
<?php endif; ?>