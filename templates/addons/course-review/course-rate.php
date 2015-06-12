<?php 
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$course_id = get_the_ID();
$course_rate = learn_press_get_course_rate( $course_id );
$total = learn_press_get_course_rate_total( $course_id );
?>
<div class="course-rate">
    <div class="review-stars-rated">
        <ul class="review-stars">
            <li><span class="dashicons dashicons-star-empty"></span> </li>
            <li><span class="dashicons dashicons-star-empty"></span> </li>
            <li><span class="dashicons dashicons-star-empty"></span> </li>
            <li><span class="dashicons dashicons-star-empty"></span> </li>
            <li><span class="dashicons dashicons-star-empty"></span> </li>
        </ul>
        <ul class="review-stars filled"  style="width:<?php echo $course_rate*20; ?>%;">
            <li><span class="dashicons dashicons-star-filled"></span> </li>
            <li><span class="dashicons dashicons-star-filled"></span> </li>
            <li><span class="dashicons dashicons-star-filled"></span> </li>
            <li><span class="dashicons dashicons-star-filled"></span> </li>
            <li><span class="dashicons dashicons-star-filled"></span> </li>
        </ul>
    </div>
<?php 
    $text=' ratings';
    if( $total <= 1 ) $text = ' rating'; 
?>
    <p class="review-number"><?php echo $total . $text ; ?></p>
</div>