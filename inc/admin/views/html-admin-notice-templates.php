<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( learn_press_get_user_option( 'hide-notice-template-files' ) == 'yes' ) {
	return;
}
$template_dir   = get_template_directory();
$stylesheet_dir = get_stylesheet_directory();
$cradle         = learn_press_detect_outdated_template();
$theme          = wp_get_theme();
$theme_name     = array();

if ( $template_dir === $stylesheet_dir ) {
	$theme_name[] = $theme['Name'];
} else {
	if ( $cradle['parent_item'] ) {
		$theme_name[] = $parent = $theme->__get( 'parent_theme' );
	}
	if ( $cradle['child_item'] ) {
		$theme_name[] = $theme['Name'];
	}
}
$theme_name = implode( ' & ', $theme_name );


?>
<div id="message" class="learn-press-message notice notice-warning">
	<p>
		<?php printf( __( 'Your theme <strong>(%s)</strong> contains outdated copies of some LearnPress template files.', 'learnpress' ), esc_html( $theme_name ) ); ?></p>
	<p>
		<?php _e( 'These files may need updating to ensure they are compatible with the current version of LearnPress. You can see which files are affected from the system status page. If in doubt, check with the author of the theme.', 'learnpress' ); ?></p>
	<p>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=learn-press-tools&tab=templates' ) ); ?>"><?php _e( 'View list of outdated templates', 'learnpress' ); ?></a>
	</p>
	<a href="<?php echo esc_url( add_query_arg( 'lp-hide-notice', 'template-files', learn_press_get_current_url() ) ); ?>" class="learn-press-admin-notice-dismiss"></a>
</div>
