<?php
/**
 * Template for displaying featured review.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

/**
 * @var string $review_content
 * @var number $review_value
 */
?>

<div class="course-featured-review margin-bottom">
    <h4 class="featured-review__title"><?php echo esc_html('Featured Review','learnpress'); ?></h4>
    <div class="featured-review__stars">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
    </div>
    <div class="featured-review__content">
		<?php echo $review_content; ?>
    </div>
</div>
