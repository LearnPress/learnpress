<?php
$course_id = learn_press_get_course_by_quiz( get_the_ID() );
$passed = learn_press_user_has_passed_course( $course_id );
$result = learn_press_get_quiz_result();
?>
<div class="clearfix"></div>
<?php if( $passed ):?>
    <?php learn_press_message( sprintf( __( 'You have passed this course with %.2f%% of total', 'learn_press' ), $result['mark_percent'] * 100 ) ) ;?>
<?php else:?>
    <?php
    $passing_condition = learn_press_get_course_passing_condition( $course_id );
    ?>
    <?php learn_press_message( sprintf( __( 'Sorry, you have not passed this course. This course required you pass %.2f%% but your result is only %.2f%%', 'learn_press' ), $passing_condition, $result['mark_percent'] * 100 ), 'error' );?>
<?php endif;?>