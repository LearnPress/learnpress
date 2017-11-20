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
?>

<?php get_header(); ?>

<?php do_action( 'learn_press_before_main_content' ); ?>

<div class="restrict-access-page">

	<?php learn_press_display_message( __( 'You have no permission to view this area. Please contact site\'s administrators for more details.', 'learnpress' ) ); ?>

</div>

<?php do_action( 'learn_press_after_main_content' ); ?>

<?php get_footer(); ?>
