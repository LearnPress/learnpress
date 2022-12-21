<?php
/**
 * Template for displaying user socials
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;
$user = LP_Profile::instance()->get_user();

if ( ! $user ) {
	return;
}

$socials = $user->get_profile_socials( $user->get_id() );
if ( empty( $socials ) ) {
	return;
}
?>

<div class="lp-user-profile-socials">
	<?php echo implode( "\n", $socials ); ?>
</div>
