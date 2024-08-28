<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Checkbox_Field extends LP_Meta_Box_Field {

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
		if ( empty( $this->id ) ) {
			return;
		}

		$field                = $this->extra;
		$field['id']          = $this->id;
		$field['default']     = $this->default;
		$field['description'] = $this->description;
		$field['label']       = $this->label;

		$class         = ! empty( $field['class'] ) ? 'class="' . esc_attr( $field['class'] ) . '"' : '';
		$style         = ! empty( $field['style'] ) ? 'style="' . esc_attr( $field['style'] ) . '"' : '';
		$wrapper_class = ! empty( $field['wrapper_class'] ) ? esc_attr( $field['wrapper_class'] ) : '';
		$wrapper_attr  = $this->extra['wrapper_attr'] ?? [];
		$name          = ! empty( $field['name'] ) ? esc_attr( $field['name'] ) : esc_attr( $field['id'] );
		$name          = 'name="' . $name . '"';

		$value_db = $this->meta_value( $thepostid );

		if ( ! $value_db && $field['default'] !== '' ) {
			$value_db = $field['default'];
		}

		$checked = '';
		if ( 'yes' === $value_db ) {
			$checked = 'checked="checked"';
		}

		if ( isset( $field['value'] ) && $field['value'] === 'yes' ) {
			$checked = 'checked="checked"';
		}

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

		echo '<input type="checkbox" ' . $class . ' ' . $style . ' ' . $name . ' ' . $checked . ' id="' . esc_attr( $field['id'] ) . '" ' . implode( ' ', $custom_attributes ) . '/> ';

		if ( ! empty( $field['description'] ) ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

			if ( ! empty( $field['desc_tip'] ) ) {
				learn_press_quick_tip( $field['desc_tip'] );
			}
		}

		echo '</div>';
	}

	public function save( $post_id ) {
		$value = isset( $_POST[ $this->id ] ) ? 'yes' : 'no';
		update_post_meta( $post_id, $this->id, $value );

		return $value;
	}
}
