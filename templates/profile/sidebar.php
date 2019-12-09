<?php
/**
 * Template for displaying sidebar in user profile.
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined('ABSPATH') or die;
?>
<aside id="profile-sidebar">

	<?php
	/**
	 * LP Hook
	 */

	do_action('learn-press/user-profile-account');
	?>

	<?php
	/**
	 * LP Hook
	 */

	do_action('learn-press/user-profile-tabs');
	?>

</aside>