<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Textarea_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $label
	 * @param string $description
	 * @param mixed $default
	 * @param array $extra
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		if ( empty( $this->id ) ) {
			return;
		}

		$field                = $this->extra;
		$field['id']          = $this->id;
		$field['default']     = $this->default;
		$field['description'] = $this->description;
		$field['label']       = $this->label;

		$field['placeholder'] = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field['class']       = isset( $field['class'] ) ? $field['class'] : 'short';
		$field['style']       = isset( $field['style'] ) ? $field['style'] : '';
		$field['default']     = ( ! $this->meta_value( $thepostid ) && isset( $field['default'] ) ) ? $field['default'] : $this->meta_value( $thepostid );
		$field['value']       = isset( $field['value'] ) ? $field['value'] : $field['default'];
		$field['desc_tip']    = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
		$field['name']        = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$wrapper_attr         = $field['wrapper_attr'] ?? [];
		$wrapper_class        = $this->extra['wrapper_class'] ?? '';

		// Custom attribute handling
		$custom_attributes = array();
		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
			}
		}

		$dependency_check = $this->extra['dependency'] ?? [];
		if ( ! empty( $dependency_check ) ) {
			if ( $dependency_check['is_disable'] ) {
				$wrapper_class .= ' lp-option-disabled';
			}

			$wrapper_attr[] = 'data-dependency=' . $dependency_check['name'];
		}

		printf(
			'<div class="form-field %s" %s><label for="%s">%s</label>',
			esc_attr( $this->id . '_field ' . $wrapper_class ),
			implode( ' ', $wrapper_attr ),
			esc_attr( $this->id ),
			wp_kses_post( $this->label )
		);

		echo '<textarea class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '"  name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" rows="5" ' . implode(
				' ',
				$custom_attributes
			) . '>' . esc_textarea( $field['value'] ) . '</textarea> ';

		if ( ! empty( $field['description'] ) ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

			if ( ! empty( $field['desc_tip'] ) ) {
				learn_press_quick_tip( $field['desc_tip'] );
			}
		}

		echo '</div>';
	}

	public function save( $post_id ) {
		$value = LP_Request::get_param( $this->id, $this->default ?? '', 'html' );
		update_post_meta( $post_id, $this->id, $value );

		return $value;
	}
}
