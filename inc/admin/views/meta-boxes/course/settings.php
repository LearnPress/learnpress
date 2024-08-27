<?php

use LearnPress\Helpers\Config;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;

class LP_Meta_Box_Course extends LP_Meta_Box {
	/**
	 * Instance
	 *
	 * @var null|LP_Meta_Box_Course
	 */
	private static $instance = null;

	public $post_type = LP_COURSE_CPT;

	/**
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function add_meta_boxes( $post ) {
		$course                   = CourseModel::find( $post->ID, true );
		$is_enable_offline_course = false;
		if ( $course instanceof CourseModel ) {
			$is_enable_offline_course = $course->get_meta_value_by_key( CoursePostModel::META_KEY_OFFLINE_COURSE, 'no' ) === 'yes';
		}

		add_meta_box(
			'course-settings',
			esc_html__( 'Course Settings', 'learnpress' ),
			array( $this, 'output' ),
			$this->post_type,
			'normal',
			'high'
		);

		if ( ! $is_enable_offline_course ) {
			add_meta_box(
				'course-editor',
				esc_html__( 'Curriculum', 'learnpress' ),
				array( $this, 'admin_editor' ),
				$this->post_type,
				'normal',
				'high'
			);
		}
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
				'offline'    => array(
					'label'    => esc_html__( 'Offline Course', 'learnpress' ),
					'target'   => 'offline_course_data',
					'icon'     => 'dashicons-welcome-view-site',
					'priority' => 10,
					'content'  => $this->tab_offline( $post_id ),
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
				'material'   => array(
					'label'    => esc_html__( 'Downloadable Materials', 'learnpress' ),
					'target'   => 'downloadable_material_data',
					'icon'     => 'dashicons-download',
					'priority' => 60,
					'content'  => $this->lp_material( $post_id ),
				),
			)
		);

		$tabs = apply_filters( 'learnpress/course/metabox/tabs', $tabs, $post_id );

		uasort( $tabs, array( __CLASS__, 'data_tabs_sort' ) );

		return $tabs;
	}

	public function general( $post_id ) {
		$course                 = CourseModel::find( $post_id, true );
		$repurchase_option_desc = sprintf( '1. %s', __( 'Reset course progress: The course progress and results of student will be removed.' ) );
		$repurchase_option_desc .= '<br/>' . sprintf( '2. %s', __( 'Keep course progress: The course progress and results of student will remain.' ) );
		$repurchase_option_desc .= '<br/>' . sprintf( '3. %s', __( 'Open popup: The student can decide whether their course progress will be reset with the confirm popup.' ) );
		$max_students_desc      = esc_html__( 'The maximum number of students that can join a course. Set 0 for unlimited.', 'learnpress' );
		$max_students_desc      .= '<br/>' . esc_html__( 'Not apply for case "No enroll requirement".', 'learnpress' );

		$is_enable_allow_course_repurchase = false;
		if ( $course instanceof CourseModel ) {
			$is_enable_allow_course_repurchase = $course->get_meta_value_by_key( CoursePostModel::META_KEY_ALLOW_COURSE_REPURCHASE, 'no' ) === 'yes';
		}

		return apply_filters(
			'lp/course/meta-box/fields/general',
			array(
				'_lp_duration'                 => new LP_Meta_Box_Duration_Field(
					esc_html__( 'Duration', 'learnpress' ),
					esc_html__( 'Set to 0 for the lifetime access.', 'learnpress' ),
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
					esc_html__( 'When the duration expires, the course is blocked.', 'learnpress' ),
					'no'
				),
				'_lp_block_finished'           => new LP_Meta_Box_Checkbox_Field(
					'',
					esc_html__( 'Block the course after the student finished this course.', 'learnpress' ),
					'yes'
				),
				'_lp_allow_course_repurchase'  => new LP_Meta_Box_Checkbox_Field(
					__( 'Allow Repurchase', 'learnpress' ),
					esc_html__( 'Allow users to repurchase this course after it has been finished or blocked (Do not apply to free courses or Create Order manual).', 'learnpress' ),
					'no'
				),
				'_lp_course_repurchase_option' => new LP_Meta_Box_Select_Field(
					esc_html__( 'Repurchase action', 'learnpress' ),
					$repurchase_option_desc,
					'reset',
					array(
						'options'    => array(
							'reset' => esc_html__( 'Reset course progress', 'learnpress' ),
							'keep'  => esc_html__( 'Keep course progress', 'learnpress' ),
							'popup' => esc_html__( 'Open popup', 'learnpress' ),
						),
						'dependency' => [
							'name'       => '_lp_allow_course_repurchase',
							'is_disable' => ! $is_enable_allow_course_repurchase
						],
						//'show'    => array( '_lp_allow_course_repurchase', '=', 'yes' ), // use 'show' or 'hide'
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
					esc_html__( 'How many students have taken this course?', 'learnpress' ),
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
					$max_students_desc,
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
					esc_html__( 'The number of times a user can learn again from this course. To disable, set to 0.', 'learnpress' ),
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
					esc_html__( 'Allow showing the finish button when the student has completed all items but has not passed the course assessment yet.', 'learnpress' ),
					'yes'
				),
				'_lp_featured'                 => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Featured list', 'learnpress' ),
					esc_html__( 'Add the course to the Featured List.', 'learnpress' ),
					'no'
				),
				'_lp_featured_review'          => new LP_Meta_Box_Textarea_Field(
					esc_html__( 'Featured review', 'learnpress' ),
					esc_html__( 'A good review to promote the course.', 'learnpress' )
				),
				'_lp_external_link_buy_course' => new LP_Meta_Box_Text_Field(
					esc_html__( 'External link', 'learnpress' ),
					esc_html__( 'Normally used for offline classes. Ex: link to a contact page. Format: https://google.com', 'learnpress' ),
					'',
					array(
						'desc_tip' => 'You can apply for case: user register form.<br> You accept for user can learn courses by add manual order on backend',
					)
				),
			),
			$post_id
		);
	}

	/**
	 * Tab setting offline course
	 *
	 * @param $post_id
	 *
	 * @return array
	 * @since 4.2.7
	 * @version 1.0.0
	 */
	public function tab_offline( $post_id ): array {
		$course = CourseModel::find( $post_id, true );

		$is_offline_course = false;
		if ( $course instanceof CourseModel ) {
			$is_offline_course = $course->is_offline();
		}

		return apply_filters(
			'lp/course/meta-box/fields/offline',
			array(
				CoursePostModel::META_KEY_OFFLINE_COURSE       => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'Enable offline course', 'learnpress' ),
					esc_html__(
						'When you enable the offline course feature, the system will disable certain online course functions,
						such as curriculum, finish button, re-take course, block content, repurchase.
						After checking the checkbox, make sure to click the "Update" button to apply the changes successfully.',
						'learnpress'
					),
					'no'
				),
				CoursePostModel::META_KEY_OFFLINE_LESSON_COUNT => new LP_Meta_Box_Text_Field(
					esc_html__( 'Lessons', 'learnpress' ),
					esc_html__( 'Total lessons of the course.', 'learnpress' ),
					10,
					[
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'dependency'        => [
							'name'       => '_lp_offline_course',
							'is_disable' => ! $is_offline_course
						],
					]
				),
				CoursePostModel::META_KEY_DELIVER              => new LP_Meta_Box_Select_Field(
					esc_html__( 'Delivery Type', 'learnpress' ),
					esc_html__( 'How your content is conveyed to students.', 'learnpress' ),
					'private_1_1',
					[
						'options'    => Config::instance()->get( 'course-deliver-type' ),
						'dependency' => [
							'name'       => '_lp_offline_course',
							'is_disable' => ! $is_offline_course
						],
					]
				),
				CoursePostModel::META_KEY_ADDRESS              => new LP_Meta_Box_Text_Field(
					esc_html__( 'Address', 'learnpress' ),
					esc_html__( 'You can enter the physical address of your class or specify the meeting method (e.g., Zoom, Google Meet, etc.).', 'learnpress' ),
					'',
					[
						'dependency' => [
							'name'       => '_lp_offline_course',
							'is_disable' => ! $is_offline_course
						],
					]
				),
			),
			$post_id
		);
	}

	/**
	 * Setting course price
	 *
	 * @param $post_id
	 *
	 * @return array
	 * @since 4.1.5
	 * @version 1.0.0
	 * @author tungnx
	 */
	public function lp_price( $post_id ): array {
		$key_exists    = LP_Database::getInstance()->check_key_postmeta_exists( $post_id, '_lp_regular_price' );
		$price         = get_post_meta( $post_id, '_lp_price', true );
		$regular_price = $key_exists ? get_post_meta( $post_id, '_lp_regular_price', true ) : $price;
		$sale_price    = get_post_meta( $post_id, '_lp_sale_price', true );

		$is_enable_no_required_enroll = get_post_meta( $post_id, '_lp_no_required_enroll', true ) === 'yes' ? 1 : 0;

		return apply_filters(
			'lp/course/meta-box/fields/price',
			array(
				'_lp_regular_price'      => new LP_Meta_Box_Text_Field(
					esc_html__( 'Regular price', 'learnpress' ),
					sprintf( __( 'Set a regular price (<strong>%s</strong>). Leave it blank for <strong>Free</strong>.', 'learnpress' ), learn_press_get_currency() ),
					$regular_price,
					array(
						'type_input'        => 'text',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
						'style'             => 'width: 70px;',
						'class'             => 'lp_meta_box_regular_price',
						'dependency'        => [
							'name'       => '_lp_no_required_enroll',
							'is_disable' => $is_enable_no_required_enroll
						],
					)
				),
				'_lp_sale_price'         => new LP_Meta_Box_Text_Field(
					esc_html__( 'Sale price', 'learnpress' ),
					'<a href="#" class="lp_sale_price_schedule">' . esc_html__( 'Schedule', 'learnpress' ) . '</a>',
					$sale_price,
					array(
						'type_input'        => 'text',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
						'style'             => 'width: 70px;',
						'class'             => 'lp_meta_box_sale_price',
						'dependency'        => [
							'name'       => '_lp_no_required_enroll',
							'is_disable' => $is_enable_no_required_enroll
						],
					)
				),
				'_lp_sale_start'         => new LP_Meta_Box_Date_Field(
					esc_html__( 'Sale start dates', 'learnpress' ),
					'',
					'',
					array(
						'wrapper_class' => 'lp_sale_start_dates_fields',
						'placeholder'   => _x( 'From&hellip;', 'placeholder', 'learnpress' ),
						'dependency'    => [
							'name'       => '_lp_no_required_enroll',
							'is_disable' => $is_enable_no_required_enroll
						],
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
						'dependency'    => [
							'name'       => '_lp_no_required_enroll',
							'is_disable' => $is_enable_no_required_enroll
						],
					)
				),
				'_lp_no_required_enroll' => new LP_Meta_Box_Checkbox_Field(
					esc_html__( 'There is no enrollment requirement', 'learnpress' ),
					esc_html__( 'Students can see the content of all course items and take the quiz without logging in.', 'learnpress' ),
					'no'
				),
			),
			$post_id
		);
	}

	public function author( $thepostid ) {
		$post = get_post( $thepostid );

		$author_id = $post ? $post->post_author : get_current_user_id();

		$options = array();
		// Code old only use for addon Frontend Editor v4.0.4
		// Code old only use for addon Co-Instructor v4.0.2
		$can_get_options_users = false;
		if ( class_exists( 'LP_Addon_Frontend_Editor_Preload' )
			&& defined( 'LP_ADDON_FRONTEND_EDITOR_VER' )
			&& version_compare( LP_ADDON_FRONTEND_EDITOR_VER, '4.0.5', '<' ) ) {
			$can_get_options_users = true;
		}

		if ( $can_get_options_users ) {
			$author_roles = array( ADMIN_ROLE, LP_TEACHER_ROLE );
			$author_roles = apply_filters( 'learn_press_course_author_role_meta_box', $author_roles );
			$authors      = get_users( [ 'role__in' => $author_roles ] );

			/**
			 * @var WP_User $author
			 */
			foreach ( $authors as $author ) {
				$options[ $author->ID ] = $author->display_name . ' (#' . $author->ID . ')';
			}
		}
		// Code old only use for addon Frontend Editor v4.0.4

		$data_struct = [
			'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
			'dataSendApi' => [
				'role_in' => ADMIN_ROLE . ',' . LP_TEACHER_ROLE,
			],
			'dataType'    => 'users',
			'keyGetValue' => [
				'value'      => 'ID',
				'text'       => '{{display_name}}(#{{ID}})',
				'key_render' => [
					'display_name' => 'display_name',
					'user_email'   => 'user_email',
					'ID'           => 'ID',
				],
			],
			'setting'     => [
				'plugins' => array(),
			],
		];

		return apply_filters(
			'lp/course/meta-box/fields/author',
			array(
				'post_author' => new LP_Meta_Box_Select_Field(
					esc_html__( 'Author', 'learnpress' ),
					'',
					$author_id,
					array(
						'options'           => $options,
						'style'             => 'min-width:200px;',
						'tom_select'        => true,
						'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct ) ) ],
					)
				),
			)
		);
	}

	public function lp_material( $thepostid ) {
		return apply_filters(
			'lp/course/meta-box/fields/material',
			array(
				'_lp_course_material' => new LP_Meta_Box_Material_Fields(),
			)
		);
	}

	public function assessment( $thepostid ) {
		$post = get_post( $thepostid );

		$course_result_desc = '';
		$course_results     = get_post_meta( $thepostid, '_lp_course_result', true );

		$course_result_desc .= __( 'The method of evaluating a student\'s performance in a course.', 'learnpress' );
		$course_result_desc .= sprintf(
			'<br/><i style="color: red">%s</i>',
			__( 'Note: changing the evaluation type will affect the assessment results of student learning.', 'learnpress' )
		);

		if ( $course_results == 'evaluate_final_quiz' && ! get_post_meta( $thepostid, '_lp_final_quiz', true ) ) {
			$course_result_desc .= __( '<br /><strong>Note! </strong>There is no final quiz in the course. Please add a final quiz.', 'learnpress' );
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
					. sprintf( esc_html__( 'Edit: %s', 'learnpress' ), '<a href="' . esc_url_raw( $url ) . '">' . get_the_title( $final_quiz ) . '</a>' ) .
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
					esc_html__( 'The conditions that must be achieved to finish the course.', 'learnpress' ),
					'80',
					array(
						'type_input'        => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
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
								<div id="<?php echo esc_attr( $tab_content['target'] ); ?>"
									 class="lp-meta-box-course-panels">
									<?php
									do_action( 'learnpress/course-settings/before-' . $key );

									foreach ( $tab_content['content'] as $meta_key => $object ) {
										if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
											$object->id = $meta_key;
											$output     = $object->output( $post->ID );
											if ( ! empty( $output ) ) {
												echo wp_kses_post( $output );
											}
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

	/**
	 * Template Editor Curriculum.
	 *
	 * @return void
	 */
	public function admin_editor() {
		learn_press_admin_view( 'course/editor' );
	}

	/*public function save( $post_id ) {
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

		// Check price is valid.
		$price_regular = LP_Request::get( '_lp_regular_price', 0, 'float' );
		$price_sale    = LP_Request::get( '_lp_sale_price', 0, 'float' );
		if ( $price_regular <= 0 ) {
			$price_sale = 0;
			update_post_meta( $post_id, '_lp_regular_price', '' );
		}

		if ( $price_sale >= $price_regular || $price_sale < 0 ) {
			update_post_meta( $post_id, '_lp_sale_price', '' );
		}
		// End check price.

		$evaluation = LP_Request::get_param( '_lp_course_result', '', 'text', 'post' );
		//$passing_condition = isset( $_POST['_lp_passing_condition'] ) ? absint( wp_unslash( $_POST['_lp_passing_condition'] ) ) : 0;

		// Update Final Quiz. - Nhamdv
		if ( $evaluation == 'evaluate_final_quiz' ) {
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
	}*/

	private static function data_tabs_sort( $a, $b ) {
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return - 1;
		}

		if ( $a['priority'] === $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? - 1 : 1;
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
