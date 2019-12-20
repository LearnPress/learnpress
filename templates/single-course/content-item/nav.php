<?php
/**
 * Template for displaying next/prev item in course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

/**
 * @var LP_Course_Item $prev_item
 * @var LP_Course_Item $next_item
 */
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

<div class="course-item-nav" data-nav="<?php echo $nav; ?>">
	<?php if ( $prev_item ) { ?>
        <div class="prev">
            <div class="course-item-nav__name"><?php echo esc_html( $prev_item->get_title() ); ?></div>
            <a href="<?php echo $prev_item->get_permalink(); ?>">
				<?php echo esc_html_x( 'Prev', 'course-item-navigation', 'learnpress' ); ?>
            </a>
        </div>
	<?php } ?>

	<?php if ( $next_item ) { ?>
        <div class="next">
            <div class="course-item-nav__name"><?php echo esc_html( $next_item->get_title() ); ?></div>
            <a href="<?php echo $next_item->get_permalink(); ?>">
				<?php echo esc_html_x( 'Next', 'course-item-navigation', 'learnpress' ); ?>
            </a>
        </div>
	<?php } ?>
</div>


