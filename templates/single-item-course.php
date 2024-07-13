<?php
/**
 * Template display single item of Course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0.0
 * @since 4.2.6.9
 */

//use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

defined( 'ABSPATH' ) || exit;

/**
 * @since 4.0.0
 *
 * @see LP_Template_General::template_header()
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-header' );
}

/**
 * LP Hook
 */
do_action( 'learn-press/before-main-content' );
$classes = apply_filters( 'learn-press/single-item-of-course/lp-content-area-class', [] );
?>

	<div class="lp-content-area <?php echo esc_attr( implode( $classes ) ); ?>">
		<?php do_action( 'learn-press/single-item-of-course/layout' ); ?>
	</div>

<?php
/**
 * LP Hook
 */
do_action( 'learn-press/after-main-content' );

/**
 * @since 4.0.0
 *
 * @see   LP_Template_General::template_footer()
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-footer' );
}
