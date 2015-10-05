<?php
/**
 * Template for displaying the enroll button of a course
 * @modified    TuNN
 */
learn_press_prevent_access_directly();

do_action( 'learn_press_before_course_enroll_button' );
$button_text = apply_filters( 'learn_press_take_course_button_text', __( 'Take this course', 'learn_press' ) );
$loading_text = apply_filters( 'learn_press_take_course_button_loading_text', __( 'Processing', 'learn_press' ) );
?>
<button id="learn_press_take_course" class="btn take-course" data-course-id="<?php the_ID();?>" data-text="<?php echo $button_text;?>" data-loading-text="<?php echo $loading_text;?>"><?php echo $button_text;?></button>
<?php do_action( 'learn_press_after_course_enroll_button' );?>

