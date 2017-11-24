<?php
/**
 * Template for displaying Dashboard of user profile.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/profile/dashboard.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

?>

<div class="learn-press-profile-dashboard">

	<?php

	do_action( 'learn-press/profile/before-dashboard' );

	do_action( 'learn-press/profile/dashboard-summary' );

	do_action( 'learn-press/profile/after-dashboard' );

	?>

</div>