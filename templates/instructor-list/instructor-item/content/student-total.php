<?php
if ( ! isset( $data ) ) {
	return;
}
?>
<div class="student-total">
	<?php
	if ( empty( $data['student_total'] ) ) {
		esc_html_e( 'No students', 'learnpress' );
	} else {
		printf( esc_html( _n( '%s student', '%s students', $data['student_total'], 'learnpress' ) ), $data['student_total'] );
	}
	?>
</div>
