<<<<<<< HEAD
<?php
/**
 * The template for displaying single course content
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header(); ?>

<?php do_action( 'learn_press_before_main_content' ); ?>

<div class="restrict-access-page">
	<?php learn_press_display_message( __( 'You have no permission to view this area. Please contact site\'s administrators for more details.', 'learnpress' ) ); ?>
</div>

<?php do_action( 'learn_press_after_main_content' ); ?>

<?php get_footer(); ?>
=======
<?php
/**
 * The template for displaying single course content
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
get_header(); ?>

<?php do_action( 'learn_press_before_main_content' ); ?>

<div class="restrict-access-page">
	<?php learn_press_display_message( __( 'You have no permission to view this area. Please contact site\'s administrators for more details.', 'learnpress' ) ); ?>
</div>

<?php do_action( 'learn_press_after_main_content' ); ?>

<?php get_footer(); ?>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
