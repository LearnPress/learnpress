<?php

class LP_Meta_Box_Course extends LP_Meta_Box {
	/**
	 * Instance
	 *
	 * @var null|LP_Meta_Box_Course
	 */
	private static $instance = null;

	public $post_type = LP_COURSE_CPT;

	public function add_meta_box() {
		add_meta_box( 'course-settings', esc_html__( 'Course Settings', 'learnpress' ), array( $this, 'output' ), $this->post_type, 'normal', 'high' );
	}

	public function metabox( $post_id ) {
		$tabs = apply_filters(
			'lp_course_data_settings_tabs',
			array(
				'general'    => array(
					'label'    => esc_html__( 'General', 'learnpress' ),
					'target'   => 'general_course_data',
					'icon'     => 'dashicons-admin-tools',
					'priority' => 10,
					'content'  => $this->general( $post_id ),
				),
				'price'      => array(
					'label'    => esc_html__( 'Pricing', 'learnpress' ),
					'target'   => 'price_course_data',
					'icon'     => 'dashicons-cart',
					'priority' => 20,
					'content'  => $this->lp_price( $post_id ),
				),
				'extra'      => array(
					'label'    => esc_html__( 'Extra Information', 'learnpress' ),
					'target'   => 'extra_course_data',
					'icon'     => 'dashicons-excerpt-view',
					'priority' => 30,
					'content'  => $this->extra( $post_id ),
				),
				'assessment' => array(
					'label'    => esc_html__( 'Assessment', 'learnpress' ),
					'target'   => 'assessment_course_data',
					'icon'     => 'dashicons-awards',
					'priority' => 40,
					'content'  => $this->assessment( $post_id ),
				),
				'author'     => array(
					'label'    => esc_html__( 'Author', 'learnpress' ),
					'target'   => 'author_course_data',
					'icon'     => 'dashicons-businessman',
					'priority' => 50,
					'content'  => $this->author( $post_id ),
				),
			)
		);

		$tabs = apply_filters( 'learnpress/course/metabox/tabs', $tabs, $post_id );

		uasort( $tabs, array( __CLASS__, 'data_tabs_sort' ) );

		return $tabs;
	}

	public function general( $thepostid ) {
		$repurchase_option_desc  = sprintf( '1. %s', __( 'Reset course progress: The course progress and results of student will be removed.' ) );
		$repurchase_option_desc .= '<br/>' . sprintf( '2. %s', __( 'Keep course progress: The course progress and results of student will remain.' ) );
		$repurchase_option_desc .= '<br/>' . sprintf( '3. %s', __( 'Open popup: The student can decide whether their course progress will be reset with the confirm popup.' ) );

		return apply_filters(
			'lp/course/meta-box/fields/general',
			array(
				'_lp_duration'                 => new LP_Meta_Box_Duration_Field(
					esc_html__( 'Duration', 'learnpress' ),
					esc_html__( 'Set 0 for lifetime access.', 'learnpress' ),
					'10',
					array(
						'default_time'      => 'week',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				),
				'_lp_block_expire_duration'    => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Block content', 'learnpress' ),
					esc_html__( 'Block course when duration expires.', 'learnpress' ),
					'no'
				),
				'_lp_block_finished'           => new LP_Meta_Box_Checkbox_Field(
					'',
					esc_html__( 'Block course after student finished this course.', 'learnpress' ),
					'yes'
				),
				'_lp_allow_course_repurchase'  => new LP_Meta_Box_Checkbox_Field(
					__( 'Allow Repurchase', 'learnpress' ),
					esc_html__( 'Allow users to repurchase this course after course finished or blocked ( Do not apply to free courses ).', 'learnpress' ),
					'no'
				),
				'_lp_course_repurchase_option' => new LP_Meta_Box_Select_Field(
					esc_html__( 'Repurchase action', 'learnpress' ),
					$repurchase_option_desc,
					'reset',
					array(
						'options' => array(
							'reset' => esc_html__( 'Reset course progress', 'learnpress' ),
							'keep'  => esc_html__( 'Keep course progress', 'learnpress' ),
							'popup' => esc_html__( 'Open popup', 'learnpress' ),
						),
						'show'    => array( '_lp_allow_course_repurchase', '=', 'yes' ), // use 'show' or 'hide'
					)
				),
				'_lp_level'                    => new LP_Meta_Box_Select_Field(
					esc_html__( 'Level', 'learnpress' ),
					esc_html__( 'Choose a difficulty level.', 'learnpress' ),
					'',
					array(
						'options' => lp_course_level(),
					)
				),
				'_lp_students'                 => new LP_Meta_Box_Text_Field(
					esc_html__( 'Fake Students Enrolled', 'learnpress' ),
					esc_html__( 'How many students have taken this course', 'learnpress' ),
					0,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_max_students'             => new LP_Meta_Box_Text_Field(
					esc_html__( 'Max student', 'learnpress' ),
					esc_html__( 'Maximum students can join the course. Set 0 for unlimited.', 'learnpress' ),
					0,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_retake_count'             => new LP_Meta_Box_Text_Field(
					esc_html__( 'Re-take Course', 'learnpress' ),
					esc_html__( 'The number of times a user can learn again this course. Set 0 to disable.', 'learnpress' ),
					0,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'style'             => 'width: 70px;',
					)
				),
				'_lp_has_finish'               => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Finish button', 'learnpress' ),
					esc_html__( 'Allow show finish button when the student has completed all items but has not passed the course assessment.', 'learnpress' ),
					'yes'
				),
				'_lp_featured'                 => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Featured list', 'learnpress' ),
					esc_html__( 'Add the course to Featured List.', 'learnpress' ),
					'no'
				),
				'_lp_featured_review'          => new LP_Meta_Box_Textarea_Field(
					esc_html__( 'Featured review', 'learnpress' ),
					esc_html__( 'A good review to promote the course.', 'learnpress' )
				),
				'_lp_external_link_buy_course' => new LP_Meta_Box_Text_Field(
					esc_html__( 'External link', 'learnpress' ),
					esc_html__( 'Normally use for offline classes, Ex: link to a contact page. Format: https://google.com', 'learnpress' ),
					'',
					array(
						'desc_tip' => 'You can apply for case: user register form.<br> You accept for user can learn courses by add manual order on backend',
					)
				),
			)
		);
	}

	/**
	 * @editor tungnx
	 * @modify 4.1.5 - replace to lp_price function
	 */
	/*public function price( $thepostid ) {
		$post = get_post( $thepostid );

		$thepostid = ! empty( $thepostid ) ? $thepostid : absint( $post->ID );

		$message    = '';
		$price      = get_post_meta( $thepostid, '_lp_price', true );
		$payment    = get_post_meta( $thepostid, '_lp_payment', true );
		$sale_price = '';
		$start_date = '';
		$end_date   = '';

		if ( $payment != 'free' ) {
			$sale_price = get_post_meta( $thepostid, '_lp_sale_price', true );
			$start_date = get_post_meta( $thepostid, '_lp_sale_start', true );
			$end_date   = get_post_meta( $thepostid, '_lp_sale_end', true );
		}

		return apply_filters(
			'lp/course/meta-box/fields/price',
			array(
				'_lp_price'              => new LP_Meta_Box_Text_Field(
					esc_html__( 'Regular price', 'learnpress' ),
					sprintf( __( 'Set a regular price (<strong>%s</strong>). Leave it blank for <strong>Free</strong>.', 'learnpress' ), learn_press_get_currency() ),
					$price,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
						'style'             => 'width: 70px;',
						'class'             => 'lp_meta_box_regular_price',
					)
				),
				'_lp_sale_price'         => new LP_Meta_Box_Text_Field(
					esc_html__( 'Sale price', 'learnpress' ),
					'<a href="#" class="lp_sale_price_schedule">' . esc_html__( 'Schedule', 'learnpress' ) . '</a>',
					$sale_price,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
						'style'             => 'width: 70px;',
						'class'             => 'lp_meta_box_sale_price',
					)
				),
				'_lp_sale_start'         => new LP_Meta_Box_Date_Field(
					esc_html__( 'Sale start dates', 'learnpress' ),
					'',
					'',
					array(
						'wrapper_class' => 'lp_sale_start_dates_fields',
						'placeholder'   => _x( 'From&hellip;', 'placeholder', 'learnpress' ),
					)
				),
				'_lp_sale_end'           => new LP_Meta_Box_Date_Field(
					esc_html__( 'Sale end dates', 'learnpress' ),
					'',
					'',
					array(
						'wrapper_class' => 'lp_sale_end_dates_fields',
						'placeholder'   => _x( 'To&hellip;', 'placeholder', 'learnpress' ),
						'cancel'        => true,
					)
				),
				'_lp_no_required_enroll' => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'No requirement enroll', 'learnpress' ),
					esc_html__( 'Students can see the content of all course items and do the quiz without login.', 'learnpress' ),
					'no'
				),
			)
		);
	}*/

	/**
	 * Setting course price
	 *
	 * @param $post_id
	 *
	 * @author tungnx
	 * @since 4.1.5
	 * @version 1.0.0
	 * @return array
	 */
	public function lp_price( $post_id ): array {
		$key_exists    = LP_Database::getInstance()->check_key_postmeta_exists( $post_id, '_lp_regular_price' );
		$price         = get_post_meta( $post_id, '_lp_price', true );
		$regular_price = $key_exists ? get_post_meta( $post_id, '_lp_regular_price', true ) : $price;
		$sale_price    = get_post_meta( $post_id, '_lp_sale_price', true );

		return apply_filters(
			'lp/course/meta-box/fields/price',
			array(
				'_lp_regular_price'      => new LP_Meta_Box_Text_Field(
					esc_html__( 'Regular price', 'learnpress' ),
					sprintf( __( 'Set a regular price (<strong>%s</strong>). Leave it blank for <strong>Free</strong>.', 'learnpress' ), learn_press_get_currency() ),
					$regular_price,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
						'style'             => 'width: 70px;',
						'class'             => 'lp_meta_box_regular_price',
					)
				),
				'_lp_sale_price'         => new LP_Meta_Box_Text_Field(
					esc_html__( 'Sale price', 'learnpress' ),
					'<a href="#" class="lp_sale_price_schedule">' . esc_html__( 'Schedule', 'learnpress' ) . '</a>',
					$sale_price,
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
						'style'             => 'width: 70px;',
						'class'             => 'lp_meta_box_sale_price',
					)
				),
				'_lp_sale_start'         => new LP_Meta_Box_Date_Field(
					esc_html__( 'Sale start dates', 'learnpress' ),
					'',
					'',
					array(
						'wrapper_class' => 'lp_sale_start_dates_fields',
						'placeholder'   => _x( 'From&hellip;', 'placeholder', 'learnpress' ),
					)
				),
				'_lp_sale_end'           => new LP_Meta_Box_Date_Field(
					esc_html__( 'Sale end dates', 'learnpress' ),
					'',
					'',
					array(
						'wrapper_class' => 'lp_sale_end_dates_fields',
						'placeholder'   => _x( 'To&hellip;', 'placeholder', 'learnpress' ),
						'cancel'        => true,
					)
				),
				'_lp_no_required_enroll' => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'No requirement enroll', 'learnpress' ),
					esc_html__( 'Students can see the content of all course items and do the quiz without login.', 'learnpress' ),
					'no'
				),
			)
		);
	}

	public function author( $thepostid ) {
		$post = get_post( $thepostid );

		$author = $post ? $post->post_author : get_current_user_id();

		$options = array();
		$role    = array( 'administrator', 'lp_teacher' );

		$role = apply_filters( 'learn_press_course_author_role_meta_box', $role );

		foreach ( $role as $_role ) {
			$users_by_role = get_users( array( 'role' => $_role ) );

			if ( $users_by_role ) {
				foreach ( $users_by_role as $user ) {
					$options[ $user->get( 'ID' ) ] = $user->user_login;
				}
			}
		}

		return apply_filters(
			'lp/course/meta-box/fields/author',
			array(
				'_lp_course_author' => new LP_Meta_Box_Select_Field(
					esc_html__( 'Author', 'learnpress' ),
					'',
					$author,
					array(
						'options' => $options,
						'style'   => 'min-width:200px;',
					)
				),
			)
		);
	}

	public function assessment( $thepostid ) {
		$post = get_post( $thepostid );

		$course_result_desc = '';
		$course_results     = get_post_meta( $thepostid, '_lp_course_result', true );

		$course_result_desc .= __( 'The method to assess the result of a student for a course.', 'learnpress' );

		if ( $course_results == 'evaluate_final_quiz' && ! get_post_meta( $thepostid, '_lp_final_quiz', true ) ) {
			$course_result_desc .= __( '<br /><strong>Note! </strong>No final quiz in course, please add a final quiz', 'learnpress' );
		}

		$final_quizz_passing = '';

		$course = learn_press_get_course( $thepostid );

		if ( $course ) {
			$passing_grade = $url = '';

			$final_quiz = $course->get_final_quiz();

			if ( $final_quiz ) {
				$passing_grade = get_post_meta( $final_quiz, '_lp_passing_grade', true );

				$url = get_edit_post_link( $final_quiz ) . '#_lp_passing_grade';

				$final_quizz_passing = '
					<div class="lp-metabox-evaluate-final_quiz">
						<div class="lp-metabox-evaluate-final_quiz__message">'
					. sprintf( esc_html__( 'Passing Grade: %s', 'learpress' ), $passing_grade . '%' ) .
					' - '
					. sprintf( esc_html__( 'Edit: %s', 'learnpress' ), '<a href="' . esc_url( $url ) . '">' . get_the_title( $final_quiz ) . '</a>' ) .
					'</div>
					</div>
				';
			}
		}

		return apply_filters(
			'lp/course/meta-box/fields/assessment',
			array(
				'_lp_course_result'     => new LP_Meta_Box_Radio_Field(
					esc_html__( 'Evaluation', 'learnpress' ),
					$course_result_desc,
					'evaluate_lesson',
					array(
						'options' => learn_press_course_evaluation_methods( $thepostid, '', $final_quizz_passing ),
					)
				),
				'_lp_passing_condition' => new LP_Meta_Box_Text_Field(
					esc_html__( 'Passing Grade(%)', 'learnpress' ),
					esc_html__( 'The condition that must be achieved to finish the course.', 'learnpress' ),
					'80',
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
							'max'  => '100',
						),
						'style'             => 'width: 60px;',
					)
				),
			)
		);
	}

	public function extra( $thepostid ) {
		return apply_filters(
			'lp/course/meta-box/fields/extra',
			array(
				'_lp_requirements'     => new LP_Meta_Box_Extra_Field(
					esc_html__( 'Requirements', 'learnpress' ),
					'',
					array()
				),
				'_lp_target_audiences' => new LP_Meta_Box_Extra_Field(
					esc_html__( 'Target Audience', 'learnpress' ),
					'',
					array()
				),
				'_lp_key_features'     => new LP_Meta_Box_Extra_Field(
					esc_html__( 'Key Features', 'learnpress' ),
					'',
					array()
				),
				'_lp_faqs'             => new LP_Meta_Box_Extra_Faq_Field(
					esc_html__( 'FAQs', 'learnpress' ),
					'',
					array()
				),
			)
		);
	}

	public function output( $post ) {
		parent::output( $post );
		?>

		<div class="lp-meta-box lp-meta-box--course">
			<div class="lp-meta-box__inner">
				<div class="lp-meta-box__course-tab">
					<ul class="lp-meta-box__course-tab__tabs">
						<?php
						foreach ( $this->metabox( $post->ID ) as $key => $tab ) {
							if ( $key === 'author' && ! is_super_admin() ) {
								continue;
							}

							$class_tab = '';

							if ( isset( $tab['class'] ) ) {
								$class_tab = implode( ' ', (array) $tab['class'] );
							}
							?>
							<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( $class_tab ); ?>">
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
						<?php foreach ( $this->metabox( $post->ID ) as $key => $tab_content ) { ?>
							<?php
							if ( $key === 'author' && ! is_super_admin() ) {
								continue;
							}
							?>
							<?php if ( isset( $tab_content['content'] ) ) { ?>
								<div id="<?php echo esc_attr( $tab_content['target'] ); ?>" class="lp-meta-box-course-panels">
									<?php
									do_action( 'learnpress/course-settings/before-' . $key );

									foreach ( $tab_content['content'] as $meta_key => $object ) {
										if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
											$object->id = $meta_key;
											echo $object->output( $post->ID );
										}
									}

									do_action( 'learnpress/course-settings/after-' . $key );
									?>
								</div>
								<?php
							}
						}

						do_action( 'lp_course_data_setting_tab_content', $post );
						?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	public function save( $post_id ) {
		if ( ! empty( $this->metabox( $post_id ) ) ) {
			foreach ( $this->metabox( $post_id ) as $key => $tab_content ) {
				if ( isset( $tab_content['content'] ) ) {
					foreach ( $tab_content['content'] as $meta_key => $object ) {
						if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
							$object->id = $meta_key;
							$object->save( $post_id );
						}
					}
				}
			}
		}

		$course = learn_press_get_course( $post_id );

		$evalution         = isset( $_POST['_lp_course_result'] ) ? LP_Helper::sanitize_params_submitted( $_POST['_lp_course_result'] ) : '';
		$passing_condition = isset( $_POST['_lp_passing_condition'] ) ? absint( wp_unslash( $_POST['_lp_passing_condition'] ) ) : 0;

		// Update Final Quiz. - Nhamdv
		if ( $evalution == 'evaluate_final_quiz' ) {
			$items = $course->get_item_ids();

			if ( $items ) {
				foreach ( $items as $item ) {
					if ( learn_press_get_post_type( $item ) === LP_QUIZ_CPT ) {
						$final_quiz = $item;
					}
				}
			}

			if ( isset( $final_quiz ) ) {
				update_post_meta( $post_id, '_lp_final_quiz', $final_quiz );
			} else {
				delete_post_meta( $post_id, '_lp_final_quiz' );
			}
		} else {
			delete_post_meta( $post_id, '_lp_final_quiz' );
		}

		$author = isset( $_POST['_lp_course_author'] ) ? wp_unslash( $_POST['_lp_course_author'] ) : '';

		if ( ! empty( $author ) ) {
			global $wpdb;

			$wpdb->update( $wpdb->posts, array( 'post_author' => $author ), array( 'ID' => $post_id ) );
		}
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

	/**
	 * Get instance
	 *
	 * @return LP_Meta_Box_Course
	 */
	public static function instance(): LP_Meta_Box_Course {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Meta_Box_Course::instance();
