<?php

/**
 *
 * @author nhamdv
 * @version 1.0.0
 * @since 4.1.7
 */
class LP_Meta_Box_Autocomplete_Field extends LP_Meta_Box_Field {

	/**
	 * @param array $field
	 * @param array $extra = array( 'placeholder' => 'Select an item', 'action': rest_url( 'wp/v2/users' ), rest_url( 'wp/v2/post' ), rest_url( 'wp/v2/page' ), 'data': users, page, post, course, lesson )
	 *
	 * @return string
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		// Enqueue scripts here for future use everywhere called.
		wp_enqueue_script( 'lp-admin-learnpress' );

		if ( empty( $this->id ) ) {
			return;
		}

		$field                = $this->extra;
		$field['id']          = $this->id;
		$field['default']     = $this->default;
		$field['description'] = $this->description;
		$field['label']       = $this->label;

		$meta = $this->meta_value( $thepostid );

		$default = ( ! $meta && isset( $field['default'] ) ) ? (array) $field['default'] : $meta;

		$field = wp_parse_args(
			$field,
			array(
				'class'             => 'select',
				'style'             => '',
				'wrapper_class'     => '',
				'value'             => isset( $field['value'] ) ? $field['value'] : $default,
				'name'              => $field['id'],
				'desc_tip'          => false,
				'custom_attributes' => array(),
			)
		);

		$wrapper_class = ! empty( $field['wrapper_class'] ) ? esc_attr( $field['wrapper_class'] ) : '';

		$field_attributes             = (array) $field['custom_attributes'];
		$field_attributes['style']    = 'width: 300px;' . $field['style'];
		$field_attributes['id']       = $field['id'];
		$field_attributes['name']     = $field['name'] . '[]';
		$field_attributes['class']    = $field['class'];
		$field_attributes['multiple'] = true;

		$data_atts = array(
			'placeholder' => $field['placeholder'] ?? esc_html__( 'Select', 'learnpress' ),
			'action'      => $field['action'] ?? '',
			'data'        => $field['data'] ?? '', // users, pages, posts, lp_course, lp_lesson...
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'rest_url'    => rest_url(),
		);
		?>
		<p class="form-field lp_autocomplete_metabox_field <?php echo esc_attr( $field['id'] . '_field ' . $wrapper_class ); ?>" data-atts="<?php echo esc_attr( wp_json_encode( $data_atts ) ); ?>">
			<label for="<?php echo esc_attr( $field['id'] ); ?>">
				<?php echo wp_kses_post( $field['label'] ); ?>
			</label>

			<select <?php echo lp_implode_html_attributes( $field_attributes ); ?>>
				<?php
				if ( ! empty( $field['value'] ) ) {
					foreach ( $field['value'] as $value ) {
						if ( ! empty( $field['data'] ) ) {
							if ( $field['data'] === 'users' ) {
								$user = get_user_by( 'id', $value );

								if ( ! $user ) {
									continue;
								}

								echo '<option value="' . esc_attr( $value ) . '" selected>' . esc_html( $user->display_name ) . '</option>';
							} else {
								$post = get_post( $value );
								if ( ! $post ) {
									continue;
								}
								echo '<option value="' . esc_attr( $value ) . '" selected>' . esc_html( $post->post_title ) . '</option>';
							}
						} else {
							echo '<option value="' . esc_attr( $value ) . '" selected>' . esc_html( $value ) . '</option>';
						}
					}

					do_action( 'learn-press/admin/metabox/autocomplete/' . $field['id'] . '/option', $field['value'], $field, $thepostid );
				}
				?>
			</select>

			<?php
			if ( ! empty( $field['description'] ) ) {
				echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

				if ( ! empty( $field['desc_tip'] ) ) {
					learn_press_quick_tip( $field['desc_tip'] );
				}
			}
			?>
		</p>
		<?php
	}

	public function save( $post_id ) {
		$raw_value = isset( $_POST[ $this->id ] ) ? (array) wp_unslash( $_POST[ $this->id ] ) : array();
		$value     = array_map( 'absint', $raw_value );
		$value     = apply_filters( 'learn-press/admin/metabox/autocomplete/' . $this->id . '/save', $value, $raw_value, $post_id );

		update_post_meta( $post_id, $this->id, $value );

		do_action( 'lp/metabox/field/autocomplete/save/after', $post_id, $this->id, $value );
	}
}
