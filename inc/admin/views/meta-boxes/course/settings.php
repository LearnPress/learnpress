<?php
class LP_Meta_Box_Course {

	public static function output( $post ) {
		wp_nonce_field( 'learnpress_save_meta_box', 'learnpress_meta_box_nonce' );
		?>

		<div class="lp-meta-box lp-meta-box--course">
			<div class="lp-meta-box__inner">
				<div class="lp-meta-box__course-tab">
					<ul class="lp-meta-box__course-tab__tabs">
						<?php foreach ( self::data_tabs() as $key => $tab ) : ?>
							<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $tab['class'] ) ? implode( ' ', (array) $tab['class'] ) : '' ); ?>">
								<a href="#<?php echo esc_attr( $tab['target'] ); ?>">
									<?php if ( isset( $tab['icon'] ) ) : ?>
										<i class="<?php echo $tab['icon']; ?>"></i>
									<?php endif; ?>
									<span><?php echo esc_html( $tab['label'] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
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

	public static function save( $post_id ) {
		$course = learn_press_get_course( $post_id );

		// General.
		$duration        = isset( $_POST['_lp_duration'][0] ) && $_POST['_lp_duration'][0] !== '' ? implode( ' ', wp_unslash( $_POST['_lp_duration'] ) ) : '0 minute';
		$level           = isset( $_POST['_lp_level'] ) ? wp_unslash( $_POST['_lp_level'] ) : '';
		$students        = isset( $_POST['_lp_students'] ) ? absint( wp_unslash( $_POST['_lp_students'] ) ) : 0;
		$max_students    = isset( $_POST['_lp_max_students'] ) ? absint( wp_unslash( $_POST['_lp_max_students'] ) ) : 0;
		$retry           = isset( $_POST['_lp_retake_count'] ) ? 'yes' : 'no';
		$feature         = isset( $_POST['_lp_featured'] ) ? 'yes' : 'no';
		$featured_review = isset( $_POST['_lp_featured_review'] ) ? wp_unslash( $_POST['_lp_featured_review'] ) : '';
		$external        = isset( $_POST['_lp_external_link_buy_course'] ) ? wp_unslash( $_POST['_lp_external_link_buy_course'] ) : '';

		update_post_meta( $post_id, '_lp_duration', $duration );
		update_post_meta( $post_id, '_lp_level', $level );
		update_post_meta( $post_id, '_lp_students', $students );
		update_post_meta( $post_id, '_lp_max_students', $max_students );
		update_post_meta( $post_id, '_lp_retake_count', $retry );
		update_post_meta( $post_id, '_lp_featured', $feature );
		update_post_meta( $post_id, '_lp_featured_review', $featured_review );
		update_post_meta( $post_id, '_lp_external_link_buy_course', $external );

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
				return;
			}

			$item_ids = $quiz_ids = $question_ids = array();

			foreach ( $curriculum as $item_id ) {
				$item_ids[] = (int) $item_id;

				if ( learn_press_get_post_type( $item_id ) == LP_QUIZ_CPT ) {
					$quiz      = LP_Quiz::get_quiz( $item_id );
					$questions = $quiz->get_questions();

					if ( $questions ) {
						$question_ids = array_merge( $question_ids, $questions );
					}
				}
			}

			$ids = array_merge( (array) $post_id, $item_ids, $question_ids );

			foreach ( $ids as $id ) {
				$wpdb->update( $wpdb->posts, array( 'post_author' => $author ), array( 'ID' => $id ) );
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
			return -1;
		}

		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? -1 : 1;
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
				}
			}
		}
	}

	public static function save_eduma_child_metabox_v3( $post_id ) {
		$general = apply_filters( 'learn_press_course_settings_meta_box_args', null );

		if ( ! empty( $general['fields'] ) ) {
			foreach ( $general['fields'] as $field ) {
				$value = isset( $_POST[ $field['id'] ] ) ? wp_unslash( $_POST[ $field['id'] ] ) : '';

				update_post_meta( $post_id, $field['id'], $value );
			}
		}
	}
}
