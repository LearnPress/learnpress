<?php learn_press_prevent_access_directly(); ?>
<button class="write-a-review"><?php _e( 'Write a Review', 'learn_press' );?></button>
<div class="review-wrapper" id="review">
    <div class="review-content" id="reviewTarget">
        <h3>
            <?php _e( 'Write a review', 'learn_press' );?>
            <a href="" class="close dashicons dashicons-no-alt"></a>
        </h3>
        <ul class="review-fields">
            <?php do_action( 'learn_press_before_review_fields' );?>
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
            <?php do_action( 'learn_press_after_review_fields' );?>
            <li class="review-actions">
                <span class="submitting"><?php _e( 'Please wait...', 'learn_press' );?></span>
                <button type="button" class="submit-review" data-id="<?php the_ID();?>"><?php _e( 'Add review', 'learn_press' );?></button>
                <button type="button" class="cancel"><?php _e( 'Cancel', 'learn_press' );?></button>
            </li>
        </ul>
    </div>
</div>