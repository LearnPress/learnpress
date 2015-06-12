<?php
/**
 * Created by PhpStorm.
 * User: Ken
 * Date: 3/13/2015
 * Time: 15:58
 */

function learn_press_profile_shortcode() {

	global $wp_query;

	if ( isset( $wp_query->query['user'] ) ) {
		$user = get_user_by( 'login', $wp_query->query['user'] );
	} else {
		$user = get_user_by( 'id', get_current_user_id() );
	}

	$output = '';
	if ( !$user ) {
		$output .= '<strong>' . __( 'This user in not available!', 'learn_press' ) . '</strong>';
		return $output;
	}

	do_action( 'learn_press_before_profile_content' );

	?>
	<div id="profile-tabs">
		<?php do_action( 'learn_press_add_profile_tab', $user ); ?>
	</div>
	<script>
		jQuery(document).ready(function ($) {
			$("#profile-tabs").tabs();
			$( "#quiz-accordion" ).accordion();
		});
	</script>
	<?php
	do_action( 'learn_press_after_profile_content' );

}

add_shortcode( 'learn_press_profile', 'learn_press_profile_shortcode' );