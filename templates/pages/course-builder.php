<?php
/**
 * Template for displaying main course builder page.
 *
 * @author   VuxMinhThanh
 * @package  Learnpress/Templates
 * @version  4.3.0
 */

defined( 'ABSPATH' ) || exit();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<title><?php echo wp_get_document_title(); ?></title>
	<?php
	do_action( 'wp_enqueue_scripts' );

	// Add required LearnPress inline data (lpData, lpSettingCourses) and root CSS that would originally be printed in wp_head
	if ( class_exists( 'LP_Assets' ) ) {
		LP_Assets::instance()->load_scripts_styles_on_head();
	}

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
		wp_print_media_templates();
	}

	wp_print_footer_scripts();
	?>
</body>
</html>
