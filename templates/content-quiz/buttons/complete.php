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
?>

<?php $quiz = LP_Global::course_item_quiz(); ?>

<?php do_action( 'learn-press/quiz/before-complete-button' ); ?>

    <form name="complete-quiz" class="complete-quiz form-button lp-form" method="post" enctype="multipart/form-data"
          @submit="completeItem">

		<?php do_action( 'learn-press/quiz/begin-complete-button' ); ?>

        <button type="submit"><?php _e( 'Complete', 'learnpress' ); ?></button>

		<?php do_action( 'learn-press/quiz/end-complete-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'complete', $quiz->get_id(), get_the_ID() ); ?>

    </form>

<?php do_action( 'learn-press/quiz/after-complete-button' ); ?>