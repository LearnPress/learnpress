<form method="post" action="" id="nav-question-form" name="nav-question-form">
    <?php do_action( 'learn_press_before_nav_question_form', $question_id, $course_id );?>
    <?php learn_press_output_question( /*$question_id*/  ! empty( $_REQUEST['question'] ) ? $_REQUEST['question'] : 0, false, get_the_ID() );?>
    <?php do_action( 'learn_press_after_nav_question_form', $question_id, $course_id );?>
</form>