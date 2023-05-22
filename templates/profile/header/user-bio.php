<?php
/**
 * Template for displaying profile user bio.
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

$bio = $user->get_description();
?>
<?php if ( $bio ) : ?>
	<div class="lp-profile-user-bio">
		<?php echo wpautop( $bio ); ?>
	</div>

<?php endif; ?>
