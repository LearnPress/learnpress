<?php
/**
 * Template for displaying user profile avatar
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$user = LP_Profile::instance()->get_user();
?>

<div class="lp-user-profile-avatar">
	<?php echo wp_kses_post( $user->get_profile_picture() ); ?>
</div>
