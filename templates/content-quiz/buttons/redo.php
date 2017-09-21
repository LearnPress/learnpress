<?php
/**
 * Template for displaying Redo quiz button
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();

?>

<?php do_action( 'learn-press/before-quiz-redo-button' ); ?>

<form name="redo-quiz" class="redo-quiz form-button" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-quiz-redo-button' ); ?>

    <button type="submit"><?php _e( 'Redo', 'learnpress' ); ?></button>

	<?php do_action( 'learn-press/end-quiz-redo-button' ); ?>

    <?php LP_Nonce_Helper::quiz_action( 'redo', $quiz->get_id(), $course->get_id() ); ?>

</form>

<?php do_action( 'learn-press/after-quiz-redo-button' ); ?>
