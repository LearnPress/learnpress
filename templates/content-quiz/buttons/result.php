<?php
/**
 * Template for displaying State quiz button
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();

global $wp;
?>

<?php do_action( 'learn-press/before-quiz-result-button' ); ?>

<form name="show-quiz-result" class="show-quiz-result form-button lp-form" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-quiz-result-button' ); ?>

    <button type="submit"><?php _e( 'Result', 'learnpress' ); ?></button>

	<?php do_action( 'learn-press/end-quiz-result-button' ); ?>

    <?php LP_Nonce_Helper::quiz_action( 'show-result', $quiz->get_id(), $course->get_id() ); ?>

</form>

<?php do_action( 'learn-press/after-quiz-result-button' ); ?>
