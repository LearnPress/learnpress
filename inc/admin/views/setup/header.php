<?php
/**
 * Template for displaying header of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$wizard          = LP_Setup_Wizard::instance();
$current_step    = $wizard->get_current_step();
$is_welcome_step = 'welcome' === $current_step;
$body_classes    = array( 'lp-setup', 'wp-core-ui', 'js' );
$body_classes[]  = $is_welcome_step ? 'lp-setup--welcome' : 'lp-setup--wizard';
$body_classes[]  = 'lp-setup-step--' . sanitize_html_class( $current_step );
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
<body class="<?php echo esc_attr( implode( ' ', $body_classes ) ); ?>">
<div id="content">
	<div class="logo">
		<a href="javascript:void(0)">
			<?php $logo_url = LP_PLUGIN_URL . 'assets/images/lp-logo-row.png'; ?>
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'LearnPress', 'learnpress' ); ?>">
		</a>
	</div>
