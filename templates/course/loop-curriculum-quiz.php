<?php if ( $enrolled = learn_press_is_enrolled_course() ) {?>
<li <?php learn_press_course_quiz_class( $lesson_quiz );?>>
    <?php do_action( 'learn_press_course_lesson_quiz_before_title', $lesson_quiz, $enrolled );?>
    <a target="_blank" href="<?php echo get_the_permalink( $lesson_quiz );?>" data-id="<?php echo $lesson_quiz?>">
        <?php do_action( 'learn_press_course_lesson_quiz_begin_title', $lesson_quiz, $enrolled );?>
        <?php echo get_the_title( $lesson_quiz );?>
        <?php do_action( 'learn_press_course_lesson_quiz_end_title', $lesson_quiz, $enrolled );?>
    </a>
    <?php do_action( 'learn_press_course_lesson_quiz_after_title', $lesson_quiz, $enrolled );?>
</li>
<?php } else { ?>
<li <?php learn_press_course_quiz_class( $lesson_quiz );?>>
    <?php do_action( 'learn_press_course_lesson_quiz_before_title', $lesson_quiz, false );?>
    <span data-id="<?php echo $lesson_quiz?>">
        <?php do_action( 'learn_press_course_lesson_quiz_begin_title', $lesson_quiz, false );?>
        <?php echo get_the_title( $lesson_quiz );?>
        <?php do_action( 'learn_press_course_lesson_quiz_end_title', $lesson_quiz, false );?>
    </span>
    <?php do_action( 'learn_press_course_lesson_quiz_before_title', $lesson_quiz, false );?>
</li>
<?php }?>