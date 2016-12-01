<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
$theme = wp_get_theme();
?>
<div id="message" class="error learn-press-message">
	<p>
		<?php printf( __( 'There are some outdated of LearnPress template files in your theme <strong>(%s)</strong>.', 'learnpress' ), esc_html( $theme['Name'] ) ); ?></p>
	<p>
		<?php _e( 'Please ensure that these templates are up-to-date to make sure they are compatible with current version of LearnPress.', 'learnpress' ); ?></p>
	<p>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=learn-press-tools&tab=templates' ) ); ?>"><?php _e( 'View list of outdated templates', 'learnpress' ); ?></a>
	</p>
</div>