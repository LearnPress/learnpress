<?php
/**
 * Template for displaying item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item.php.
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

<?php
global $lp_course, $lp_course_item;
$user = learn_press_get_current_user();
?>

<div id="learn-press-content-item">

	<?php do_action( 'learn-press/course-item-content-header', $lp_course_item->get_id(), $lp_course->get_id() ); ?>

    <div class="content-item-scrollable">

        <div class="content-item-wrap">

			<?php
			/**
			 * @deprecated
			 */
			do_action( 'learn_press/before_course_item_content', $lp_course_item->get_id(), $lp_course->get_id() );

			/**
			 * @since 3.0.0
			 *
			 */
			do_action( 'learn-press/before-course-item-content', $lp_course_item->get_id(), $lp_course->get_id() );

			if ( $user->can_view_item( $lp_course_item->get_id(), $lp_course->get_id() ) ) {
				/**
				 * @deprecated
				 */
				do_action( 'learn_press_course_item_content', $lp_course_item );

				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/course-item-content', $lp_course_item->get_id(), $lp_course->get_id() );

			} else {
				learn_press_get_template( 'single-course/content-protected.php', array( 'item' => $lp_course_item ) );
			}

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn_press/after-course-item-content', $lp_course_item->get_id(), $lp_course->get_id() );

			/**
			 * @deprecated
			 */
			do_action( 'learn_press_after_content_item', $lp_course_item->get_id(), $lp_course->get_id() );
			?>

        </div>

    </div>

	<?php do_action( 'learn-press/course-item-content-footer', $lp_course_item->get_id(), $lp_course->get_id() ); ?>

</div>