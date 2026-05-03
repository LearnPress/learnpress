<?php
/**
 * class CourseBuilderAjax
 *
 * @since 4.3
 * @version 1.0.0
 */

namespace LearnPress\Ajax\CourseBuilder;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderEditCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderListCoursesTemplate;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Lesson\BuilderListLessonsTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Question\BuilderListQuestionsTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Question\BuilderQuestionTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Quiz\BuilderListQuizzesTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Quiz\BuilderQuizTemplate;
use LP_Course_CURD;
use LP_Helper;
use LP_Lesson_CURD;
use LP_Question_CURD;
use LP_Quiz_CURD;
use LP_REST_Response;
use LP_Settings;
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
	 * Prepare desired slug when restoring a trashed post with a new permalink.
	 *
	 * WordPress stores `_wp_desired_post_slug` on trash and may restore that value
	 * when status changes from `trash` to a non-trash status.
	 *
	 * @param int    $post_id
	 * @param string $target_status
	 * @param string $requested_slug
	 *
	 * @return bool
	 */
	protected function prepare_desired_slug_for_restore( int $post_id, string $target_status, string $requested_slug ): bool {
		if ( $post_id <= 0 || '' === $requested_slug ) {
			return false;
		}

		if ( ! in_array( $target_status, [ 'publish', 'draft' ], true ) ) {
			return false;
		}

		if ( 'trash' !== get_post_status( $post_id ) ) {
			return false;
		}

		update_post_meta( $post_id, '_wp_desired_post_slug', $requested_slug );

		return true;
	}

	/**
	 * Ensure restored post keeps requested slug after trash status transition.
	 *
	 * @param int    $post_id
	 * @param string $requested_slug
	 *
	 * @return void
	 */
	protected function sync_slug_after_restore( int $post_id, string $requested_slug ): void {
		if ( $post_id <= 0 || '' === $requested_slug ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'trash' === $post->post_status ) {
			return;
		}

		if ( $post->post_name === $requested_slug ) {
			return;
		}

		wp_update_post(
			[
				'ID'        => $post_id,
				'post_name' => $requested_slug,
			]
		);
	}

	/**
	 * Permalink notice when lesson/quiz is not assigned to a course.
	 *
	 * @return string
	 */
	protected function get_item_permalink_unavailable_message(): string {
		return __( 'Permalink is only available if the item is already assigned to a course.', 'learnpress' );
	}

	/**
	 * Check whether a post status is public.
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	protected function is_public_post_status( string $status ): bool {
		$status_object = get_post_status_object( $status );

		return (bool) ( $status_object && ! empty( $status_object->public ) );
	}

	/**
	 * Normalize target course status based on role/capability and current status.
	 * Mirrors wp-admin behavior for instructors without publish capability.
	 *
	 * @param string          $requested_status
	 * @param bool            $insert
	 * @param CourseModel|null $course_model
	 *
	 * @return string
	 */
	protected function normalize_course_status_for_save( string $requested_status, bool $insert, ?CourseModel $course_model ): string {
		$allowed_statuses = [ 'publish', 'future', 'draft', 'pending', 'private' ];
		if ( ! in_array( $requested_status, $allowed_statuses, true ) ) {
			$requested_status = 'draft';
		}

		$is_admin_user = current_user_can( ADMIN_ROLE );
		if ( $is_admin_user ) {
			return $requested_status;
		}

		$is_instructor_user = current_user_can( LP_TEACHER_ROLE );
		$required_review    = LP_Settings::get_option( 'required_review', 'yes' ) === 'yes';
		$can_publish_course = current_user_can( 'publish_' . LP_COURSE_CPT . 's' );

		$current_status  = $insert ? 'auto-draft' : (string) ( $course_model->post_status ?? 'draft' );
		$is_public_state = $this->is_public_post_status( $current_status );

		// Require moderation for instructors when review is enabled.
		if ( $is_instructor_user && $required_review && ! $is_public_state && in_array( $requested_status, [ 'publish', 'private', 'future' ], true ) ) {
			return 'pending';
		}

		// Keep wp-admin behavior: users without publish capability submit for review.
		if ( ! $can_publish_course && ! $is_public_state && in_array( $requested_status, [ 'publish', 'private', 'future' ], true ) ) {
			return 'pending';
		}

		return $requested_status;
	}

	/**
	 * Normalize publish date from Course Builder UI datetime value.
	 *
	 * @param string $datetime_value
	 *
	 * @return array<string, string>|null
	 */
	protected function normalize_course_post_date_for_save( string $datetime_value ): ?array {
		$datetime_value = trim( $datetime_value );
		if ( '' === $datetime_value ) {
			return null;
		}

		$timezone = wp_timezone();
		$formats  = [ 'Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s' ];
		$parsed   = null;

		foreach ( $formats as $format ) {
			$date_candidate = DateTimeImmutable::createFromFormat( $format, $datetime_value, $timezone );
			if ( ! $date_candidate instanceof DateTimeImmutable ) {
				continue;
			}

			$errors = DateTimeImmutable::getLastErrors();
			if ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) {
				continue;
			}

			$parsed = $date_candidate;
			break;
		}

		if ( ! $parsed instanceof DateTimeImmutable ) {
			return null;
		}

		$gmt_timezone = new DateTimeZone( 'UTC' );

		return [
			'post_date'     => $parsed->format( 'Y-m-d H:i:s' ),
			'post_date_gmt' => $parsed->setTimezone( $gmt_timezone )->format( 'Y-m-d H:i:s' ),
		];
	}

	/**
	 * Save Course.
	 *
	 * @since 4.3
	 * @version 1.0.1
	 */
	public function save_courses() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data                    = self::check_valid_course();
			$course_id               = $data['course_id'] ?? 0;
			$settings                = $data['course_settings'] ?? false;
			$insert                  = $data['insert'];
			$course_title            = isset( $data['course_title'] ) ? trim( sanitize_text_field( wp_unslash( (string) $data['course_title'] ) ) ) : '';
			$course_status_requested = ! empty( $data['course_status'] ) ? sanitize_text_field( $data['course_status'] ) : 'publish';
			$course_visibility       = ! empty( $data['course_visibility'] ) ? sanitize_key( $data['course_visibility'] ) : '';
			$course_password_raw     = isset( $data['course_password'] ) ? wp_unslash( (string) $data['course_password'] ) : '';
			$course_password         = sanitize_text_field( $course_password_raw );
			$course_post_date_raw    = ! empty( $data['course_post_date'] ) ? sanitize_text_field( $data['course_post_date'] ) : '';
			$course_post_date_data   = $this->normalize_course_post_date_for_save( $course_post_date_raw );
			$post_password_to_update = null;

			if ( '' === $course_title ) {
				throw new Exception( __( 'Course title is required.', 'learnpress' ) );
			}

			if ( $insert ) {
				// Check user capability before insert
				if ( ! current_user_can( 'edit_lp_courses' ) ) {
					throw new Exception( __( 'You are not allowed to create courses', 'learnpress' ) );
				}

				$course_status = $this->normalize_course_status_for_save( $course_status_requested, true, null );

				$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
				$tags       = ! empty( $data['course_tags'] ) ? array_map( 'absint', explode( ',', $data['course_tags'] ) ) : array();

				$insert_post_data = array(
					'post_type'    => LP_COURSE_CPT,
					'post_title'   => $course_title,
					'post_content' => wp_unslash( $data['course_description'] ?? '' ),
					'post_status'  => $course_status,
					'tax_input'    => array(
						'course_category' => $categories,
						'course_tag'      => $tags,
					),
				);

				if ( ! empty( $course_post_date_data['post_date'] ) ) {
					$insert_post_data['post_date']     = $course_post_date_data['post_date'];
					$insert_post_data['post_date_gmt'] = $course_post_date_data['post_date_gmt'];
				}

				if ( in_array( $course_visibility, [ 'public', 'private' ], true ) ) {
					$insert_post_data['post_password'] = '';
				} elseif ( 'password' === $course_visibility ) {
					if ( '' === $course_password ) {
						throw new Exception( __( 'Password is required for password protected visibility.', 'learnpress' ) );
					}
					$insert_post_data['post_password'] = $course_password;
				}

				$course_id = wp_insert_post( $insert_post_data, true );

				if ( is_wp_error( $course_id ) ) {
					throw new Exception( $course_id->get_error_message() );
				}

				// Load the newly created course as CourseModel
				$courseModel = CourseModel::find( $course_id, true );
				if ( ! $courseModel ) {
					throw new Exception( __( 'Failed to load course model', 'learnpress' ) );
				}
			} else {
				$courseModel   = $data['course_model'];
				$course_status = $this->normalize_course_status_for_save( $course_status_requested, false, $courseModel );

				$co_instructor_ids = $courseModel->get_meta_value_by_key( '_lp_co_teacher', [] );
				if ( absint( $courseModel->post_author ) !== get_current_user_id() &&
					! current_user_can( 'manage_options' ) &&
					! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress' ) );
				}

				$courseModel->post_status = $course_status;
				if ( ! empty( $course_post_date_data['post_date'] ) ) {
					$courseModel->post_date     = $course_post_date_data['post_date'];
					$courseModel->post_date_gmt = $course_post_date_data['post_date_gmt'];
				}

				if ( in_array( $course_visibility, [ 'public', 'private' ], true ) ) {
					$post_password_to_update = '';
				} elseif ( 'password' === $course_visibility ) {
					if ( '' !== $course_password ) {
						$post_password_to_update = $course_password;
					} else {
						$existing_post = get_post( $courseModel->ID );
						if ( $existing_post && ! empty( $existing_post->post_password ) ) {
							$post_password_to_update = (string) $existing_post->post_password;
						} else {
							throw new Exception( __( 'Password is required for password protected visibility.', 'learnpress' ) );
						}
					}
				}

				if ( '' !== $course_title ) {
					$categories = ! empty( $data['course_categories'] ) ? array_map( 'absint', explode( ',', $data['course_categories'] ) ) : array();
					$tags       = ! empty( $data['course_tags'] ) ? array_map( 'absint', explode( ',', $data['course_tags'] ) ) : array();

					$courseModel->post_title   = $course_title;
					$courseModel->post_content = wp_unslash( $data['course_description'] ?? '' );

					wp_set_post_terms( $courseModel->ID, $categories, 'course_category' );
					wp_set_post_terms( $courseModel->ID, $tags, 'course_tag' );
				}

				// Update permalink/slug if provided
				if ( ! empty( $data['course_permalink'] ) ) {
					$new_slug = sanitize_title( $data['course_permalink'] );
					if ( $new_slug && $new_slug !== $courseModel->post_name ) {
						wp_update_post(
							array(
								'ID'        => $courseModel->ID,
								'post_name' => $new_slug,
							)
						);
						$courseModel->post_name = $new_slug;
					}
				}

				$course_id = $courseModel->ID;
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

			// Handle course thumbnail - AFTER coursePostModel->save() to avoid being overwritten
			if ( isset( $data['course_thumbnail_id'] ) ) {
				$thumbnail_id = absint( $data['course_thumbnail_id'] );

				if ( $thumbnail_id > 0 ) {
					$result = set_post_thumbnail( $course_id, $thumbnail_id );
				} else {
					$result = delete_post_thumbnail( $course_id );
				}
				$current_thumbnail = get_post_thumbnail_id( $course_id );
			}

			if ( null !== $post_password_to_update ) {
				wp_update_post(
					array(
						'ID'            => $course_id,
						'post_password' => $post_password_to_update,
					)
				);
			}

			// Use persisted status after save to avoid UI drift when WP normalizes status by capability.
			$saved_course_status = get_post_status( $course_id );
			if ( ! is_string( $saved_course_status ) || '' === $saved_course_status ) {
				$saved_course_status = $course_status;
			}

			$response->status              = 'success';
			$response->message             = $insert ? __( 'Insert course successfully!', 'learnpress' ) : __( 'Update course successfully!', 'learnpress' );
			$response->data->status        = $saved_course_status;
			$response->data->button_title  = 'publish' === $saved_course_status
				? __( 'Update', 'learnpress' )
				: ( 'pending' === $saved_course_status ? __( 'Submit for Review', 'learnpress' ) : __( 'Publish', 'learnpress' ) );
			$response->data->course_id_new = $insert ? $course_id : '';

			// Return the actual saved permalink data (important if WordPress auto-generated a unique slug)
			$saved_post = get_post( $course_id );
			if ( $saved_post ) {
				$visibility = 'public';
				if ( 'private' === $saved_post->post_status ) {
					$visibility = 'private';
				} elseif ( ! empty( $saved_post->post_password ) ) {
					$visibility = 'password';
				}

				$response->data->visibility       = $visibility;
				$response->data->course_slug      = $saved_post->post_name;
				$response->data->course_permalink = get_permalink( $course_id );
			}

			// Return full redirect URL for new courses
			if ( $insert && $course_id ) {
				$response->data->redirect_url = CourseBuilder::get_link_course_builder( "courses/{$course_id}" );
			}

			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Save Course Settings only (from Settings tab).
	 *
	 * @since 4.3
	 * @version 1.0.0
	 */
	public function save_course_settings() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data        = self::check_valid_course();
			$course_id   = $data['course_id'] ?? 0;
			$insert      = $data['insert'];
			$courseModel = $data['course_model'];

			if ( $insert || empty( $courseModel ) ) {
				throw new Exception( __( 'Course not found. Please save the course first.', 'learnpress' ) );
			}

			$co_instructor_ids = $courseModel->get_meta_value_by_key( '_lp_co_teacher', [] );
			if ( absint( $courseModel->post_author ) !== get_current_user_id() &&
				! current_user_can( 'manage_options' ) &&
				! in_array( get_current_user_id(), $co_instructor_ids ) ) {
				throw new Exception( __( 'You are not allowed to update this course', 'learnpress' ) );
			}

			// Save course settings
			$this->save_course_settings_to_model( $courseModel, $data );

			// Save meta data
			if ( ! empty( $courseModel->meta_data ) ) {
				$coursePostModel = new CoursePostModel( $courseModel );
				foreach ( $courseModel->meta_data as $meta_key => $meta_value ) {
					$coursePostModel->save_meta_value_by_key( $meta_key, $meta_value );
				}
				$coursePostModel->save();
			}

			$response->status  = 'success';
			$response->message = __( 'Settings saved successfully!', 'learnpress' );

			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Save all course settings to CourseModel
	 *
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function save_course_settings_to_model( CoursePostModel &$courseModel, array $data ) {
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
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 */
	protected function save_general_settings_to_model( CoursePostModel &$courseModel, array $data ) {
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
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 */
	protected function save_offline_settings_to_model( CoursePostModel &$courseModel, array $data ) {
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
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 */
	protected function save_price_settings_to_model( CoursePostModel &$courseModel, array $data ) {
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
	}

	/**
	 * Save extra info settings to CourseModel
	 *
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 */
	protected function save_extra_settings_to_model( CoursePostModel &$courseModel, array $data ) {
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
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 */
	protected function save_assessment_settings_to_model( CoursePostModel &$courseModel, array $data ) {
		// Course result evaluation type
		if ( isset( $data['_lp_course_result'] ) ) {
			$courseModel->meta_data->{CoursePostModel::META_KEY_EVALUATION_TYPE} = sanitize_text_field( $data['_lp_course_result'] );
		}

		// Passing condition
		if ( isset( $data['_lp_passing_condition'] ) ) {
			$passing_condition = floatval( $data['_lp_passing_condition'] );
			if ( $passing_condition < 0 ) {
				$passing_condition = 0;
			} elseif ( $passing_condition > 100 ) {
				$passing_condition = 100;
			}
			$courseModel->meta_data->{CoursePostModel::META_KEY_PASSING_CONDITION} = $passing_condition;
		}
	}

	/**
	 * Save author settings to CourseModel
	 *
	 * @param CoursePostModel $courseModel
	 * @param array $data
	 */
	protected function save_author_settings_to_model( CoursePostModel &$courseModel, array $data ) {
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

			$course_model                  = CourseModel::find( $new_item_id, true );
			$html                          = BuilderListCoursesTemplate::render_course( $course_model );
			$response->status              = 'success';
			$response->data->html          = $html;
			$response->data->course_id_new = $new_item_id;
			$response->data->redirect_url  = CourseBuilder::get_link_course_builder( "courses/{$new_item_id}" );
			$response->message             = __( 'Course duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( Throwable $th ) {
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
				// Store original slug before trashing (wp_trash_post adds __trashed suffix)
				$original_slug = $course_model->post_name;

				$delete = wp_trash_post( $course_id );

				if ( ! $delete ) {
					throw new Exception( __( 'Course cannot be moved to trash', 'learnpress' ) );
				}

				// Restore original slug after trashing
				if ( $original_slug ) {
					wp_update_post(
						array(
							'ID'        => $course_id,
							'post_name' => $original_slug,
						)
					);
				}

				$message = __( 'Course moved to trash', 'learnpress' );
			}

			$response->status             = 'success';
			$response->data->button_title = __( 'Publish', 'learnpress' );
			$response->data->status       = $status;
			$response->message            = $message;
			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function add_course_category() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data      = self::check_valid_course();
			$course_id = absint( $data['course_id'] ?? 0 );
			if ( $course_id > 0 ) {
				if ( ! CourseBuilderAccessPolicy::can_edit_course_by_id( $course_id ) ) {
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress' ) );
				}
			} elseif ( ! CourseBuilderAccessPolicy::can_create_item_type( LP_COURSE_CPT ) ) {
				throw new Exception( __( 'You are not allowed to create courses', 'learnpress' ) );
			}

			if ( ! current_user_can( 'edit_lp_courses' ) ) {
				throw new Exception( __( 'You are not allowed to create categories', 'learnpress' ) );
			}

			$name   = sanitize_text_field( $data['name'] ?? '' );
			$parent = isset( $data['parent'] ) ? (int) $data['parent'] : 0;

			if ( $parent < 0 ) {
				$parent = 0;
			}

			$term = wp_insert_term( $name, 'course_category', array( 'parent' => $parent ) );

			if ( is_wp_error( $term ) ) {
				throw new Exception( $term->get_error_message() );
			}

			$term_id = $term['term_id'];

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
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function add_course_tag() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data      = self::check_valid_course();
			$course_id = absint( $data['course_id'] ?? 0 );
			if ( $course_id > 0 ) {
				if ( ! CourseBuilderAccessPolicy::can_edit_course_by_id( $course_id ) ) {
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress' ) );
				}
			} elseif ( ! CourseBuilderAccessPolicy::can_create_item_type( LP_COURSE_CPT ) ) {
				throw new Exception( __( 'You are not allowed to create courses', 'learnpress' ) );
			}

			if ( ! current_user_can( 'edit_lp_courses' ) ) {
				throw new Exception( __( 'You are not allowed to create tags', 'learnpress' ) );
			}

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
		} catch ( Throwable $th ) {
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
			$html                 = BuilderListLessonsTemplate::render_lesson( $lesson_model_new );
			$response->status     = 'success';
			$response->data->html = $html;
			$response->message    = __( 'Lesson duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Update Lesson from Course Builder.
	 * Supports partial updates (title only, description only, or settings only).
	 *
	 * @since 4.3
	 * @version 1.0.2
	 */
	public function builder_update_lesson() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_lesson();
			$lesson_id    = $data['lesson_id'] ?? 0;
			$title        = $data['lesson_title'] ?? null;
			$description  = $data['lesson_description'] ?? null;
			$settings     = $data['lesson_settings'] ?? false;
			$is_elementor = $data['is_elementor'] ?? false;
			$return_html  = ( $data['return_html'] ?? 'no' ) === 'yes';
			$insert       = $data['insert'];
			$lesson_slug  = ! empty( $data['lesson_permalink'] )
				? sanitize_title( wp_unslash( (string) $data['lesson_permalink'] ) )
				: '';
			$course_id    = absint( $data['course_id'] ?? 0 );

			// Determine target status (draft or publish)
			$target_status            = ! empty( $data['lesson_status'] ) && in_array( $data['lesson_status'], [ 'draft', 'publish' ], true )
				? sanitize_text_field( $data['lesson_status'] )
				: 'publish';
			$restore_with_custom_slug = false;

			if ( $insert ) {
				if ( ! CourseBuilderAccessPolicy::can_create_item_type( LP_LESSON_CPT ) ) {
					throw new Exception( __( 'You are not allowed to create lessons', 'learnpress' ) );
				}

				$insert_arg = array(
					'post_type'    => LP_LESSON_CPT,
					'post_title'   => sanitize_text_field( $title ?? '' ),
					'post_content' => wp_kses_post( $description ?? '' ),
					'post_status'  => $target_status,
				);

				if ( ! empty( $lesson_slug ) ) {
					$insert_arg['post_name'] = $lesson_slug;
				}

				$lesson_id = wp_insert_post( $insert_arg, true );

				if ( is_wp_error( $lesson_id ) ) {
					throw new Exception( $lesson_id->get_error_message() );
				}

				$lesson_model = LessonPostModel::find( $lesson_id, true );
			} else {
				$lesson_model = $data['lesson_model'];

				if ( ! $lesson_model ) {
					throw new Exception( __( 'Lesson not found', 'learnpress' ) );
				}

				if ( ! $course_id ) {
					$course_id = absint( $this->get_course_by_item_id( $lesson_id ) );
				}

				// Support for co-instructor.
				$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
				$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

				if ( absint( $lesson_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this lesson', 'learnpress' ) );
				}

				$update_arg = array(
					'ID'          => $lesson_id,
					'post_type'   => LP_LESSON_CPT,
					'post_status' => $target_status,
				);

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $lesson_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				if ( isset( $data['lesson_title'] ) ) {
					$update_arg['post_title'] = sanitize_text_field( $title );
				}

				if ( isset( $data['lesson_description'] ) ) {
					$update_arg['post_content'] = wp_kses_post( $description );
				}

				if ( ! empty( $lesson_slug ) ) {
					$update_arg['post_name'] = $lesson_slug;
				}

				$restore_with_custom_slug = $this->prepare_desired_slug_for_restore( $lesson_id, $target_status, $lesson_slug );

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}

				if ( $restore_with_custom_slug ) {
					$this->sync_slug_after_restore( $lesson_id, $lesson_slug );
				}

				if ( $course_id ) {
					$course_model_cache = CourseModel::find( $course_id, true );
					if ( $course_model_cache ) {
						$course_model_cache->sections_items = null;
						$course_model_cache->save();
					}
				}
			}

			if ( $settings && $lesson_model ) {
				$this->save_lesson_settings_to_model( $lesson_model, $data );
			}

			// Remove lesson from curriculum if status is not public
			if ( $target_status !== 'publish' ) {
				$this->remove_course_item_from_curriculum( $lesson_id, $course_id );
			}

			do_action( 'learn-press/course-builder/update-lesson', $data, $lesson_model );

			$saved_lesson_status = get_post_status( $lesson_id );
			if ( ! is_string( $saved_lesson_status ) || '' === $saved_lesson_status ) {
				$saved_lesson_status = $target_status;
			}

			$response->status              = 'success';
			$response->data->status        = $saved_lesson_status;
			$response->data->button_title  = $saved_lesson_status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );
			$response->data->lesson_id_new = $insert ? $lesson_id : '';
			$response->message             = $target_status === 'draft'
				? esc_html__( 'Lesson saved as draft', 'learnpress' )
				: ( $insert ? esc_html__( 'Insert lesson successfully', 'learnpress' ) : esc_html__( 'Update lesson successfully', 'learnpress' ) );

			$saved_lesson_post = get_post( $lesson_id );
			if ( $saved_lesson_post ) {
				$response->data->lesson_slug = $saved_lesson_post->post_name;
			}

			$course_id_of_item                   = $this->get_course_by_item_id( $lesson_id );
			$response->data->permalink_available = false;
			$response->data->permalink_notice    = $this->get_item_permalink_unavailable_message();
			if ( 'publish' === $saved_lesson_status && $course_id_of_item ) {
				$course = learn_press_get_course( $course_id_of_item );
				if ( $course ) {
					$response->data->permalink_available = true;
					$response->data->lesson_permalink    = urldecode( $course->get_item_link( $lesson_id ) );
				}
			}

			$lesson_model_for_html = LessonPostModel::find( $lesson_id, true );
			if ( $return_html ) {
				$response->data->list_item_html = $lesson_model_for_html
					? BuilderListLessonsTemplate::render_lesson( $lesson_model_for_html )
					: '';

				if ( $course_id ) {
					$course_model_for_html = CourseModel::find( $course_id, true );
					if ( $course_model_for_html && $lesson_model_for_html ) {
						$item            = new stdClass();
						$item->item_id   = $lesson_id;
						$item->title     = $lesson_model_for_html->post_title;
						$item->item_type = LP_LESSON_CPT;
						AdminEditCurriculumTemplate::instance()->context_data = [ 'is_course_builder' => true ];
						$response->data->section_item_html                    = AdminEditCurriculumTemplate::instance()->html_section_item( $course_model_for_html, $item );
					}
				}
			}

			wp_send_json( $response );
		} catch ( Throwable $th ) {
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
			$lesson_slug  = ! empty( $data['lesson_permalink'] )
				? sanitize_title( wp_unslash( (string) $data['lesson_permalink'] ) )
				: '';

			if ( ! $lesson_model ) {
				throw new Exception( __( 'Lesson not found', 'learnpress' ) );
			}

			if ( absint( $lesson_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete this lesson', 'learnpress' ) );
			}
			$course_id = absint( $data['course_id'] ?? 0 );
			if ( ! $course_id ) {
				$course_id = absint( $this->get_course_by_item_id( $lesson_id ) );
			}

			if ( $status === 'trash' ) {
				$original_slug = (string) get_post_field( 'post_name', $lesson_id );
				$move_trash    = wp_trash_post( $lesson_id );

				if ( is_wp_error( $move_trash ) ) {
					throw new Exception( esc_html__( 'Cannot move this lesson to trash', 'learnpress' ) );
				}

				if ( '' !== $original_slug ) {
					wp_update_post(
						[
							'ID'        => $lesson_id,
							'post_name' => $original_slug,
						]
					);
				}

				$message = esc_html__( 'This lesson has been moved to trash.', 'learnpress' );
			} elseif ( $status === 'delete' ) {

				if ( $lesson_model->post_status !== 'trash' ) {
					throw new Exception( esc_html__( 'Lesson must be trashed before deleting.', 'learnpress' ) );
				}

				$delete = wp_delete_post( $lesson_id );

				if ( is_wp_error( $delete ) ) {
					throw new Exception( esc_html__( 'Cannot delete this lesson.', 'learnpress' ) );
				}
				$message = esc_html__( 'Delete this lesson successfully', 'learnpress' );
			} elseif ( in_array( $status, [ 'publish', 'draft' ], true ) ) {
				$restore_with_custom_slug = $this->prepare_desired_slug_for_restore( $lesson_id, $status, $lesson_slug );
				$update_args              = [
					'ID'          => $lesson_id,
					'post_type'   => LP_LESSON_CPT,
					'post_status' => $status,
				];

				if ( '' !== $lesson_slug ) {
					$update_args['post_name'] = $lesson_slug;
				}

				$update = wp_update_post( $update_args );
				if ( ! $update ) {
					if ( 'draft' === $status ) {
						throw new Exception( __( 'Lesson cannot be moved to draft', 'learnpress' ) );
					}

					throw new Exception( __( 'Lesson cannot be moved to publish', 'learnpress' ) );
				}

				if ( $restore_with_custom_slug ) {
					$this->sync_slug_after_restore( $lesson_id, $lesson_slug );
				}

				$message = 'draft' === $status
					? __( 'Lesson has been moved to draft', 'learnpress' )
					: __( 'Lesson has been moved to publish', 'learnpress' );
			} else {
				throw new Exception( __( 'Invalid lesson status transition', 'learnpress' ) );
			}

			$saved_lesson_status = get_post_status( $lesson_id );
			if ( ! is_string( $saved_lesson_status ) || '' === $saved_lesson_status ) {
				$saved_lesson_status = $status;
			}

			if ( $saved_lesson_status !== 'publish' ) {
				$this->remove_course_item_from_curriculum( $lesson_id, $course_id );
			}

			$response->data->status              = $saved_lesson_status;
			$response->data->button_title        = 'publish' === $saved_lesson_status ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );
			$response->data->lesson_slug         = (string) get_post_field( 'post_name', $lesson_id );
			$response->data->permalink_available = false;
			$response->data->permalink_notice    = $this->get_item_permalink_unavailable_message();

			$course_id_of_item = $this->get_course_by_item_id( $lesson_id );
			if ( 'publish' === $saved_lesson_status && $course_id_of_item ) {
				$course = learn_press_get_course( $course_id_of_item );
				if ( $course ) {
					$response->data->permalink_available = true;
					$response->data->lesson_permalink    = urldecode( $course->get_item_link( $lesson_id ) );
				}
			}

			if ( 'delete' !== $status ) {
				$lesson_model_new     = LessonPostModel::find( $lesson_id, true );
				$response->data->html = $lesson_model_new ? BuilderListLessonsTemplate::render_lesson( $lesson_model_new ) : '';
			}

			$response->status  = 'success';
			$response->message = $message;
			wp_send_json( $response );
		} catch ( Throwable $th ) {
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
			$enable = $data['_lp_preview'] === 'yes';
			$lessonModel->set_preview( $enable );
		}
	}

	/**
	 * Save Quiz Settings to QuizPostModel
	 *
	 * @param QuizPostModel $quizModel
	 * @param array $data
	 *
	 * @since 4.3
	 * @version 1.0.0
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
			'_lp_show_check_answer',
			'_lp_show_hint',
		];

		foreach ( $checkbox_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$value = $data[ $key ] === 'yes' ? 'yes' : 'no';
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
				$value = absint( $data[ $key ] );
				if ( '_lp_passing_grade' === $key && $value > 100 ) {
					$value = 100;
				}
				$quizModel->save_meta_value_by_key( $key, $value );
			}
		}
	}

	/**
	 * Update Quiz from Course Builder.
	 * Supports partial updates (title only, description only, or settings only).
	 *
	 * @since 4.3
	 * @version 1.0.2
	 */
	public function builder_update_quiz() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data         = self::check_valid_quiz();
			$quiz_id      = $data['quiz_id'] ?? 0;
			$title        = $data['quiz_title'] ?? null;
			$description  = $data['quiz_description'] ?? null;
			$settings     = $data['quiz_settings'] ?? false;
			$is_elementor = $data['is_elementor'] ?? false;
			$return_html  = ( $data['return_html'] ?? 'no' ) === 'yes';
			$insert       = $data['insert'];
			$quiz_slug    = ! empty( $data['quiz_permalink'] )
				? sanitize_title( wp_unslash( (string) $data['quiz_permalink'] ) )
				: '';
			$course_id    = absint( $data['course_id'] ?? 0 );

			// Determine target status (draft or publish)
			$target_status            = ! empty( $data['quiz_status'] ) && in_array( $data['quiz_status'], [ 'draft', 'publish' ], true )
				? sanitize_text_field( $data['quiz_status'] )
				: 'publish';
			$restore_with_custom_slug = false;

			if ( $insert ) {
				if ( ! CourseBuilderAccessPolicy::can_create_item_type( LP_QUIZ_CPT ) ) {
					throw new Exception( __( 'You are not allowed to create quizzes', 'learnpress' ) );
				}

				$insert_arg = array(
					'post_type'    => LP_QUIZ_CPT,
					'post_title'   => sanitize_text_field( $title ?? '' ),
					'post_content' => wp_kses_post( $description ?? '' ),
					'post_status'  => $target_status,
				);

				if ( ! empty( $quiz_slug ) ) {
					$insert_arg['post_name'] = $quiz_slug;
				}

				$quiz_id = wp_insert_post( $insert_arg, true );

				if ( is_wp_error( $quiz_id ) ) {
					throw new Exception( $quiz_id->get_error_message() );
				}

				$quiz_model = QuizPostModel::find( $quiz_id, true );
			} else {
				$quiz_model = $data['quiz_model'];

				if ( ! $quiz_model ) {
					throw new Exception( __( 'Quiz not found', 'learnpress' ) );
				}

				if ( ! $course_id ) {
					$course_id = absint( $this->get_course_by_item_id( $quiz_id ) );
				}

				// Support for co-instructor.
				$co_instructor_ids = get_post_meta( $course_id, '_lp_co_teacher', false );
				$co_instructor_ids = ! empty( $co_instructor_ids ) ? $co_instructor_ids : array();

				if ( absint( $quiz_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) && ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this quiz', 'learnpress' ) );
				}

				$update_arg = array(
					'ID'          => $quiz_id,
					'post_type'   => LP_QUIZ_CPT,
					'post_status' => $target_status,
				);

				if ( isset( $data['quiz_title'] ) ) {
					$update_arg['post_title'] = sanitize_text_field( $title );
				}

				if ( isset( $data['quiz_description'] ) ) {
					$update_arg['post_content'] = wp_kses_post( $description );
				}

				if ( ! empty( $quiz_slug ) ) {
					$update_arg['post_name'] = $quiz_slug;
				}

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $quiz_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				$restore_with_custom_slug = $this->prepare_desired_slug_for_restore( $quiz_id, $target_status, $quiz_slug );

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}

				if ( $restore_with_custom_slug ) {
					$this->sync_slug_after_restore( $quiz_id, $quiz_slug );
				}

				if ( $course_id ) {
					$course_model_cache = CourseModel::find( $course_id, true );
					if ( $course_model_cache ) {
						$course_model_cache->sections_items = null;
						$course_model_cache->save();
					}
				}
			}

			if ( $settings && $quiz_model ) {
				$this->save_quiz_settings_to_model( $quiz_model, $data );
			}

			// Remove quiz from curriculum if status is not public
			if ( $target_status !== 'publish' ) {
				$this->remove_course_item_from_curriculum( $quiz_id, $course_id );
			}

			do_action( 'learn-press/course-builder/update-quiz', $data, $quiz_model );

			$saved_quiz_status = get_post_status( $quiz_id );
			if ( ! is_string( $saved_quiz_status ) || '' === $saved_quiz_status ) {
				$saved_quiz_status = $target_status;
			}

			if ( $insert ) {
				$response->data->redirect_url = CourseBuilder::get_link_course_builder(
					CourseBuilderTemplate::MENU_QUIZZES . "/{$quiz_id}"
				);
			}

			$response->status             = 'success';
			$response->data->status       = $saved_quiz_status;
			$response->data->button_title = $saved_quiz_status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );
			$response->message            = $target_status === 'draft'
				? esc_html__( 'Quiz saved as draft', 'learnpress' )
				: ( $insert ? esc_html__( 'Insert quiz successfully', 'learnpress' ) : esc_html__( 'Update quiz successfully', 'learnpress' ) );

			$saved_quiz_post = get_post( $quiz_id );
			if ( $saved_quiz_post ) {
				$response->data->quiz_slug = $saved_quiz_post->post_name;
			}

			$course_id_of_item                   = $this->get_course_by_item_id( $quiz_id );
			$response->data->permalink_available = false;
			$response->data->permalink_notice    = $this->get_item_permalink_unavailable_message();
			if ( 'publish' === $saved_quiz_status && $course_id_of_item ) {
				$course = learn_press_get_course( $course_id_of_item );
				if ( $course ) {
					$response->data->permalink_available = true;
					$response->data->quiz_permalink      = urldecode( $course->get_item_link( $quiz_id ) );
				}
			}

			if ( $return_html ) {
				$quiz_model_new                 = QuizPostModel::find( $quiz_id, true );
				$response->data->list_item_html = $quiz_model_new
					? BuilderListQuizzesTemplate::render_quiz( $quiz_model_new )
					: '';

				if ( $course_id ) {
					$course_model_for_html = CourseModel::find( $course_id, true );
					if ( $course_model_for_html && $quiz_model_new ) {
						$item            = new stdClass();
						$item->item_id   = $quiz_id;
						$item->title     = $quiz_model_new->post_title;
						$item->item_type = LP_QUIZ_CPT;
						AdminEditCurriculumTemplate::instance()->context_data = [ 'is_course_builder' => true ];
						$response->data->section_item_html                    = AdminEditCurriculumTemplate::instance()->html_section_item( $course_model_for_html, $item );
					}
				}
			}

			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
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
			$response->status             = 'success';
			$response->data->redirect_url = CourseBuilder::get_link_course_builder( CourseBuilderTemplate::MENU_QUIZZES . "/{$new_item_id}" );
			$response->message            = __( 'Quiz duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
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
			$quiz_slug  = ! empty( $data['quiz_permalink'] )
				? sanitize_title( wp_unslash( (string) $data['quiz_permalink'] ) )
				: '';

			if ( ! $quiz_model ) {
				throw new Exception( __( 'Quiz not found', 'learnpress' ) );
			}

			if ( absint( $quiz_model->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
				throw new Exception( __( 'You are not allowed to delete this quiz', 'learnpress' ) );
			}
			$course_id = absint( $data['course_id'] ?? 0 );
			if ( ! $course_id ) {
				$course_id = absint( $this->get_course_by_item_id( $quiz_id ) );
			}

			if ( $status === 'trash' ) {
				$original_slug = (string) get_post_field( 'post_name', $quiz_id );
				$move_trash    = wp_trash_post( $quiz_id );

				if ( is_wp_error( $move_trash ) ) {
					throw new Exception( esc_html__( 'Cannot move this quiz to trash', 'learnpress' ) );
				}

				if ( '' !== $original_slug ) {
					wp_update_post(
						[
							'ID'        => $quiz_id,
							'post_name' => $original_slug,
						]
					);
				}

				$message = esc_html__( 'This quiz has been moved to trash.', 'learnpress' );
			} elseif ( $status === 'delete' ) {

				if ( $quiz_model->post_status !== 'trash' ) {
					throw new Exception( esc_html__( 'Quiz must be trashed before deleting.', 'learnpress' ) );
				}

				$delete = wp_delete_post( $quiz_id );

				if ( is_wp_error( $delete ) ) {
					throw new Exception( esc_html__( 'Cannot delete this quiz.', 'learnpress' ) );
				}
				$message = esc_html__( 'Delete this quiz successfully', 'learnpress' );
			} elseif ( in_array( $status, [ 'publish', 'draft' ], true ) ) {
				$restore_with_custom_slug = $this->prepare_desired_slug_for_restore( $quiz_id, $status, $quiz_slug );
				$update_args              = [
					'ID'          => $quiz_id,
					'post_type'   => LP_QUIZ_CPT,
					'post_status' => $status,
				];

				if ( '' !== $quiz_slug ) {
					$update_args['post_name'] = $quiz_slug;
				}

				$update = wp_update_post( $update_args );
				if ( ! $update ) {
					if ( 'draft' === $status ) {
						throw new Exception( __( 'Quiz cannot be moved to draft', 'learnpress' ) );
					}

					throw new Exception( __( 'Quiz cannot be moved to publish', 'learnpress' ) );
				}

				if ( $restore_with_custom_slug ) {
					$this->sync_slug_after_restore( $quiz_id, $quiz_slug );
				}

				$message = 'draft' === $status
					? __( 'Quiz has been moved to draft', 'learnpress' )
					: __( 'Quiz has been moved to publish', 'learnpress' );
			} else {
				throw new Exception( __( 'Invalid quiz status transition', 'learnpress' ) );
			}

			$saved_quiz_status = get_post_status( $quiz_id );
			if ( ! is_string( $saved_quiz_status ) || '' === $saved_quiz_status ) {
				$saved_quiz_status = $status;
			}

			if ( $saved_quiz_status !== 'publish' ) {
				$this->remove_course_item_from_curriculum( $quiz_id, $course_id );
			}

			$response->data->status              = $saved_quiz_status;
			$response->data->button_title        = 'publish' === $saved_quiz_status ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );
			$response->data->quiz_slug           = (string) get_post_field( 'post_name', $quiz_id );
			$response->data->permalink_available = false;
			$response->data->permalink_notice    = $this->get_item_permalink_unavailable_message();

			$course_id_of_item = $this->get_course_by_item_id( $quiz_id );
			if ( 'publish' === $saved_quiz_status && $course_id_of_item ) {
				$course = learn_press_get_course( $course_id_of_item );
				if ( $course ) {
					$response->data->permalink_available = true;
					$response->data->quiz_permalink      = urldecode( $course->get_item_link( $quiz_id ) );
				}
			}

			if ( 'delete' !== $status ) {
				$fresh_quiz_model     = QuizPostModel::find( $quiz_id, true );
				$response->data->html = $fresh_quiz_model
					? BuilderListQuizzesTemplate::render_quiz( $fresh_quiz_model )
					: '';
			}

			$response->status  = 'success';
			$response->message = $message;
			wp_send_json( $response );
		} catch ( Throwable $th ) {
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
			$response->status             = 'success';
			$response->data->redirect_url = CourseBuilder::get_link_course_builder( CourseBuilderTemplate::MENU_QUESTIONS . "/{$new_item_id}" );
			$response->message            = __( 'Question duplicated successfully', 'learnpress' );
			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	public function builder_update_question() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$data          = self::check_valid_question();
			$question_id   = $data['question_id'] ?? 0;
			$title         = $data['question_title'] ?? '';
			$description   = $data['question_description'] ?? '';
			$is_elementor  = $data['is_elementor'] ?? false;
			$return_html   = ( $data['return_html'] ?? 'no' ) === 'yes';
			$insert        = $data['insert'];
			$question_slug = ! empty( $data['question_permalink'] )
				? sanitize_title( wp_unslash( (string) $data['question_permalink'] ) )
				: '';

			// Determine target status (draft or publish)
			$target_status = ! empty( $data['question_status'] ) && in_array( $data['question_status'], [ 'draft', 'publish' ], true )
				? sanitize_text_field( $data['question_status'] )
				: 'publish';

			if ( $insert ) {
				if ( ! CourseBuilderAccessPolicy::can_create_item_type( LP_QUESTION_CPT ) ) {
					throw new Exception( __( 'You are not allowed to create questions', 'learnpress' ) );
				}

				$insert_arg = array(
					'post_type'    => LP_QUESTION_CPT,
					'post_title'   => sanitize_text_field( $title ?? '' ),
					'post_content' => $description ?? '',
					'post_status'  => $target_status,
				);

				if ( ! empty( $question_slug ) ) {
					$insert_arg['post_name'] = $question_slug;
				}

				$question_id = wp_insert_post( $insert_arg, true );

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
					'post_status' => $target_status,
				);

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $question_id )->set_is_built_with_elementor( ! empty( $is_elementor ) );
				}

				if ( isset( $data['question_title'] ) ) {
					$update_arg['post_title'] = sanitize_text_field( $title );
				}

				if ( isset( $data['question_description'] ) ) {
					$update_arg['post_content'] = wp_kses_post( $description );
				}

				if ( ! empty( $question_slug ) ) {
					$update_arg['post_name'] = $question_slug;
				}

				$update = wp_update_post( $update_arg );

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}
			}

			// Remove question from quizzes if status is not public
			if ( $target_status !== 'publish' ) {
				$this->remove_question_from_assigned_quizzes( $question_id );
			}

			do_action( 'learn-press/course-builder/update-question', $data, $question_model ?? null );

			$saved_question_status = get_post_status( $question_id );
			if ( ! is_string( $saved_question_status ) || '' === $saved_question_status ) {
				$saved_question_status = $target_status;
			}

			if ( $insert ) {
				$response->data->redirect_url = CourseBuilder::get_link_course_builder(
					CourseBuilderTemplate::MENU_QUESTIONS . "/{$question_id}"
				);
			}

			$response->status             = 'success';
			$response->data->status       = $saved_question_status;
			$response->data->button_title = $saved_question_status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );

			$response->message = $target_status === 'draft'
				? esc_html__( 'Question saved as draft', 'learnpress' )
				: ( $insert ? esc_html__( 'Insert question successfully', 'learnpress' ) : esc_html__( 'Update question successfully', 'learnpress' ) );

			$saved_question = get_post( $question_id );
			if ( $saved_question ) {
				$response->data->question_slug      = $saved_question->post_name;
				$response->data->question_permalink = get_permalink( $question_id );
			}

			if ( $return_html ) {
				$question_model_new             = QuestionPostModel::find( $question_id, true );
				$response->data->list_item_html = $question_model_new
					? BuilderListQuestionsTemplate::render_question( $question_model_new )
					: '';
			}

			wp_send_json( $response );
		} catch ( Throwable $th ) {
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
				$message = esc_html__( 'This question has been moved to trash.', 'learnpress' );
			} elseif ( $status === 'delete' ) {

				if ( $question_model->post_status !== 'trash' ) {
					throw new Exception( esc_html__( 'Question must be trashed before deleting.', 'learnpress' ) );
				}

				$delete = wp_delete_post( $question_id );

				if ( is_wp_error( $delete ) ) {
					throw new Exception( esc_html__( 'Cannot delete this question.', 'learnpress' ) );
				}
				$message = esc_html__( 'Delete this question successfully', 'learnpress' );

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
			} elseif ( $status === 'draft' ) {
				$update = wp_update_post(
					array(
						'ID'          => $question_id,
						'post_type'   => LP_QUESTION_CPT,
						'post_status' => 'draft',
					)
				);
				if ( ! $update ) {
					throw new Exception( __( 'Question cannot be restored to draft', 'learnpress' ) );
				}

				$message = __( 'Question has been restored to draft', 'learnpress' );
			}

			if ( $status !== 'publish' ) {
				$this->remove_question_from_assigned_quizzes( $question_id );
			}

			$response->data->status       = $status;
			$response->data->button_title = __( 'Publish', 'learnpress' );

			if ( 'delete' !== $status ) {
				$fresh_question_model = QuestionPostModel::find( $question_id, true );
				$response->data->html = $fresh_question_model
					? BuilderListQuestionsTemplate::render_question( $fresh_question_model )
					: '';
			}

			$response->status  = 'success';
			$response->message = $message;
			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}

	/**
	 * Remove lesson/quiz assignment from curriculum sections.
	 *
	 * @param int $item_id
	 * @param int $course_id_fallback
	 *
	 * @return void
	 */
	protected function remove_course_item_from_curriculum( int $item_id, int $course_id_fallback = 0 ) {
		if ( $item_id <= 0 ) {
			return;
		}

		global $wpdb;

		$section_items = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT si.section_id, s.section_course_id
				FROM {$wpdb->learnpress_section_items} si
				INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
				WHERE si.item_id = %d
				",
				$item_id
			)
		);
		if ( ! $section_items ) {
			if ( $course_id_fallback > 0 ) {
				$this->clean_course_curriculum_cache( $course_id_fallback );
			}

			return;
		}

		foreach ( $section_items as $section_item ) {
			$section_id = absint( $section_item->section_id ?? 0 );
			$course_id  = absint( $section_item->section_course_id ?? 0 );
			if ( ! $section_id || ! $course_id ) {
				continue;
			}

			$course_section_item_model = CourseSectionItemModel::find( $section_id, $item_id );
			if ( ! $course_section_item_model ) {
				continue;
			}

			$course_section_item_model->section_course_id = $course_id;
			$course_section_item_model->delete();
		}
	}

	/**
	 * Keep course curriculum caches in sync after direct section item removal.
	 *
	 * @param int $course_id
	 *
	 * @return void
	 */
	protected function clean_course_curriculum_cache( int $course_id ) {
		if ( $course_id <= 0 ) {
			return;
		}

		try {
			$course_model = CourseModel::find( $course_id, true );
			if ( ! $course_model ) {
				return;
			}

			$course_model->sections_items = null;
			$course_model->total_items    = null;
			$course_model->save( true );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Remove question assignment from all quizzes.
	 *
	 * @param int $question_id
	 *
	 * @return void
	 */
	protected function remove_question_from_assigned_quizzes( int $question_id ) {
		if ( $question_id <= 0 ) {
			return;
		}

		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'learnpress_quiz_questions',
			array( 'question_id' => $question_id ),
			array( '%d' )
		);
	}

	protected function get_course_by_item_id( $item_id ) {
		static $cache = [];

		global $wpdb;

		if ( empty( $item_id ) ) {
			return false;
		}

		$item_id = absint( $item_id );
		if ( isset( $cache[ $item_id ] ) ) {
			return $cache[ $item_id ] ? $cache[ $item_id ] : false;
		}

		$course_id         = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT c.ID FROM {$wpdb->posts} c
				INNER JOIN {$wpdb->learnpress_sections} s ON c.ID = s.section_course_id
				INNER JOIN {$wpdb->learnpress_section_items} si ON si.section_id = s.section_id
				WHERE si.item_id = %d ORDER BY si.section_id DESC LIMIT 1
				",
				$item_id
			)
		);
		$cache[ $item_id ] = absint( $course_id );

		if ( $cache[ $item_id ] ) {
			return $cache[ $item_id ];
		}

		return false;
	}

	/**
	 * Save global settings for Course Builder.
	 *
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function save_global_settings() {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$params = wp_unslash( $_REQUEST['data'] ?? '' );
			if ( empty( $params ) ) {
				throw new Exception( 'Error: params invalid!' );
			}

			$data = LP_Helper::json_decode( $params, true );

			if ( ! current_user_can( ADMIN_ROLE ) ) {
				throw new Exception( __( 'Permission denied', 'learnpress' ) );
			}

			$hide_instructor_access_admin_screen = ! empty( $data['hide_instructor_access_admin_screen'] ) && $data['hide_instructor_access_admin_screen'] === 'yes' ? 'yes' : 'no';
			$logo_remove          = ! empty( $data['course_builder_logo_remove'] ) && $data['course_builder_logo_remove'] === 'yes';
			$logo_id              = absint( $data['course_builder_logo_id'] ?? 0 );

			if ( $logo_remove ) {
				$logo_id = 0;
			}

			LP_Settings::update_option( 'hide_instructor_access_admin_screen', $hide_instructor_access_admin_screen );
			LP_Settings::update_option( 'course_builder_logo_id', $logo_id );

			$logo_url = '';
			if ( $logo_id ) {
				$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
			}

			$response->status                        = 'success';
			$response->message                       = __( 'Course Builder settings updated.', 'learnpress' );
			$response->data->hide_instructor_access_admin_screen    = $hide_instructor_access_admin_screen;
			$response->data->course_builder_logo_id  = $logo_id;
			$response->data->course_builder_logo_url = $logo_url;

			wp_send_json( $response );
		} catch ( Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
			wp_send_json( $response );
		}
	}
}
