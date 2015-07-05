<?php
$viewable = learn_press_user_can_view_quiz( $lesson_quiz );//learn_press_is_enrolled_course();
$tag = $viewable ? 'a' : 'span';
$target = apply_filters( 'learn_press_quiz_link_target', '_blank', $lesson_quiz );
?>
<li <?php learn_press_course_quiz_class( $lesson_quiz );?>>
    <?php do_action( 'learn_press_course_lesson_quiz_before_title', $lesson_quiz, $viewable );?>
    <<?php echo $tag;?> target="<?php echo $target;?>" <?php echo $viewable ? 'href="' . get_the_permalink( $lesson_quiz ) . '"' : '';?> data-id="<?php echo $lesson_quiz?>">
        <?php do_action( 'learn_press_course_lesson_quiz_begin_title', $lesson_quiz, $viewable );?>
        <?php echo get_the_title( $lesson_quiz );?>
        <?php do_action( 'learn_press_course_lesson_quiz_end_title', $lesson_quiz, $viewable );?>
    </<?php echo $tag;?>>
    <?php do_action( 'learn_press_course_lesson_quiz_after_title', $lesson_quiz, $viewable );?>
</li>