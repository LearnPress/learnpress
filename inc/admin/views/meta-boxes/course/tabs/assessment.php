<?php
$course_result_desc = '';
$course_results     = get_post_meta( $thepostid, '_lp_course_result', true );

$course_result_desc .= __( 'The method to assess the result of a student for a course.', 'learnpress' );

if ( $course_results == 'evaluate_final_quiz' && ! get_post_meta( $thepostid, '_lp_final_quiz', true ) ) {
	$course_result_desc .= __( '<br /><strong>Note! </strong>No final quiz in course, please add a final quiz', 'learnpress' );
}

$final_quizz_passing = '';

$course = learn_press_get_course( $thepostid );

if ( $course ) {
	$passing_grade = $url = '';

	$final_quiz = $course->get_final_quiz();

	if ( $final_quiz ) {
		$passing_grade = get_post_meta( $final_quiz, '_lp_passing_grade', true );

		$url = get_edit_post_link( $final_quiz ) . '#_lp_passing_grade';

		$final_quizz_passing = '
			<div class="lp-metabox-evaluate-final_quiz">
				<div class="lp-metabox-evaluate-final_quiz__message">'
				. sprintf( esc_html__( 'Passing Grade: %s', 'learpress' ), $passing_grade . '%' ) .
				' - '
				. sprintf( esc_html__( 'Edit: %s', 'learnpress' ), '<a href="' . esc_url( $url ) . '">' . get_the_title( $final_quiz ) . '</a>' ) .
				'</div>
			</div>
		';
	}
}
?>

<div id="assessment_course_data" class="lp-meta-box-course-panels">
	<?php
	do_action( 'learnpress/course-settings/before-assessment' );

	lp_meta_box_radio_field(
		array(
			'id'          => '_lp_course_result',
			'label'       => esc_html__( 'Evaluation', 'learnpress' ),
			'description' => $course_result_desc,
			'options'     => learn_press_course_evaluation_methods( '', $final_quizz_passing ),
			'default'     => 'evaluate_lesson',
		)
	);

	lp_meta_box_text_input_field(
		array(
			'id'                => '_lp_passing_condition',
			'label'             => esc_html__( 'Passing Grade(%)', 'learnpress' ),
			'description'       => esc_html__( 'The condition that must be achieved to finish the course.', 'learnpress' ),
			'type'              => 'number',
			'default'           => '80',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => '1',
			),
			'style'             => 'width: 60px;',
		)
	);

	do_action( 'learnpress/course-settings/after-assessment' );
	?>
</div>
