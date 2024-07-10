<?php
/**
 * Template for displaying header of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php esc_html_e( 'LearnPress &rsaquo; Setup Wizard', 'learnpress' ); ?></title>
	<?php
	wp_print_scripts( 'lp-setup' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	do_action( 'admin_print_styles' );
	do_action( 'admin_print_scripts' );
	?>
</head>
<body class="lp-setup wp-core-ui js">
<div id="content">
	<div class="logo">
		<a href="javascript:void(0)">
			<?php $logoUrl = LP_PLUGIN_URL . 'assets/images/icon-128x128.png'; ?>
			<img src="<?php echo esc_url_raw( $logoUrl ); ?>">
		</a>
	</div>
