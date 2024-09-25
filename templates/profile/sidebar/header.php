<?php
$profile   = LP_Profile::instance();
$image_url = $profile->get_cover_image_src();
$style     = '';
if ( ! empty( $image_url ) ) {
	$style = "background-image: url($image_url); background-repeat: no-repeat; background-position: center; background-size: cover;";
	$style = htmlspecialchars( $style, ENT_QUOTES, 'UTF-8' );
}
?>
<div class="wrapper-profile-header wrap-fullwidth" style="<?php esc_attr_e( $style ); ?>" >
	<div class="lp-content-area lp-profile-content-area">
		<?php do_action( 'learn-press/user-profile-account' ); ?>
	</div>
</div>
