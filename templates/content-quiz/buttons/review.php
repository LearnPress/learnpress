<?php
/**
 * Template for displaying Review quiz button.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/buttons/review.php.
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

do_action( 'learn-press/before-quiz-review-button' ); ?>

<form name="show-quiz-review" class="show-quiz-review form-button lp-form" method="post" enctype="multipart/form-data">

	<?php do_action( 'learn-press/begin-quiz-review-button' ); ?>

    <button type="submit"><?php _e( 'Review', 'learnpress' ); ?></button>

	<?php do_action( 'learn-press/end-quiz-review-button' ); ?>

    <?php LP_Nonce_Helper::quiz_action( 'show-review', $quiz->get_id(), $course->get_id() ); ?>

</form>

<?php do_action( 'learn-press/after-quiz-review-button' ); ?>
