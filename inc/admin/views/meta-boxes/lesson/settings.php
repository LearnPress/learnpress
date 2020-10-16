<?php
class LP_Meta_Box_Lesson {

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--lesson">
			<div class="lp-meta-box__inner">
				<?php
				// lp_meta_box_duration_field(
				// array(
				// 'id'                => '_lp_duration',
				// 'label'             => esc_html__( 'Duration', 'learnpress' ),
				// 'default_time'      => 'minute',
				// 'custom_attributes' => array(
				// 'min'  => '0',
				// 'step' => '1',
				// ),
				// )
				// );

				lp_meta_box_checkbox_field(
					array(
						'id'          => '_lp_preview',
						'label'       => esc_html__( 'Preview', 'learnpress' ),
						'description' => esc_html__( 'Allows any users to view the lesson content.', 'learnpress' ),
						'default'     => 'no',
					)
				);
				?>
			</div>
		</div>

		<?php
	}

	public static function save( $post_id ) {
		$preview = ! empty( $_POST['_lp_preview'] ) ? 'yes' : 'no';

		update_post_meta( $post_id, '_lp_preview', $preview );
	}
}
