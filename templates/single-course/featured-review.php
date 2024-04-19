<?php
/**
 * Template for displaying featured review.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $review_content ) ) {
	return;
}
?>

<div class="course-featured-review margin-bottom">
	<div class="featured-review__title"><?php echo esc_html__( 'Featured Review', 'learnpress' ); ?></div>
	<div class="featured-review__stars">
		<i class="lp-icon-star"></i>
		<i class="lp-icon-star"></i>
		<i class="lp-icon-star"></i>
		<i class="lp-icon-star"></i>
		<i class="lp-icon-star"></i>
	</div>
	<div class="featured-review__content">
		<?php echo wp_kses_post( wpautop( $review_content ) ); ?>
	</div>
</div>
