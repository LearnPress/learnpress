<?php
/**
 * Template for displaying the buttons of a quiz
 *
 */
learn_press_prevent_access_directly();
?>
<?php do_action( 'learn_press_before_quiz_buttons' );?>
<div class="quiz-buttons">
    <?php if( !learn_press_user_has_started_quiz() ):?>
    <?php do_action( 'learn_press_before_start_quiz_button' );?>
    <button class="button-start-quiz btn" quiz-id="<?php echo get_the_ID() ?>">
        <?php
        // allow doing quiz if enrolled
        echo apply_filters( 'learn_press_start_quiz_button_text', esc_attr( "Start Quiz", "learn_press" ) );
        ?>
    </button>
    <?php do_action( 'learn_press_after_start_quiz_button' );?>
    <?php endif; ?>
    <?php if( !learn_press_user_has_completed_quiz() ):?>
    <?php do_action( 'learn_press_before_finish_quiz_button' );?>
    <button class="button-finish-quiz btn hidden" quiz-id="<?php echo get_the_ID() ?>">
        <?php
        // allow doing quiz if enrolled
        echo apply_filters( 'learn_press_finish_quiz_button_text', esc_attr( "Finish Quiz", "learn_press" ) );
        ?>
    </button>
    <?php do_action( 'learn_press_after_finish_quiz_button' );?>
    <?php endif;?>
    <?php if( learn_press_user_can_retake_quiz() ):?>
        <button class="button-retake-quiz btn" data-id="<?php the_ID();?>"><?php _e( 'Retake', 'learn_press' );?></button>
    <?php endif;?>
</div>
<?php do_action( 'learn_press_after_quiz_buttons' );?>
