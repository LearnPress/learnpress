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
    <div class="featured-review__stars">
        <i class="fa fa-star"></i>
        <i class="fa fa-star"></i>
        <i class="fa fa-star"></i>
        <i class="fa fa-star"></i>
        <i class="fa fa-star"></i>
    </div>
    <div class="featured-review__content">
		<?php echo $review_content; ?>
    </div>
</div>
