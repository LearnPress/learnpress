<?php do_action( 'learn_press_before_quiz_question_nav_buttons' );?>
<div class="quiz-question-nav-buttons">
    <button type="button" data-nav="prev" class="prev-question"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_back_title', __( 'Back', 'learn_press' ) );?></button>
    <button type="button" data-nav="next" class="next-question"><?php echo apply_filters( 'learn_press_quiz_question_nav_button_next_title', __( 'Next', 'learn_press' ) );?></button>
</div>
<?php do_action( 'learn_press_after_quiz_question_nav_buttons' );?>
