<?php
/**
 * Template for displaying content of single course with curriculum and
 * item's content inside it
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-single-course.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * @since 4.0.0
 *
 * @see LP_Template_General::template_header()
 */
do_action('learn-press/template-header');

/**
 * LP Hook
 */
do_action( 'learn-press/before-main-content' );

/**
 * LP Hook
 */
do_action( 'learn-press/before-single-item' );

/**
 * Get cookie stored for sidebar state
 */
$show_sidebar = learn_press_cookie_get( 'sidebar-toggle' );
?>
    <div id="popup-course" class="course-summary">

        <input type="checkbox" id="sidebar-toggle" <?php checked( $show_sidebar, true ); ?> />

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
 * LP Hook
 *
 * @since 3.0.0
 */
do_action( 'learn-press/after-main-content' );

/**
 * LP Hook
 *
 * @since 3.0.0
 */
do_action( 'learn-press/after-single-course' );

/**
 * LP Hook
 *
 * @since 4.0.0
 *
 * @see LP_Template_General::template_footer()
 */
do_action('learn-press/template-footer');
