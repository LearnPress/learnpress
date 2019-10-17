<?php
/**
 * Template for displaying course content within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-single-course.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

get_header();

//do_action( 'learn-press/before-main-content' );

//do_action( 'learn-press/before-single-item' );
$checked = learn_press_cookie_get( 'sidebar-toggle' );
?>
    <div id="popup-course" class="course-summary">

        <input type="checkbox" id="sidebar-toggle" <?php checked( $checked, true ); ?> />

		<?php
		/**
		 * @since 3.0.0
		 *
		 * @see   learn_press_single_item_summary()
		 */
		do_action( 'learn-press/single-item-summary' );
		?>
    </div>
<?php

/**
 * @since 3.0.0
 */
//do_action( 'learn-press/after-main-content' );

//do_action( 'learn-press/after-single-course' );

get_footer();
