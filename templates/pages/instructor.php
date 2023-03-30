<?php
/**
 * Template for displaying content of instructor page.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

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
do_action( 'learn-press/instructor/before-main-content' );

$instructor_page_id = learn_press_get_page_id( 'instructors' );
$page_title         = get_the_title( $instructor_page_id );

?>
<div class="lp-instructors">
<?php
call_user_func( LearnPress::instance()->template( 'general' )->func( 'breadcrumb' ) );
?>
	<div class="lp-content-area">
		<?php if ( $page_title ) : ?>
			<header class="learn-press-instructor-header">
				<h1><?php echo wp_kses_post( $page_title ); ?></h1>
			</header>
		<?php endif; ?>

		<?php
		/**
		 * LP Hook
		 */
		do_action( 'learn-press/before-instructor-loop' );
		?>
		<ul class="lp-instructor-list">
			<li class="lp-loading">
			</li>
		</ul>
		<?php
		do_action( 'learn-press/after-instructor-loop' );
		?>
	</div>
<?php

/**
 * LP Hook
 */
do_action( 'learn-press/instructor/after-main-content' );

/**
 * @since 4.0.0
 *
 * @see   LP_Template_General::template_footer()
 */
if ( ! wp_is_block_theme() ) {
	do_action( 'learn-press/template-footer' );
}
