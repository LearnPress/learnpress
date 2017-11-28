<?php
/**
 * Template for displaying restrict access.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/restrict-access.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

get_header();

// @deprecated
do_action( 'learn_press_before_main_content' );

// @since 3.0.0
do_action( 'learn-press/before-main-content' );
?>

<div class="restrict-access-page">

	<?php learn_press_display_message( __( 'You have no permission to view this area. Please contact site\'s administrators for more details.', 'learnpress' ) ); ?>

</div>

<?php

// @since 3.0.0
do_action( 'learn-press/after-main-content' );

// @deprecated
do_action( 'learn_press_after_main_content' );

get_footer(); ?>
