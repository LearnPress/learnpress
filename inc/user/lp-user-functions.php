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
	global $wp, $wpdb;
	if ( is_admin() ) {
		return;
	}
	if ( !empty( $_POST ) && isset( $_POST['from'] ) && isset( $_POST['action'] ) && $_POST['from'] == 'profile' && $_POST['action'] == 'update' ) {
		$user    = learn_press_get_current_user();
		$user_id = learn_press_get_current_user_id();
//		$user_info = get_userdata( $user->id );

		$update_data = array(
			'ID'           => $user_id,
			'user_url'     => filter_input( INPUT_POST, 'url', FILTER_SANITIZE_URL ),
			'user_email'   => filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL ),
			'first_name'   => filter_input( INPUT_POST, 'first_name', FILTER_SANITIZE_STRING ),
			'last_name'    => filter_input( INPUT_POST, 'last_name', FILTER_SANITIZE_STRING ),
			'display_name' => filter_input( INPUT_POST, 'display_name', FILTER_SANITIZE_STRING ),
			'nickname'     => filter_input( INPUT_POST, 'nickname', FILTER_SANITIZE_STRING ),
			'description'  => filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING ),
		);

		# check and update pass word
		if ( !empty( $_POST['pass0'] ) && !empty( $_POST['pass1'] ) && !empty( $_POST['pass1'] ) ) {
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
				learn_press_add_message( __( 'Old password incorrect!', 'learnpress' ) );
				return;
			} else {
				// check new pass
				$new_pass  = filter_input( INPUT_POST, 'pass1' );
				$new_pass2 = filter_input( INPUT_POST, 'pass2' );

				if ( $new_pass != $new_pass2 ) {
					learn_press_add_message( __( 'Retype new password incorrect!', 'learnpress' ) );
					return;
				} else {
					$update_data['user_pass'] = $new_pass;
				}
			}
		}


		// upload profile picture
		$profile_picture_type = filter_input( INPUT_POST, 'profile_picture_type', FILTER_SANITIZE_STRING );


		if ( $profile_picture_type == 'picture' ) {

			$upload     = wp_get_upload_dir();
			$upload_dir = $upload['basedir'] . '/learn-press-profile/' . $user_id;
			if ( !is_dir( $upload_dir ) ) {
				mkdir( $upload_dir );
			}
			# get old file
			$filename_old = get_user_meta( $user_id, '_lp_profile_picture', true );
			if ( file_exists( $upload_dir . '/' . $filename_old ) ) {
				unlink( $upload_dir . '/' . $filename_old );
			}

			$pathinfo_old = pathinfo($filename_old);
			$thumb_old = $upload_dir . '/' . $pathinfo_old['filename'].'-thumb'. $pathinfo_old['extension'];
			if ( file_exists( $thumb_old ) ) {
				unlink( $thumb_old );
			}

			$data     = explode( ',', $_POST['profile_picture_data'] );
			$imgtype  = explode( '/', $data[0] );
			$filename = isset( $_FILES['profile_picture']['name'] ) && $_FILES['profile_picture']['name'] ? $_FILES['profile_picture']['name'] : 'avatar' . $imgtype[1];
			if ( file_put_contents( $upload_dir . '/' . $filename, base64_decode( $data[1] ) ) ) {
				$editor = wp_get_image_editor( $upload_dir . '/' . $filename );
				if ( is_wp_error( $editor ) ){
					learn_press_add_message( __( 'Thumbnail of image profile not created', 'learnpress' ) );
				} else {
					$editor->set_quality( 90 );

					$resized = $editor->resize( 96, 96, true );
					if ( is_wp_error( $resized ) ){
						learn_press_add_message( __( 'Thumbnail of image profile not created', 'learnpress' ) );
					} else {
						$dest_file = $editor->generate_filename( 'thumb' );
						$saved = $editor->save( $dest_file );

						if ( is_wp_error( $saved ) ) {
							learn_press_add_message( __( 'Thumbnail of image profile not created', 'learnpress' ) );
						}
					}
				}
				
				update_user_meta( $user->id, '_lp_profile_picture', $filename );
			}
		}

		update_user_meta( $user->id, '_lp_profile_picture_type', $profile_picture_type );
		$res = wp_update_user( $update_data );
		if ( $res ) {
			learn_press_add_message( __( 'Your change is saved', 'learnpress' ) );
		}
		if ( !empty( $_POST['profile-nonce'] ) && wp_verify_nonce( $_POST['profile-nonce'], 'learn-press-user-profile-' . $user->id ) ) {
			$current_url = learn_press_get_page_link( 'profile' ) . $user->user_login . '/edit';
			wp_redirect( $current_url );
			exit();
		}
	}
}

function learn_press_user_profile_picture_upload_dir( $args ) {
	$subdir         = '/learn-press-profile';
	$args['path']   = str_replace( $args['subdir'], $subdir, $args['path'] );
	$args['url']    = str_replace( $args['subdir'], $subdir, $args['url'] );
	$args['subdir'] = $subdir;
	return $args;
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
			learn_press_add_message( __( 'You have already enrolled in this course', 'learnpress' ) );
			$redirect = true;
		}
		if ( $redirect ) {
			wp_redirect( get_the_permalink( $course_id ) );
			exit();
		}
	}
}

function learn_press_profile_tab_endpoints_edit_profile( $endpoints ) {
	$endpoints['edit'] = 'edit';
	return $endpoints;
}

add_filter( 'learn_press_profile_tab_endpoints', 'learn_press_profile_tab_endpoints_edit_profile' );

function learn_press_profile_tab_edit_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/edit.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function learn_press_filter_get_avatar( $avatar, $id_or_email = '', $size = array(), $default = '', $alt = '' ) {
	global $parent_file;
	if ( $parent_file == 'users.php' ) {
		$user_id = 0;
		if ( !is_numeric( $id_or_email ) && is_string( $id_or_email ) ) {
			if ( $user = get_user_by( 'email', $id_or_email ) ) {
				$user_id = $user->id;
			}
		} elseif ( is_numeric( $id_or_email ) ) {
			$user_id = $id_or_email;
		} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) && $id_or_email->user_id ) {
			$user_id = $id_or_email->user_id;
		}
		// get user data
		$profile_picture_type = get_user_meta( $user_id, '_lp_profile_picture_type', true );
		if ( !$profile_picture_type || $profile_picture_type == 'gravatar' ) {
			return;
		}
		$profile_picture = get_user_meta( $user_id, '_lp_profile_picture', true );
		$array_sizes     = array( 200, 200 );
		if ( is_array( $size ) ) {
			if ( !empty( $size['width'] ) ) {
				$array_sizes[0] = $size['width'];
			}
			if ( !empty( $size['height'] ) ) {
				$array_sizes[1] = $size['height'];
			}
			$profile_picture_src = wp_get_attachment_image_src( $profile_picture, $array_sizes );
		} else {
			$profile_picture_src = wp_get_attachment_image_src( $profile_picture, $size );
		}
		if ( is_array( $profile_picture_src ) ) {
			$profile_picture_src = $profile_picture_src[0];
		}
		$avatar = '<img alt="" src="' . esc_attr( $profile_picture_src ) . '" class="avatar avatar-' . $size['size'] . ' photo" height="' . $size['height'] . '" width="' . $size['width'] . '" />';
	}
	return $avatar;
}

add_filter( 'pre_get_avatar', 'learn_press_filter_get_avatar', 1, 5 );

function _learn_press_redirect_logout_redirect() {
	if ( !is_admin() && $redirect = learn_press_get_page_link( 'profile' ) ) {
		wp_redirect( $redirect );
		exit();
	}
}

add_action( 'wp_logout', '_learn_press_redirect_logout_redirect' );
