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
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\BuilderEditCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderTabCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderTabLessonTemplate;
use LP_Course_CURD;
use LP_Course_Post_Type;
use LP_Helper;
use LP_Lesson_CURD;
use LP_REST_Response;
use stdClass;

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
		$course_model = CoursePostModel::find( $course_id, true );
		if ( empty( $course_model ) ) {
			$params['insert'] = true;
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
			$params['insert'] = true;
		} else {
			$params['insert']       = false;
			$params['lesson_model'] = $lesson_model;
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

			if ( $data['insert'] ) {
				$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
				$tags       = ! empty( $data['course_tags'] ) ? array_map( 'absint', explode( ',', $data['course_tags'] ) ) : array();
				$course_id  = wp_insert_post(
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
			} else {
				$course_model = $data['course_model'];
				// Support for co-instructor.
				$co_instructor_ids = $course_model->get_meta_value_by_key( '_lp_co_teacher', [] );
				if ( absint( $course_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' )
				&& ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress' ) );
				}

				$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
				$tags       = ! empty( $data['course_tags'] ) ? array_map( 'absint', explode( ',', $data['course_tags'] ) ) : array();

				$update = wp_update_post(
					array(
						'ID'           => $course_id,
						'post_type'    => LP_COURSE_CPT,
						'post_title'   => sanitize_text_field( $data['course_title'] ?? '' ),
						'post_content' => wp_unslash( $data['course_description'] ?? '' ),
						'post_status'  => ! empty( $data['course_status'] ) ? sanitize_text_field( $data['course_status'] ) : 'publish',
						'tax_input'    => array(
							'course_category' => $categories,
							'course_tag'      => $tags,
						),
					)
				);
				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}
			}

			if ( ! empty( $data['course_thumbnail_id'] ) ) {
				set_post_thumbnail( $course_id, absint( $data['course_thumbnail_id'] ) );
			} else {
				delete_post_thumbnail( $course_id );
			}

			$response->status              = 'success';
			$response->message             = $data['insert'] ? __( 'Insert course successfully!', 'learnpress' ) : __( 'Update course successfully!', 'learnpress' );
			$response->data->status        = $data['course_status'];
			$response->data->button_title  = $data['course_status'] === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );
			$response->data->course_id_new = $data['insert'] ? $course_id : '';
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function update_setting( $id, $settings ) {
		switch ( $settings['type'] ) {
			case 'LP_Meta_Box_Text_Field':
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
			case 'LP_Meta_Box_Textarea_Field':
				update_post_meta( $id, $settings['id'], wp_kses_post( wp_unslash( $settings['value'] !== false ? $settings['value'] : $settings['default'] ) ) );
				break;
			case 'LP_Meta_Box_Duration_Field':
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
			case 'LP_Meta_Box_Extra_Field':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? $settings['value'] : $settings['default'] );
				$value = array_filter(
					$value,
					function ( $item ) {
						return ! is_null( $item ) && $item !== '';
					}
				);

				update_post_meta( $id, $settings['id'], array_map( 'sanitize_text_field', array_values( $value ) ) );
				break;

			case 'LP_Meta_Box_File_Field':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? wp_unslash( array_map( 'absint', $settings['value'] ) ) : $settings['default'] );

				update_post_meta( $id, $settings['id'], $value );
				break;
			case 'LP_Meta_Box_Autocomplete_Field':
				$value = wp_unslash( $settings['value'] !== false && $settings['value'] !== '' ? wp_unslash( array_map( 'absint', $settings['value'] ) ) : $settings['default'] );
				$value = apply_filters( 'learn-press/admin/metabox/autocomplete/' . $settings['id'] . '/save', $value, wp_unslash( $settings['value'] ), $id );

				update_post_meta( $id, $settings['id'], $value );
				break;
			case 'LP_Meta_Box_Select_Field':
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
				update_post_meta( $id, $settings['id'], wp_unslash( $settings['value'] !== false ? $settings['value'] : $settings['default'] ) ); // Cannot sanitize because "Nham bao the"
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

			$response->status       = 'success';
			$response->data->status = $status;
			$response->message      = $message;
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
			$name = sanitize_text_field( $data['name'] ?? '' );
			$term = wp_insert_term( $name, 'course_category', array() );

			if ( is_wp_error( $term ) ) {
				throw new Exception( $term->get_error_message() );
			}

			$html                 = BuilderEditCourseTemplate::instance()->input_checkbox_category_item( $term['term_id'], $name, false );
			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Insert category successfully!', 'learnpress' );
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
	 * Duplicate for course.
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
			$settings     = $data['lesson_settings'] ?? [];
			$is_elementor = $data['is_elementor'] ?? false;
			$insert       = $data['insert'];
			$is_publish   = $data['isPublic'] ?? false;

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
					'ID'           => $lesson_id,
					'post_type'    => LP_LESSON_CPT,
					'post_title'   => sanitize_text_field( $title ?? '' ),
					'post_content' => $description ?? '',
				);

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $lesson_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				if ( $is_publish ) {
					$update_arg['post_status'] = 'publish';
				}

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}
			}

			if ( ! empty( $settings ) ) {
				foreach ( $settings as $item_setting ) {
					$this->update_setting( $lesson_id, $item_setting );
				}
			}

			$response->status              = 'success';
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

			$response->status  = 'success';
			$response->message = $message;
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
