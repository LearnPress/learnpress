<?php do_action( 'learn_press_quiz_questions_begin_questions_loop' );?>
<li class="qq sibdebar-quiz-question-<?php echo $question_id;?><?php echo $current ? ' current':'';?>">
    <?php do_action( 'learn_press_quiz_questions_before_question_title_element' );?>
    <h4 class="list-quiz-question" question-id="<?php echo $question_id;?>" question-index="<?php echo $index;?>">
        <?php do_action( 'learn_press_quiz_questions_begin_questions_title_element' );?>
        <?php echo $question_title;?>
        <?php do_action( 'learn_press_quiz_questions_end_questions_title_element' );?>
    </h4>
    <?php do_action( 'learn_press_quiz_questions_after_question_title_element', $question_id );?>
</li>
<?php do_action( 'learn_press_quiz_questions_end_questions_loop' );?>
