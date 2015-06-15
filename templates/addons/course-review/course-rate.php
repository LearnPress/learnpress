<?php 
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$course_id = get_the_ID();
$course_rate = learn_press_get_course_rate( $course_id );
$total = learn_press_get_course_rate_total( $course_id );
?>
<div class="course-rate">
<?php
    learn_press_get_template( 'addons/course-review/rating-stars.php', array( 'rated' => $course_rate ) );
    $text=' ratings';
    if( $total <= 1 ) $text = ' rating'; 
?>
    <p class="review-number">
        <?php do_action( 'learn_press_before_total_review_number' );?>
        <?php echo $total . $text ; ?>
        <?php do_action( 'learn_press_after_total_review_number' );?>
    </p>
</div>