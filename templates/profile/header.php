<?php
use LearnPress\Helpers\Template;

/**
 * Template for displaying profile header.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$profile = LP_Profile::instance();
$user    = $profile->get_user();

if ( ! isset( $user ) ) {
	return;
}

$bio = $user->get_description();
?>
<div class="lp-profile-right">
	<?php
	$sections = apply_filters(
		'learn-press/profile/header/sections',
		array(
			'profile/header/user-name.php',
			'profile/header/user-bio.php',
		),
		$user
	);

	Template::instance()->get_frontend_templates( $sections );
	?>
</div>
