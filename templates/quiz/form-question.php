<form method="post" action="" id="nav-question-form" name="nav-question-form">
    <?php do_action( 'learn_press_before_nav_question_form', $question_id, $course_id );?>
    <?php learn_press_output_question( $question_id );?>
    <?php do_action( 'learn_press_after_nav_question_form', $question_id, $course_id );?>
</form>