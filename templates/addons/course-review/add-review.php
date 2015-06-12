<?php
$user_id = get_current_user_id();
$course_id = get_the_ID();
$user_review    = learn_press_get_user_review_title( $course_id, $user_id );
if( $user_review ) {
    return 0;
}
?>
<button class="write-a-review"><?php _e( 'Write a Review', 'learn_press' );?></button>
<div class="review-wrapper" id="review">
    <div class="review-content" id="reviewTarget">
        <h3>
            <?php _e( 'Write a review', 'learn_press' );?>
            <a href="" class="close dashicons dashicons-no-alt"></a>
        </h3>
        <ul class="review-fields">
            <li>
                <label><?php _e( 'Title', 'learn_press' );?> <span class="required">*</span></label>
                <input type="text" name="review-title" />
            </li>
            <li>
                <label><?php _e( 'Content', 'learn_press' );?><span class="required">*</span></label>
                <textarea name="review-content"></textarea>
            </li>
            <li>
                <label><?php _e( 'Rating', 'learn_press' );?><span class="required">*</span></label>
                <ul class="review-stars">
                    <?php for( $i = 1; $i <= 5; $i ++ ){?>
                        <li class="review-title" title="<?php echo $i;?>"><span class="dashicons dashicons-star-empty"></span> </li>
                    <?php }?>
                </ul>
            </li>
            <li class="review-actions">
                <span class="submitting"><?php _e( 'Please wait...', 'learn_press' );?></span>
                <button type="button" class="submit-review" data-id="<?php the_ID();?>"><?php _e( 'Add review', 'learn_press' );?></button>
                <button type="button" class="cancel"><?php _e( 'Cancel', 'learn_press' );?></button>
            </li>
        </ul>
        <!--
            <form>
                <fieldset>
                    <span class="star-cb-group">
                        <input type="radio" id="rating-5" name="rating" required value="5" /><label for="rating-5">5</label>
                        <input type="radio" id="rating-4" name="rating" value="4" /><label for="rating-4">4</label>
                        <input type="radio" id="rating-3" name="rating" value="3" /><label for="rating-3">3</label>
                        <input type="radio" id="rating-2" name="rating" value="2" /><label for="rating-2">2</label>
                        <input type="radio" id="rating-1" name="rating" value="1" /><label for="rating-1">1</label>
                        <input type="radio" id="rating-0" name="rating" value="0" class="star-cb-clear" /><label for="rating-0">0</label>
                    </span>
                </fieldset>
            </form>
            <a class="close">x</a>
            <h3>Write a review</h3>
            <form>
                <input type="text" name="review-title" placeholder="Your review title" required />
                <textarea id="review-content" name="review-content" placeholder="Your review content here" required></textarea>
                <button class="submit-review" course-id="<?php echo get_the_ID() ?>">Add review</button>
            </form>-->
    </div>
</div>