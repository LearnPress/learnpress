<?php
learn_press_prevent_access_directly();
if( learn_press_user_has_completed_quiz( learn_press_get_current_user_id() ) ){
    return;
}
?>
<?php do_action( 'learn_press_before_quiz_question_nav_buttons' );?>
<div class="quiz-question-nav-buttons">
    <?php $question_id = ! empty( $_REQUEST['question'] ) ? $_REQUEST['question'] : 0;?>
    <?php //if( $prev = learn_press_get_user_prev_question_url( get_the_ID(), $question_id ) ){?>
    <button type="button" data-nav="prev"  class="prev-question" data-url="<?php echo $prev;?>"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_back_title', __( 'Back', 'learn_press' ) );?></button>
    <?php //}?>
    <?php //if( $next = learn_press_get_user_next_question_url( get_the_ID(), $question_id ) ){?>
    <button type="button" data-nav="next" class="next-question" data-url="<?php echo $next;?>"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_title', __( 'Next', 'learn_press' ) );?></button>
    <?php //}?>
    <button class="button-finish-quiz btn hidden" quiz-id="<?php echo get_the_ID() ?>" data-area="nav">
        <?php
        // allow doing quiz if enrolled
        echo apply_filters( 'learn_press_finish_quiz_text', __( "Finish", "learn_press" ) );
        ?>
    </button>
</div>
<?php do_action( 'learn_press_after_quiz_question_nav_buttons' );?>
