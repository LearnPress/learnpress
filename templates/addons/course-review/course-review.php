<?php


if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$course_id = get_the_ID();        
$course_review = learn_press_get_course_review( $course_id );    
if( $course_review ) {
    $course_rate = learn_press_get_course_rate( $course_id );
    echo '<h3 class="review">Reviews</h3>';
    echo '<div class="user-review">';
    echo '<p>Average rate:  </p><h4 class="course-average-rate">'. $course_rate .'<h4>';        
    foreach( $course_review['user'] as $user_id ) {            
        $user_info = get_userdata( $user_id );
        if( !$user_info ) continue;
	        $review_rate = $course_review['rate'][$user_id];
            $review_title = $course_review['review_title'][$user_id];
            $review_content = $course_review['review_content'][$user_id];            
        ?>                                            
        <h4 class="user-name"><?php echo $user_info->user_login; ?></h4>
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
        <p class="review-title"> <?php echo $review_title ?></p>
        <p class="review-content"> <?php echo $review_content ?> </p>            
    <?php
        echo '</div>';            
    }
}