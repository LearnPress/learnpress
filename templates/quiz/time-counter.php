<?php
/**
 * Template for displaying the remaining time of a quiz
 *
 */
learn_press_prevent_access_directly();

if( learn_press_user_has_completed_quiz() ){
    return;
}
if( learn_press_get_quiz_duration() ):
?>
<?php do_action( 'learn_press_before_quiz_clock' ); ?>
<div class="quiz-clock">
    <div class="quiz-timer">
        <div id="quiz-countdown"></div>
        <p class="quiz-countdown-text">
            <span class="quiz-time-remaining-text">
                <?php echo apply_filters( 'learn_press_quiz_time_remaining_text', __( 'Time remaining', 'learn_press' ) ); ?>
            </span>
            <span class="quiz-time-remaining-label"><?php echo apply_filters( 'learn_press_quiz_time_label', __( 'mins/secs', 'learn_press' ) ); ?></span>
        </p>
    </div>
</div>
<?php do_action( 'learn_press_after_quiz_clock' ); ?>
<?php endif;?>