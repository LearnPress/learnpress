<?php
/**
 * Template for displaying the students of a course
 */
learn_press_prevent_access_directly();
?>
<?php do_action( 'learn_press_before_course_students' );?>
<span class="course-students">
    <?php do_action( 'learn_press_begin_course_students' );?>
    <?php if( $count = learn_press_count_students_enrolled() ):?>
        <?php if( strtolower( learn_press_get_user_course_status() ) == 'completed' ):?>
            <?php if( $count == 1):?>
                <?php _e( 'You enrolled', 'learn_press' );?>
            <?php else:?>
            <?php printf( _nx( 'You and one student enrolled', 'You and %1$s students enrolled', intval( $count - 1 ), '', 'learn_press' ), $count - 1 );?>
            <?php endif;?>
        <?php else:?>
            <?php printf( _nx( 'One student enrolled', '%1$s students enrolled', $count, '', 'learn_press' ), $count );?>
        <?php endif;?>
    <?php else:?>
    <?php _e( 'No student enrolled', 'learn_press' );?>
    <?php endif;?>
    <?php do_action( 'learn_press_end_course_students' );?>
</span>
<?php do_action( 'learn_press_after_course_students' );?>
