<?php

/**
 * LP_Meta_Box_WP_Editor_Field
 *
 * @author Nhamdv
 * @version 1.0.0
 * @since 4.1.3
 */
class LP_Meta_Box_WP_Editor_Field extends LP_Meta_Box_Field {

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
		wp_enqueue_editor();

		if ( empty( $this->id ) ) {
			return;
		}

		$extra         = $this->extra;
		$wrapper_class = ! empty( $extra['wrapper_class'] ) ? esc_attr( $extra['wrapper_class'] ) : '';

		$meta     = $this->meta_value( $thepostid );
		$value    = ! $meta && ! empty( $this->default ) ? $this->default : $meta;
		$value    = $extra['value'] ?? $value;
		$desc_tip = $extra['desc_tip'] ?? '';

		echo '<div class="lp-meta-box__wp-editor form-field ' . esc_attr( $this->id . '_field ' . $wrapper_class ) . '">
		<label for="' . esc_attr( $this->id ) . '">' . wp_kses_post( $this->label ) . '</label>';

		echo wp_editor(
			$value,
			$this->id,
			array(
				'textarea_rows' => 10,
				'editor_class'  => 'lp-meta-box__wp-editor__textarea',
			)
		);

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
		$meta_value = isset( $_POST[ $this->id ] ) ? wpautop( wp_unslash( $_POST[ $this->id ] ) ) : $this->default;

		update_post_meta( $post_id, $this->id, $meta_value );
	}
}
