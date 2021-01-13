<?php
/**
 * Template for displaying item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$user                    = LP_Global::user();
$course                  = LP_Global::course();
$course_item             = LP_Global::course_item();
$can_view_content_course = $user->can_view_content_course( $course->get_id() );
$can_view_content_item   = $user->can_view_item( $course_item->get_id(), $can_view_content_course );
?>

<div id="learn-press-content-item">

	<?php do_action( 'learn-press/course-item-content-header' ); ?>

	<div class="content-item-scrollable">
		<div class="content-item-wrap">
			<?php
			do_action( 'learn-press/before-course-item-content' );

			if ( $can_view_content_item->flag ) {

				do_action(
					'learn-press/course-item-content',
					$user,
					$course,
					$course_item,
					$course_item,
					$can_view_content_course,
					$can_view_content_item
				);

			} else {
				learn_press_get_template(
					'single-course/content-protected.php',
					array( 'can_view_item' => $can_view_content_item )
				);
			}

			do_action(
				'learn-press/after-course-item-content',
				$user,
				$course,
				$course_item,
				$course_item,
				$can_view_content_course,
				$can_view_content_item
			);
			?>

		</div>
	</div>

	<?php do_action( 'learn-press/course-item-content-footer' ); ?>

</div>
