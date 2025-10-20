<?php

namespace LearnPress\CourseBuilder;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LP_Course_Post_Type;
use LP_Forms_Handler;
use LP_REST_Profile_Controller;
use LP_Settings;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * Course Builder class.
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class CourseBuilder {
	/**
	 *  Constructor
	 *
	 */
	protected function __construct() {
	}

	/**
	 * Get tabs default in course builder.
	 *
	 * @return array
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_tabs_arr(): array {
		$tab_arr = [
			'courses'   => array(
				'title'    => esc_html__( 'Courses', 'learnpress' ),
				'slug'     => 'courses',
				'sections' => array(
					'overview'   => array(
						'title' => esc_html__( 'Course Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'curriculum' => array(
						'title' => esc_html__( 'Curriculum', 'learnpress' ),
						'slug'  => 'curriculum',
					),
					'settings'   => array(
						'title' => esc_html__( 'Course Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'lessons'   => array(
				'title'    => esc_html__( 'Lessons', 'learnpress' ),
				'slug'     => 'lessons',
				'sections' => array(
					'overview' => array(
						'title' => esc_html__( 'Lesson Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'settings' => array(
						'title' => esc_html__( 'Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'quizzes'   => array(
				'title'    => esc_html__( 'Quizzes', 'learnpress' ),
				'slug'     => 'quizzes',
				'sections' => array(
					'overview' => array(
						'title' => esc_html__( 'Quizz Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'question' => array(
						'title' => esc_html__( 'Question', 'learnpress' ),
						'slug'  => 'question',
					),
					'settings' => array(
						'title' => esc_html__( 'Quizz Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'questions' => array(
				'title'    => esc_html__( 'Questions', 'learnpress' ),
				'slug'     => 'questions',
				'sections' => array(
					'overview' => array(
						'title' => esc_html__( 'Question Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'settings' => array(
						'title' => esc_html__( 'Question Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
		];

		return $tab_arr;
	}

	/**
	 * Get the current course builder tab.
	 * @param string $current
	 * @return string
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_current_tab() {
		global $wp;
		$current = '';
		if ( ! empty( $_REQUEST['tab'] ) ) {
			$current = sanitize_text_field( $_REQUEST['tab'] );
		} elseif ( ! empty( $wp->query_vars['tab'] ) ) {
			$current = $wp->query_vars['tab'];
		} else {
			$tab_data    = self::get_tabs_arr();
			$current_tab = reset( $tab_data );
			if ( ! empty( $current_tab['slug'] ) ) {
				$current = $current_tab['slug'];
			} else {
				$current = array_keys( $tab_data );
			}
		}

		return $current;
	}

	/**
	 * Get the current section being viewed in a course builder tab.
	 * @param string $current
	 * @param string $tab
	 * @return string
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_current_section( $current = '', $tab = '' ) {
		global $wp;

		if ( empty( $_REQUEST['post_id'] ) && empty( $wp->query_vars['post_id'] ) ) {
			return $current;
		}

		if ( ! empty( $_REQUEST['section'] ) ) {
			$current = sanitize_text_field( $_REQUEST['section'] );
		} elseif ( ! empty( $wp->query_vars['section'] ) ) {
			$current = $wp->query_vars['section'];
		} else {
			if ( ! $tab ) {
				$current_tab = self::get_current_tab();
			} else {
				$current_tab = $tab;
			}
			$tab_data = self::get_data( $current_tab );

			if ( ! empty( $tab_data['sections'] ) ) {
				$sections = $tab_data['sections'];
				$section  = reset( $sections );
				if ( ! empty( $section['slug'] ) ) {
					$current = $section['slug'];
				} else {
					$current = array_keys( $tab_data['sections'] );
				}
			}
		}

		return $current;
	}

	/**
	 * Retrieves tabs data or a specific tab by key.
	 *
	 * @param string|bool
	 * @return array
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_data( $key = false ) {
		$tabs = self::get_tabs_arr();
		return false !== $key ? ( array_key_exists( $key, $tabs ) ? $tabs[ $key ] : [] ) : $tabs;
	}

	public static function get_link_course_builder( $sub = '' ) {
		$page = LP_Settings::get_option( 'course_builder', 'course-builder' );
		$link = sprintf( '%s/%s/', home_url(), $page );
		$tab  = self::get_current_tab();

		if ( $sub ) {
			$link .= $tab . '/' . $sub;
		}
		return $link;
	}

	public static function get_tab_link( $tab = false, $post_id = null, $section = false ) {
		$link = '';
		if ( ! $tab ) {
			return $link;
		}

		$link = self::get_link_course_builder();

		if ( ! empty( $tab ) ) {
			$link .= $tab . '/';
		}

		if ( ! empty( $post_id ) ) {
			$link .= $post_id . '/';
		}

		if ( ! empty( $section ) ) {
			$link .= $section . '/';
		}

		return $link;
	}

	/**
	 * Get post id
	 *
	 * @return int| post-new
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_post_id() {
		global $wp;
		$post_id = 0;
		if ( ! empty( $_REQUEST['post_id'] ) ) {
			$post_id = $_REQUEST['post_id'];
		}

		if ( ! empty( $wp->query_vars['post_id'] ) ) {
			$post_id = $wp->query_vars['post_id'];
		}

		return $post_id;
	}

	public static function can_view_course_builder() {
		return is_user_logged_in() && current_user_can( 'edit_lp_courses' );
	}

	public function save_courses( $request ) {
		$general  = $request->get_param( 'general' );
		$sections = $request->get_param( 'section' );
		$insert   = $request->get_param( 'insert' );

		$course_id = absint( $general['id'] );

		try {
			global $wpdb;

			if ( ! $course_id && ! $insert ) {
				throw new Exception( __( 'Course not found', 'learnpress-frontend-editor' ) );
			}

			if ( $insert ) {
				$course_id = wp_insert_post(
					array(
						'post_type'    => LP_COURSE_CPT,
						'post_title'   => sanitize_text_field( $general['title'] ?? '' ),
						'post_content' => wp_unslash( $general['description'] ?? '' ),
						'post_status'  => ! empty( $general['post_status'] ) ? sanitize_text_field( $general['post_status'] ) : 'publish',
						'post_name'    => sanitize_text_field( $general['permalink'] ),
						'tax_input'    => array(
							'course_category' => ! empty( $general['categories'] ) ? array_map( 'absint', $general['categories'] ) : array(),
							'course_tag'      => ! empty( $general['tags'] ) ? array_map( 'absint', $general['tags'] ) : array(),
						),
					),
					true
				);

				if ( is_wp_error( $course_id ) ) {
					throw new Exception( $course_id->get_error_message() );
				}
			} else {
				$course = CourseModel::find( $course_id, true );
				if ( ! $course ) {
					throw new Exception( __( 'Course not found', 'learnpress-frontend-editor' ) );
				}

				// Support for co-instructor.
				$co_instructor_ids = $course->get_meta_value_by_key( '_lp_co_teacher', [] );
				if ( absint( $course->post_author ) !== get_current_user_id() && ! current_user_can( 'manage_options' )
					&& ! in_array( get_current_user_id(), $co_instructor_ids ) ) {
					throw new Exception( __( 'You are not allowed to update this course', 'learnpress-frontend-editor' ) );
				}

				$update = wp_update_post(
					array(
						'ID'           => $course_id,
						'post_type'    => LP_COURSE_CPT,
						'post_title'   => sanitize_text_field( $general['title'] ?? '' ),
						'post_content' => wp_unslash( $general['description'] ?? '' ),
						'post_status'  => ! empty( $general['post_status'] ) ? sanitize_text_field( $general['post_status'] ) : 'publish',
						'post_name'    => sanitize_text_field( $general['permalink'] ),
						'tax_input'    => array(
							'course_category' => ! empty( $general['categories'] ) ? array_map( 'absint', $general['categories'] ) : [],
							'course_tag'      => ! empty( $general['tags'] ) ? array_map( 'absint', $general['tags'] ) : [],
						),
					)
				);

				if ( is_wp_error( $update ) ) {
					throw new Exception( $update->get_error_message() );
				}

				if ( defined( 'ELEMENTOR_VERSION' ) ) {
					\Elementor\Plugin::$instance->documents->get( $course_id )->set_is_built_with_elementor( ! empty( $general['is_elementor'] ) );
				}
			}

			if ( ! empty( $general['featuredImage']['id'] ) ) {
				set_post_thumbnail( $course_id, absint( $general['featuredImage']['id'] ) );
			}

			$userModel = UserModel::find( get_current_user_id(), true );

			// Save settings post_meta.
			if ( ! empty( $general['settings'] ) ) {
				foreach ( $general['settings'] as $setting_content ) {
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
		} catch ( \Throwable $th ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $th->getMessage(),
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => $insert ? __( 'Insert course successfully!', 'learnpress' ) : __( 'Update course successfully!', 'learnpress' ),
			)
		);
	}

	public function update_settings( $request ) {
		$user = wp_get_current_user();

		if ( ! $user instanceof WP_User ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Instructor not found', 'learnpress-frontend-editor' ),
				)
			);
		}

		$update_data = array(
			'ID'           => get_current_user_id(),
			'first_name'   => ! empty( $request['first_name'] ) ? sanitize_text_field( $request['first_name'] ) : '',
			'last_name'    => ! empty( $request['last_name'] ) ? sanitize_text_field( $request['last_name'] ) : '',
			'description'  => ! empty( $request['description'] ) ? sanitize_textarea_field( $request['description'] ) : '',
			'display_name' => ! empty( $request['display_name'] ) ? sanitize_text_field( $request['display_name'] ) : '',
			'user_email'   => ! empty( $request['email'] ) ? sanitize_email( $request['email'] ) : '',
		);

		$custom_register = array();

		if ( ! empty( $request['custom_fields'] ) ) {
			$fields = LP_Settings::instance()->get( 'register_profile_fields' );

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( $field['type'] === 'checkbox' ) {
						$custom_register[ $field['id'] ] = $request['custom_fields'][ $field['id'] ] ? 1 : 0;
					} elseif ( $field['type'] === 'textarea' ) {
						$custom_register[ $field['id'] ] = ! empty( $request['custom_fields'][ $field['id'] ] ) ? sanitize_textarea_field( $request['custom_fields'][ $field['id'] ] ) : '';
					} else {
						$custom_register[ $field['id'] ] = ! empty( $request['custom_fields'][ $field['id'] ] ) ? sanitize_text_field( $request['custom_fields'][ $field['id'] ] ) : '';
					}
				}
			}
		}

		$update = LP_Forms_Handler::update_user_data( $update_data, $custom_register );

		// Update social.
		$extra_data = get_user_meta( get_current_user_id(), '_lp_extra_info', true );
		$socials    = ! empty( $request['social'] ) ? array_map( 'sanitize_text_field', $request['social'] ) : array();

		if ( ! empty( $extra_data ) ) {
			$socials = array_merge( $extra_data, $socials );
		}

		update_user_meta( get_current_user_id(), '_lp_extra_info', $socials );

		// Update avatar.
		$profile_controller = new LP_REST_Profile_Controller();

		if ( ! empty( $request['avatar']['url'] ) && strpos( $request['avatar']['url'], 'data:image' ) !== false ) {
			$request_profile = new WP_REST_Request( 'POST' );
			$request_profile->set_body_params(
				array(
					'file' => $request['avatar']['url'],
				)
			);
			$update_avatar = $profile_controller->upload_avatar( $request_profile );
		}

		if ( empty( $request['avatar']['url'] ) ) {
			$delete_avatar = $profile_controller->remove_avatar( new WP_REST_Request( 'POST' ) );
		}

		// Update logo.
		if ( isset( $request['logo'] ) ) {
			$logo_data = array(
				'url' => ! empty( $request['logo']['url'] ) ? esc_url_raw( $request['logo']['url'] ) : '',
				'id'  => ! empty( $request['logo']['id'] ) ? absint( $request['logo']['id'] ) : 0,
			);
			update_user_meta( get_current_user_id(), '_lp_fe_logo', $logo_data );
		}

		// Update new Password.
		if ( ! empty( $request['newPassword'] ) ) {
			$new_password = trim( $request['newPassword'] );

			wp_set_password( $new_password, get_current_user_id() );
		}

		if ( is_wp_error( $update ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $update->get_error_message(),
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'logout'  => empty( $request['newPassword'] ) ? false : true,
				'message' => __( 'Profile updated', 'learnpress-frontend-editor' ),
			)
		);
	}
}
