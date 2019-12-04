<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;
?>
<div class="course-sidebar-preview margin-bottom">
    <div class="media-preview">
		<?php LP()->template( 'course' )->course_media_preview(); ?>
    </div>

	<?php

	// Price box
	if ( ! in_array( learn_press_user_course_status(), array( 'finished', 'enrolled' ) ) ) {
		LP()->template( 'course' )->course_pricing();
	}

	// Buttons
	LP()->template( 'course' )->course_buttons();

	LP()->template( 'course' )->user_time();

	LP()->template( 'course' )->user_progress();

	// Target audiences
	//LP()->template('course')->course_extra_target_audiences();
	?>
</div>
