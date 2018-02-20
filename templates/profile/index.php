<<<<<<< HEAD
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

=======
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

>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
</div>