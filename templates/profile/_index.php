<?php
/**
 * Template for displaying user profile
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="learn-press-user-profile" id="learn-press-user-profile">

	<?php
	do_action( 'learn_press_user_profile_summary', $user, $current, $tabs );
	?>

</div>