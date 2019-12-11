<?php
/**
 * Template for displaying content of page for processing checkout feature.
 *
 * @author   ThimPress
 * @package  LearnPress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) or die;

get_header();
?>

    <div id="learn-press-checkout" class="lp-content-wrap">

		<?php

		/**
		 * LP Hook
		 *
		 * @since 4.0.0
		 */
		do_action( 'learn-press/before-checkout-page' );

		// Shortcode for displaying checkout form
		echo do_shortcode( '[learn_press_checkout]' );

		/**
		 * LP Hook
		 *
		 * @since 4.0.0
		 */
		do_action( 'learn-press/after-checkout-page' );
		?>

    </div>

<?php
get_footer();