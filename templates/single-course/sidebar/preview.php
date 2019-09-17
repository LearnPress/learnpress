<?php
?>
<div class="course-sidebar-preview">
    <div class="media-preview">
		<?php LP()->template()->course_media_preview(); ?>
    </div>

	<?php
	// Price box
	LP()->template()->course_pricing();

	// Buttons
	LP()->template()->course_buttons();

	// Target audiences
	LP()->template()->course_extra_target_audiences();
	?>
</div>
