<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'LearnPress update version 1.0', 'learnpress' ); ?></title>
	<?php wp_print_scripts( 'wc-setup' ); ?>
	<?php do_action( 'admin_print_styles' ); ?>
	<?php do_action( 'admin_print_scripts' ); ?>
	<?php do_action( 'admin_head' ); ?>
	<script type="text/javascript">
		if( typeof window.ajaxurl == 'undefined' ) {
			window.ajaxurl = '<?php echo admin_url( 'admin-ajax.php' );?>';
		}
	</script>
</head>
<body class="wp-core-ui lp-update-10">
	<h1 class="head">
		<span class="dashicons dashicons-welcome-learn-more"></span>
		<?php _e( 'LearnPress update version 1.0', 'learnpress' );?>
	</h1>
	<div class="lp-update-content">
		<form name="learn-press-upgrade" method="post" action="options-general.php?page=learn_press_upgrade_10&_wpnonce=<?php echo wp_create_nonce( 'learn-press-upgrade' );?>">
			<?php do_action( 'learn_press_update_step_' . $this->_current_step );?>
			<?php wp_nonce_field( 'learn-press-upgrade', '_wpnonce' );?>
		</form>
	</div>
</body>
</html>
