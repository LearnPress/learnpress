<?php
/**
 * @author  ThimPress
 * @package LearnPress/Shortcodes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

function learn_press_profile_shortcode() {
	global $wp_query;

	if ( isset( $wp_query->query['user'] ) ) {
		$user = get_user_by( 'login', urldecode( $wp_query->query['user'] ) );
	} else {
		$user = get_user_by( 'id', get_current_user_id() );
	}

	$output = '';
	if ( !$user ) {
		$output .= '<strong>' . __( 'This user is not available!', 'learnpress' ) . '</strong>';
		return $output;
	}

	do_action( 'learn_press_before_profile_content', $user );

	?>
	<div id="profile-tabs">
		<?php do_action( 'learn_press_add_profile_tab', $user ); ?>
	</div>
	<?php do_action( 'learn_press_after_profile_content', $user ); ?>
	<script>
		jQuery(document).ready(function ($) {
			$("#profile-tabs").tabs();
			$("#quiz-accordion").accordion();
		});
	</script>
	<?php
}

//add_shortcode( 'learn_press_profile', 'learn_press_profile_shortcode' );