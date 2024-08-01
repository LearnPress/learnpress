<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Date_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param mixed $default
	 * @param array $extra
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		$date = $this->meta_value( $thepostid );
		if ( ! empty( $date ) ) {
			$dateObj = new LP_Datetime( $date );
			$date    = $dateObj->format( 'mysql' );
		}

		$wrapper_attr     = $this->extra['wrapper_attr'] ?? [];
		$dependency_check = $this->extra['dependency'] ?? [];
		if ( ! empty( $dependency_check ) ) {
			if ( $dependency_check['is_disable'] ) {
				$this->extra['wrapper_class'] .= ' lp-option-disabled';
			}

			$wrapper_attr[] = 'data-dependency=' . $dependency_check['name'];
		}
		?>

		<div class="lp_sale_dates_fields">
			<p class="form-field <?php echo esc_attr( $this->extra['wrapper_class'] ); ?>"
				<?php echo esc_attr( implode( ' ', $wrapper_attr ) ) ?>>
				<label for="_lp_sale_start"><?php echo wp_kses_post( $this->label ); ?></label>
				<input type="datetime-local" class="short" name="<?php echo esc_attr( $this->id ); ?>"
					   step=1
					   id="<?php echo esc_attr( $this->id ); ?>"
					   value="<?php echo esc_attr( $date ); ?>"
					   placeholder="<?php echo esc_attr( $this->extra['placeholder'] ); ?>"
					   style="width:320px;"/>

				<?php if ( ! empty( $this->extra['cancel'] ) ) : ?>
					<a href="#" class="description lp_cancel_sale_schedule">
						<?php esc_html_e( 'Cancel', 'learnpress' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>

		<?php
	}

	public function save( $post_id ) {
		$value = LP_Request::get_param( $this->id, $this->default ?? '' );
		update_post_meta( $post_id, $this->id, $value );

		return $value;
	}
}
