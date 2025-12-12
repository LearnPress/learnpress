<?php
/**
 * class CourseBuilderAjax
 *
 * @since 4.3
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\CourseBuilder\BuilderEditCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderTabCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderTabLessonTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderTabQuestionTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderTabQuizTemplate;
use LP_Course_Cache;
use LP_Course_CURD;
use LP_Helper;
use LP_Lesson_CURD;
use LP_Question_CURD;
use LP_Quiz_CURD;
use LP_REST_Response;
use stdClass;
use Throwable;

class CourseBuilderAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.3
	 * @version 1.0.0
	 */
	public static function check_valid_course() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		$params       = LP_Helper::json_decode( $params, true );
		$course_id    = ! empty( $params['course_id'] ) ? (int) $params['course_id'] : 0;
		$course_model = CourseModel::find( $course_id, true );
		if ( empty( $course_model ) ) {
			$params['insert']       = true;
			$params['course_model'] = '';
		} else {
			$params['insert']       = false;
			$params['course_model'] = $course_model;
		}

		return $params;
	}

	public static function check_valid_lesson() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		$params       = LP_Helper::json_decode( $params, true );
		$lesson_id    = ! empty( $params['lesson_id'] ) ? (int) $params['lesson_id'] : 0;
		$lesson_model = LessonPostModel::find( $lesson_id, true );
		if ( empty( $lesson_model ) ) {
			$params['insert']       = true;
			$params['lesson_model'] = '';
		} else {
			$params['insert']       = false;
			$params['lesson_model'] = $lesson_model;
		}

		return $params;
	}

	public static function check_valid_quiz() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		$params     = LP_Helper::json_decode( $params, true );
		$quiz_id    = ! empty( $params['quiz_id'] ) ? (int) $params['quiz_id'] : 0;
		$quiz_model = QuizPostModel::find( $quiz_id, true );
		if ( empty( $quiz_model ) ) {
			$params['insert'] = true;
		} else {
			$params['insert']     = false;
			$params['quiz_model'] = $quiz_model;
		}

		return $params;
	}

	public static function check_valid_question() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		$params         = LP_Helper::json_decode( $params, true );
		$question_id    = ! empty( $params['question_id'] ) ? (int) $params['question_id'] : 0;
		$question_model = QuestionPostModel::find( $question_id, true );
		if ( empty( $question_model ) ) {
			$params['insert'] = true;
		} else {
			$params['insert']         = false;
			$params['question_model'] = $question_model;
		}

		return $params;
	}

	/**
	 * Save Course.
	 *
	 * @since 4.3
	 * @version 1.0.0
	 */
	public function save_courses() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data      = self::check_valid_course();
			$course_id = $data['course_id'] ?? 0;
			$settings  = $data['course_settings'] ?? false;
			$insert    = $data['insert'];

			if ( $insert ) {
				$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
				$tags       = ! empty( $data['course_tags'] ) ? array_map( 'absint', explode( ',', $data['course_tags'] ) ) : array();

				$course_id = wp_insert_post(
					array(
						'post_type'    => LP_COURSE_CPT,
						'post_title'   => sanitize_text_field( $data['course_title'] ?? '' ),
						'post_content' => wp_unslash( $data['course_description'] ?? '' ),
						'post_status'  => ! empty( $data['course_status'] ) ? sanitize_text_field( $data['course_status'] ) : 'publish',
						'tax_input'    => array(
							'course_category' => $categories,
							'course_tag'      => $tags,
						),
					),
					true
				);

				if ( is_wp_error( $course_id ) ) {
					throw new Exception( $course_id->get_error_message() );
				}

				// Load the newly created course as CourseModel
				$courseModel = CourseModel::find( $course_id, true );
				if ( ! $courseModel ) {
					throw new Exception( __( 'Failed to load course model', 'learnpress' ) );
				}
			} else {
				$courseModel = $data['course_model'];

				$co_instructor_ids = $courseModel->get_meta_value_by_key( '_lp_co_teacher', [] );
				if ( absint( $courseModel->post_author ) !== get_current_user_id() &&
					! current_user_can( 'manage_options' ) &&
					! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress' ) );
				}

				$courseModel->post_status = ! empty( $data['course_status'] ) ? sanitize_text_field( $data['course_status'] ) : 'publish';

				if ( ! empty( $data['course_title'] ) ) {
					$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
					$tags       = ! empty( $data['course_tags'] ) ? array_map( 'absint', explode( ',', $data['course_tags'] ) ) : array();

					$courseModel->post_title   = sanitize_text_field( $data['course_title'] );
					$courseModel->post_content = wp_unslash( $data['course_description'] ?? '' );

					wp_set_post_terms( $courseModel->ID, $categories, 'course_category' );
					wp_set_post_terms( $courseModel->ID, $tags, 'course_tag' );
				}

				$course_id = $courseModel->ID;
			}

			if ( ! empty( $data['course_thumbnail_id'] ) ) {
				set_post_thumbnail( $course_id, absint( $data['course_thumbnail_id'] ) );
			} else {
				delete_post_thumbnail( $course_id );
			}

			if ( $settings ) {
				$this->save_course_settings_to_model( $courseModel, $data );
			}

			if ( ! empty( $courseModel->meta_data ) ) {
				$coursePostModel = new CoursePostModel( $courseModel );
				foreach ( $courseModel->meta_data as $meta_key => $meta_value ) {
					$coursePostModel->save_meta_value_by_key( $meta_key, $meta_value );
				}
				$coursePostModel->save();
			}

			$response->status              = 'success';
			$response->message             = $insert ? __( 'Insert course successfully!', 'learnpress' ) : __( 'Update course successfully!', 'learnpress' );
			$response->data->status        = $data['course_status'];
			$response->data->button_title  = $data['course_status'] === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );
			$response->data->course_id_new = $insert ? $course_id : '';

			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Save all course settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_course_settings_to_model( CourseModel &$courseModel, array $data ) {
		// General settings
		$this->save_general_settings_to_model( $courseModel, $data );

		// Offline course settings
		$this->save_offline_settings_to_model( $courseModel, $data );

		// Price settings
		$this->save_price_settings_to_model( $courseModel, $data );

		// Extra info settings
		$this->save_extra_settings_to_model( $courseModel, $data );

		// Assessment settings
		$this->save_assessment_settings_to_model( $courseModel, $data );

		// Author settings
		$this->save_author_settings_to_model( $courseModel, $data );
	}

	/**
	 * Save general settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_general_settings_to_model( CourseModel &$courseModel, array $data ) {
		if ( isset( $data['_lp_duration'] ) ) {
			$duration_value = ! empty( $data['_lp_duration'] ) ? str_replace( ',', ' ', $data['_lp_duration'] ) : '0 minute';
			$explode        = explode( ' ', $duration_value );
			$number         = (float) $explode[0] < 0 ? 0 : absint( $explode[0] );
			$unit           = $explode[1] ?? 'minute';

			$courseModel->meta_data->{CoursePostModel::META_KEY_DURATION} = $number . ' ' . $unit;
		}

		$checkbox_fields = [
			'_lp_block_expire_duration'   => CoursePostModel::META_KEY_BLOCK_EXPIRE_DURATION,
			'_lp_block_finished'          => CoursePostModel::META_KEY_BLOCK_FINISH,
			'_lp_allow_course_repurchase' => CoursePostModel::META_KEY_ALLOW_COURSE_REPURCHASE,
			'_lp_has_finish'              => CoursePostModel::META_KEY_HAS_FINISH,
			'_lp_featured'                => CoursePostModel::META_KEY_FEATURED,
		];

		foreach ( $checkbox_fields as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) {
				$courseModel->meta_data->{$meta_key} = $data[ $key ] === 'yes' ? 'yes' : '';
			}
		}

		$simple_fields = [
			'_lp_course_repurchase_option' => CoursePostModel::META_KEY_COURSE_REPURCHASE_OPTION,
			'_lp_level'                    => CoursePostModel::META_KEY_LEVEL,
			'_lp_featured_review'          => CoursePostModel::META_KEY_FEATURED_REVIEW,
			'_lp_external_link_buy_course' => CoursePostModel::META_KEY_EXTERNAL_LINK_BY_COURSE,
		];

		foreach ( $simple_fields as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) {
				$courseModel->meta_data->{$meta_key} = sanitize_text_field( $data[ $key ] );
			}
		}

		$numeric_fields = [
			'_lp_students'     => CoursePostModel::META_KEY_STUDENTS,
			'_lp_max_students' => CoursePostModel::META_KEY_MAX_STUDENTS,
			'_lp_retake_count' => CoursePostModel::META_KEY_RETAKE_COUNT,
		];

		foreach ( $numeric_fields as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) {
				$value                               = absint( $data[ $key ] );
				$courseModel->meta_data->{$meta_key} = $value;
			}
		}
	}

	/**
	 * Save offline course settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_offline_settings_to_model( CourseModel &$courseModel, array $data ) {
		if ( isset( $data['_lp_offline_course'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_OFFLINE_COURSE} = $data['_lp_offline_course'] === 'yes' ? 'yes' : '';
		}

		if ( isset( $data['_lp_offline_lesson_count'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_OFFLINE_LESSON_COUNT} = absint( $data['_lp_offline_lesson_count'] );
		}

		if ( isset( $data['_lp_deliver_type'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_DELIVER} = sanitize_text_field( $data['_lp_deliver_type'] );
		}

		if ( isset( $data['_lp_address'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_ADDRESS} = sanitize_text_field( $data['_lp_address'] );
		}
	}

	/**
	 * Save price settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_price_settings_to_model( CourseModel &$courseModel, array $data ) {
		// Regular price
		if ( isset( $data['_lp_regular_price'] ) ) {
			$regular_price = floatval( $data['_lp_regular_price'] );
			if ( $regular_price < 0 ) {
				$regular_price = '';
			}
			$courseModel->meta_data->{CoursePostModel::META_KEY_REGULAR_PRICE} = $regular_price;
		}

		// Sale price
		if ( isset( $data['_lp_sale_price'] ) ) {
			$sale_price    = $data['_lp_sale_price'] !== '' ? floatval( $data['_lp_sale_price'] ) : '';
			$regular_price = $courseModel->get_regular_price();

			if ( $sale_price !== '' && $sale_price > $regular_price ) {
				$sale_price = '';
			}
			$courseModel->meta_data->{CoursePostModel::META_KEY_SALE_PRICE} = $sale_price;
		}

		// Sale dates
		if ( isset( $data['_lp_sale_start'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_SALE_START} = sanitize_text_field( $data['_lp_sale_start'] );
		}

		if ( isset( $data['_lp_sale_end'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_SALE_END} = sanitize_text_field( $data['_lp_sale_end'] );
		}

		// Price prefix/suffix
		if ( isset( $data['_lp_price_prefix'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_PRICE_PREFIX} = sanitize_text_field( $data['_lp_price_prefix'] );
		}

		if ( isset( $data['_lp_price_suffix'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_PRICE_SUFFIX} = sanitize_text_field( $data['_lp_price_suffix'] );
		}

		// No required enroll
		if ( isset( $data['_lp_no_required_enroll'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_NO_REQUIRED_ENROLL} = $data['_lp_no_required_enroll'] === 'yes' ? 'yes' : '';
		}

		// Calculate and set the actual price
		$courseModel->price_to_sort = $courseModel->get_price();

		// Set is_sale flag
		$has_sale             = $courseModel->has_sale_price();
		$courseModel->is_sale = $has_sale ? 1 : 0;
	}

	/**
	 * Save extra info settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_extra_settings_to_model( CourseModel &$courseModel, array $data ) {
		// Requirements
		if ( isset( $data['_lp_requirements'] ) ) {
			$requirements = ! empty( $data['_lp_requirements'] ) ? explode( ',', $data['_lp_requirements'] ) : [];
			$requirements = array_filter(
				$requirements,
				function ( $item ) {
					return ! is_null( $item ) && $item !== '';
				}
			);
			$courseModel->meta_data->{CoursePostModel::META_KEY_REQUIREMENTS} = array_map( 'sanitize_text_field', array_values( $requirements ) );
		}

		if ( isset( $data['_lp_target_audiences'] ) ) {
			$target_audiences = ! empty( $data['_lp_target_audiences'] ) ? explode( ',', $data['_lp_target_audiences'] ) : [];
			$target_audiences = array_filter(
				$target_audiences,
				function ( $item ) {
					return ! is_null( $item ) && $item !== '';
				}
			);
			$courseModel->meta_data->{CoursePostModel::META_KEY_TARGET} = array_map( 'sanitize_text_field', array_values( $target_audiences ) );
		}

		if ( isset( $data['_lp_key_features'] ) ) {
			$key_features = ! empty( $data['_lp_key_features'] ) ? explode( ',', $data['_lp_key_features'] ) : [];
			$key_features = array_filter(
				$key_features,
				function ( $item ) {
					return ! is_null( $item ) && $item !== '';
				}
			);
			$courseModel->meta_data->{CoursePostModel::META_KEY_FEATURES} = array_map( 'sanitize_text_field', array_values( $key_features ) );
		}

		// FAQs
		if ( isset( $data['_lp_faqs_question'] ) ) {
			$questions = ! empty( $data['_lp_faqs_question'] ) ? explode( ',', $data['_lp_faqs_question'] ) : [];
			$answers   = ! empty( $data['_lp_faqs_answer'] ) ? explode( ',', $data['_lp_faqs_answer'] ) : [];
			$faqs      = [];

			if ( ! empty( $questions ) ) {
				foreach ( $questions as $index => $question ) {
					$clean_question = trim( $question );
					if ( ! empty( $clean_question ) ) {
						$answer_content = $answers[ $index ] ?? '';
						$faqs[]         = [ sanitize_text_field( $clean_question ), wp_kses_post( $answer_content ) ];
					}
				}
			}
			$courseModel->meta_data->{CoursePostModel::META_KEY_FAQS} = $faqs;
		}
	}

	/**
	 * Save assessment settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_assessment_settings_to_model( CourseModel &$courseModel, array $data ) {
		// Course result evaluation type
		if ( isset( $data['_lp_course_result'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_EVALUATION_TYPE} = sanitize_text_field( $data['_lp_course_result'] );
		}

		// Passing condition
		if ( isset( $data['_lp_passing_condition'] ) ) {
			$passing_condition = floatval( $data['_lp_passing_condition'] );
			if ( $passing_condition < 0 ) {
				$passing_condition = 0;
			}
			$courseModel->meta_data->{CoursePostModel::META_KEY_PASSING_CONDITION} = $passing_condition;
		}
	}

	/**
	 * Save author settings to CourseModel
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 */
	protected function save_author_settings_to_model( CourseModel &$courseModel, array $data ) {
		if ( ! isset( $data['_post_author'] ) ) {
			return;
		}

		$new_author_id = absint( $data['_post_author'] );
		if ( $new_author_id <= 0 || $new_author_id === (int) $courseModel->post_author ) {
			return;
		}
		$courseModel->meta_data->_post_author = $new_author_id;
		$courseModel->post_author             = $new_author_id;
	}

	public function update_setting( $id, $settings ) {
		switch ( $settings['type'] ) {
			case 'text':
				$value = sanitize_text_field( wp_unslash( $settings['value'] !== false ? $settings['value'] : $settings['default'] ) );

				if ( isset( $settings['extra']['type_input'] ) && $settings['extra']['type_input'] === 'number' && $value !== '' ) {
					$value = floatval( $value );

					if ( $settings['extra']['custom_attributes']['step'] === '1' ) {
						$value = (int) $value;
					}

					if ( isset( $settings['extra']['custom_attributes']['min'] ) ) {
						$min = floatval( $settings['extra']['custom_attributes']['min'] );

						if ( $value < $min ) {
							$value = $min;
						}

						if ( floatval( $settings['extra']['custom_attributes']['min'] ) >= 0 ) {
							$value = abs( $value );
						}
					}

					if ( isset( $settings['extra']['custom_attributes']['max'] ) ) {
						$max = floatval( $settings['extra']['custom_attributes']['max'] );

						if ( $value > $max ) {
							$value = $max;
						}
					}
				}

				update_post_meta( $id, $settings['id'], $value );
				break;
			case 'textarea':
				update_post_meta( $id, $settings['id'], wp_kses_post( wp_unslash( $settings['value'] !== false ? $settings['value'] : $settings['default'] ) ) );
				break;
			case 'duration':
				if ( $settings['value'] !== false && $settings['value'] !== '' ) {
					$value = sanitize_text_field( wp_unslash( $settings['value'] ) );

					$explode = explode( ' ', $value );
					$number  = (float) $explode[0] < 0 ? 0 : absint( $explode[0] );
					$unit    = $explode[1] ?? $settings['extra']['default_time'];

					$value = $number . ' ' . $unit;
				} else {
					$value = absint( wp_unslash( $settings['default'] ) ) . ' ' . $settings['extra']['default_time'];
				}

				update_post_meta( $id, $settings['id'], $value );
				break;
			case 'extra':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? $settings['value'] : $settings['default'] );
				$value = array_filter(
					$value,
					function ( $item ) {
						return ! is_null( $item ) && $item !== '';
					}
				);

				update_post_meta( $id, $settings['id'], array_map( 'sanitize_text_field', array_values( $value ) ) );
				break;

			case 'file':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? wp_unslash( array_map( 'absint', $settings['value'] ) ) : $settings['default'] );

				update_post_meta( $id, $settings['id'], $value );
				break;
			case 'autocomplete':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? wp_unslash( array_map( 'absint', $settings['value'] ) ) : $settings['default'] );
				$value = apply_filters( 'learn-press/admin/metabox/autocomplete/' . $settings['id'] . '/save', $value, wp_unslash( $settings['value'] ), $id );

				update_post_meta( $id, $settings['id'], $value );
				break;
			case 'select':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? wp_unslash( $settings['value'] ) : $settings['default'] );

				if ( ! empty( $settings['extra']['custom_save'] ) ) {
					do_action( 'learnpress/admin/metabox/select/save', $settings['id'], $value, $id );
				} else {
					if ( ! empty( $settings['extra']['multil_meta'] ) ) {
						$get_values = get_post_meta( $id, $settings['id'] ) ?? array();
						$new_values = $value;

						$array_get_values = ! empty( $get_values ) ? array_values( $get_values ) : array();
						$array_new_values = ! empty( $new_values ) ? array_values( $new_values ) : array();

						$del_val = array_diff( $array_get_values, $array_new_values );
						$new_val = array_diff( $array_new_values, $array_get_values );

						foreach ( $del_val as $level_id ) {
							delete_post_meta( $id, $settings['id'], $level_id );
						}

						foreach ( $new_val as $level_id ) {
							add_post_meta( $id, $settings['id'], $level_id, false );
						}
					} else {
						update_post_meta( $id, $settings['id'], $value );
					}
				}
				break;
			default:
				update_post_meta( $id, $settings['id'], wp_unslash( $settings['value'] !== false ? $settings['value'] : $settings['default'] ) );
		}
	}

	/**
	 * Duplicate for course.
	 *
	 */
	public function duplicate_course() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_course();
			$course_id    = $data['course_id'] ?? 0;
			$course_model = $data['course_model'];

			if ( absint( $course_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to duplicate this course', 'learnpress' ) );
			}

			if ( ! function_exists( 'learn_press_duplicate_post' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/admin/lp-admin-functions.php';
			}

			$curd        = new LP_Course_CURD();
			$new_item_id = $curd->duplicate(
				$course_id,
				array(
					'exclude_meta' => array(
						'order-pending',
						'order-processing',
						'order-completed',
						'order-cancelled',
						'order-failed',
						'count_enrolled_users',
						'_lp_sample_data',
						'_lp_retake_count',
					),
				)
			);

			if ( is_wp_error( $new_item_id ) ) {
				throw new Exception( $new_item_id->get_error_message() );
			}

			$course_model         = CourseModel::find( $new_item_id, true );
			$html                 = BuilderTabCourseTemplate::render_course( $course_model );
			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Course duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function move_trash_course() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_course();
			$course_id    = $data['course_id'] ?? 0;
			$course_model = $data['course_model'];
			$status       = $data['status'] ?? 'trash';

			$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
			$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

			if ( absint( $course_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
				throw new Exception( __( 'You are not allowed to delete this course', 'learnpress' ) );
			}

			if ( $status === 'delete' ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					throw new Exception( __( 'You are not allowed to delete this course', 'learnpress' ) );
				}

				wp_delete_post( $course_id, true );

				$message = __( 'Course has been deleted', 'learnpress' );
			} elseif ( $status === 'draft' ) {
				$update = wp_update_post(
					array(
						'ID'          => $course_id,
						'post_type'   => LP_COURSE_CPT,
						'post_status' => 'draft',
					)
				);

				if ( ! $update ) {
					throw new Exception( __( 'Course cannot be moved to draft', 'learnpress' ) );
				}

				$message = __( 'Course has been moved to draft', 'learnpress' );
			} else {
				$delete = wp_trash_post( $course_id );

				if ( ! $delete ) {
					throw new Exception( __( 'Course has been moved to trash', 'learnpress' ) );
				}

				$message = __( 'Course moved to trash', 'learnpress' );
			}

			$response->status             = 'success';
			$response->data->button_title = __( 'Publish', 'learnpress' );
			$response->data->status       = $status;
			$response->message            = $message;
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function add_course_category() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data = self::check_valid_course();

			// 1. Lấy dữ liệu an toàn
			$name   = sanitize_text_field( $data['name'] ?? '' );
			$parent = isset( $data['parent'] ) ? (int) $data['parent'] : 0; // Lấy ID cha

			if ( $parent < 0 ) {
				$parent = 0; // Fix trường hợp value="-1" của select mặc định WP
			}

			// 2. Insert Term với Parent
			$term = wp_insert_term( $name, 'course_category', array( 'parent' => $parent ) );

			if ( is_wp_error( $term ) ) {
				throw new Exception( $term->get_error_message() );
			}

			$term_id = $term['term_id'];

			// 3. Tạo HTML chuẩn theo cấu trúc WordPress Admin Checklist
			// ID checkbox phải là: in-course_category-{ID} để JS WP nhận diện được
			$html = sprintf(
				'<li id="in-course_category-%1$s" class="popular-category">
                    <label class="selectit">
                        <input value="%1$s" type="checkbox" name="tax_input[course_category][]" id="in-course_category-%1$s" checked="checked"> 
                        %2$s
                    </label>
                </li>',
				esc_attr( $term_id ),
				esc_html( $name )
			);

			$response->status        = 'success';
			$response->data->html    = $html;
			$response->data->term_id = $term_id;
			$response->data->parent  = $parent;
			$response->message       = __( 'Insert category successfully!', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function add_course_tag() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data = self::check_valid_course();
			$name = sanitize_text_field( wp_unslash( $data['name'] ?? '' ) );
			$term = wp_insert_term( $name, 'course_tag', array() );

			if ( is_wp_error( $term ) ) {
				throw new Exception( $term->get_error_message() );
			}

			$html                 = BuilderEditCourseTemplate::instance()->input_checkbox_tag_item( $term['term_id'], $name, false );
			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Insert term successfully!', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Duplicate for lesson.
	 *
	 */
	public function duplicate_lesson() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_lesson();
			$lesson_id    = $data['lesson_id'] ?? 0;
			$lesson_model = $data['lesson_model'];

			if ( absint( $lesson_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to duplicate this lesson', 'learnpress' ) );
			}

			if ( ! function_exists( 'learn_press_duplicate_post' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/admin/lp-admin-functions.php';
			}

			$duplicate_args = apply_filters( 'learn-press/duplicate-post-args', array( 'post_status' => 'publish' ) );
			$curd           = new LP_Lesson_CURD();
			$new_item_id    = $curd->duplicate( $lesson_id, $duplicate_args );

			if ( is_wp_error( $new_item_id ) ) {
				throw new Exception( $new_item_id->get_error_message() );
			}
			$lesson_model_new     = LessonPostModel::find( $new_item_id, true );
			$html                 = BuilderTabLessonTemplate::render_lesson( $lesson_model_new );
			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Lesson duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function update_lesson() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_lesson();
			$lesson_id    = $data['lesson_id'] ?? 0;
			$title        = $data['lesson_title'] ?? '';
			$description  = $data['lesson_description'] ?? '';
			$settings     = $data['lesson_settings'] ?? false;
			$is_elementor = $data['is_elementor'] ?? false;
			$insert       = $data['insert'];

			if ( $insert ) {
				$lesson_id = wp_insert_post(
					array(
						'post_type'    => LP_LESSON_CPT,
						'post_title'   => sanitize_text_field( $title ?? '' ),
						'post_content' => $description ?? '',
						'post_status'  => 'publish',
					),
					true
				);

				if ( is_wp_error( $lesson_id ) ) {
					throw new Exception( $lesson_id->get_error_message() );
				}

				$lesson_model = LessonPostModel::find( $lesson_id, true );
			} else {
				$lesson_model = $data['lesson_model'];

				if ( ! $lesson_model ) {
					throw new Exception( __( 'Lesson not found', 'learnpress' ) );
				}

				$course_id = $this->get_course_by_item_id( $lesson_id );

				// Support for co-instructor.
				$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
				$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

				if ( absint( $lesson_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this lesson', 'learnpress' ) );
				}

				$update_arg = array(
					'ID'          => $lesson_id,
					'post_type'   => LP_LESSON_CPT,
					'post_status' => 'publish',
				);

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $lesson_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				if ( ! empty( $title ) ) {
					$update_arg['post_title']   = sanitize_text_field( $title ?? '' );
					$update_arg['post_content'] = $description ?? '';
				}

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}
			}

			if ( $settings ) {
				$this->save_lesson_settings_to_model( $lesson_model, $data );
			}

			do_action( 'learn-press/course-builder/update-lesson', $data );

			$response->status              = 'success';
			$response->data->status        = 'publish';
			$response->data->button_title  = __( 'Update', 'learnpress' );
			$response->data->lesson_id_new = $data['insert'] ? $lesson_id : '';
			$response->message             = $insert ? esc_html__( 'Insert lesson successfully', 'learnpress' ) : esc_html__( 'Update lesson successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function move_trash_lesson() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_lesson();
			$lesson_id    = $data['lesson_id'] ?? 0;
			$status       = $data['status'] ?? 'trash';
			$lesson_model = $data['lesson_model'] ?? [];

			if ( ! $lesson_model ) {
				throw new Exception( __( 'Lesson not found', 'learnpress' ) );
			}

			if ( absint( $lesson_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete this lesson', 'learnpress' ) );
			}

			if ( $status === 'trash' ) {
				$move_trash = wp_trash_post( $lesson_id );

				if ( is_wp_error( $move_trash ) ) {
					throw new Exception( esc_html__( 'Cannot move this lesson to trash', 'learnpress' ) );
				}
				$message = esc_html__( 'Delete this lesson successfully', 'learnpress' );
			} elseif ( $status === 'delete' ) {
				$delete = wp_delete_post( $lesson_id );

				if ( is_wp_error( $delete ) ) {
					throw new Exception( esc_html__( 'Cannot delete this lesson.', 'learnpress' ) );
				}
				$message = esc_html__( 'This lesson has been moved to trash.', 'learnpress' );

			} elseif ( $status === 'publish' ) {
				$update = wp_update_post(
					array(
						'ID'          => $lesson_id,
						'post_type'   => LP_LESSON_CPT,
						'post_status' => 'publish',
					)
				);
				if ( ! $update ) {
					throw new Exception( __( 'Lesson cannot be moved to publish', 'learnpress' ) );
				}

				$message = __( 'Lesson has been moved to publish', 'learnpress' );
			}

			$response->data->status       = $status;
			$response->data->button_title = __( 'Publish', 'learnpress' );
			$response->status             = 'success';
			$response->message            = $message;
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Save Lesson Settings
	 */
	protected function save_lesson_settings_to_model( LessonPostModel $lessonModel, array $data ) {
		if ( isset( $data['_lp_duration'] ) ) {
			$duration = ! empty( $data['_lp_duration'] ) ? str_replace( ',', ' ', $data['_lp_duration'] ) : '0 minute';
			$explode  = explode( ' ', $duration );
			$number   = (float) $explode[0] < 0 ? 0 : absint( $explode[0] );
			$unit     = $explode[1] ?? 'minute';

			$lessonModel->save_meta_value_by_key( '_lp_duration', $number . ' ' . $unit );
		}

		if ( isset( $data['_lp_preview'] ) ) {
			$enable = $data['_lp_preview'] === 'yes' ? 'yes' : '';
			$lessonModel->set_preview( $enable );
		}
	}

	public function duplicate_quiz() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data       = self::check_valid_quiz();
			$quiz_id    = $data['quiz_id'] ?? 0;
			$quiz_model = $data['quiz_model'];

			if ( absint( $quiz_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to duplicate this quiz', 'learnpress' ) );
			}

			if ( ! function_exists( 'learn_press_duplicate_post' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/admin/lp-admin-functions.php';
			}

			$duplicate_args = apply_filters( 'learn-press/duplicate-post-args', array( 'post_status' => 'publish' ) );
			$curd           = new LP_Quiz_CURD();
			$new_item_id    = $curd->duplicate( $quiz_id, $duplicate_args );

			if ( is_wp_error( $new_item_id ) ) {
				throw new Exception( $new_item_id->get_error_message() );
			}
			$quiz_model_new       = QuizPostModel::find( $new_item_id, true );
			$html                 = BuilderTabQuizTemplate::render_quiz( $quiz_model_new );
			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Quiz duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function update_quiz() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_quiz();
			$quiz_id      = $data['quiz_id'] ?? 0;
			$title        = $data['quiz_title'] ?? '';
			$description  = $data['quiz_description'] ?? '';
			$settings     = $data['quiz_settings'] ?? false;
			$is_elementor = $data['is_elementor'] ?? false;
			$insert       = $data['insert'];

			if ( $insert ) {
				$quiz_id = wp_insert_post(
					array(
						'post_type'    => LP_QUIZ_CPT,
						'post_title'   => sanitize_text_field( $title ?? '' ),
						'post_content' => $description ?? '',
						'post_status'  => 'publish',
					),
					true
				);

				if ( is_wp_error( $quiz_id ) ) {
					throw new Exception( $quiz_id->get_error_message() );
				}

				$quiz_model = QuizPostModel::find( $quiz_id, true );
			} else {
				$quiz_model = $data['quiz_model'];

				if ( ! $quiz_model ) {
					throw new Exception( __( 'Quiz not found', 'learnpress' ) );
				}

				$course_id = $this->get_course_by_item_id( $quiz_id );

				// Support for co-instructor.
				$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
				$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

				if ( absint( $quiz_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this quiz', 'learnpress' ) );
				}

				$update_arg = array(
					'ID'          => $quiz_id,
					'post_type'   => LP_QUIZ_CPT,
					'post_status' => 'publish',
				);

				if ( ! empty( $title ) ) {
					$update_arg['post_title']   = sanitize_text_field( $title ?? '' );
					$update_arg['post_content'] = $description ?? '';
				}

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $quiz_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}
			}

			if ( $settings ) {
				$this->save_quiz_settings_to_model( $quiz_model, $data );
			}

			do_action( 'learn-press/course-builder/update-quiz', $data );

			$response->status             = 'success';
			$response->data->status       = 'publish';
			$response->data->button_title = __( 'Update', 'learnpress' );
			$response->data->quiz_id_new  = $data['insert'] ? $quiz_id : '';
			$response->message            = $insert ? esc_html__( 'Insert quiz successfully', 'learnpress' ) : esc_html__( 'Update quiz successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Save Quiz Settings
	 */
	protected function save_quiz_settings_to_model( QuizPostModel $quizModel, array $data ) {
		if ( isset( $data['_lp_duration'] ) ) {
			$duration = ! empty( $data['_lp_duration'] ) ? str_replace( ',', ' ', $data['_lp_duration'] ) : '0 minute';
			$explode  = explode( ' ', $duration );
			$number   = (float) $explode[0] < 0 ? 0 : absint( $explode[0] );
			$unit     = $explode[1] ?? 'minute';
			$quizModel->save_meta_value_by_key( '_lp_duration', $number . ' ' . $unit );
		}

		$checkbox_keys = [
			'_lp_instant_check',
			'_lp_negative_marking',
			'_lp_minus_skip_questions',
			'_lp_review',
			'_lp_show_correct_review',
		];

		foreach ( $checkbox_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$value = ( isset( $data[ $key ] ) && $data[ $key ] === 'yes' ) ? 'yes' : 'no';
				$quizModel->save_meta_value_by_key( $key, $value );
			}
		}

		$numeric_keys = [
			'_lp_passing_grade',
			'_lp_retake_count',
			'_lp_pagination',
		];

		foreach ( $numeric_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$quizModel->save_meta_value_by_key( $key, intval( $data[ $key ] ) );
			}
		}
	}

	public function move_trash_quiz() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data       = self::check_valid_quiz();
			$quiz_id    = $data['quiz_id'] ?? 0;
			$status     = $data['status'] ?? 'trash';
			$quiz_model = $data['quiz_model'] ?? [];

			if ( ! $quiz_model ) {
				throw new Exception( __( 'Quiz not found', 'learnpress' ) );
			}

			if ( absint( $quiz_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete this quiz', 'learnpress' ) );
			}

			if ( $status === 'trash' ) {
				$move_trash = wp_trash_post( $quiz_id );

				if ( is_wp_error( $move_trash ) ) {
					throw new Exception( esc_html__( 'Cannot move this quiz to trash', 'learnpress' ) );
				}
				$message = esc_html__( 'Delete this quiz successfully', 'learnpress' );
			} elseif ( $status === 'delete' ) {
				$delete = wp_delete_post( $quiz_id );

				if ( is_wp_error( $delete ) ) {
					throw new Exception( esc_html__( 'Cannot delete this quiz.', 'learnpress' ) );
				}
				$message = esc_html__( 'This quiz has been moved to trash.', 'learnpress' );

			} elseif ( $status === 'publish' ) {
				$update = wp_update_post(
					array(
						'ID'          => $quiz_id,
						'post_type'   => LP_QUIZ_CPT,
						'post_status' => 'publish',
					)
				);
				if ( ! $update ) {
					throw new Exception( __( 'Quiz cannot be moved to publish', 'learnpress' ) );
				}

				$message = __( 'Quiz has been moved to publish', 'learnpress' );
			}

			$response->data->status       = $status;
			$response->data->button_title = __( 'Publish', 'learnpress' );
			$response->status             = 'success';
			$response->message            = $message;
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function duplicate_question() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data           = self::check_valid_question();
			$question_id    = $data['question_id'] ?? 0;
			$question_model = $data['question_model'];

			if ( absint( $question_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to duplicate this question', 'learnpress' ) );
			}

			if ( ! function_exists( 'learn_press_duplicate_post' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/admin/lp-admin-functions.php';
			}

			$duplicate_args = apply_filters( 'learn-press/duplicate-post-args', array( 'post_status' => 'publish' ) );
			$curd           = new LP_Question_CURD();
			$new_item_id    = $curd->duplicate( $question_id, $duplicate_args );

			if ( is_wp_error( $new_item_id ) ) {
				throw new Exception( $new_item_id->get_error_message() );
			}
			$question_model_new = QuestionPostModel::find( $new_item_id, true );
			$html               = BuilderTabQuestionTemplate::render_question( $question_model_new );

			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Question duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function builder_update_question() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_question();
			$question_id  = $data['question_id'] ?? 0;
			$title        = $data['question_title'] ?? '';
			$description  = $data['question_description'] ?? '';
			$is_elementor = $data['is_elementor'] ?? false;
			$insert       = $data['insert'];

			if ( $insert ) {
				$question_id = wp_insert_post(
					array(
						'post_type'    => LP_QUESTION_CPT,
						'post_title'   => sanitize_text_field( $title ?? '' ),
						'post_content' => $description ?? '',
						'post_status'  => 'publish',
					),
					true
				);

				if ( is_wp_error( $question_id ) ) {
					throw new Exception( $question_id->get_error_message() );
				}
			} else {
				$question_model = $data['question_model'];

				if ( ! $question_model ) {
					throw new Exception( __( 'Question not found', 'learnpress' ) );
				}

				$course_id = $this->get_course_by_item_id( $question_id );

				// Support for co-instructor.
				$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
				$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

				if ( absint( $question_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this question', 'learnpress' ) );
				}

				$update_arg = array(
					'ID'          => $question_id,
					'post_type'   => LP_QUESTION_CPT,
					'post_status' => 'publish',
				);

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $question_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				if ( ! empty( $title ) ) {
					$update_arg['post_title']   = sanitize_text_field( $title ?? '' );
					$update_arg['post_content'] = $description ?? '';
				}

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}
			}

			do_action( 'learn-press/course-builder/update-question', $data );

			$response->status                = 'success';
			$response->data->status          = 'publish';
			$response->data->button_title    = __( 'Update', 'learnpress' );
			$response->data->question_id_new = $data['insert'] ? $question_id : '';
			$response->message               = $insert ? esc_html__( 'Insert question successfully', 'learnpress' ) : esc_html__( 'Update question successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function move_trash_question() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data           = self::check_valid_question();
			$question_id    = $data['question_id'] ?? 0;
			$status         = $data['status'] ?? 'trash';
			$question_model = $data['question_model'] ?? [];

			if ( ! $question_model ) {
				throw new Exception( __( 'Question not found', 'learnpress' ) );
			}

			if ( absint( $question_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete this question', 'learnpress' ) );
			}

			if ( $status === 'trash' ) {
				$move_trash = wp_trash_post( $question_id );

				if ( is_wp_error( $move_trash ) ) {
					throw new Exception( esc_html__( 'Cannot move this question to trash', 'learnpress' ) );
				}
				$message = esc_html__( 'Delete this question successfully', 'learnpress' );
			} elseif ( $status === 'delete' ) {
				$delete = wp_delete_post( $question_id );

				if ( is_wp_error( $delete ) ) {
					throw new Exception( esc_html__( 'Cannot delete this question.', 'learnpress' ) );
				}
				$message = esc_html__( 'This question has been moved to trash.', 'learnpress' );

			} elseif ( $status === 'publish' ) {
				$update = wp_update_post(
					array(
						'ID'          => $question_id,
						'post_type'   => LP_QUESTION_CPT,
						'post_status' => 'publish',
					)
				);
				if ( ! $update ) {
					throw new Exception( __( 'Question cannot be moved to publish', 'learnpress' ) );
				}

				$message = __( 'Question has been moved to publish', 'learnpress' );
			}

			$response->data->status       = $status;
			$response->data->button_title = __( 'Publish', 'learnpress' );
			$response->status             = 'success';
			$response->message            = $message;
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	protected function get_course_by_item_id( $item_id ) {
		static $output;

		global $wpdb;

		if ( empty( $item_id ) ) {
			return false;
		}

		if ( ! isset( $output ) ) {
			$output = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT c.ID FROM {$wpdb->posts} c
					INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
					INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
					WHERE si.item_id = %d ORDER BY si.section_id DESC LIMIT 1
					",
					$item_id
				)
			);
		}

		if ( $output ) {
			return absint( $output );
		}

		return false;
	}
}
