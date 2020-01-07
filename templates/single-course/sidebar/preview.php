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
		<?php

		LP()->template( 'course' )->course_media_preview();

		learn_press_get_template( 'loop/course/badge-featured' );

		?>
    </div>

	<?php

	// Price box
	if ( ! in_array( learn_press_user_course_status(), learn_press_course_enrolled_slugs() /* array( 'finished', 'enrolled', 'passed', 'failed' )*/ ) ) {
		LP()->template( 'course' )->course_pricing();
	}

	// Graduation
	LP()->template( 'course' )->course_graduation();

	// Buttons
	LP()->template( 'course' )->course_buttons();

	LP()->template( 'course' )->user_time();

	LP()->template( 'course' )->user_progress();

	// Target audiences
	//LP()->template('course')->course_extra_target_audiences();
	?>
</div>
