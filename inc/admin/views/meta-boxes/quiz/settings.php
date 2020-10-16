<?php
class LP_Meta_Box_Quiz {

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--quiz">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'lp_before_quiz_meta_box_settings' );

				lp_meta_box_duration_field(
					array(
						'id'                => '_lp_duration',
						'label'             => esc_html__( 'Duration', 'learnpress' ),
						'description'       => esc_html__( 'Set 0 for unlimited time on the quiz.', 'learnpress' ),
						'default_time'      => 'minute',
						'default'           => '10',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				lp_meta_box_text_input_field(
					array(
						'id'                => '_lp_passing_grade',
						'label'             => esc_html__( 'Passing Grade(%)', 'learnpress' ),
						'description'       => esc_html__( 'The condition that must be achieved in order to be passed the quiz.', 'learnpress' ),
						'type'              => 'number',
						'default'           => '80',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
							'max'  => '100',
						),
						'style'             => 'width: 60px;',
					)
				);

				lp_meta_box_checkbox_field(
					array(
						'id'          => '_lp_instant_check',
						'label'       => esc_html__( 'Instant Check', 'learnpress' ),
						'description' => esc_html__( 'Allow students to immediately check their answers while doing the quiz.', 'learnpress' ),
						'default'     => 'no',
					)
				);

				lp_meta_box_checkbox_field(
					array(
						'id'          => '_lp_negative_marking',
						'label'       => esc_html__( 'Negative Marking', 'learnpress' ),
						'description' => esc_html__( 'For each question which students answer wrongly, the total point is deducted exactly the question mark.', 'learnpress' ),
						'default'     => 'no',
					)
				);

				lp_meta_box_checkbox_field(
					array(
						'id'          => '_lp_retry',
						'label'       => esc_html__( 'Retry', 'learnpress' ),
						'description' => esc_html__( 'Allow students to try the quiz one more time.', 'learnpress' ),
						'default'     => 'no',
					)
				);

				lp_meta_box_text_input_field(
					array(
						'id'                => '_lp_pagination',
						'label'             => esc_html__( 'Pagination', 'learnpress' ),
						'description'       => esc_html__( 'Set a number of questions showed in each page.', 'learnpress' ),
						'type'              => 'number',
						'default'           => '1',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
							'max'  => '100',
						),
						'style'             => 'width: 60px;',
					)
				);

				lp_meta_box_checkbox_field(
					array(
						'id'          => '_lp_review',
						'label'       => esc_html__( 'Review', 'learnpress' ),
						'description' => esc_html__( 'Allow students to review the quiz after submitted.', 'learnpress' ),
						'default'     => 'yes',
					)
				);

				do_action( 'lp_after_quiz_meta_box_settings' );
				?>
			</div>
		</div>

		<?php
	}

	public static function save( $post_id ) {
		$duration         = ! empty( $_POST['_lp_duration'] ) ? implode( ' ', wp_unslash( $_POST['_lp_duration'] ) ) : '10 minute';
		$passing_grade    = ! empty( $_POST['_lp_passing_grade'] ) ? absint( wp_unslash( $_POST['_lp_passing_grade'] ) ) : '80';
		$instant_check    = ! empty( $_POST['_lp_instant_check'] ) ? 'yes' : 'no';
		$negative_marking = ! empty( $_POST['_lp_negative_marking'] ) ? 'yes' : 'no';
		$retry            = ! empty( $_POST['_lp_retry'] ) ? 'yes' : 'no';
		$pagination       = ! empty( $_POST['_lp_pagination'] ) ? absint( wp_unslash( $_POST['_lp_pagination'] ) ) : '1';
		$review           = ! empty( $_POST['_lp_review'] ) ? 'yes' : 'no';

		update_post_meta( $post_id, '_lp_duration', $duration );
		update_post_meta( $post_id, '_lp_passing_grade', $passing_grade );
		update_post_meta( $post_id, '_lp_instant_check', $instant_check );
		update_post_meta( $post_id, '_lp_negative_marking', $negative_marking );
		update_post_meta( $post_id, '_lp_retry', $retry );
		update_post_meta( $post_id, '_lp_pagination', $pagination );
		update_post_meta( $post_id, '_lp_review', $review );
	}
}
