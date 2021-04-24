<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Extra_Faq_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param mixed  $default
	 * @param array  $extra
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		$faqs = $this->meta_value( $thepostid );
		?>

		<div class="form-field lp_course_faq_meta_box">
			<label for="_lp_key_features"><?php echo $this->label; ?></label>
			<div class="lp_course_faq_meta_box__content">
				<div class="lp_course_faq_meta_box__fields">
					<?php if ( ! empty( $faqs[0][0] ) ) : ?>
						<?php foreach ( $faqs as $key => $faq ) : ?>
							<div class="lp_course_faq_meta_box__field">
								<label>
									<span><?php esc_attr_e( 'Title', 'learnpress' ); ?></span>
									<input type="text" name="_lp_faqs_question[]" value="<?php echo $faq[0]; ?>">
								</label>
								<label>
									<span><?php esc_attr_e( 'Content', 'learnpress' ); ?></span>
									<textarea name="_lp_faqs_answer[]"><?php echo $faq[1]; ?></textarea>
								</label>
								<a href="#" class="delete"></a>
								<span class="sort"></span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<a href="#" class="button button-primary lp_course_faq_meta_box__add"
					data-add="
					<?php
					echo esc_attr(
						'<div class="lp_course_faq_meta_box__field">
							<label>
								<span>' . esc_attr__( 'Title', 'learnpress' ) . '</span>
								<input type="text" name="_lp_faqs_question[]" value="">
							</label>
							<label>
								<span>' . esc_attr__( 'Content', 'learnpress' ) . '</span>
								<textarea name="_lp_faqs_answer[]"></textarea>
							</label>
							<a href="#" class="delete"></a>
							<span class="sort"></span>
						</div>'
					);
					?>
					"><?php esc_html_e( '+ Add more', 'learnpress' ); ?>
				</a>
			</div>
		</div>

		<?php
	}

	public function save( $post_id ) {
		$faqs_question = isset( $_POST['_lp_faqs_question'] ) ? wp_unslash( $_POST['_lp_faqs_question'] ) : array();
		$faqs_answer   = isset( $_POST['_lp_faqs_answer'] ) ? wp_unslash( $_POST['_lp_faqs_answer'] ) : array();

		$faqs = array();
		if ( ! empty( $faqs_question ) ) {
			$faqs_question_size = count( $faqs_question );

			for ( $i = 0; $i < $faqs_question_size; $i ++ ) {
				if ( ! empty( $faqs_question[ $i ] ) ) {
					$faqs[] = array( $faqs_question[ $i ], $faqs_answer[ $i ] );
				}
			}
		}

		update_post_meta( $post_id, $this->id, $faqs );
	}
}
