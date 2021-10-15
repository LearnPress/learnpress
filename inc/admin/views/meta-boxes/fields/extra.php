<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Extra_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $label
	 * @param string $description
	 * @param mixed  $default
	 * @param array  $extra
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		$fields = $this->meta_value( $thepostid );
		?>

		<div class="form-field lp_course_extra_meta_box">
			<label for="<?php echo esc_attr( $this->id ); ?>"><?php echo $this->label; ?></label>
			<div class="lp_course_extra_meta_box__content">
				<div class="lp_course_extra_meta_box__fields">
					<?php if ( ! empty( $fields[0][0] ) ) : ?>
						<?php foreach ( $fields as $field ) : ?>
							<div class="lp_course_extra_meta_box__field">
								<span class="sort"></span>
								<input name="<?php echo esc_attr( $this->id ); ?>[]" value="<?php echo esc_attr( $field ); ?>" type="text" class="lp_course_extra_meta_box__input">
								<a href="#" class="delete"></a>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<a href="#" class="button button-primary lp_course_extra_meta_box__add" data-add="<?php echo esc_attr( '<div class="lp_course_extra_meta_box__field"><span class="sort"></span></a><input name="' . $this->id . '[]" value="" type="text" class="lp_course_extra_meta_box__input"><a href="#" class="delete"></a></div>' ); ?>">
					<?php esc_html_e( '+ Add more', 'learnpress' ); ?>
				</a>
			</div>
		</div>

		<?php
	}

	public function save( $post_id ) {
		$fields = isset( $_POST[ $this->id ] ) ? LP_Helper::sanitize_params_submitted( $_POST[ $this->id ], 'html' ) : array();

		update_post_meta( $post_id, $this->id, $fields );
	}
}
