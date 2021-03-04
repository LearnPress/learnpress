<?php
class LP_Meta_Box_Quiz {

	public static function metabox() {
		return apply_filters(
			'lp/metabox/quiz/lists',
			array(
				'_lp_duration'         => array(
					'label'             => esc_html__( 'Duration', 'learnpress' ),
					'type'              => 'duration',
					'default_time'      => 'minute',
					'default'           => '0',
					'custom_attributes' => array(
						'min'  => '0',
						'step' => '1',
					),
				),
				'_lp_passing_grade'    => array(
					'label'             => esc_html__( 'Passing Grade(%)', 'learnpress' ),
					'description'       => esc_html__( 'The condition that must be achieved in order to be passed the quiz.', 'learnpress' ),
					'type'              => 'text',
					'type_input'        => 'number',
					'default'           => '80',
					'custom_attributes' => array(
						'min'  => '0',
						'step' => '1',
						'max'  => '100',
					),
					'style'             => 'width: 60px;',
				),
				'_lp_instant_check'    => array(
					'label'       => esc_html__( 'Instant Check', 'learnpress' ),
					'description' => esc_html__( 'Allow students to immediately check their answers while doing the quiz.', 'learnpress' ),
					'default'     => 'no',
					'type'        => 'checkbox',
				),
				'_lp_negative_marking' => array(
					'label'       => esc_html__( 'Negative Marking', 'learnpress' ),
					'description' => esc_html__( 'For each question which students answer wrongly, the total point is deducted exactly the question mark.', 'learnpress' ),
					'default'     => 'no',
					'type'        => 'checkbox',
				),
				'_lp_retake_count'     => array(
					'label'             => esc_html__( 'Retake', 'learnpress' ),
					'description'       => esc_html__( 'Number times the user can learn again this quiz. Set to 0 to disable.', 'learnpress' ),
					'type'              => 'text',
					'type_input'        => 'number',
					'custom_attributes' => array(
						'min'  => '0',
						'step' => '1',
						'max'  => '100',
					),
					'style'             => 'width: 60px;',
				),
				'_lp_pagination'       => array(
					'label'             => esc_html__( 'Pagination', 'learnpress' ),
					'description'       => esc_html__( 'Set a number of questions showed in each page.', 'learnpress' ),
					'type'              => 'text',
					'type_input'        => 'number',
					'default'           => '1',
					'custom_attributes' => array(
						'min'  => '0',
						'step' => '1',
						'max'  => '100',
					),
					'style'             => 'width: 60px;',
				),
				'_lp_review'           => array(
					'label'       => esc_html__( 'Review', 'learnpress' ),
					'description' => esc_html__( 'Allow students to review the quiz after submitted.', 'learnpress' ),
					'default'     => 'yes',
					'type'        => 'checkbox',
				),
			)
		);
	}

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--quiz">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/quiz-settings/before' );

				lp_meta_box_output( self::metabox() );

				do_action( 'learnpress/quiz-settings/after' );
				?>
			</div>
		</div>

		<?php
	}

	public static function save( $post_id ) {
		$duration         = isset( $_POST['_lp_duration'][0] ) && $_POST['_lp_duration'][0] !== '' ? implode( ' ', wp_unslash( $_POST['_lp_duration'] ) ) : '0 minute';
		$passing_grade    = isset( $_POST['_lp_passing_grade'] ) ? absint( wp_unslash( $_POST['_lp_passing_grade'] ) ) : '80';
		$instant_check    = isset( $_POST['_lp_instant_check'] ) ? 'yes' : 'no';
		$negative_marking = isset( $_POST['_lp_negative_marking'] ) ? 'yes' : 'no';
		$retake           = isset( $_POST['_lp_retake_count'] ) ? absint( wp_unslash( $_POST['_lp_retake_count'] ) ) : 0;
		$review           = isset( $_POST['_lp_review'] ) ? 'yes' : 'no';
		$pagination       = ! empty( $_POST['_lp_pagination'] ) ? absint( wp_unslash( $_POST['_lp_pagination'] ) ) : '1';

		update_post_meta( $post_id, '_lp_duration', $duration );
		update_post_meta( $post_id, '_lp_passing_grade', $passing_grade );
		update_post_meta( $post_id, '_lp_instant_check', $instant_check );
		update_post_meta( $post_id, '_lp_negative_marking', $negative_marking );
		update_post_meta( $post_id, '_lp_retake_count', $retake );
		update_post_meta( $post_id, '_lp_pagination', $pagination );
		update_post_meta( $post_id, '_lp_review', $review );
	}
}
