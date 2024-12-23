<?php

use LearnPress\TemplateHooks\TemplateAJAX;

$content  = $inner_content;
$callback = [
	'class'  => 'LearnPress\\TemplateHooks\\Course\\ListCoursesTemplate',
	'method' => 'render_courses',
];

$custom = $attributes['custom'] ?? false;

if ( ! $custom ) {
	$args                          = lp_archive_skeleton_get_args();
	$args['courses_load_ajax']     = LP_Settings_Courses::is_ajax_load_courses() ? 1 : 0;
	$args['courses_first_no_ajax'] = LP_Settings_Courses::is_no_load_ajax_first_courses() ? 1 : 0;
	$content                       = TemplateAJAX::load_content_via_ajax( $args, $callback );
}
?>
<div class="lp-list-courses-default">
	<?php
	if ( ! empty( $content ) ) :
		echo $content;
	endif;
	?>
</div>