<?php

class LP_Meta_Box_Course {
	/**
	 * Instance
	 *
	 * @var null|LP_Meta_Box_Course
	 */
	private static $instance = null;

	/**
	 * General fields
	 *
	 * @var array
	 */
	public static $general_fields = array();

	/**
	 * LP_Meta_Box_Course constructor.
	 *
	 * @see lp_meta_box_checkbox_field
	 */
	private function __construct() {
		self::$general_fields = apply_filters(
			'lp/course/meta-box/fields/general',
			array(
				'_lp_duration'                 => new LP_Meta_Box_Duration_Attribute(
					'_lp_duration',
					esc_html__( 'Duration', 'learnpress' ),
					esc_html__( 'Set 0 for lifetime access.', 'learnpress' ),
					'lp_meta_box_duration_field',
					'10',
					'',
					'',
					'',
					array(
						'min'  => '0',
						'step' => '1',
					),
					'week'
				),
				'_lp_block_expire_duration'    => new LP_Meta_Box_Attribute(
					'_lp_block_expire_duration',
					esc_html__( 'Block content', 'learnpress' ),
					esc_html__(
						'Block course when duration expires.',
						'learnpress'
					),
					'lp_meta_box_checkbox_field',
					'no'
				),
				'_lp_block_finished'           => new LP_Meta_Box_Attribute(
					'_lp_block_finished',
					'',
					esc_html__(
						'Block course when finished course.',
						'learnpress'
					),
					'lp_meta_box_checkbox_field',
					'yes'
				),
				'_lp_level'                    => new LP_Meta_Box_Select_Attribute(
					'_lp_level',
					esc_html__( 'Level', 'learnpress' ),
					esc_html__( 'Choose a difficulty level.', 'learnpress' ),
					'lp_meta_box_select_field',
					'',
					'',
					'',
					'',
					array(),
					array(
						''             => esc_html__( 'All levels', 'learnpress' ),
						'beginner'     => esc_html__( 'Beginner', 'learnpress' ),
						'intermediate' => esc_html__( 'Intermediate', 'learnpress' ),
						'expert'       => esc_html__( 'Expert', 'learnpress' ),
					)
				),
				'_lp_students'                 => new LP_Meta_Box_Text_Attribute(
					'_lp_students',
					esc_html__( 'Fake Students Enrolled', 'learnpress' ),
					esc_html__( 'It only display fake students enrolled', 'learnpress' ),
					0,
					'',
					'',
					'width: 70px;',
					array(
						'min'  => '0',
						'step' => '1',
					),
					'number'
				),
				'_lp_max_students'             => new LP_Meta_Box_Text_Attribute(
					'_lp_max_students',
					esc_html__( 'Max student', 'learnpress' ),
					esc_html__(
						'Maximum students can join the course. Set 0 for unlimited.',
						'learnpress'
					),
					'0',
					'',
					'',
					'width: 70px;',
					array(
						'min'  => '0',
						'step' => '1',
					),
					'number'
				),
				'_lp_retake_count'             => new LP_Meta_Box_Text_Attribute(
					'_lp_retake_count',
					esc_html__( 'Re-take Course', 'learnpress' ),
					esc_html__(
						'Number times the user can learn again this course. Set to 0 to disable.',
						'learnpress'
					),
					0,
					'',
					'',
					'width: 70px;',
					array(),
					'number'
				),
				'_lp_has_finish'               => new LP_Meta_Box_Attribute(
					'_lp_has_finish',
					esc_html__( 'Finish button', 'learnpress' ),
					esc_html__(
						'Allow show finish button when all items completed but evalution not passed.',
						'learnpress'
					),
					'lp_meta_box_checkbox_field',
					'yes'
				),
				'_lp_featured'                 => new LP_Meta_Box_Attribute(
					'_lp_featured',
					esc_html__( 'Featured list', 'learnpress' ),
					esc_html__( 'Add the course to Featured List.', 'learnpress' ),
					'lp_meta_box_checkbox_field',
					'no'
				),
				'_lp_featured_review'          => new LP_Meta_Box_Attribute(
					'_lp_featured_review',
					esc_html__( 'Featured review', 'learnpress' ),
					esc_html__( 'A good review to promote the course.', 'learnpress' ),
					'lp_meta_box_textarea_field'

				),
				'_lp_external_link_buy_course' => new LP_Meta_Box_Text_Attribute(
					'_lp_external_link_buy_course',
					esc_html__( 'External link', 'learnpress' ),
					esc_html__(
						'Normally use for offline classes, Ex: link to a contact page. Format: https://google.com',
						'learnpress'
					),
					'',
					'You can apply for case: user register form.<br> You accept for user can learn courses by add manual order on backend'
				),
			)
		);
	}

	/**
	 * Get instance
	 *
	 * @return LP_Meta_Box_Course
	 */
	public static function get_instance(): LP_Meta_Box_Course {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--course">
			<div class="lp-meta-box__inner">
				<div class="lp-meta-box__course-tab">
					<ul class="lp-meta-box__course-tab__tabs">
						<?php
						foreach ( self::data_tabs() as $key => $tab ) {
							$class_tab = '';
							if ( isset( $tab['class'] ) ) {
								$class_tab = implode( ' ', (array) $tab['class'] );
							}
							?>
							<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab
							<?php echo esc_attr( $class_tab ); ?>">
								<a href="#<?php echo esc_attr( $tab['target'] ); ?>">
									<?php if ( isset( $tab['icon'] ) ) : ?>
										<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>
									<?php endif; ?>
									<span><?php echo esc_html( $tab['label'] ); ?></span>
								</a>
							</li>
						<?php } ?>
					</ul>

					<div class="lp-meta-box__course-tab__content">
						<?php
						self::output_tabs();
						do_action( 'lp_course_data_setting_tab_content' );
						?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Save meta fields
	 *
	 * @param int $post_id
	 */
	public static function save( $post_id = 0 ) {
		$course = learn_press_get_course( $post_id );

		/**
		 * Save general course setting
		 *
		 * @var LP_Meta_Box_Attribute $field
		 */
		foreach ( self::$general_fields as $k => $field ) {
			if ( isset( $field->type ) && $field->type ) {
				self::save_field_type( $field->type, $post_id, $k );
			}
		}
		// End save general course setting.

		// Price.
		$price      = isset( $_POST['_lp_price'] ) ? floatval( wp_unslash( $_POST['_lp_price'] ) ) : '';
		$sale_price = isset( $_POST['_lp_sale_price'] ) ? floatval( wp_unslash( $_POST['_lp_sale_price'] ) ) : '';
		$sale_start = isset( $_POST['_lp_sale_start'] ) ? wp_unslash( $_POST['_lp_sale_start'] ) : '';
		$sale_end   = isset( $_POST['_lp_sale_end'] ) ? wp_unslash( $_POST['_lp_sale_end'] ) : '';

		update_post_meta( $post_id, '_lp_price', $price );
		update_post_meta( $post_id, '_lp_sale_price', $sale_price );
		update_post_meta( $post_id, '_lp_sale_start', $sale_start );
		update_post_meta( $post_id, '_lp_sale_end', $sale_end );

		// Extra Infomation.
		$requirements     = isset( $_POST['_lp_requirements'] ) ? wp_unslash( $_POST['_lp_requirements'] ) : array();
		$target_audiences = isset( $_POST['_lp_target_audiences'] ) ? wp_unslash( $_POST['_lp_target_audiences'] ) : array();
		$key_features     = isset( $_POST['_lp_key_features'] ) ? wp_unslash( $_POST['_lp_key_features'] ) : array();
		$faqs_question    = isset( $_POST['_lp_faqs_question'] ) ? wp_unslash( $_POST['_lp_faqs_question'] ) : array();
		$faqs_answer      = isset( $_POST['_lp_faqs_answer'] ) ? wp_unslash( $_POST['_lp_faqs_answer'] ) : array();

		$faqs = array();

		if ( ! empty( $faqs_question ) ) {
			$faqs_question_size = count( $faqs_question );

			for ( $i = 0; $i < $faqs_question_size; $i ++ ) {
				if ( ! empty( $faqs_question[ $i ] ) ) {
					$faqs[] = array( $faqs_question[ $i ], $faqs_answer[ $i ] );
				}
			}
		}

		update_post_meta( $post_id, '_lp_requirements', $requirements );
		update_post_meta( $post_id, '_lp_target_audiences', $target_audiences );
		update_post_meta( $post_id, '_lp_key_features', $key_features );
		update_post_meta( $post_id, '_lp_faqs', $faqs );

		// Assessment.
		$evalution         = isset( $_POST['_lp_course_result'] ) ? wp_unslash( $_POST['_lp_course_result'] ) : '';
		$final_quiz        = isset( $_POST['_lp_course_result_final_quiz_passing_condition'] ) ? absint( wp_unslash( $_POST['_lp_course_result_final_quiz_passing_condition'] ) ) : 0;
		$passing_condition = isset( $_POST['_lp_passing_condition'] ) ? absint( wp_unslash( $_POST['_lp_passing_condition'] ) ) : 0;

		if ( $evalution == 'evaluate_final_quiz' ) {
			$api = LP_Repair_Database::instance();
			$api->sync_course_final_quiz( $course->get_id() );

			$quiz_id = $course->get_final_quiz();

			update_post_meta( $quiz_id, '_lp_passing_grade', $final_quiz );
		}

		update_post_meta( $post_id, '_lp_course_result', $evalution );
		update_post_meta( $post_id, '_lp_passing_condition', $passing_condition );

		// Author.
		$author = isset( $_POST['_lp_course_author'] ) ? wp_unslash( $_POST['_lp_course_author'] ) : '';
		update_post_meta( $post_id, '_lp_course_author', $author );

		if ( ! empty( $author ) ) {
			global $wpdb;

			$curriculum = $course->get_items( '', false );

			if ( ! $curriculum ) {
				$wpdb->update( $wpdb->posts, array( 'post_author' => $author ), array( 'ID' => $post_id ) );
			}
		}
	}

	private static function data_tabs() {
		$tabs = apply_filters(
			'lp_course_data_settings_tabs',
			array(
				'general'    => array(
					'label'    => esc_html__( 'General', 'learnpress' ),
					'target'   => 'general_course_data',
					'icon'     => 'dashicons-admin-tools',
					'priority' => 10,
				),
				'price'      => array(
					'label'    => esc_html__( 'Pricing', 'learnpress' ),
					'target'   => 'price_course_data',
					'icon'     => 'dashicons-cart',
					'priority' => 20,
				),
				'extra'      => array(
					'label'    => esc_html__( 'Extra Information', 'learnpress' ),
					'target'   => 'extra_course_data',
					'icon'     => 'dashicons-excerpt-view',
					'priority' => 30,
				),
				'assessment' => array(
					'label'    => esc_html__( 'Assessment', 'learnpress' ),
					'target'   => 'assessment_course_data',
					'icon'     => 'dashicons-awards',
					'priority' => 40,
				),
				'author'     => array(
					'label'    => esc_html__( 'Author', 'learnpress' ),
					'target'   => 'author_course_data',
					'icon'     => 'dashicons-businessman',
					'priority' => 50,
				),
			)
		);

		// Sort tabs based on priority.
		uasort( $tabs, array( __CLASS__, 'data_tabs_sort' ) );

		return $tabs;
	}

	private static function output_tabs() {
		global $post, $thepostid;

		include 'tabs/general.php';
		include 'tabs/price.php';
		include 'tabs/extra.php';
		include 'tabs/author.php';
		include 'tabs/assessment.php';
	}

	private static function data_tabs_sort( $a, $b ) {
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return - 1;
		}

		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? - 1 : 1;
	}

	/**
	 * Save with field type
	 *
	 * @param string $field_type
	 * @param int $post_id
	 * @param string $k
	 *
	 * @return void
	 */
	protected static function save_field_type( $field_type = '', $post_id = 0, $k = '' ) {
		switch ( $field_type ) {
			case 'lp_meta_box_checkbox_field':
				$yes_no_field = isset( $_POST[ $k ] ) ? 'yes' : 'no';
				update_post_meta( $post_id, $k, $yes_no_field );
				break;
			case 'lp_meta_box_duration_field':
				$duration = '0 minute';

				if ( isset( $_POST[ $k ] ) && isset( $_POST[ $k ][0] ) && '' !== $_POST[ $k ][0] ) {
					$duration = implode( ' ', LP_Helper::sanitize_params_submitted( $_POST[ $k ] ) );
				}

				update_post_meta( $post_id, $k, $duration );
				break;
			case 'lp_meta_box_textarea_field':
				update_post_meta( $post_id, $k, LP_Helper::sanitize_params_submitted( $_POST[ $k ], 'html' ) );
				break;
			case $field_type . '_custom':
				do_action( 'lp/meta_box/custom_field/save_value', $post_id, $k, $_POST[ $k ] );
				break;
			default:
				update_post_meta( $post_id, $k, LP_Helper::sanitize_params_submitted( $_POST[ $k ] ) );
		}
	}

	/**
	 * In child theme use metabox in v3,
	 * so need use for child theme.
	 * function in child: thim_add_course_meta.
	 *
	 * @return void
	 */
	public static function eduma_child_metabox_v3( $meta_boxes ) {
		if ( ! empty( $meta_boxes['fields'] ) ) {
			foreach ( $meta_boxes['fields'] as $setting ) {
				$field = wp_parse_args(
					$setting,
					array(
						'id'   => '',
						'name' => '',
						'desc' => '',
						'std'  => '',
					)
				);

				switch ( $field['type'] ) {
					case 'text':
					case 'number':
						lp_meta_box_text_input_field(
							array(
								'id'                => $field['id'],
								'label'             => isset( $field['label'] ) ? $field['label'] : $field['name'],
								'description'       => isset( $field['description'] ) ? $field['description'] : $field['desc'],
								'type'              => $field['type'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
							)
						);
						break;

					case 'textarea':
						lp_meta_box_textarea_field(
							array(
								'id'                => $field['id'],
								'label'             => isset( $field['label'] ) ? $field['label'] : $field['name'],
								'description'       => isset( $field['description'] ) ? $field['description'] : $field['desc'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
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
								'id'                => $field['id'],
								'label'             => isset( $field['label'] ) ? $field['label'] : $field['name'],
								'default_time'      => $field['default_time'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'description'       => isset( $field['description'] ) ? $field['description'] : $field['desc'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
							)
						);
						break;

					case 'select':
						lp_meta_box_select_field(
							array(
								'id'                => $field['id'],
								'label'             => isset( $field['label'] ) ? $field['label'] : $field['name'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'description'       => isset( $field['description'] ) ? $field['description'] : $field['desc'],
								'options'           => $field['options'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
							)
						);
						break;

					case 'select_advanced':
						lp_meta_box_select_field(
							array(
								'id'                => $field['id'],
								'label'             => isset( $field['label'] ) ? $field['label'] : $field['name'],
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'description'       => isset( $field['description'] ) ? $field['description'] : $field['desc'],
								'options'           => $field['options'],
								'multiple'          => true,
								'default'           => isset( $field['default'] ) ? $field['default'] : $field['std'],
								'wrapper_class'     => 'lp-select-2',
								'style'             => 'min-width: 200px',
								'custom_attributes' => isset( $field['custom_attributes'] ) ? $field['custom_attributes'] : '',
							)
						);
						break;
				}
			}
		}
	}

	public static function save_eduma_child_metabox_v3( $post_id ) {
		$general = apply_filters( 'learn_press_course_settings_meta_box_args', array( 'fields' => array() ) );

		if ( ! empty( $general['fields'] ) ) {
			foreach ( $general['fields'] as $field ) {
				$value = isset( $_POST[ $field['id'] ] ) ? wp_unslash( $_POST[ $field['id'] ] ) : '';

				update_post_meta( $post_id, $field['id'], $value );
			}
		}
	}
}

LP_Meta_Box_Course::get_instance();
