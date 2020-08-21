<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>
		<?php
		// translators: %s: version.
		printf( __( 'LearnPress update version %s', 'learnpress' ), learn_press_get_current_version() );
		?>
	</title>
	<?php do_action( 'admin_print_styles' ); ?>
	<?php do_action( 'admin_print_scripts' ); ?>
	<?php do_action( 'admin_head' ); ?>
	<script type="text/javascript">
		if (typeof window.ajaxurl == 'undefined') {
			window.ajaxurl = '<?php echo admin_url( 'admin-ajax.php' );?>';
		}
	</script>
</head>
<body class="wp-core-ui lp-update-10">
<h1 class="head">
	<span class="dashicons dashicons-welcome-learn-more"></span>
	<?php printf( __( 'LearnPress update version %s', 'learnpress' ), learn_press_get_current_version() ); ?>
</h1>
<div class="lp-update-content">
	<form name="learn-press-upgrade" method="post"
		  action="options-general.php?page=learn_press_upgrade_from_09&_wpnonce=<?php echo wp_create_nonce( 'learn-press-upgrade-09' ); ?>">
		<?php do_action( 'learn_press_update_step_' . $this->_current_step ); ?>
		<?php wp_nonce_field( 'learn-press-upgrade-09', '_wpnonce' ); ?>
	</form>
</div>
</body>
</html>
