<?php
/**
 * Template for displaying content of landing course
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action( 'learn_press_before_content_coming_soon' ); ?>

<div class="course-content_coming_soon">
	<?php do_action( 'learn_press_content_coming_soon_message' ); ?>
	<?php do_action( 'learn_press_content_coming_soon_countdown' ); ?>

</div>

<?php do_action( 'learn_press_after_content_coming_soon' ); ?>
