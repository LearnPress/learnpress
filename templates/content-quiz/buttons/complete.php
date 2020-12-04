<?php
/**
 * Template for displaying Complete button in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/complete.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $quiz = LP_Global::course_item_quiz(); ?>

<?php do_action( 'learn-press/quiz/before-complete-button' ); ?>

    <form name="complete-quiz"
          data-confirm="<?php LP_Strings::esc_attr_e( 'confirm-complete-quiz', '', array( $quiz->get_title() ) ); ?>"
          data-title="<?php esc_html_e('Complete quiz', 'learnpress'); ?>"
          data-action="complete-quiz"
          class="complete-quiz form-button lp-form" method="post" enctype="multipart/form-data">

		<?php do_action( 'learn-press/quiz/begin-complete-button' ); ?>

        <button type="submit" class="lp-button lp-btn-complete-quiz lp-btn-complete-item"><?php _e( 'Complete', 'learnpress' ); ?></button>

		<?php do_action( 'learn-press/quiz/end-complete-button' ); ?>

		<?php LP_Nonce_Helper::quiz_action( 'complete', $quiz->get_id(), get_the_ID() ); ?>
        <input type="hidden" name="noajax" value="yes">

    </form>

<?php do_action( 'learn-press/quiz/after-complete-button' ); ?>
