<?php
/**
 * Use for Add-on old version.
 * support for Add-on in LP3
 *
 * @author Nhamdv <email@email.com>
 * @since 4.0.0
 */

if ( ! class_exists( 'RW_Meta_Box' ) && ! is_plugin_active( 'thim-core/thim-core.php' ) ) {
	class RW_Meta_Box {

		public $meta_box;

		public function __construct( $meta_box ) {
			$meta_box = self::normalize( $meta_box );

			$this->meta_box = $meta_box;

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ), 100, 2 );
		}

		public function add_meta_boxes() {
			$meta_box = $this->meta_box;

			if ( isset( $meta_box['pages'] ) ) {
				$meta_box['post_types'] = $meta_box['pages'];
			}

			if ( is_array( $meta_box['post_types'] ) ) {
				foreach ( $meta_box['post_types'] as $post_type ) {
					add_meta_box( $meta_box['id'], $meta_box['title'], array( $this, 'display' ), $post_type, $meta_box['context'], $meta_box['priority'] );
				}
			} else {
				add_meta_box( $meta_box['id'], $meta_box['title'], array( $this, 'display' ), $meta_box['post_types'], $meta_box['context'], $meta_box['priority'] );
			}
		}

		public static function normalize( $meta_box ) {
			$meta_box = wp_parse_args(
				$meta_box,
				array(
					'id'             => sanitize_title( $meta_box['title'] ),
					'context'        => 'normal',
					'priority'       => 'high',
					'post_types'     => 'post',
					'autosave'       => false,
					'default_hidden' => false,
					'style'          => 'default',
					'icon'           => '',
				)
			);

			return $meta_box;
		}

		public function normalize_setting( $setting ) {
			$setting = wp_parse_args(
				$setting,
				array(
					'id'   => '',
					'name' => '',
					'desc' => '',
					'std'  => '',
				)
			);

			return $setting;
		}

		public function save_meta_box( $post_id, $post ) {
			$post_id = absint( $post_id );

			if ( empty( $_POST['learnpress_meta_box_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['learnpress_meta_box_nonce'] ), 'learnpress_save_meta_box' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return;
			}

			if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$meta_boxes = $this->meta_box;

			if ( ! empty( $meta_boxes['fields'] ) ) {
				foreach ( $meta_boxes['fields'] as $setting ) {
					$field = $this->normalize_setting( $setting );

					switch ( $field['type'] ) {
						case 'text':
						case 'number':
						case 'textarea':
						case 'select':
							$text = isset( $_POST[ $field['id'] ] ) ? wp_unslash( $_POST[ $field['id'] ] ) : '';
							update_post_meta( $post_id, $field['id'], $text );
							break;

						case 'checkbox':
							$checkbox = isset( $_POST[ $field['id'] ] ) ? 'yes' : 'no';
							update_post_meta( $post_id, $field['id'], $checkbox );
							break;

						case 'duration':
							$duration = isset( $_POST[ $field['id'] ][0] ) && $_POST[ $field['id'] ][0] !== '' ? implode( ' ', wp_unslash( $_POST[ $field['id'] ] ) ) : '0 minute';
							update_post_meta( $post_id, $field['id'], $duration );
					}
				}
			}

		}

		public function display() {
			$meta_boxes = $this->meta_box;

			if ( ! empty( $meta_boxes['fields'] ) ) {
				wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
				?>

				<div class="lp-meta-box">
					<div class="lp-meta-box__inner">
						<?php
						foreach ( $meta_boxes['fields'] as $setting ) {
							$field = $this->normalize_setting( $setting );

							switch ( $field['type'] ) {
								case 'text':
								case 'number':
									lp_meta_box_text_input_field(
										array(
											'id'          => $field['id'],
											'label'       => isset( $field['label'] ) ? $field['label'] : $field['name'],
											'description' => isset( $field['description'] ) ? $field['description'] : $field['desc'],
											'type'        => $field['type'],
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
										)
									);
									break;

								case 'textarea':
									lp_meta_box_textarea_field(
										array(
											'id'          => $field['id'],
											'label'       => isset( $field['label'] ) ? $field['label'] : $field['name'],
											'description' => isset( $field['description'] ) ? $field['description'] : $field['desc'],
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
										)
									);
									break;

								case 'checkbox':
									lp_meta_box_checkbox_field(
										array(
											'id'          => $field['id'],
											'label'       => isset( $field['label'] ) ? $field['label'] : $field['name'],
											'description' => isset( $field['description'] ) ? $field['description'] : $field['desc'],
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
										)
									);
									break;

								case 'duration':
									lp_meta_box_duration_field(
										array(
											'id'           => $field['id'],
											'label'        => isset( $field['label'] ) ? $field['label'] : $field['name'],
											'default_time' => $field['default_time'],
											'default'      => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'description'  => isset( $field['description'] ) ? $field['description'] : $field['desc'],
											'default'      => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
										)
									);
									break;

								case 'select':
									lp_meta_box_select_field(
										array(
											'id'          => $field['id'],
											'label'       => isset( $field['label'] ) ? $field['label'] : $field['name'],
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'description' => isset( $field['description'] ) ? $field['description'] : $field['desc'],
											'options'     => $field['options'],
											'style'       => 'min-width: 200px',
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
										)
									);
									break;

								case 'select_advanced':
									lp_meta_box_select_field(
										array(
											'id'          => $field['id'],
											'label'       => isset( $field['label'] ) ? $field['label'] : $field['name'],
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'description' => isset( $field['description'] ) ? $field['description'] : $field['desc'],
											'options'     => $field['options'],
											'multiple'    => true,
											'default'     => isset( $field['default'] ) ? $field['default'] : $field['std'],
											'wrapper_class' => 'lp-select-2',
											'style'       => 'min-width: 200px',
											'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
										)
									);
									break;
							}
						}
						?>
					</div>
				</div>
				<?php
			}
		}
	}
}

class LP_Course_MetaBox_Tab_V3 {
	public function __construct() {
		add_action( 'lp_course_data_settings_tabs', array( $this, 'get_tab_title' ) );
		add_action( 'lp_course_data_setting_tab_content', array( $this, 'get_tab_content' ) );
		add_action( 'learnpress/course-settings/after-author', array( $this, 'co_instructor_author' ) );
		add_action( 'admin_init', array( $this, 'add_course_tab' ) );
	}

	public function add_course_tab() {
		$tabs = apply_filters( 'learn-press/admin-course-tabs', array() );

		if ( ! empty( $tabs ) ) {
			foreach ( $tabs as $key => $tab ) {
				return $tab;
			}
		}
	}

	public function get_tab_title( $tabs ) {
		$tab = apply_filters( 'learn-press/' . LP_COURSE_CPT . '/tabs', false );

		$priority = 60;
		if ( ! empty( $tab ) && is_array( $tab ) ) {
			foreach ( $tab as $key => $field ) {
				$tabs[ $key ] = array(
					'label'    => $field['title'],
					'target'   => $field['id'],
					'icon'     => $field['icon'],
					'priority' => $priority + 10,
				);
			}
		}

		return $tabs;
	}

	public function get_tab_content() {
		$tab = apply_filters( 'learn-press/' . LP_COURSE_CPT . '/tabs', false );

		if ( ! $tab ) {
			return;
		}

		foreach ( $tab as $field ) {
			if ( isset( $field['callback'] ) ) {
				?>
				<div id="<?php echo $field['id']; ?>" class="lp-meta-box-course-panels">
					<?php call_user_func( $field['callback'] ); ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Support for Co Instructor plugin.
	 *
	 * @return void
	 */
	public function co_instructor_author() {
		$fields = apply_filters( 'learn_press_course_author_meta_box', array() );

		if ( ! empty( $fields ) ) {
			LP_Meta_Box_Course::eduma_child_metabox_v3( $fields );
		}
	}
}

new LP_Course_MetaBox_Tab_V3();

if ( ! class_exists( 'LP_Meta_Box_Tabs' ) ) {
	/**
	 * Not use, but need to fix error.
	 * use in learnpress-coupon plugin.
	 *
	 * @author nhamdv <email@email.com>
	 */
	class LP_Meta_Box_Tabs {
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return true;
		}

		public function admin_notices() {
			?>
			<div class="error">
				<p><?php esc_html_e( 'LearnPress 4 don\'t use LP_Meta_Box_Tabs, please update your code.', 'learnpress' ); ?></p>
			</div>
			<?php
		}
	}
}
