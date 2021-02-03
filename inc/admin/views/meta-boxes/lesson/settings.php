<?php
class LP_Meta_Box_Lesson {

	public static function metabox() {
		return apply_filters(
			'lp/metabox/lesson/lists',
			array(
				'_lp_duration' => array(
					'label'             => esc_html__( 'Duration', 'learnpress' ),
					'type'              => 'duration',
					'default_time'      => 'minute',
					'default'           => '0',
					'custom_attributes' => array(
						'min'  => '0',
						'step' => '1',
					),
				),
				'_lp_preview'  => array(
					'label'       => esc_html__( 'Preview', 'learnpress' ),
					'description' => esc_html__( 'Allows any users to view the lesson content.', 'learnpress' ),
					'type'        => 'checkbox',
					'default'     => 'no',
				),
			)
		);
	}

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--lesson">
			<div class="lp-meta-box__inner">
				<?php
				do_action( 'learnpress/lesson-settings/before' );

				lp_meta_box_output( self::metabox() );

				do_action( 'learnpress/lesson-settings/after' );
				?>
			</div>
		</div>

		<?php
	}

	public static function save( $post_id ) {
		$preview  = ! empty( $_POST['_lp_preview'] ) ? 'yes' : 'no';
		$duration = isset( $_POST['_lp_duration'][0] ) && $_POST['_lp_duration'][0] !== '' ? implode( ' ', wp_unslash( $_POST['_lp_duration'] ) ) : '0 minute';

		update_post_meta( $post_id, '_lp_duration', $duration );
		update_post_meta( $post_id, '_lp_preview', $preview );
	}
}
