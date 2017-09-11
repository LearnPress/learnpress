<?php
/**
 * Template for displaying Start quiz button
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();

?>

<?php do_action( 'learn-press/before-quiz-start-button' ); ?>

<form name="start-quiz" class="start-quiz" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-quiz-start-button' ); ?>

    <button type="submit"><?php _e( 'Start', 'learnpress' ); ?></button>

	<?php do_action( 'learn-press/end-quiz-start-button' ); ?>

    <?php LP_Nonce_Helper::quiz_action( 'start', $quiz->get_id(), $course->get_id() ); ?>

</form>

<?php do_action( 'learn-press/after-quiz-start-button' ); ?>
