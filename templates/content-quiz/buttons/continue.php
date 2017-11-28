<?php
/**
 * Template for displaying Continue button in quiz.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/complete.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user = LP_Global::user(); ?>

<?php do_action( 'learn-press/quiz/before-continue-button' ); ?>

    <form name="continue-quiz" class="continue-quiz form-button lp-form" method="post"
          action="<?php echo $user->get_current_question( $user->get_current_item( get_the_ID() ), get_the_ID(), true ); ?>">

		<?php do_action( 'learn-press/quiz/begin-continue-button' ); ?>

        <button type="submit"><?php _e( 'Continue', 'learnpress' ); ?></button>

		<?php do_action( 'learn-press/quiz/end-continue-button' ); ?>
    </form>

<?php do_action( 'learn-press/quiz/after-continue-button' ); ?>