<?php do_action( 'learn_press_before_quiz_question_nav_buttons' );?>
<div class="quiz-question-nav-buttons">
    <!--<button type="button" data-nav="prev" class="prev-question"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_back_title', __( 'Back', 'learn_press' ) );?></button>
    <button type="button" data-nav="next" class="next-question"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_title', __( 'Next', 'learn_press' ) );?></button>
    -->
    <?php $question_id = ! empty( $_REQUEST['question'] ) ? $_REQUEST['question'] : 0;?>
    <?php if( $prev = learn_press_get_user_prev_question_url( get_the_ID(), $question_id ) ){?>
    <button type="button" data-nav="prev"  class="pre-question" data-url="<?php echo $prev;?>"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_back_title', __( 'Back', 'learn_press' ) );?></button>
    <?php }?>
    <?php if( $next = learn_press_get_user_next_question_url( get_the_ID(), $question_id ) ){?>
    <button type="button" data-nav="next" class="next-question" data-url="<?php echo $next;?>"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_title', __( 'Next', 'learn_press' ) );?></button>
    <?php }?>
</div>
<?php do_action( 'learn_press_after_quiz_question_nav_buttons' );?>
