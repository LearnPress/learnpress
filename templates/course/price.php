<?php
/**
 * Template for displaying the price of a course
 */
learn_press_prevent_access_directly();
if ( learn_press_is_enrolled_course() ) {
    return;
}
do_action( 'learn_press_before_course_price' );
?>
<span class="course-price">
    <?php do_action( 'learn_press_begin_course_price' );?>
	<?php echo learn_press_get_course_price( null, true );?>
    <?php do_action( 'learn_press_end_course_price' );?>
</span>
<?php do_action( 'learn_press_after_course_price' );?>
