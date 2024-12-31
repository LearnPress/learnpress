<?php defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();
if ( $profile->get_user()->is_guest() ) {
	return;
}

if ( $profile->get_user_current()->is_guest()
	&& 'yes' !== LP_Profile::get_option_publish_profile() ) {
	return;
}

?>

<div class="learnpress">
	<div id="learn-press-profile" class="lp-user-profile current-user">
		<div class="lp-content-area">
			<?php
			if ( ! empty( $inner_content ) ) {
				echo $inner_content;
			}
			?>
		</div>
	</div>
</div>