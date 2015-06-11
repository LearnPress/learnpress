<li>
    <h4 class="user-name">
        <?php do_action( 'learn_press_before_review_username' );?>
        <?php echo $review->user_login; ?>
        <?php do_action( 'learn_press_after_review_username' );?>
    </h4>
    <?php learn_press_get_template( 'addons/course-review/rating-stars.php', array( 'rated' => $review->rate ) );?>
    <p class="review-title">
        <?php do_action( 'learn_press_before_review_title' );?>
        <?php echo $review->title ?>
        <?php do_action( 'learn_press_after_review_title' );?>
    </p>
    <div class="review-content">
        <?php do_action( 'learn_press_before_review_content' );?>
        <?php echo $review->content ?>
        <?php do_action( 'learn_press_after_review_content' );?>
    </div>
</li>