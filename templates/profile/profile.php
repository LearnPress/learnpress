<?php
/**
 * Template for displaying user profile.
 * Main page which wrap all content in user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit;
/**
 * If $user is not set then get the current user.
 */
if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}
?>
<div id="learn-press-user-profile" class="lp-user-profile">

	<?php
	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/before-user-profile', $user );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/user-profile', $user );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/after-user-profile', $user );
	?>

</div>