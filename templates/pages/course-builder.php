<?php
/**
 * Template for displaying main course builder page.
 *
 * @author   VuxMinhThanh
 * @package  Learnpress/Templates
 * @version  1.0.0
 * @since 4.3.6
 */

use LearnPress\CourseBuilder\CourseBuilder;

defined( 'ABSPATH' ) || exit();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<title>
		<?php
		printf(
			'%s &lsaquo; %s &#8212; %s',
			CourseBuilder::get_title_page(),
			__( 'Course Builder', 'learnpress' ),
			esc_html( get_bloginfo( 'name' ) ),
		)
		?>
	</title>
	<?php

	$asset = LP_Assets::instance();
	$asset->load_scripts_on_head();
	$asset->load_scripts();

	wp_enqueue_editor();
	wp_enqueue_media();

	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	wp_print_styles();
	wp_print_head_scripts();
	?>
</head>
<body <?php body_class( 'lp-course-builder-canvas' ); ?>>
	<div id="lp-course-builder">
		<div class="lp-course-builder_layout">
			<?php do_action( 'learn-press/course-builder/layout' ); ?>
		</div>
	</div>
	<?php
	// Print media templates required by wp.media (usually resides in admin_footer or wp_footer)
	if ( function_exists( 'wp_print_media_templates' ) ) {
		wp_print_styles(
			[
				'media-views',
				'imgareaselect',
				'mediaelement',
				'wp-mediaelement',
				'buttons',
				//'wp-admin', // Comment it, it good for style media, but it makes wp-editor error (theme2021)
			]
		);
		wp_print_media_templates();
	}

	wp_print_footer_scripts();
	?>
</body>
</html>
