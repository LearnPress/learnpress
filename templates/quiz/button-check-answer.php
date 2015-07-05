<?php do_action( 'learn_press_before_checK_answer_button' );?>
<div class="quiz-question-answer">
    <button type="button" data-nav="answer" class="check_answer"><?php echo apply_filters( 'learn_press_check_answer_button', __( 'Check answer', 'learn_press' ) );?></button>    
</div>
<?php do_action( 'learn_press_after_check_answer_button' );?>