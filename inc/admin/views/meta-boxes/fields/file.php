<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author Nhamdv
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_File_Field extends LP_Meta_Box_Field {

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

		$field                = $this->extra;
		$field['id']          = $this->id;
		$field['default']     = $this->default;
		$field['description'] = $this->description;
		$field['label']       = $this->label;

		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['default']       = ( ! $this->meta_value( $thepostid ) && isset( $field['default'] ) ) ? $field['default'] : $this->meta_value( $thepostid );
		$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['mime_type']     = isset( $field['mime_type'] ) ? implode( ',', $field['mime_type'] ) : '';
		$field['multil']        = ( isset( $field['multil'] ) && $field['multil'] ) ? true : false;
		$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

		// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
			}
		}

		echo '<div class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		echo '<div id="' . esc_attr( $field['id'] ) . '" class="lp-meta-box__file ' . esc_attr( $field['class'] ) . '" data-mime="' . $field['mime_type'] . '" data-multil="' . $field['multil'] . '" style="' . esc_attr( $field['style'] ) . '" ' . implode(
			' ',
			$custom_attributes
		) . '>';
		echo '<ul class="lp-meta-box__file_list">';

		if ( ! empty( $field['value'] ) ) {
			foreach ( (array) $field['value'] as $attachment_id ) {
				$url = wp_get_attachment_url( $attachment_id );

				if ( $url ) {
					$check_file = wp_check_filetype( $url );

					echo '<li class="lp-meta-box__file_list-item image" data-attachment_id="' . $attachment_id . '">';

					if ( in_array( $check_file['ext'], array( 'jpg', 'png', 'gif', 'bmp', 'tif' ), true ) ) {
						echo wp_get_attachment_image( $attachment_id, 'thumbnail' );
					} else {
						echo '<img class="is_file" src="' . wp_mime_type_icon( $check_file['type'] ) . '" />';
						echo '<span>' . wp_basename( get_attached_file( $attachment_id ) ) . '</span>';
					}
					echo '<ul class="actions"><li><a href="#" class="delete"></a></li></ul>';
					echo '</li>';
				}
			}
		}

		echo '</ul>';
		echo '<input class="lp-meta-box__file_input" type="hidden" name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) ? implode( ',', $field['value'] ) : $field['value'] ) . '" />';
		echo '<p>';
		echo '<a href="#" class="button btn-upload">' . esc_html__( '+ Add media', 'learnpress' ) . '</a>';
		if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
			learn_press_quick_tip( $field['description'] );
		}
		echo '</p>';

		if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}
		echo '</div>';
		echo '</div>';

	}

	public function save( $post_id ) {
		$value = ! empty( $_POST[ $this->id ] ) ? wp_unslash( array_filter( explode( ',', $_POST[ $this->id ] ) ) ) : '';

		update_post_meta( $post_id, $this->id, $value );
	}
}
