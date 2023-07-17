<?php
/**
 * Widget class
 *
 * @package  Learnpress/Abstracts
 * @author ThimPress <nhamdv>
 */

defined( 'ABSPATH' ) || exit;

/**
 * LP_Widget
 *
 * @version  4.0.0
 * @extends  WP_Widget
 */
class LP_Widget extends WP_Widget {

	/**
	 * CSS class.
	 *
	 * @var string
	 */
	public $widget_cssclass;

	/**
	 * Widget description.
	 *
	 * @var string
	 */
	public $widget_description;

	/**
	 * Widget ID.
	 *
	 * @var string
	 */
	public $widget_id;

	/**
	 * Widget name.
	 *
	 * @var string
	 */
	public $widget_name;

	/**
	 * Enable rest_api for LearnPress.
	 *
	 * @var string
	 */
	public $widget_in_rest = true;

	/**
	 * New param add to rest api need for data.
	 *
	 * @var array
	 */
	public $widget_data_attr = array();

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => $this->widget_cssclass,
			'description'                 => $this->widget_description,
			'customize_selective_refresh' => true,
		);

		parent::__construct( $this->widget_id, $this->widget_name, $widget_ops );
	}

	/**
	 * Get this widgets title.
	 */
	protected function get_instance_title( $instance ) {
		if ( isset( $instance['title'] ) ) {
			return $instance['title'];
		}

		if ( isset( $this->settings, $this->settings['title'], $this->settings['title']['std'] ) ) {
			return $this->settings['title']['std'];
		}

		return '';
	}

	/**
	 * Output the html at the start of a widget.
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget_start( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );

		$title = apply_filters( 'widget_title', $this->get_instance_title( $instance ), $instance, $this->id_base );

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}
	}

	/**
	 * Output the html at the end of a widget.
	 *
	 * @param array $args Arguments.
	 */
	public function widget_end( $args ) {
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Output Widgets HTML.
	 *
	 * @param [type] $args
	 * @param [type] $instance
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		wp_enqueue_script( 'lp-widgets' );
		if ( $this->id_base === 'learnpress_widget_course_filter' ) {
			wp_enqueue_script( 'lp-course-filter' );
		}

		if ( empty( $instance['show_in_rest'] ) ) {
			$this->widget_in_rest = false;
		}

		$data = array_merge(
			$this->widget_data_attr,
			array(
				'widget'   => $this->widget_id,
				'instance' => wp_json_encode( $instance ),
			)
		);

		echo $this->lp_widget_content( $data, $args, $instance );
	}

	/**
	 * Show widget content.
	 *
	 * @param array $data Data attribute HTML for Rest API js.
	 * @param [type] $args Default Widget Args
	 * @param [type] $instance Default Widget Instance
	 *
	 * @return string HTML
	 */
	public function lp_widget_content( $data, $args, $instance ) {
		ob_start();

		$this->widget_start( $args, $instance );

		if ( ! is_admin() && $this->widget_in_rest ) {
			?>
			<div class="learnpress-widget-wrapper learnpress-widget-wrapper__restapi"
				data-widget="<?php echo htmlentities( wp_json_encode( $data ) ); ?>">
				<?php lp_skeleton_animation_html( 5 ); ?>
			</div>
			<?php
		} else { // Use for Preview in Widget Editor since WordPress 5.8
			$content = $this->lp_rest_api_content( $instance, array() );

			echo '<div class="learnpress-widget-wrapper">';

			if ( is_wp_error( $content ) ) {
				echo $content->get_error_message();
			} else {
				echo $content;
			}

			echo '</div>';
		}

		$this->widget_end( $args );

		return ob_get_clean();
	}

	/**
	 * Send content for API
	 *
	 * @param array $instance Widget Instance
	 * @param array $params RestAPI param need for content.
	 *
	 * @return string || WP_Error
	 */
	public function lp_rest_api_content( $instance, $params ) {
		return 'No content for Rest API';
	}

	/**
	 * Updates a particular instance of a widget.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		if ( empty( $this->settings ) ) {
			return $instance;
		}

		foreach ( $this->settings as $key => $setting ) {
			if ( ! isset( $setting['type'] ) ) {
				continue;
			}

			switch ( $setting['type'] ) {
				case 'number':
					$instance[ $key ] = absint( $new_instance[ $key ] );

					if ( isset( $setting['min'] ) && '' !== $setting['min'] ) {
						$instance[ $key ] = max( $instance[ $key ], $setting['min'] );
					}

					if ( isset( $setting['max'] ) && '' !== $setting['max'] ) {
						$instance[ $key ] = min( $instance[ $key ], $setting['max'] );
					}
					break;
				case 'textarea':
					$instance[ $key ] = wp_kses( trim( wp_unslash( $new_instance[ $key ] ) ), wp_kses_allowed_html( 'post' ) );
					break;
				case 'checkbox':
					$instance[ $key ] = empty( $new_instance[ $key ] ) ? 0 : 1;
					break;
				case 'sortable-checkbox':
					$instance[ $key ] = empty( $new_instance[ $key ] ) ? [] : map_deep( $new_instance[ $key ], 'sanitize_text_field' );
					break;
				default:
					$instance[ $key ] = isset( $new_instance[ $key ] ) ? sanitize_text_field( $new_instance[ $key ] ) : ( $setting['std'] ?? '' );
					break;
			}

			/**
			 * Sanitize the value of a setting.
			 */
			$instance[ $key ] = apply_filters( 'learnpress_widget_settings_sanitize_option', $instance[ $key ], $new_instance, $key, $setting );
		}

		return $instance;
	}

	/**
	 * Outputs the settings update form.
	 */
	public function form( $instance ) {
		if ( empty( $this->settings ) ) {
			echo '<p>' . esc_html_e( 'There are no options for this widget.', 'learnpress' ) . '</p>';

			return;
		}

		foreach ( $this->settings as $key => $setting ) {
			$class = $setting['class'] ?? '';
			$value = $instance[ $key ] ?? $setting['std'] ?? '';

			switch ( $setting['type'] ) {
				case 'text':
					?>
					<p>
						<label
							for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo wp_kses_post( $setting['label'] ); ?></label>
						<?php
						// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
						?>
						<input class="widefat <?php echo esc_attr( $class ); ?>"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="text"
							value="<?php echo esc_attr( $value ); ?>"/>
					</p>
					<?php
					break;
				case 'hidden':
					?>
					<p>
						<input
							class="fields-sort"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="hidden"
							value="<?php echo esc_attr( $value ); ?>"/>
					</p>
					<?php
					break;
				case 'number':
					?>
					<p>
						<label
							for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo wp_kses_post( $setting['label'] ); ?></label>
						<input class="widefat <?php echo esc_attr( $class ); ?>"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="number"
							step="<?php echo isset( $setting['step'] ) ? esc_attr( $setting['step'] ) : '1'; ?>"
							min="<?php echo isset( $setting['min'] ) ? esc_attr( $setting['min'] ) : ''; ?>"
							max="<?php echo isset( $setting['max'] ) ? esc_attr( $setting['max'] ) : ''; ?>"
							value="<?php echo esc_attr( $value ); ?>"/>
					</p>
					<?php
					break;
				case 'select':
					?>
					<p>
						<label
							for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo wp_kses_post( $setting['label'] ); ?></label>
						<select class="widefat <?php echo esc_attr( $class ); ?>"
								id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
								name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>">
							<?php foreach ( $setting['options'] as $option_key => $option_value ) : ?>
								<option
									value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $value ); ?>><?php echo esc_html( $option_value ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<?php
					break;
				case 'textarea':
					?>
					<p>
						<label
							for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo wp_kses_post( $setting['label'] ); ?></label>
						<textarea class="widefat <?php echo esc_attr( $class ); ?>"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" cols="20"
							rows="3"><?php echo esc_textarea( $value ); ?></textarea>
						<?php if ( isset( $setting['desc'] ) ) : ?>
							<small><?php echo esc_html( $setting['desc'] ); ?></small>
						<?php endif; ?>
					</p>
					<?php
					break;
				case 'checkbox':
					?>
					<p>
						<input class="checkbox <?php echo esc_attr( $class ); ?>"
							id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="checkbox"
							value="1" <?php checked( $value, 1 ); ?> />
						<label
							for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo wp_kses_post( $setting['label'] ); ?></label>
					</p>
					<?php
					break;
				case 'sortable-checkbox':
					$values_default = $setting['std'] ?? array();
					$order          = $instance['fields_order'] ?? '';
					?>
					<div class="sortable-wrapper">
						<label><?php echo wp_kses_post( $setting['label'] ); ?></label>
						<div>
							<?php
							$options = $setting['options'] ?? array();
							if ( ! empty( $order ) ) {
								$order       = explode( ',', $order );
								$new_options = array();
								foreach ( $order as $order_val ) {
									if ( isset( $options[ $order_val ] ) ) {
										$new_options[ $order_val ] = $options[ $order_val ];
									}
								}

								$options = $new_options;
							}
							?>
							<div class="sortable <?php echo esc_attr( $class ); ?>">
								<?php
								foreach ( $options as $option_name => $option ) {
									$checked_value = ! empty( $instance ) ? ( $instance[ $key ] ?? array() ) : $values_default;
									?>
									<div class="sortable__item">
										<i class="dashicons dashicons-menu drag"></i>
										<input class="checkbox"
											id="<?php echo esc_attr( $this->get_field_id( $option['id'] ) ); ?>"
											name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>[]"
											type="checkbox"
											value="<?php echo esc_attr( $option_name ); ?>"
											<?php checked( in_array( $option_name, $checked_value ) ); ?>
										/>
										<label
											for="<?php echo esc_attr( $this->get_field_id( $option['id'] ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?></label>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
					<script>
						jQuery(document).trigger('learnpress/widgets/select');
					</script>
					<?php
					break;

				case 'autocomplete':
					?>
					<p>
						<label
							for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo wp_kses_post( $setting['label'] ); ?></label>
						<select class="widefat lp-widget_select_course"
								id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"
								name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>"
								data-rest-url="<?php echo get_rest_url(); ?>"
								data-post-type="<?php echo esc_attr( $setting['post_type'] ?? LP_COURSE_CPT ); ?>"
								style="width: 300px;">
							<?php if ( ! empty( $value ) ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"
										selected="selected"><?php echo esc_html( get_the_title( $value ) ); ?></option>
							<?php endif; ?>
							<script>
								jQuery(document).trigger('learnpress/widgets/select');
							</script>
						</select>
					</p>
					<?php
					break;

				default:
					do_action( 'learnpress_widget_field_' . $setting['type'], $key, $value, $setting, $instance );
					break;
			}
		}
	}
}
