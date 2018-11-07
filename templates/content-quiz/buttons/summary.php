<?php
/**
 * Template for displaying Summary quiz button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/summary.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$quiz   = LP_Global::course_item_quiz();

do_action( 'learn-press/before-quiz-result-button' );
?>

<form name="show-quiz-result" class="show-quiz-result form-button lp-form" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-quiz-result-button' ); ?>

    <button type="submit"><?php _e( 'Summary', 'learnpress' ); ?></button>

	<?php do_action( 'learn-press/end-quiz-result-button' ); ?>

    <?php LP_Nonce_Helper::quiz_action( 'show-result', $quiz->get_id(), $course->get_id() ); ?>

</form>

<?php do_action( 'learn-press/after-quiz-result-button' ); ?>
