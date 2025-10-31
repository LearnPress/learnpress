<?php
/**
 * class CourseBuilderAjax
 *
 * @since 4.3
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LP_Course_CURD;
use LP_Course_Post_Type;
use LP_Helper;
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
	public static function check_valid() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		$params       = LP_Helper::json_decode( $params, true );
		$course_id    = (int) $params['course_id'] ?? 0;
		$course_model = CoursePostModel::find( $course_id, true );
		if ( empty( $course_model ) ) {
			$params['insert'] = true;
		} else {
			$params['insert']       = false;
			$params['course_model'] = $course_model;
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
			global $wpdb;
			$data      = self::check_valid();
			$course_id = $data['course_id'] ?? 0;

			if ( $data['insert'] ) {
				$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
				$terms      = ! empty( $data['course_terms'] ) ? array_map( 'absint', explode( ',', $data['course_terms'] ) ) : array();
				$course_id  = wp_insert_post(
					array(
						'post_type'    => LP_COURSE_CPT,
						'post_title'   => sanitize_text_field( $data['course_title'] ?? '' ),
						'post_content' => wp_unslash( $data['course_description'] ?? '' ),
						'post_status'  => ! empty( $data['course_status'] ) ? sanitize_text_field( $data['course_status'] ) : 'publish',
						'post_name'    => sanitize_text_field( $data['course_permalink'] ),
						'tax_input'    => array(
							'course_category' => $categories,
							'course_tag'      => $terms,
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
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress-frontend-editor' ) );
				}

				$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
				$terms      = ! empty( $data['course_terms'] ) ? array_map( 'absint', explode( ',', $data['course_terms'] ) ) : array();

				$update = wp_update_post(
					array(
						'ID'           => $course_id,
						'post_type'    => LP_COURSE_CPT,
						'post_title'   => sanitize_text_field( $data['course_title'] ?? '' ),
						'post_content' => wp_unslash( $data['course_description'] ?? '' ),
						'post_status'  => ! empty( $data['course_status'] ) ? sanitize_text_field( $data['course_status'] ) : 'publish',
						'tax_input'    => array(
							'course_category' => $categories,
							'course_tag'      => $terms,
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

			$userModel = UserModel::find( get_current_user_id(), true );

			// Save settings post_meta.
			if ( ! empty( $data['settings'] ) ) {
				foreach ( $data['settings'] as $setting_content ) {
					if ( ! empty( $setting_content['content'] ) ) {
						foreach ( $setting_content['content'] as $setting ) {
							if ( $userModel->is_instructor() && in_array(
								$setting['id'],
								array(
									'_lp_course_author',
									'_lp_co_teacher',
								)
							) ) {
								continue;
							}

							$this->update_setting( $course_id, $setting );

							// Update post_author.
							if ( $setting['id'] === '_lp_course_author' && ! empty( $setting['value'] ) ) {
								$wpdb->update(
									$wpdb->posts,
									[ 'post_author' => absint( wp_unslash( $setting['value'] ) ) ],
									[ 'ID' => $course_id ]
								);
							}
						}
					}
				}
			}

			$course_post_type = LP_Course_Post_Type::instance();
			$course_post_type->save_post( $course_id, null, true );

			$response->status  = 'success';
			$response->message = $data['insert'] ? __( 'Insert course successfully!', 'learnpress' ) : __( 'Update course successfully!', 'learnpress' );
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
			$data         = self::check_valid();
			$course_id    = $data['course_id'] ?? 0;
			$course_model = $data['course_model'];

			if ( absint( $course_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to duplicate this course', 'learnpress' ) );
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

			$response->status   = 'success';
			$response->data->id = $new_item_id;
			$response->message  = __( 'Course duplicated successfully', 'learnpress' );
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
			$data         = self::check_valid();
			$course_id    = $data['course_id'] ?? 0;
			$course_model = $data['course_model'];
			$status       = $data['status'] ?? 'trash';

			$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
			$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

			if ( absint( $course_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
				throw new Exception( __( 'You are not allowed to delete this course', 'learnpress-frontend-editor' ) );
			}

			if ( $status === 'delete' ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					throw new Exception( __( 'You are not allowed to delete this course', 'learnpress' ) );
				}

				wp_delete_post( $course_id, true );

				$message = __( 'Course has been deleted', 'learnpress' );
			} else {
				$delete = wp_trash_post( $course_id );

				if ( ! $delete ) {
					throw new Exception( __( 'Course has been moved to trash', 'learnpress' ) );
				}

				$message = __( 'Course moved to trash', 'learnpress' );
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

	public function add_category() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$name       = sanitize_text_field( wp_unslash( $_REQUEST['name'] ?? '' ) );
			$slug       = sanitize_title( wp_unslash( $_REQUEST['slug'] ?? '' ) );
			$parent_id  = absint( $_REQUEST['parent_id'] ?? 0 );
			$term_array = array(
				'cat_name'        => $name,
				'slug'            => $slug,
				'category_parent' => $parent_id,
			);

			$term = wp_insert_term( $name, 'course_category', $term_array );

			if ( is_wp_error( $term ) ) {
				throw new Exception( $term->get_error_message() );
			}

			$response->status     = 'success';
			$response->data->term = $term;
			$response->message    = __( 'Insert category successfully!', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function add_term() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$name = sanitize_text_field( wp_unslash( $_REQUEST['name'] ?? '' ) );
			$slug = sanitize_title( wp_unslash( $_REQUEST['slug'] ?? '' ) );

			$term = wp_insert_term( $name, 'course_tag', array( 'slug' => $slug ) );

			if ( is_wp_error( $term ) ) {
				throw new Exception( $term->get_error_message() );
			}

			$response->status     = 'success';
			$response->data->term = $term;
			$response->message    = __( 'Insert term successfully!', 'learnpress' );
			wp_send_json( $response );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}
}
