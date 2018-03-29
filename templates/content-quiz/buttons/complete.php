<?php
/**
 * Template for displaying Complete button in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/complete.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user      = LP_Global::user();
$quiz      = LP_Global::course_item_quiz();
$question = LP_Global::quiz_question();
$course_id = get_the_ID();
$hide_next = get_post_meta($quiz->get_id(), '_lp_hide_finish_until_last', true);

?>

<?php $quiz = LP_Global::course_item_quiz(); ?>

<?php do_action( 'learn-press/quiz/before-complete-button' ); ?>
<?php if (!$hide_next == 'yes' || !( $next_id = $user->get_next_question( $quiz->get_id(), $course_id ) ) ) { ?>
    <form name="complete-quiz" class="complete-quiz form-button lp-form" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/quiz/begin-complete-button' ); ?>

        <button type="submit"><?php _e( 'Complete', 'learnpress' ); ?></button>

		<?php do_action( 'learn-press/quiz/end-complete-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'complete', $quiz->get_id(), get_the_ID() ); ?>
        <input type="hidden" name="noajax" value="yes">

    </form>
<?php } ?>
<?php do_action( 'learn-press/quiz/after-complete-button' ); ?>