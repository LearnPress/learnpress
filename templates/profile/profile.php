<?php
/**
 * Template for displaying user profile.
 * Main page which wrap all content in user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
/**
 * If $user is not set then get the current user.
 */
global $wp, $wp_rewrite;

if ( ! isset( $profile ) ) {
	$profile = learn_press_get_profile();
}
?>
<div id="learn-press-user-profile" class="lp-user-profile">

	<?php
	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/before-user-profile', $profile );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/user-profile', $profile );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/after-user-profile', $profile );
	?>

</div>