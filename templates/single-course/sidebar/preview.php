<?php
?>
<div class="course-sidebar-preview">
    <div class="media-preview">
		<?php LP()->template()->course_media_preview(); ?>
    </div>

	<?php

	// Price box
	if ( ! in_array( learn_press_user_course_status(), array( 'finished', 'enrolled' ) ) ) {
		LP()->template()->course_pricing();
	}

	// Buttons
	LP()->template()->course_buttons();

	// Target audiences
	LP()->template()->course_extra_target_audiences();
	?>
</div>
