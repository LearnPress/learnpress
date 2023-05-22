<?php
if ( ! isset( $data ) ) {
	return;
}
?>
<div class="course-total">
	<?php
	if ( empty( $data['course_total'] ) ) {
		esc_html_e( 'No courses', 'learnpress' );
	} else {
		printf( esc_html( _n( '%s course', '%s courses', $data['course_total'], 'learnpress' ) ), $data['course_total'] );
	}
	?>
</div>
