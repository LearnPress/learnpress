<?php
/**
 * Template for displaying course items navigation
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1.5
 */

defined( 'ABSPATH' ) || exit;

$nav = learn_press_get_nav_course_item_url( $course_id, $item_id, $content_only );

?>
<nav id="lp-navigation" class="navigation course-item-navigation" role="navigation">
	<div class="nav-links">
		<?php if ( isset( $nav['back']['link'] ) ) { ?>
			<div class="nav-previous nav-link">
				<a class="course-item-title button-load-item js-action" data-id="<?php echo esc_attr( $nav['back']['id'] ); ?>" href="<?php echo esc_attr( $nav['back']['link'] ); ?>" rel="prev">
					<span class="meta-nav" aria-hidden="true"><?php esc_html_e( 'Previous', 'learnpress' ); ?></span>
					<span class="post-title"><?php echo esc_html( $nav['back']['title'] ); ?></span>
				</a>
			</div>
		<?php } ?>

		<?php if ( isset( $nav['next']['link'] ) ) { ?>
			<div class="nav-next nav-link">
				<a class="course-item-title button-load-item js-action" data-id="<?php echo esc_attr( $nav['next']['id'] ); ?>" href="<?php echo esc_attr( $nav['next']['link'] ); ?>" rel="next">
					<span class="meta-nav" aria-hidden="true"><?php esc_html_e( 'Next', 'learnpress' ); ?></span>
					<span class="post-title"><?php echo esc_html( $nav['next']['title'] ); ?></span>
				</a>
			</div>
		<?php } ?>
	</div>
</nav>