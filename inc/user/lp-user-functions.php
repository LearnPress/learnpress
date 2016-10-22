<?php
/**
 * Common functions to process actions about user
 *
 * @author  ThimPress
 * @package LearnPress/Functions/User
 * @version 1.0
 */

/**
 * Delete user data by user ID
 *
 * @param $user_id
 * @param $course_id
 */
function learn_press_delete_user_data( $user_id, $course_id = 0 ) {
	global $wpdb;
	// TODO: Should be deleted user's order and order data???

	$query_args = array( $user_id );
	if ( $course_id ) {
		$query_args[] = $course_id;
	}
	// delete all courses user has enrolled
	$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_user_items
				WHERE user_id = %d
				" . ( $course_id ? " AND item_id = %d" : "" ) . "
			", $query_args );
	@$wpdb->query( $query );
}

function learn_press_get_user_item_id( $user_id, $item_id ) {
	static $user_item_ids = array();
	if ( empty( $user_item_ids[$user_id . '-' . $item_id] ) ) {
		global $wpdb;
		$query                                    = $wpdb->prepare( "SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items WHERE user_id = %d AND item_id = %d ORDER BY user_item_id DESC LIMIT 0,1", $user_id, $item_id );
		$user_item_ids[$user_id . '-' . $item_id] = $wpdb->get_var( $query );
	}
	return $user_item_ids[$user_id . '-' . $item_id];
}

/**
 * @return int
 */
function learn_press_get_current_user_id() {
	$user = learn_press_get_current_user();
	return $user->id;
}

/**
 * Get the user by $user_id passed. If $user_id is NULL, get current user.
 *
 * If current user is not logged in, return a GUEST user
 *
 * @param int $user_id
 *
 * @return LP_User
 */
function learn_press_get_current_user( $user_id = 0 ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	$current_user = false;
	if ( $user_id ) {
		$current_user = learn_press_get_user( $user_id );
	} else {
		$current_user = LP_User_Guest::instance();
	}
	return $current_user;
}

/**
 * Get user by ID, if the ID is NULL then return a GUEST user
 *
 * @param int  $user_id
 * @param bool $force
 *
 * @return LP_User_Guest|mixed
 */
function learn_press_get_user( $user_id, $force = false ) {
	if ( $user_id ) {
		return LP_User::get_user( $user_id, $force );
	}
	return LP_User_Guest::instance();
}

function learn_press_send_user_email_order( $status, $order_id ) {
	return;
	$status = strtolower( $status );
	if ( 'completed' == $status ) {
		$order        = new LP_Order( $order_id );
		$mail_to      = $order->get_user( 'email' );
		$instructors  = array();
		$course_title = '';

		$transaction_object = $order->get_items();
		$items              = $transaction_object->products;
		$item               = array_shift( $items );

		$course = get_post( $item['id'] );

		$course_title = get_the_title( $item['id'] );

		$instructor                   = LP_User::get_user( $course->post_author );
		$instructors[$instructor->ID] = $instructor->data->display_name;

		learn_press_send_mail(
			$mail_to,
			'enrolled_course',
			apply_filters(
				'learn_press_vars_enrolled_course',
				array(
					'user_name'   => $order->get_user( 'display_name' ),
					'course_name' => $course_title,
					'course_link' => get_permalink( $item['id'] )
				),
				$course,
				$instructor
			)
		);
	}
}

add_action( 'learn_press_update_order_status', 'learn_press_send_user_email_order', 5, 2 );

/**
 * Add more 2 user roles teacher and student
 *
 * @access public
 * @return void
 */
function learn_press_add_user_roles() {
	$settings = LP()->settings;
	/* translators: user role */
	_x( 'Instructor', 'User role' );
	add_role(
		LP_TEACHER_ROLE,
		'Instructor',
		array()
	);
	$course_cap = LP_COURSE_CPT . 's';
	$lesson_cap = LP_LESSON_CPT . 's';
	$order_cap  = LP_ORDER_CPT . 's';
	// teacher
	$teacher = get_role( LP_TEACHER_ROLE );
	$teacher->add_cap( 'delete_published_' . $course_cap );
	$teacher->add_cap( 'edit_published_' . $course_cap );
	$teacher->add_cap( 'edit_' . $course_cap );
	$teacher->add_cap( 'delete_' . $course_cap );

	$settings->get( 'required_review' );

	if ( $settings->get( 'required_review' ) == 'yes' ) {
		$teacher->remove_cap( 'publish_' . $course_cap );
	} else {
		$teacher->add_cap( 'publish_' . $course_cap );
	}
	//


	$teacher->add_cap( 'delete_published_' . $lesson_cap );
	$teacher->add_cap( 'edit_published_' . $lesson_cap );
	$teacher->add_cap( 'edit_' . $lesson_cap );
	$teacher->add_cap( 'delete_' . $lesson_cap );
	$teacher->add_cap( 'publish_' . $lesson_cap );
	$teacher->add_cap( 'upload_files' );
	$teacher->add_cap( 'read' );
	$teacher->add_cap( 'edit_posts' );

	// administrator
	$admin = get_role( 'administrator' );
	$admin->add_cap( 'delete_' . $course_cap );
	$admin->add_cap( 'delete_published_' . $course_cap );
	$admin->add_cap( 'edit_' . $course_cap );
	$admin->add_cap( 'edit_published_' . $course_cap );
	$admin->add_cap( 'publish_' . $course_cap );
	$admin->add_cap( 'delete_private_' . $course_cap );
	$admin->add_cap( 'edit_private_' . $course_cap );
	$admin->add_cap( 'delete_others_' . $course_cap );
	$admin->add_cap( 'edit_others_' . $course_cap );

	$admin->add_cap( 'delete_' . $lesson_cap );
	$admin->add_cap( 'delete_published_' . $lesson_cap );
	$admin->add_cap( 'edit_' . $lesson_cap );
	$admin->add_cap( 'edit_published_' . $lesson_cap );
	$admin->add_cap( 'publish_' . $lesson_cap );
	$admin->add_cap( 'delete_private_' . $lesson_cap );
	$admin->add_cap( 'edit_private_' . $lesson_cap );
	$admin->add_cap( 'delete_others_' . $lesson_cap );
	$admin->add_cap( 'edit_others_' . $lesson_cap );

	$admin->add_cap( 'delete_' . $order_cap );
	$admin->add_cap( 'delete_published_' . $order_cap );
	$admin->add_cap( 'edit_' . $order_cap );
	$admin->add_cap( 'edit_published_' . $order_cap );
	$admin->add_cap( 'publish_' . $order_cap );
	$admin->add_cap( 'delete_private_' . $order_cap );
	$admin->add_cap( 'edit_private_' . $order_cap );
	$admin->add_cap( 'delete_others_' . $order_cap );
	$admin->add_cap( 'edit_others_' . $order_cap );
}

add_action( 'init', 'learn_press_add_user_roles' );

function learn_press_get_user_questions( $user_id = null, $args = array() ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}
	return learn_press_get_user( $user_id )->get_questions( $args );
}

/**
 * Get the type of current user
 *
 * @param null $check_type
 *
 * @return bool|string
 */
function learn_press_current_user_is( $check_type = null ) {
	global $current_user;
	$user_roles = $current_user->roles;
	$user_type  = '';

	// backward compatible
	if ( in_array( 'lpr_teacher', $user_roles ) ) {
		$user_type = 'instructor';
	} elseif ( in_array( 'lp_teacher', $user_roles ) ) {
		$user_type = 'instructor';
	} elseif ( in_array( 'administrator', $user_roles ) ) {
		$user_type = 'administrator';
	}
	return $check_type ? $check_type == $user_type : $user_type;
}

function learn_press_user_has_roles( $roles, $user_id = null ) {
	$has_role = false;
	if ( !$user_id ) {
		$user = wp_get_current_user();
	} else {
		$user = get_user_by( 'id', $user_id );
	}
	$available_roles = (array) $user->roles;
	if ( is_array( $roles ) ) {
		foreach ( $roles as $role ) {
			if ( in_array( $role, $available_roles ) ) {
				$has_role = true;
				break; // only need one of roles is in available
			}
		}
	} else {
		if ( in_array( $roles, $available_roles ) ) {
			$has_role = true;
		}
	}
	return $has_role;
}

function learn_press_edit_admin_bar() {
	global $wp_admin_bar;
	if ( ( $profile = learn_press_get_page_id( 'profile' ) ) && get_post_type( $profile ) == 'page' && get_post_status( $profile ) != 'trash' && ( LP()->settings->get( 'admin_bar_link' ) == 'yes' ) ) {
		$text                             = LP()->settings->get( 'admin_bar_link_text' );
		$course_profile                   = array();
		$course_profile['id']             = 'course_profile';
		$course_profile['parent']         = 'user-actions';
		$course_profile['title']          = $text ? $text : get_the_title( $profile );
		$course_profile['href']           = learn_press_user_profile_link();
		$course_profile['meta']['target'] = LP()->settings->get( 'admin_bar_link_target' );
		$wp_admin_bar->add_menu( $course_profile );
	}
	$current_user = wp_get_current_user();
	// add `be teacher` link
	if ( in_array( LP_TEACHER_ROLE, $current_user->roles ) || in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
	//if ( !class_exists( 'LP_Admin_Settings' ) ) return;
	/**
	 * $settings = LP_Admin_Settings::instance( 'general' );
	 * if ( $settings->get( 'instructor_registration' ) ) {
	 * $be_teacher           = array();
	 * $be_teacher['id']     = 'be_teacher';
	 * $be_teacher['parent'] = 'user-actions';
	 * $be_teacher['title']  = __( 'Become An Instructor', 'learnpress' );
	 * $be_teacher['href']   = '#';
	 * $wp_admin_bar->add_menu( $be_teacher );
	 * }*/
}

add_action( 'admin_bar_menu', 'learn_press_edit_admin_bar' );


function learn_press_current_user_can_view_profile_section( $section, $user ) {
	$current_user = wp_get_current_user();
	$view         = true;
	if ( $user->user_login != $current_user->user_login && $section == LP()->settings->get( 'profile_endpoints.profile-orders', 'profile-orders' ) ) {
		$view = false;
	}
	return apply_filters( 'learn_press_current_user_can_view_profile_section', $view, $section, $user );
}

function learn_press_profile_tab_courses_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/courses.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function learn_press_profile_tab_quizzes_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/quizzes.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function learn_press_profile_tab_orders_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

//function learn_press_update_user_lesson_start_time() {
//	global $wpdb;
//	$course = LP()->global['course'];
//
//	if ( !$course->id || !( $lesson = $course->current_lesson ) ) {
//		return;
//	}
//        $table = $wpdb->prefix . 'learnpress_user_lessons';
//        if ( $wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table ) {
//            return;
//        }
//	$query = $wpdb->prepare( "
//		SELECT user_lesson_id FROM {$wpdb->prefix}learnpress_user_lessons WHERE user_id = %d AND lesson_id = %d AND course_id = %d
//	", get_current_user_id(), $lesson->id, $course->id );
//	if ( $wpdb->get_row( $query ) ) {
//		return;
//	}
//	$wpdb->insert(
//		$wpdb->prefix . 'learnpress_user_lessons',
//		array(
//			'user_id'    => get_current_user_id(),
//			'lesson_id'  => $lesson->id,
//			'start_time' => current_time( 'mysql' ),
//			'status'     => 'stared',
//			'course_id'  => $course->id
//		),
//		array( '%d', '%d', '%s', '%s', '%d' )
//	);
//}
//
//add_action( 'learn_press_course_content_lesson', 'learn_press_update_user_lesson_start_time' );

function learn_press_get_profile_user() {
	global $wp;
	return !empty( $wp->query_vars['user'] ) ? get_user_by( 'login', $wp->query_vars['user'] ) : false;
}

/**
 * Add instructor registration button to register page and admin bar
 */
function learn_press_user_become_teacher_registration_form() {
	if ( LP()->settings->get( 'instructor_registration' ) != 'yes' ) {
		return;
	}
	?>
	<p>
		<label for="become_teacher">
			<input type="checkbox" name="become_teacher" id="become_teacher">
			<?php _e( 'Want to be an instructor?', 'learnpress' ) ?>
		</label>
	</p>
	<?php
}

add_action( 'register_form', 'learn_press_user_become_teacher_registration_form' );

/**
 * Process instructor registration while user register new account
 *
 * @param $user_id
 */
function learn_press_update_user_teacher_role( $user_id ) {
	if ( LP()->settings->get( 'instructor_registration' ) != 'yes' ) {
		return;
	}
	if ( !isset( $_POST['become_teacher'] ) ) {
		return;
	}
	$new_user = new WP_User( $user_id );
	$new_user->set_role( LP_TEACHER_ROLE );
}

add_action( 'user_register', 'learn_press_update_user_teacher_role', 10, 1 );


/**
 * @param array $fields
 * @param mixed $where
 *
 * @return mixed
 */
function learn_press_update_user_item_field( $fields, $where = false ) {
	global $wpdb;

	// Table fields
	$table_fields = array( 'user_id' => '%d', 'item_id' => '%d', 'ref_id' => '%d', 'start_time' => '%s', 'end_time' => '%s', 'item_type' => '%s', 'status' => '%s', 'ref_type' => '%s', 'parent_id' => '%d' );

	// Data and format
	$data        = array();
	$data_format = array();

	// Build data and data format
	foreach ( $fields as $field => $value ) {
		if ( !empty( $table_fields[$field] ) ) {
			$data[$field]  = $value;
			$data_format[] = $table_fields[$field];
		}
	}

	//
	if ( $where && empty( $where['user_id'] ) ) {
		$where['user_id'] = learn_press_get_current_user_id();
	}
	$where_format = array();
	/// Build where and where format
	if ( $where ) foreach ( $where as $field => $value ) {
		if ( !empty( $table_fields[$field] ) ) {
			$where_format[] = $table_fields[$field];
		}
	}
	$return = false;
	if ( $data ) {
		if ( $where ) {
			$return = $wpdb->update(
				$wpdb->prefix . 'learnpress_user_items',
				$data,
				$where,
				$data_format,
				$where_format
			);
		} else {
			if ( $wpdb->insert(
				$wpdb->prefix . 'learnpress_user_items',
				$data,
				$data_format
			)
			) {
				$return = $wpdb->insert_id;
			}
		}
	}
	return $return;
}

/**
 * Get user item row(s) from user items table by multiple WHERE conditional
 *
 * @param      $where
 * @param bool $single
 *
 * @return array|bool|null|object|void
 */
function learn_press_get_user_item( $where, $single = true ) {
	global $wpdb;

	// Table fields
	$table_fields = array( 'user_id' => '%d', 'item_id' => '%d', 'ref_id' => '%d', 'start_time' => '%s', 'end_time' => '%s', 'item_type' => '%s', 'status' => '%s', 'ref_type' => '%s', 'parent_id' => '%d' );

	$where_str = array();
	foreach ( $where as $field => $value ) {
		if ( !empty( $table_fields[$field] ) ) {
			$where_str[] = "{$field} = " . $table_fields[$field];
		}
	}
	$item = false;
	if ( $where_str ) {
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE " . join( ' AND ', $where_str ) . "
		", $where );
		if ( $single ) {
			$item = $wpdb->get_row( $query );
		} else {
			$item = $wpdb->get_results( $query );
		}
	}
	return $item;
}

/**
 * Get user item meta from user_itemmeta table
 *
 * @param      $user_item_id
 * @param      $meta_key
 * @param bool $single
 *
 * @return mixed
 */
function learn_press_get_user_item_meta( $user_item_id, $meta_key, $single = true ) {
	return get_metadata( 'learnpress_user_item', $user_item_id, $meta_key, $single );
}

/**
 * Add user item meta into table user_itemmeta
 *
 * @param        $user_item_id
 * @param        $meta_key
 * @param        $meta_value
 * @param string $prev_value
 *
 * @return false|int
 */
function learn_press_add_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return add_metadata( 'learnpress_user_item', $user_item_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Update user item meta to table user_itemmeta
 *
 * @param        $user_item_id
 * @param        $meta_key
 * @param        $meta_value
 * @param string $prev_value
 *
 * @return bool|int
 */
function learn_press_update_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'learnpress_user_item', $user_item_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Update field created_time after added user item meta
 *
 * @use updated_{meta_type}_meta hook
 *
 * @param $meta_id
 * @param $object_id
 * @param $meta_key
 * @param $_meta_value
 */
function _learn_press_update_created_time_user_item_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	global $wpdb;
	$wpdb->update(
		$wpdb->learnpress_user_itemmeta,
		array( 'create_time' => current_time( 'mysql' ) ),
		array( 'meta_id' => $meta_id ),
		array( '%s' ),
		array( '%d' )
	);
}

///add_action( 'added_learnpress_user_item_meta', '_learn_press_update_created_time_user_item_meta', 10, 4 );

/**
 * Update field updated_time after updated user item meta
 *
 * @use updated_{meta_type}_meta hook
 *
 * @param $meta_id
 * @param $object_id
 * @param $meta_key
 * @param $_meta_value
 */
function _learn_press_update_updated_time_user_item_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	global $wpdb;
	$wpdb->update(
		$wpdb->learnpress_user_itemmeta,
		array( 'update_time' => current_time( 'mysql' ) ),
		array( 'meta_id' => $meta_id ),
		array( '%s' ),
		array( '%d' )
	);
}

//add_action( 'updated_learnpress_user_item_meta', '_learn_press_update_updated_time_user_item_meta', 10, 4 );

/**
 * @param     $status
 * @param int $quiz_id
 * @param int $user_id
 * @param int $course_id
 *
 * @return bool|mixed
 */
function learn_press_user_has_quiz_status( $status, $quiz_id = 0, $user_id = 0, $course_id = 0 ) {
	$user = learn_press_get_user( $user_id );
	return $user->has_quiz_status( $status, $quiz_id, $course_id );
}

add_action( 'init', 'learn_press_user_update_user_info' );

function learn_press_user_update_user_info() {
	if ( !empty( $_POST ) && isset( $_POST['from'] ) && isset( $_POST['action'] ) && $_POST['from'] == 'profile' && $_POST['action'] == 'update' ) {
		$user      = learn_press_get_current_user();
		$user_id   = learn_press_get_current_user_id();
		$user_info = get_userdata( $user->id );
		// upload profile picture
		$profile_picture_type = filter_input( INPUT_POST, 'profile_picture_type', FILTER_SANITIZE_STRING );
		update_user_meta( $user->id, '_lp_profile_picture_type', $profile_picture_type );
		if ( $profile_picture_type == 'picture' ) {
			if ( !function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
				require_once( ABSPATH . "wp-admin" . '/includes/file.php' );
				require_once( ABSPATH . "wp-admin" . '/includes/media.php' );
			}
			$attach_id = 0;
			if ( $_FILES ) {
				foreach ( $_FILES as $file => $array ) {
					if ( $_FILES[$file]['error'] !== UPLOAD_ERR_OK ) {
						return "upload error : " . $_FILES[$file]['error'];
					}
					$attach_id = media_handle_upload( $file, 0 );
				}
			}
			if ( $attach_id > 0 ) {
				update_user_meta( $user->id, '_lp_profile_picture', $attach_id );
			}
		}

		// check old pass
		$old_pass       = filter_input( INPUT_POST, 'pass0' );
		$check_old_pass = false;
		if ( !$old_pass ) {
			$check_old_pass = false;
		} else {
			$cuser = wp_get_current_user();
			require_once( ABSPATH . 'wp-includes/class-phpass.php' );
			$wp_hasher = new PasswordHash( 8, TRUE );
			if ( $wp_hasher->CheckPassword( $old_pass, $cuser->data->user_pass ) ) {
				$check_old_pass = true;
			}
		}

		if ( !$check_old_pass ) {
			_e( 'old password incorect!', 'learnpress' );
		}

		// check new pass
		$new_pass  = filter_input( INPUT_POST, 'pass1' );
		$new_pass2 = filter_input( INPUT_POST, 'pass2' );

		if ( $new_pass != $new_pass2 ) {
			_e( 'retype new password incorect!', 'learnpress' );
		} else {
			wp_set_password( $new_pass, $user_id );
		}

		$update_data = array(
			'ID'          => $user_id,
			'user_url'    => filter_input( INPUT_POST, 'url', FILTER_SANITIZE_URL ),
			'user_email'  => filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL ),
			'first_name'  => filter_input( INPUT_POST, 'first_name', FILTER_SANITIZE_STRING ),
			'last_name'   => filter_input( INPUT_POST, 'last_name', FILTER_SANITIZE_STRING ),
			'description' => filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING ),
		);

		$user_id = wp_update_user( $update_data );
		if ( $user_id ) {
			_e( 'Your change is saved', 'learnpress' );
		}

	}
}

add_action( 'learn_press_before_purchase_course_handler', '_learn_press_before_purchase_course_handler', 10, 2 );
function _learn_press_before_purchase_course_handler( $course_id, $cart ) {
	// Redirect to login page if user is not logged in
	if ( !is_user_logged_in() ) {
		$return_url = add_query_arg( $_POST, get_the_permalink( $course_id ) );
		$return_url = apply_filters( 'learn_press_purchase_course_login_redirect_return_url', $return_url );
		$redirect   = apply_filters( 'learn_press_purchase_course_login_redirect', learn_press_get_login_url( $return_url ) );
		learn_press_add_message( __( 'Please login to enroll this course', 'learnpress' ) );
		if ( $redirect !== false ) {
			if ( is_ajax() ) {
				learn_press_send_json(
					array(
						'redirect' => $redirect,
						'result'   => 'success'
					)
				);
			} else {
				wp_redirect( $redirect );
				exit();
			}
		}
	} else {
		$user     = learn_press_get_current_user();
		$redirect = false;
		if ( $user->has_finished_course( $course_id ) ) {
			learn_press_add_message( __( 'You have already finished course', 'learnpress' ) );
			$redirect = true;
		} elseif ( $user->has_purchased_course( $course_id ) ) {
			learn_press_add_message( __( 'You have already enrolled course', 'learnpress' ) );
			$redirect = true;
		}
		if ( $redirect ) {
			wp_redirect( get_the_permalink( $course_id ) );
			exit();
		}
	}
}