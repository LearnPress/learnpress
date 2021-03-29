<?php
class LP_Meta_Box_Question {

	public static function metabox() {
		return apply_filters(
			'lp/metabox/lesson/lists',
			array(
				'_lp_mark'        => array(
					'label'             => esc_html__( 'Points for choosing the right answer.', 'learnpress' ),
					'description'       => esc_html__( 'Set question points.', 'learnpress' ),
					'desc_tip'          => true,
					'type'              => 'number',
					'default'           => '1',
					'custom_attributes' => array(
						'min'  => '1',
						'step' => '1',
					),
					'style'             => 'width: 60px;',
				),
				'_lp_hint'        => array(
					'label'       => esc_html__( 'Hint', 'learnpress' ),
					'description' => esc_html__( 'Instruction for user to select the right answer. The text will be shown when users click the \'Hint\' button.', 'learnpress' ),
					'default'     => '',
					'type'        => 'textarea',
				),
				'_lp_explanation' => array(
					'label'       => esc_html__( 'Explanation', 'learnpress' ),
					'description' => esc_html__( 'Explanation will be displayed when students click button "Check Answer".', 'learnpress' ),
					'default'     => '',
					'type'        => 'textarea',
				),
			)
		);
	}

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--question">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/question-settings/before' );

				lp_meta_box_output( self::metabox() );

				do_action( 'learnpress/question-settings/after' );
				?>
			</div>
		</div>

		<?php
	}

	public static function save( $post_id ) {
		$mark        = ! empty( $_POST['_lp_mark'] ) ? absint( wp_unslash( $_POST['_lp_mark'] ) ) : '0';
		$hint        = ! empty( $_POST['_lp_hint'] ) ? wp_kses_post( wp_unslash( $_POST['_lp_hint'] ) ) : '';
		$explanation = ! empty( $_POST['_lp_explanation'] ) ? wp_kses_post( wp_unslash( $_POST['_lp_explanation'] ) ) : '';

		update_post_meta( $post_id, '_lp_mark', $mark );
		update_post_meta( $post_id, '_lp_hint', $hint );
		update_post_meta( $post_id, '_lp_explanation', $explanation );
	}
}
