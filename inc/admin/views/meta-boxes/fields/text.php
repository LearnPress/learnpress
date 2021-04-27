<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Text_Field extends LP_Meta_Box_Field {

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
		if ( empty( $this->id ) ) {
			return;
		}

		$extra         = $this->extra;
		$placeholder   = $extra['placeholder'] ?? '';
		$class         = ! empty( $extra['class'] ) ? 'class="' . esc_attr( $extra['class'] ) . '"' : '';
		$style         = ! empty( $extra['style'] ) ? 'style="' . esc_attr( $extra['style'] ) . '"' : '';
		$wrapper_class = ! empty( $extra['wrapper_class'] ) ? esc_attr( $extra['wrapper_class'] ) : '';

		$meta       = $this->meta_value( $thepostid );
		$value      = ! $meta && ! empty( $this->default ) ? $this->default : $meta;
		$value      = isset( $extra['value'] ) ? $extra['value'] : $value;
		$type_input = $extra['type_input'] ?? 'text';
		$desc_tip   = $extra['desc_tip'] ?? '';

		// Custom attribute handling
		$custom_attributes = array();
		if ( ! empty( $extra['custom_attributes'] ) && is_array( $extra['custom_attributes'] ) ) {
			foreach ( $extra['custom_attributes'] as $attribute => $custom_attribute ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
			}
		}

		echo '<div class="form-field ' . $this->id . '_field ' . $wrapper_class . '">
		<label for="' . esc_attr( $this->id ) . '">' . wp_kses_post( $this->label ) . '</label>';

		echo '<input type="' . esc_attr( $type_input ) . '" ' . $class . ' ' . $style . ' name="' . $this->id . '" id="' . $this->id . '" value="' . $value . '" placeholder="' . esc_attr( $placeholder ) . '" ' . implode(
			' ',
			$custom_attributes
		) . ' /> ';

		if ( ! empty( $this->description ) ) {
			echo '<p class="description">';
			echo '<span>' . wp_kses_post( $this->description ) . '</span>';

			if ( ! empty( $desc_tip ) ) {
				learn_press_quick_tip( $desc_tip );
			}
			echo '</p>';
		}
		echo '</div>';
	}

	public function save( $post_id ) {
		$type_input = $this->extra['type_input'] ?? 'text';
		$meta_value = isset( $_POST[ $this->id ] ) ? wp_unslash( $_POST[ $this->id ] ) : $this->default;

		if ( $meta_value !== '' && $type_input === 'number' ) {
			$step = isset( $this->extra['custom_attributes']['step'] ) ? $this->extra['custom_attributes']['step'] : '';

			if ( floatval( $step ) !== 1 ) {
				$meta_value = floatval( $meta_value );
			} else {
				$meta_value = absint( $meta_value );
			}
		}

		update_post_meta( $post_id, $this->id, $meta_value );
	}
}
