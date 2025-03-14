<?php
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

$class = 'lp-single-course';

?>
<div class="<?php echo esc_attr( $class ); ?>">
	<?php
	if ( ! empty( $inner_content ) ) {
		echo $inner_content;
	}
	echo SingleCourseTemplate::instance()->html_content_item();
	?>
</div>