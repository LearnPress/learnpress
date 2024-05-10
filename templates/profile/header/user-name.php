<?php
/**
 * Template for displaying profile username.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) || exit;

$profile = LP_Profile::instance();
$user    = $profile->get_user();

if ( ! isset( $user ) ) {
	return;
}

?>
	<h2 class="lp-profile-username">
		<?php echo wp_kses_post( $user->get_display_name() ); ?>
	</h2>
<?php
