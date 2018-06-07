<?php
/**
 * Template for displaying next/prev item in course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

if ( ! isset( $prev_item ) && ! isset( $next_item ) ) {
	return;
}
?>

<div class="course-item-nav">
	<?php if ( $prev_item ) { ?>
        <div class="prev">
            <span><?php echo esc_html_x( 'Prev', 'course-item-navigation', 'learnpress' ); ?></span>
            <a href="<?php echo $prev_item->get_permalink(); ?>">
				<?php echo $prev_item->get_title(); ?>
            </a>
        </div>
	<?php } ?>

	<?php if ( $next_item ) { ?>
        <div class="next">
            <span><?php echo esc_html_x( 'Next', 'course-item-navigation', 'learnpress' ); ?></span>
            <a href="<?php echo $next_item->get_permalink(); ?>">
				<?php echo $next_item->get_title(); ?>
            </a>
        </div>
	<?php } ?>
</div>


