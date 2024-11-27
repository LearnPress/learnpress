<?php

use LearnPress\TemplateHooks\TemplateAJAX;
$settings = [];
$settings = array_merge(
	$settings,
	lp_archive_skeleton_get_args()
);

$callback = [
	'class'  => 'LearnPress\\TemplateHooks\\Course\\ListCoursesTemplate',
	'method' => 'render_courses',
];

$settings['html_no_load_ajax_first'] = $inner_content;
$content                             = TemplateAJAX::load_content_via_ajax( $settings, $callback );
?>
<div class="lp-list-courses-default">
	<div class="lp-load-ajax-element">
	<?php
	if ( ! empty( $content ) ) :
		echo $content;
	endif;
	?>
	</div>
</div>