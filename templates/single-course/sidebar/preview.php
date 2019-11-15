<?php
?>
<div class="course-sidebar-preview">
    <div class="media-preview">
		<?php LP()->template('course')->course_media_preview(); ?>
    </div>

	<?php

	// Price box
	if ( ! in_array( learn_press_user_course_status(), array( 'finished', 'enrolled' ) ) ) {
		LP()->template('course')->course_pricing();
	}

	// Buttons
	LP()->template('course')->course_buttons();

	// Target audiences
	LP()->template('course')->course_extra_target_audiences();
	?>
</div>
