<?php

/**
 * LP_Meta_Box_Editor_Field
 *
 * @author vuxminhthanh
 * @version 1.0.0
 * @since 4.2.7
 */
class LP_Meta_Box_Editor_Field extends LP_Meta_Box_Field {

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
		$custom_settings = array();
		if ( ! empty( $field['custom_settings'] ) && is_array( $field['custom_settings'] ) ) {
			foreach ( $field['custom_settings'] as $setting => $custom_setting ) {
				$custom_settings[] = esc_attr( $setting ) . '="' . esc_attr( $custom_setting ) . '"';
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

		wp_editor(
			$field['value'],
			esc_attr( $this->id ),
			array(
				'textarea_name' => $field['name'],
				'media_buttons' => false,
				'textarea_rows' => 10,
				'tinymce'       => true,
				'quicktags'     => false,
				$custom_settings,
			)
		);

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
