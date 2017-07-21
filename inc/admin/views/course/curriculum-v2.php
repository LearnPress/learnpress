<?php
/**
 * Template curriculum course.
 *
 * @since 3.0.0
 */
?>

<?php

global $post;

$course = learn_press_get_course( $post->ID );
if ( ! $course ) {
	return;
}
$course_sections = $course->get_curriculum();

?>

<script type="text/x-template" id="tmpl-lp-course-curriculum">
    <div id="lp-course-curriculum" class="lp-course-curriculum">
        <div class="heading">
            <h3><?php _e( 'Curriculum', 'learnpress' ); ?></h3>
            <p class="description"><?php _e( 'Outline your course and add content with sections, lessons and quizzes.', 'learnpress' ); ?></p>
        </div>
    </div>

    <div class="curriculum-sections">
		<?php
		var_dump( $course_sections );
		if ( $course_sections ) :
			foreach ( $course_sections as $k => $section ):
				$content_items = '';
			endforeach;
		endif;
		?>
    </div>
</script>
