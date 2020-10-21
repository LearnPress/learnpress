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
	$passing_grade = '';

	$final_quiz = $course->get_final_quiz();

	if ( $final_quiz ) {
		$quiz = learn_press_get_quiz( $final_quiz );

		if ( $quiz ) {
			$passing_grade = $quiz->get_passing_grade();
		}
	}

	$final_quizz_passing = '
		<div id="passing-condition-quiz-result">
		<input type="number" name="_lp_course_result_final_quiz_passing_condition" value="' . absint( $passing_grade ) . '" /> %
		<p>' . __( 'This is conditional "passing grade" of Final quiz will apply for result of this course. When you change it here, the "passing grade" also change with new value for the Final quiz.', 'learnpress' ) . '</p>
		</div>
	';
}
?>

<div id="assessment_course_data" class="lp-meta-box-course-panels">
	<?php
	lp_meta_box_radio_field(
		array(
			'id'          => '_lp_course_result',
			'label'       => esc_html__( 'Evalution', 'learnpress' ),
			'description' => $course_result_desc,
			'options'     => learn_press_course_evaluation_methods( '', $final_quizz_passing ),
			'default'     => 'evaluate_lesson',
		)
	);

	lp_meta_box_text_input_field(
		array(
			'id'                => '_lp_passing_condition',
			'label'             => esc_html__( 'Passing Grade(%)', 'learnpress' ),
			'description'       => esc_html__( 'The condition that must be achieved in order to be completed the course.', 'learnpress' ),
			'type'              => 'number',
			'default'           => '80',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => '1',
			),
			'style'             => 'width: 60px;',
		)
	);
	?>
</div>
