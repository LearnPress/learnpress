<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'LearnPress update version 1.0', 'learn_press' ); ?></title>
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
		<?php _e( 'LearnPress update version 1.0', 'learn_press' );?>
	</h1>
	<div class="lp-update-content">
		<?php do_action( 'learn_press_update_step_' . $this->_current_step );?>
	</div>
</body>
</html>
