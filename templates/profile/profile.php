<?php
/**
 * Template for displaying user profile.
 * Main page which wrap all content in user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$profile = LP_Global::profile();
?>
<div id="learn-press-user-profile"<?php $profile->main_class();?>>

	<?php
	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/before-user-profile', $profile );

	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/user-profile', $profile );

	/**
	 * @since 3.0.0
	 */
	do_action( 'learn-press/after-user-profile', $profile );
	?>

</div>