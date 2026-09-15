<?php
/**
 * Template for displaying next/prev item in course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $prev_item ) && ! isset( $next_item ) ) {
	return;
}

if ( $prev_item && $next_item ) {
	$nav = 'all';
} elseif ( $prev_item ) {
	$nav = 'prev';
} else {
	$nav = 'next';
}
?>

<div class="course-item-nav" data-nav="<?php echo esc_attr( $nav ); ?>">
	<?php if ( $prev_item instanceof LP_Course_Item ) : ?>
		<a href="<?php echo esc_url_raw( $prev_item->get_permalink() ); ?>"  class="prev">
			<span class="nav-text">
				<?php echo esc_html_x( 'Previous', 'course-item-navigation', 'learnpress' ); ?>
				<span class="course-item-nav__title"><?php echo esc_html( $prev_item->get_title() ); ?></span>
			</span>
		</a>
	<?php endif; ?>
	<?php if ( $next_item instanceof LP_Course_Item ) : ?>
		<a href="<?php echo esc_url_raw( $next_item->get_permalink() ); ?>" class="next">
			<span class="nav-text">
				<?php echo esc_html_x( 'Next', 'course-item-navigation', 'learnpress' ); ?>
				<span class="course-item-nav__title"><?php echo esc_html( $next_item->get_title() ); ?></span>
			</span>
		</a>
	<?php endif; ?>
</div>


