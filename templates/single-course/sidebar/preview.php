<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="course-sidebar-preview">
	<div class="media-preview">
		<?php
		LearnPress::instance()->template( 'course' )->course_media_preview();
		learn_press_get_template( 'loop/course/badge-featured' );
		?>
	</div>

	<?php
	// Price box.
	LearnPress::instance()->template( 'course' )->course_pricing();

	// Graduation.
	LearnPress::instance()->template( 'course' )->course_graduation();

	// Buttons.
	LearnPress::instance()->template( 'course' )->course_buttons();

	LearnPress::instance()->template( 'course' )->user_time();

	LearnPress::instance()->template( 'course' )->user_progress();
	?>
</div>
