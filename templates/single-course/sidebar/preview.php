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
		LP()->template( 'course' )->course_media_preview();
		learn_press_get_template( 'loop/course/badge-featured' );
		?>
	</div>

	<?php
	// Price box.
	LP()->template( 'course' )->course_pricing();

	// Graduation.
	LP()->template( 'course' )->course_graduation();

	// Buttons.
	LP()->template( 'course' )->course_buttons();

	LP()->template( 'course' )->user_time();

	LP()->template( 'course' )->user_progress();
	?>
</div>
