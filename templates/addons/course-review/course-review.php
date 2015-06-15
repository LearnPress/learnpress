<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$course_id = get_the_ID();
$course_review = learn_press_get_course_review( $course_id );
if( $course_review['total'] ) {
    $course_rate = learn_press_get_course_rate( $course_id );
    $reviews = $course_review['reviews'];
    ?>
    <div id="course-reviews">
        <h3 class="course-review-head"><?php _e( 'Reviews', 'learn_press' );?></h3>
        <p class="course-average-rate"><?php printf( __( 'Average rate: <span>%.1f</span>', 'learn_press' ), $course_rate );?></p>
        <ul class="course-reviews-list">
            <?php foreach( $reviews as $review ) {?>
                <?php
                    $loop = learn_press_locate_template( 'addons/course-review/loop-review.php' );
                    require $loop;
                ?>
            <?php } ?>
            <?php if( empty( $course_review['finish'] ) ){?>
            <li class="loading"><?php _e( 'Loading...', 'learn_press' );?></li>
            <?php }else{?>
            <li><?php _e( 'No review to load', 'learn_press' );?></li>
            <?php }?>
        </ul>
        <?php if( empty( $course_review['finish'] ) ){?>
        <button class="button" id="course-review-load-more" data-paged="<?php echo $course_review['paged'];?>"><?php _e( 'Load More', 'learn_press' );?></button>
        <?php }?>
    </div>
    <?php
}