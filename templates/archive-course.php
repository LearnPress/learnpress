<?php
/**
 * Template default for displaying content of archive courses page.
 * If you want to override layout default, please override via hook 'learn-press/list-courses/layout', or another hook inside.
 * Override file is will be soon not support on the feature. Because it is many risks.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.2
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

$page_title = learn_press_page_title( false );
$classes    = [];

if ( is_active_sidebar( 'archive-courses-sidebar' ) ) {
	$classes[] = 'has-sidebar';
}
/**
 * @since 4.2.3.4
 *
 *  filter lp/show-archive-course/title
 */
?>

<div class="lp-content-area <?php echo esc_attr( implode( $classes ) ); ?>">
	<div class="lp-main-content">
	<?php if ( $page_title && apply_filters( 'lp/show-archive-course/title', true ) ) : ?>
		<header class="learn-press-courses-header">
			<h1><?php echo wp_kses_post( $page_title ); ?></h1>

			<?php do_action( 'lp/template/archive-course/description' ); ?>
		</header>
	<?php endif; ?>

	<?php do_action( 'learn-press/list-courses/layout' ); ?>
	</div>
	<?php
	/**
	 * LP Hook
	 *
	 * @since 4.0.0
	 */
	do_action( 'learn-press/archive-course/sidebar' );
	?>
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
