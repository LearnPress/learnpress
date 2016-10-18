<?php
/**
 * Displaying the description of single quiz
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$quiz_id = LP()->global['course-item'];
$quiz    = learn_press_get_quiz( $quiz_id );
if ( !$quiz->id ) {
	return;
}
?>

<?php if ( false !== ( $item_quiz_content = apply_filters( 'learn_press_item_quiz_content', $quiz->get_content() ) ) ): ?>
	<div class="quiz-description"><?php echo $item_quiz_content; ?></div>
<?php endif; ?>