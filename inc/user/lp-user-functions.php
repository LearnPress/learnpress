<?php
/**
 * Common functions to process actions about user
 *
 * @author  ThimPress
 * @package LearnPress/Functions/User
 * @version 1.0
 */

function learn_press_get_user_profile_tabs() {
	return LP_Profile::instance()->get_tabs();
}

/**
 * Delete user data by user ID
 *
 * @param int $user_id
 * @param int $course_id
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

/**
 * Get user_item_id field in table learnpress_user_items
 * with the user_id, item_id. If $course_id is not passed
 * then item_id is ID of a course. Otherwise, item_id is
 * ID of an item (like quiz/lesson).
 *
 * @param int $user_id
 * @param int $item_id
 * @param int $course_id
 *
 * @return bool
 */
function learn_press_get_user_item_id( $user_id, $item_id, $course_id = 0 /* added 3.0.0 */ ) {

	// If $course_id is not passed consider $item_id is ID of a course
	if ( ! $course_id ) {
		if ( $item = wp_cache_get( 'course-' . $user_id . '-' . $item_id, 'lp-user-courses' ) ) {
			return $item['user_item_id'];
		}
	} else {

		// Otherwise, get item of the course
		if ( $items = wp_cache_get( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, 'lp-user-course-items' ) ) {
			$item = reset( $items );

			return $item['user_item_id'];
		}
	}

	return false;
}

/**
 * Get current user ID
 *
 * @return int
 */
function learn_press_get_current_user_id() {
	$user = learn_press_get_current_user();

	return $user->get_id();
}

/**
 * Get the user by $user_id passed. If $user_id is NULL, get current user.
 * If current user is not logged in, return a GUEST user
 *
 * @param bool $create_temp - Optional. Create temp user if user is not logged in.
 *
 * @return bool|LP_User|LP_User_Guest
 */
function learn_press_get_current_user( $create_temp = true, $force_new = false ) {
	if ( $id = get_current_user_id() ) {
		return learn_press_get_user( $id, $force_new );
	}

	return learn_press_get_user( 0 );

	//return $create_temp ? new LP_User_Guest( 0 ) : false;
	//return $create_temp ? LP_User_Factory::get_temp_user() : false;
}

if ( ! function_exists( 'learn_press_get_user' ) ) {
	/**
	 * Get user by ID. Return false if the user does not exists.
	 *
	 * @param int  $user_id
	 * @param bool $current
	 *
	 * @return LP_User|mixed
	 */
	function learn_press_get_user( $user_id, $current = false, $force_new = false ) {

		global $pagenow;

		// Check if user is existing
		if ( ! get_user_by( 'id', $user_id ) && $current ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id && isset( LP()->session ) ) {

			if ( ! LP()->session->guest_user_id ) {
				LP()->session->set_customer_session_cookie( 1 );
				LP()->session->guest_user_id = time();
			}

			$user_id  = LP()->session->guest_user_id;
			$is_guest = true;
		}

		if ( ! $user_id ) {
			return false;
		}



		if ( $force_new || empty( LP_Global::$users[ $user_id ] ) ) {
			LP_Global::$users[ $user_id ] = isset( $is_guest ) ? new LP_User_Guest( $user_id ) : new LP_User( $user_id );
		}

		return LP_Global::$users[ $user_id ];
	}
}

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

add_action( 'learn_press_ready', 'learn_press_add_user_roles' );
function learn_press_get_user_questions( $user_id = null, $args = array() ) {
	if ( ! $user_id ) {
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
	if ( ! $user_id ) {
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

/**
 * Add user profile link into admin bar
 */
function learn_press_edit_admin_bar() {
	global $wp_admin_bar;
	if ( ( $profile = learn_press_get_page_id( 'profile' ) ) && get_post_type( $profile ) == 'page' && get_post_status( $profile ) != 'trash' && ( LP()->settings->get( 'admin_bar_link' ) == 'yes' ) ) {
		$text                             = LP()->settings->get( 'admin_bar_link_text' );
		$user_id                          = learn_press_get_current_user_id();
		$course_profile                   = array();
		$course_profile['id']             = 'course_profile';
		$course_profile['parent']         = 'user-actions';
		$course_profile['title']          = $text ? $text : get_the_title( $profile );
		$course_profile['href']           = learn_press_user_profile_link( $user_id, false );
		$course_profile['meta']['target'] = LP()->settings->get( 'admin_bar_link_target' );
		$wp_admin_bar->add_menu( $course_profile );
	}
	$current_user = wp_get_current_user();
	// add `be teacher` link
	if ( in_array( LP_TEACHER_ROLE, $current_user->roles ) || in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
}

add_action( 'admin_bar_menu', 'learn_press_edit_admin_bar' );


function learn_press_current_user_can_view_profile_section( $section, $user ) {
	$current_user = wp_get_current_user();
	$view         = true;
	if ( $user->get_data( 'user_login' ) != $current_user->user_login && $section == LP()->settings->get( 'profile_endpoints.profile-orders', 'profile-orders' ) ) {
		$view = false;
	}

	return apply_filters( 'learn_press_current_user_can_view_profile_section', $view, $section, $user );
}

function learn_press_profile_tab_courses_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/courses.php', array(
		'user'    => $user,
		'current' => $current,
		'tab'     => $tab
	) );
}

function learn_press_profile_tab_quizzes_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/quizzes.php', array(
		'user'    => $user,
		'current' => $current,
		'tab'     => $tab
	) );
}

function learn_press_profile_tab_orders_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/orders.php', array(
		'user'    => $user,
		'current' => $current,
		'tab'     => $tab
	) );
}

/**
 * Get queried user in profile link
 *
 * @since 3.0.0
 *
 * @return false|WP_User
 */
function learn_press_get_profile_user() {
	return LP_Profile::get_queried_user();
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
			<?php _e( 'Want to become an instructor?', 'learnpress' ) ?>
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
	if ( ! isset( $_POST['become_teacher'] ) ) {
		return;
	}
	$new_user = new WP_User( $user_id );
	$new_user->set_role( LP_TEACHER_ROLE );
}

add_action( 'user_register', 'learn_press_update_user_teacher_role', 10, 1 );


/**
 * Update data into table learnpress_user_items.
 *
 * @param array $fields             - Fields and values to be updated.
 *                                  Format: array(
 *                                  field_name_1 => value 1,
 *                                  field_name_2 => value 2,
 *                                  ....
 *                                  field_name_n => value n
 *                                  )
 * @param mixed $where              - Optional. Fields with values for conditional update with the same format of $fields.
 * @param bool  $update_cache       - Optional. Should be update to cache or not (since 3.0.0).
 *
 * @return mixed
 */
function learn_press_update_user_item_field( $fields, $where = false, $update_cache = true ) {
	global $wpdb;

	// Table fields
	$table_fields = array(
		'user_id'        => '%d',
		'item_id'        => '%d',
		'ref_id'         => '%d',
		'start_time'     => '%s',
		'start_time_gmt' => '%s',
		'end_time'       => '%s',
		'end_time_gmt'   => '%s',
		'item_type'      => '%s',
		'status'         => '%s',
		'ref_type'       => '%s',
		'parent_id'      => '%d'
	);

	/**
	 * Validate item status
	 */
	if ( ! empty( $fields['item_id'] ) && ! empty( $fields['status'] ) ) {
		$item_type = get_post_type( $fields['item_id'] );
		if ( LP_COURSE_CPT === $item_type ) {
			if ( 'completed' === $fields['status'] ) {
				$fields['status'] = 'finished';
			}
		} else {
			if ( 'finished' === $fields['status'] ) {
				$fields['status'] = 'completed';
			}
		}
	}

	// Data and format
	$data        = array();
	$data_format = array();

	// Build data and data format
	foreach ( $fields as $field => $value ) {
		if ( ! empty( $table_fields[ $field ] ) ) {
			$data[ $field ] = $value;
			$data_format[]  = $table_fields[ $field ];
		}
	}

	if ( ! empty( $fields['user_item_id'] ) ) {
		$where = wp_parse_args(
			$where,
			array( 'user_item_id' => $fields['user_item_id'] )
		);
	}

	//
	if ( $where && empty( $where['user_id'] ) ) {
		$where['user_id'] = learn_press_get_current_user_id();
	}

	$where_format = array();

	/// Build where and where format
	if ( $where ) {
		foreach ( $where as $field => $value ) {
			if ( ! empty( $table_fields[ $field ] ) ) {
				$where_format[] = $table_fields[ $field ];
			}
		}
	}

	if ( ! $data ) {
		return false;
	}

	$inserted = false;
	$updated  = false;

	// If $where is not empty consider we are updating
	if ( $where ) {
		$updated = $wpdb->update(
			$wpdb->learnpress_user_items,
			$data,
			$where,
			$data_format,
			$where_format
		);
	} else {

		// Otherwise, insert a new one
		if ( $wpdb->insert(
			$wpdb->learnpress_user_items,
			$data,
			$data_format
		)
		) {
			$inserted = $wpdb->insert_id;
		}
	}

	if ( $updated && ! empty( $where['user_item_id'] ) ) {
		$inserted = $where['user_item_id'];
	}

	$updated_item = false;

	// Get the item we just have updated or inserted.
	if ( $inserted ) {
		$updated_item = learn_press_get_user_item( $inserted );
	} else if ( $updated ) {
		$updated_item = learn_press_get_user_item( $where );
	}

	/**
	 * If there is some fields does not contain in the main table
	 * then consider update them as meta data.
	 */
	if ( $updated_item ) {
		$extra_fields = array_diff_key( $fields, $table_fields );
		if ( $extra_fields ) {
			foreach ( $extra_fields as $meta_key => $meta_value ) {
				if ( $meta_value == 'user_item_id' ) {
					continue;
				}

				if ( empty( $meta_value ) ) {
					$meta_value = '';
				}
				learn_press_update_user_item_meta( $updated_item->user_item_id, $meta_key, $meta_value );
			}
		}
	}

	// Refresh cache
	if ( $update_cache && $updated_item ) {

		// Get course id
		if ( LP_COURSE_CPT === get_post_type( $updated_item->item_id ) ) {
			$course_id = $updated_item->item_id;
		} else {
			$course_id = $updated_item->ref_id;
		}

		// Read new data from DB.
		$curd = learn_press_get_curd( 'user' );
		$curd->read_course( $updated_item->user_id, $course_id, true );
	}

	do_action( 'learn-press/updated-user-item-meta', $updated_item );

	return $updated_item;
}

/**
 * Get user item row(s) from user items table by multiple WHERE conditional
 *
 * @param array|int $where
 * @param bool      $single
 *
 * @return array
 */
function learn_press_get_user_item( $where, $single = true ) {
	global $wpdb;

	// Table fields
	$table_fields = array(
		'user_item_id' => '%d',
		'user_id'      => '%d',
		'item_id'      => '%d',
		'ref_id'       => '%d',
		'start_time'   => '%s',
		'end_time'     => '%s',
		'item_type'    => '%s',
		'status'       => '%s',
		'ref_type'     => '%s',
		'parent_id'    => '%d'
	);

	// If $where is a number consider we are searching the record with unique user_item_id
	if ( is_numeric( $where ) ) {
		$where = array( 'user_item_id' => $where );
	}

	$where_str = array();
	foreach ( $where as $field => $value ) {
		if ( ! empty( $table_fields[ $field ] ) ) {
			$where_str[] = "{$field} = " . $table_fields[ $field ];
		}
	}
	$item = false;

	if ( $where_str ) {
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE " . join( ' AND ', $where_str ) . "
		", $where );
		if ( $single || ! empty( $where['user_item_id'] ) ) {
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
 * @param int    $user_item_id
 * @param string $meta_key
 * @param bool   $single
 *
 * @return mixed
 */
function learn_press_get_user_item_meta( $user_item_id, $meta_key, $single = true ) {
	$meta = false;
	if ( metadata_exists( 'learnpress_user_item', $user_item_id, $meta_key ) ) {
		$meta = get_metadata( 'learnpress_user_item', $user_item_id, $meta_key, $single );
	}

	return $meta;
}

/**
 * Add user item meta into table user_itemmeta
 *
 * @param int    $user_item_id
 * @param string $meta_key
 * @param mixed  $meta_value
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
 * @param int    $user_item_id
 * @param string $meta_key
 * @param mixed  $meta_value
 * @param string $prev_value
 *
 * @return bool|int
 */
function learn_press_update_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'learnpress_user_item', $user_item_id, $meta_key, $meta_value, $prev_value );
}


/**
 * Update user item meta to table user_itemmeta
 *
 * @param int    $object_id
 * @param string $meta_key
 * @param mixed  $meta_value
 * @param bool   $delete_all
 *
 * @return bool|int
 */
function learn_press_delete_user_item_meta( $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
	return delete_metadata( 'learnpress_user_item', $object_id, $meta_key, $meta_value, $delete_all );
}

/**
 * Exclude the temp users from query.
 *
 * @param WP_User_Query $q
 */
function learn_press_filter_temp_users( $q ) {
//	if ( $temp_users = learn_press_get_temp_users() ) {
//		$exclude = (array) $q->get( 'exclude' );
//		$exclude = array_merge( $exclude, $temp_users );
//		$q->set( 'exclude', $exclude );
//	}
}

//add_action( 'pre_get_users', 'learn_press_filter_temp_users' );

/**
 * Get temp users.
 *
 * @return array
 */
function learn_press_get_temp_users() {
	return false;
	if ( false === ( $temp_users = wp_cache_get( 'learn-press/temp-users' ) ) ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->users} u 
			INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
			LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
		", '_lp_temp_user', 'yes', '_lp_expiration' );

		$temp_users = $wpdb->get_col( $query );

		wp_cache_set( 'learn-press/temp-users', $temp_users );
	}

	return $temp_users;
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

//add_action( 'init', 'learn_press_user_update_user_info' );

function learn_press_user_update_user_info() {
	global $wp, $wpdb;
	$user             = learn_press_get_current_user();
	$user_id          = learn_press_get_current_user_id();
	$message_template = '<div class="learn-press-message %s">'
	                    . '<p>%s</p>'
	                    . '</div>';

	if ( ! $user_id || is_admin() ) {
		return;
	}
	if ( ! empty( $_POST ) && isset( $_POST['from'] ) && isset( $_POST['action'] ) && $_POST['from'] == 'profile' && $_POST['action'] == 'update' ) {
# - - - - - - - - - - - - - - - - - - - -
# CREATE SOME DIRECTORY
#
		$upload = wp_get_upload_dir();
		$ppdir  = $upload['basedir'] . DIRECTORY_SEPARATOR . 'learn-press-profile';
		if ( ! is_dir( $ppdir ) ) {
			mkdir( $ppdir );
		}
		$upload_dir = $ppdir . DIRECTORY_SEPARATOR . $user_id;
		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir );
		}
		$upload_dir_tmp = $upload_dir . DIRECTORY_SEPARATOR . 'tmp';
		if ( ! is_dir( $upload_dir_tmp ) ) {
			mkdir( $upload_dir_tmp );
		}
		$lp_profile_url = $upload['baseurl'] . '/learn-press-profile/' . $user_id . '/';
#
# CREATE SOME DIRECTORY
# - - - - - - - - - - - - - - - - - - - -


# - - - - - - - - - - - - - - - - - - - -
# UPLOAD TEMP PICTURE PROFILE
#
		if ( isset( $_POST['sub_action'] ) && 'upload_avatar' === $_POST['sub_action'] && isset( $_FILES['image'] ) ) {
			$image_name = $_FILES['image']['name'];
			$image_tmp  = $_FILES['image']['tmp_name'];
			$image_size = intval( $_FILES['image']['size'] );
			$image_type = strtolower( $_FILES['image']['type'] );
			$filename   = strtolower( pathinfo( $image_name, PATHINFO_FILENAME ) );
			$file_ext   = strtolower( pathinfo( $image_name, PATHINFO_EXTENSION ) );

			if ( ( ! empty( $_FILES["image"] ) ) && ( $_FILES['image']['error'] == 0 ) ) {
				$allowed_image_types = array(
					'image/pjpeg' => "jpg",
					'image/jpeg'  => "jpg",
					'image/jpg'   => "jpg",
					'image/png'   => "png",
					'image/x-png' => "png",
					'image/gif'   => "gif"
				);
				$mine_types          = array_keys( $allowed_image_types );
				$image_exts          = array_values( $allowed_image_types );
				# caculate $image_size_limit
				$max_upload       = intval( ini_get( 'upload_max_filesize' ) );
				$max_post         = intval( ini_get( 'post_max_size' ) );
				$memory_limit     = intval( ini_get( 'memory_limit' ) );
				$image_size_limit = min( $max_upload, $max_post, $memory_limit, WP_MEMORY_LIMIT );
				if ( ! $image_size_limit ) {
					$image_size_limit = 1;
				}
				if ( ! in_array( $image_type, $mine_types ) ) {
					$_message = __( 'Only', 'learnpress' ) . ' <strong>' . implode( ',', $image_exts ) . '</strong> ' . __( 'images accepted for upload', 'learnpress' );
					$message  = sprintf( $message_template, 'error', $_message );
					$return   = array(
						'return'  => false,
						'message' => $message
					);
					learn_press_send_json( $return );
				}
				if ( $image_size > $image_size_limit * 1048576 ) {
					$message = __( 'Images must be under', 'learnpress' ) . ' ' . $image_size_limit . __( 'MB in size', 'learnpress' );
					$return  = array(
						'return'  => false,
						'message' => $message
					);
					learn_press_send_json( $return );
				}
			} else {
				$message = __( 'Please select an image for upload', 'learnpress' );
				$return  = array(
					'return'  => false,
					'message' => $message
				);
				learn_press_send_json( $return );
			}

			if ( isset( $_FILES['image']['name'] ) ) {
				// upload picture to tmp folder
				$path_image_tmp = $upload_dir_tmp . DIRECTORY_SEPARATOR . $filename . '.' . $file_ext;
				if ( file_exists( $path_image_tmp ) ) {
					$filename       .= '1';
					$path_image_tmp = $upload_dir_tmp . DIRECTORY_SEPARATOR . $filename . '.' . $file_ext;
				}
				$uploaded = move_uploaded_file( $image_tmp, $path_image_tmp );
				chmod( $path_image_tmp, 0777 );
				if ( $uploaded ) {
					$editor3 = wp_get_image_editor( $path_image_tmp );
					if ( ! is_wp_error( $editor3 ) ) {
						# Calculator new width height
						$size_current = $editor3->get_size();
						if ( $size_current['width'] < 250 || $size_current['width'] < 250 ) {
							$editor3->resize( 250, 250, true );
							$saved = $editor3->save();
						}
					}
				}

				$_message = $uploaded ? __( 'Image is uploaded success', 'learnpress' ) : __( 'Error in uploading image', 'learnpress' );
				$message  = sprintf( $message_template, 'success', $_message );
				$return   = array(
					'return'  => $uploaded,
					'message' => $message
				);
				if ( $uploaded ) {
					$return['avatar_tmp']          = $lp_profile_url . 'tmp/' . $filename . '.' . $file_ext;
					$return['avatar_tmp_filename'] = $filename . '.' . $file_ext;
				}
				learn_press_send_json( $return );
			}
			exit();
		}
# 
# END OF UPLOAD TEMP PROFILE PICTURE
# - - - - - - - - - - - - - - - - - - - -

# - - - - - - - - - - - - - - - - - - - -
# CREATE PROFILE PICTURE & THUMBNAIL
#	
		if ( isset( $_POST['sub_action'] ) && 'crop_avatar' === $_POST['sub_action'] && isset( $_POST['avatar_filename'] ) ) {
			$avatar_filename = filter_input( INPUT_POST, 'avatar_filename', FILTER_SANITIZE_STRING );
			$avatar_filepath = $upload_dir . DIRECTORY_SEPARATOR . $avatar_filename;
			$editor          = wp_get_image_editor( $upload_dir_tmp . DIRECTORY_SEPARATOR . $avatar_filename );
			if ( is_wp_error( $editor ) ) {
				learn_press_add_message( __( 'Thumbnail of image profile not created', 'learnpress' ) );
			} else {
				# Calculator new width height
				$size_current = $editor->get_size();
				$zoom         = floatval( $_POST['zoom'] );
				$offset       = $_POST['offset'];
				$size_new     = array(
					'width'  => $size_current['width'] * $zoom,
					'height' => $size_current['height'] * $zoom
				);
				$editor->resize( $size_new['width'], $size_new['height'], true );
				$offset_x = max( intval( $offset['x'] ), - intval( $offset['x'] ) );
				$offset_y = max( intval( $offset['y'] ), - intval( $offset['y'] ) );
				$editor->crop( $offset_x, $offset_y, 248, 248 );
				$saved          = $editor->save( $upload_dir . DIRECTORY_SEPARATOR . $avatar_filename );
				$res            = array();
				$res['message'] = '';
				if ( is_wp_error( $saved ) ) {
					$_message               = __( 'Error in cropping user picture profile', 'learnpress' );
					$message                = sprintf( $message_template, 'error', $_message );
					$res['return']          = false;
					$res['message']         = $message;
					$res['avatar_filename'] = '';
					$res['avatar_url']      = '';
				} else {
					# - - - - - - - - - - - - - - - - - - - -
					# Create Thumbnai
					#
					if ( file_exists( $avatar_filepath ) ) {
						$editor2 = wp_get_image_editor( $avatar_filepath );
						if ( is_wp_error( $editor2 ) ) {
							$_message       = __( 'Thumbnail of image profile not created', 'learnpress' );
							$message        = sprintf( $message_template, 'error', $_message );
							$res['message'] .= $message;
						} else {
							$editor2->set_quality( 90 );
							$lp         = LP();
							$lp_setting = $lp->settings;
							$size       = $lp_setting->get( 'profile_picture_thumbnail_size' );
							if ( empty( $size ) || ! isset( $size['width'] ) ) {
								$size = array( 'width' => 150, 'height' => 150, 'crop' => 'yes' );
							}
							if ( isset( $size['crop'] ) && $size['crop'] == 'yes' ) {
								$size_width  = $size['width'];
								$size_height = $size['height'];
								$resized     = $editor2->resize( $size_width, $size_height, true );
								if ( is_wp_error( $resized ) ) {
									$_message       = __( 'Thumbnail of image profile not created', 'learnpress' );
									$message        = sprintf( $message_template, 'error', $_message );
									$res['message'] .= $message;
								} else {
									$dest_file = $editor2->generate_filename( 'thumb' );
									$saved     = $editor2->save( $dest_file );
									if ( is_wp_error( $saved ) ) {
										$_message       = __( 'Thumbnail of image profile not created', 'learnpress' );
										$message        = sprintf( $message_template, 'error', $_message );
										$res['message'] .= $message;
									} else {
										// save thumbnail profile picture to user option
										$avatar_thumbnail_filename = pathinfo( $dest_file, PATHINFO_BASENAME );
										update_user_option( $user->get_id(), '_lp_profile_picture_thumbnail_url', $lp_profile_url . $avatar_thumbnail_filename, true );
									}
								}
							}
						}
					}
					#
					# Create Thumbnai for Profile Picture
					# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					update_user_option( $user->get_id(), '_lp_profile_picture_type', 'picture', true );
					update_user_option( $user->get_id(), '_lp_profile_picture', $avatar_filename, true );
					update_user_option( $user->get_id(), '_lp_profile_picture_url', $lp_profile_url . $avatar_filename, true );

					$_message               = __( 'Profile picture is changed', 'learnpress' );
					$message                = sprintf( $message_template, 'success', $_message );
					$res['return']          = true;
					$res['message']         .= $message;
					$res['avatar_filename'] = $avatar_filename;
					$res['avatar_url']      = $lp_profile_url . $avatar_filename;
				}
				learn_press_send_json( $res );
			}
			exit();
		}
#		
# CREATE PROFILE PICTURE & THUMBNAIL
# - - - - - - - - - - - - - - - - - - - -


# - - - - - - - - - - - - - - - - - - - -
# UPDATE USER INFO
#	
		$return      = array();
		$update_data = array(
			'ID'           => $user_id,
			'first_name'   => filter_input( INPUT_POST, 'first_name', FILTER_SANITIZE_STRING ),
			'last_name'    => filter_input( INPUT_POST, 'last_name', FILTER_SANITIZE_STRING ),
			'display_name' => filter_input( INPUT_POST, 'display_name', FILTER_SANITIZE_STRING ),
			'nickname'     => filter_input( INPUT_POST, 'nickname', FILTER_SANITIZE_STRING ),
			'description'  => filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING ),
		);
		# check and update pass word
		if ( ! empty( $_POST['pass0'] ) && ! empty( $_POST['pass1'] ) && ! empty( $_POST['pass1'] ) ) {
			// check old pass
			$old_pass       = filter_input( INPUT_POST, 'pass0' );
			$check_old_pass = false;
			if ( ! $old_pass ) {
				$check_old_pass = false;
			} else {
				$cuser = wp_get_current_user();
				require_once( ABSPATH . 'wp-includes/class-phpass.php' );
				$wp_hasher = new PasswordHash( 8, true );
				if ( $wp_hasher->CheckPassword( $old_pass, $cuser->data->user_pass ) ) {
					$check_old_pass = true;
				}
			}
			if ( ! $check_old_pass ) {
				$_message               = __( 'Old password incorrect!', 'learnpress' );
				$message                = sprintf( $message_template, 'error', $_message );
				$return['return']       = false;
				$return['message']      = $message;
				$return['redirect_url'] = '';
				learn_press_send_json( $return );
				exit();

				return;
			} else {
				// check new pass
				$new_pass  = filter_input( INPUT_POST, 'pass1' );
				$new_pass2 = filter_input( INPUT_POST, 'pass2' );
				if ( $new_pass != $new_pass2 ) {
					$_message               = __( 'Confirmation password incorrect!', 'learnpress' );
					$message                = sprintf( $message_template, 'error', $_message );
					$return['return']       = false;
					$return['message']      = $message;
					$return['redirect_url'] = '';
					learn_press_send_json( $return );
					exit();

					return;
				} else {
					$update_data['user_pass'] = $new_pass;
				}
			}
		}

		$profile_picture_type = filter_input( INPUT_POST, 'profile_picture_type', FILTER_SANITIZE_STRING );
		update_user_option( $user->get_id(), '_lp_profile_picture_type', $profile_picture_type, true );
		$res = wp_update_user( $update_data );
		if ( $res ) {
			$_message               = __( 'Your changes are saved', 'learnpress' );
			$message                = sprintf( $message_template, 'success', $_message );
			$return['return']       = true;
			$return['message']      = $message;
			$return['redirect_url'] = '';
			learn_press_send_json( $return );
			exit();
		} else {
			$_message               = __( 'Error in update your profile info', 'learnpress' );
			$message                = sprintf( $message_template, 'error', $_message );
			$return['return']       = false;
			$return['message']      = $message;
			$return['redirect_url'] = '';
			learn_press_send_json( $return );
			exit();
		}

		$current_url = learn_press_get_page_link( 'profile' ) . $user->user_login . '/edit';
		wp_redirect( $current_url );
		exit();
	}
}

if ( ! function_exists( 'learn_press_pre_get_avatar_callback' ) ) {
	/**
	 * Filter the avatar
	 *
	 * @param string $avatar
	 * @param string $id_or_email
	 * @param array  $size
	 *
	 * @return string
	 */
	function learn_press_pre_get_avatar_callback( $avatar, $id_or_email = '', $size ) {

		$profile = LP_Profile::instance();

		if ( ! $profile->is_enable_avatar() ) {
			return $avatar;
		}

		if ( ( isset( $size['gravatar'] ) && $size['gravatar'] ) || ( $size['default'] && $size['force_default'] ) ) {
			return $avatar;
		}

		$user_id = 0;

		/**
		 * Get the ID of user from $id_or_email
		 */
		if ( ! is_numeric( $id_or_email ) && is_string( $id_or_email ) ) {
			if ( $user = get_user_by( 'email', $id_or_email ) ) {
				$user_id = $user->ID;
			}
		} elseif ( is_numeric( $id_or_email ) ) {
			$user_id = $id_or_email;
		} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) && $id_or_email->user_id ) {
			$user_id = $id_or_email->user_id;
		} elseif ( is_object( $id_or_email ) && $id_or_email instanceof WP_Comment ) {
			if ( $user = get_user_by( 'email', $id_or_email->comment_author_email ) ) {
				$user_id = $user->ID;
			}
		}

		if ( ! $user_id ) {
			return $avatar;
		}

		$user = LP_User_Factory::get_user( $user_id );

		if ( $profile_picture_src = $user->get_upload_profile_src() ) {
			$lp           = LP();
			$lp_setting   = $lp->settings;
			$setting_size = $lp_setting->get( 'profile_picture_thumbnail_size' );
			$img_size     = '';

			// Get avatar size
			if ( ! is_array( $size ) ) {
				if ( $size === 'thumbnail' ) {
					$img_size = '';
					$height   = $setting_size['height'];
					$width    = $setting_size['width'];
				} else {
					$height = 250;
					$width  = 250;
				}
			} else {
				$img_size = $size['size'];
				$height   = $size['height'];
				$width    = $size['width'];
			}
			$avatar = '<img alt="Admin bar avatar" src="' . esc_attr( $profile_picture_src ) . '" class="avatar avatar-' . $img_size . ' photo" height="' . $height . '" width="' . $width . '" />';
		}

		return $avatar;
	}
}
add_filter( 'pre_get_avatar', 'learn_press_pre_get_avatar_callback', 1, 5 );


function learn_press_user_profile_picture_upload_dir( $width_user = true ) {
	static $upload_dir;
	if ( ! $upload_dir ) {
		$upload_dir = wp_upload_dir();
		$subdir     = apply_filters( 'learn_press_user_profile_folder', 'learn-press-profile', $width_user );
		if ( $width_user ) {
			$subdir .= '/' . get_current_user_id();
		}
		$subdir = '/' . $subdir;

		if ( ! empty( $upload_dir['subdir'] ) ) {
			$u_subdir = str_replace( '\\', '/', $upload_dir['subdir'] );
			$u_path   = str_replace( '\\', '/', $upload_dir['path'] );

			$upload_dir['path'] = str_replace( $u_subdir, $subdir, $u_path );
			$upload_dir['url']  = str_replace( $u_subdir, $subdir, $upload_dir['url'] );
		} else {
			$upload_dir['path'] = $upload_dir['path'] . $subdir;
			$upload_dir['url']  = $upload_dir['url'] . $subdir;
		}

		$upload_dir['subdir'] = $subdir;

		// Point path/url to main site if we are in multisite
		if ( is_multisite() && ! ( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {
			foreach ( array( 'path', 'url', 'basedir', 'baseurl' ) as $v ) {
				$upload_dir[ $v ] = str_replace( '/sites/' . get_current_blog_id(), '', $upload_dir[ $v ] );
			}
		}
	}

	return $upload_dir;
}

add_action( 'learn_press_before_purchase_course_handler', '_learn_press_before_purchase_course_handler', 10, 2 );
function _learn_press_before_purchase_course_handler( $course_id, $cart ) {
	// Redirect to login page if user is not logged in
	if ( ! is_user_logged_in() ) {
		$return_url = add_query_arg( $_POST, get_the_permalink( $course_id ) );
		$return_url = apply_filters( 'learn_press_purchase_course_login_redirect_return_url', $return_url );
		$redirect   = apply_filters( 'learn_press_purchase_course_login_redirect', learn_press_get_login_url( $return_url ) );
		if ( $redirect !== false ) {
			learn_press_add_message( __( 'Please login to enroll this course', 'learnpress' ) );

			if ( learn_press_is_ajax() ) {
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

function learn_press_user_is( $role, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user = learn_press_get_current_user();
	} else {
		$user = learn_press_get_user( $user_id );
	}
	if ( $role == 'admin' ) {
		return $user->is_admin();
	}
	if ( $role == 'instructor' ) {
		return $user->is_instructor();
	}

	return $role;
}

//function learn_press_profile_tab_endpoints_edit_profile( $endpoints ) {
//	$endpoints['edit'] = 'edit';
//	print_r($endpoints);
//	return $endpoints;
//}
//
//add_filter( 'learn_press_profile_tab_endpoints', 'learn_press_profile_tab_endpoints_edit_profile' );

function learn_press_profile_tab_edit_content( $current, $tab, $user ) {
	learn_press_get_template( 'profile/tabs/edit.php', array( 'user' => $user, 'current' => $current, 'tab' => $tab ) );
}

function _learn_press_redirect_logout_redirect() {
	$redirect_to = LP_Request::get_string( 'redirect_to' );
	$admin_url   = admin_url();
	$pos         = strpos( $redirect_to, $admin_url );

	if ( $pos !== false ) {
		return;
	}

	if ( ( $page_id = LP()->settings->get( 'logout_redirect_page_id' ) ) && get_post( $page_id ) ) {
		$page_url = get_page_link( $page_id );
		wp_redirect( $page_url );
		exit();
	}
}

add_action( 'wp_logout', '_learn_press_redirect_logout_redirect' );

function learn_press_get_profile_endpoints() {
	$endpoints = (array) LP()->settings->get( 'profile_endpoints' );
	if ( $tabs = LP_Profile::instance()->get_tabs() ) {
		foreach ( $tabs as $slug => $info ) {
			if ( empty( $endpoints[ $slug ] ) ) {
				$endpoints[ $slug ] = $slug;
			}
		}
	}

	return apply_filters( 'learn_press_profile_tab_endpoints', $endpoints );
}


function learn_press_update_user_option( $name, $value, $id = 0 ) {
	if ( ! $id ) {
		$id = get_current_user_id();
	}
	$key              = 'learnpress_user_options';
	$options          = get_user_option( $key, $id );
	$options[ $name ] = $value;
	update_user_option( $id, $key, $options, true );
}

/**
 * @param     $name
 * @param int $id
 *
 * @return bool
 */
function learn_press_delete_user_option( $name, $id = 0 ) {
	if ( ! $id ) {
		$id = get_current_user_id();
	}
	$key     = 'learnpress_user_options';
	$options = get_user_option( $key, $id );
	if ( is_array( $options ) && array_key_exists( $name, $options ) ) {
		unset( $options[ $name ] );
		update_user_option( $id, $key, $options, true );

		return true;
	}

	return false;
}

/**
 * @param     $name
 * @param int $id
 *
 * @return bool
 */
function learn_press_get_user_option( $name, $id = 0 ) {
	if ( ! $id ) {
		$id = get_current_user_id();
	}
	$key     = 'learnpress_user_options';
	$options = get_user_option( $key, $id );
	if ( is_array( $options ) && array_key_exists( $name, $options ) ) {
		return $options[ $name ];
	}

	return false;
}

/**
 * @param LP_User
 *
 * @return array
 */
function learn_press_get_display_name_publicly( $user_id ) {

	$user_info = learn_press_get_user( $user_id );

	$public_display                     = array();
	$public_display['display_nickname'] = $user_info->get_data( 'nickname' );
	$public_display['display_username'] = $user_info->get_data( 'user_login' );

	if ( $user_info->get_data( 'first_name' ) ) {
		$public_display['display_firstname'] = $user_info->get_data( 'first_name' );
	}

	if ( $user_info->get_data( 'last_name' ) ) {
		$public_display['display_lastname'] = $user_info->get_data( 'last_name' );
	}

	if ( $user_info->get_data( 'first_name' ) && $user_info->get_data( 'last_name' ) ) {
		$public_display['display_firstlast'] = $user_info->get_data( 'first_name' ) . ' ' . $user_info->get_data( 'last_name' );
		$public_display['display_lastfirst'] = $user_info->get_data( 'last_name' ) . ' ' . $user_info->get_data( 'first_name' );
	}

	if ( ! in_array( $user_info->get_data( 'display_name' ), $public_display ) ) // Only add this if it isn't duplicated elsewhere
	{
		$public_display = array( 'display_displayname' => $user_info->get_data( 'display_name' ) ) + $public_display;
	}

	$public_display = array_map( 'trim', $public_display );
	$public_display = array_unique( $public_display );

	return apply_filters( 'learn_press_display_name_publicly', $public_display );
}

/**
 * Check and update user information from request in user profile page
 */
function learn_press_update_user_profile() {
	if ( ! LP()->is_request( 'post' ) ) {
		return;
	}
	$nonce = learn_press_get_request( 'profile-nonce' );
	if ( ! wp_verify_nonce( $nonce, 'learn-press-update-user-profile-' . get_current_user_id() ) ) {
		return;
	}
	$section = learn_press_get_request( 'lp-profile-section' );
	do_action( 'learn_press_update_user_profile_' . $section );
	do_action( 'learn_press_update_user_profile', $section );
}

add_action( 'init', 'learn_press_update_user_profile' );

/**
 * Update user avatar
 */
function learn_press_update_user_profile_avatar() {
	$upload_dir = learn_press_user_profile_picture_upload_dir();
	if ( learn_press_get_request( 'lp-user-avatar-custom' ) != 'yes' ) {
		delete_user_meta( get_current_user_id(), '_lp_profile_picture' );
	} else {
		$data = learn_press_get_request( 'lp-user-avatar-crop' );
		if ( $data && ( $path = $upload_dir['basedir'] . $data['name'] ) && file_exists( $path ) ) {
			$filetype = wp_check_filetype( $path );
			if ( 'jpg' == $filetype['ext'] ) {
				$im = imagecreatefromjpeg( $path );
			} elseif ( 'png' == $filetype['ext'] ) {
				$im = imagecreatefrompng( $path );
			} else {
				return;
			}
			$points  = explode( ',', $data['points'] );
			$im_crop = imagecreatetruecolor( $data['width'], $data['height'] );
			if ( $im !== false ) {
				$user  = wp_get_current_user();
				$dst_x = 0;
				$dst_y = 0;
				$dst_w = $data['width'];
				$dst_h = $data['height'];
				$src_x = $points[0];
				$src_y = $points[1];
				$src_w = $points[2] - $points[0];
				$src_h = $points[3] - $points[1];
				imagecopyresampled( $im_crop, $im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );
				$newname = md5( $user->user_login );
				$output  = dirname( $path );
				if ( 'jpg' == $filetype['ext'] ) {
					$newname .= '.jpg';
					$output  .= '/' . $newname;
					imagejpeg( $im_crop, $output );
				} elseif ( 'png' == $filetype['ext'] ) {
					$newname .= '.png';
					$output  .= '/' . $newname;
					imagepng( $im_crop, $output );
				}
				if ( file_exists( $output ) ) {
					update_user_meta( get_current_user_id(), '_lp_profile_picture', preg_replace( '!^/!', '', $upload_dir['subdir'] ) . '/' . $newname );
					update_user_meta( get_current_user_id(), '_lp_profile_picture_changed', 'yes' );
				}

			}
			@unlink( $path );
		}
	}

	return true;
}

//add_action( 'learn_press_update_user_profile_avatar', 'learn_press_update_user_profile_avatar' );

/**
 * Update user basic information.
 *
 * @param bool $wp_error - Optional. Return WP_Error object in case updating failed.
 *
 * @return bool|mixed|WP_Error
 */
function learn_press_update_user_profile_basic_information( $wp_error = false ) {

	$user_id = get_current_user_id();

	$_POST['description'] = 1000;

	$update_data = array(
		'ID'           => $user_id,
		'first_name'   => filter_input( INPUT_POST, 'first_name', FILTER_SANITIZE_STRING ),
		'last_name'    => filter_input( INPUT_POST, 'last_name', FILTER_SANITIZE_STRING ),
		'display_name' => filter_input( INPUT_POST, 'display_name', FILTER_SANITIZE_STRING ),
		'nickname'     => filter_input( INPUT_POST, 'nickname', FILTER_SANITIZE_STRING ),
		'description'  => filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING ),
	);
	print_r( $update_data );
	print_r( $_POST );
	echo "xxxxx";
	$update_data = apply_filters( 'learn-press/update-profile-basic-information-data', $update_data );
	$return      = wp_update_user( $update_data );

	if ( is_wp_error( $return ) ) {
		return $wp_error ? $return : false;
	}

	return $return;

}

//add_action( 'learn_press_update_user_profile_basic-information', 'learn_press_update_user_profile_basic_information' );

/**
 * Update new password.
 *
 * @param bool $wp_error - Optional. Return WP_Error instance in case updating failed.
 *
 * @return WP_Error|bool
 */
function learn_press_update_user_profile_change_password( $wp_error = false ) {
	# check and update pass word
	// check old pass
	$old_pass       = filter_input( INPUT_POST, 'pass0' );
	$check_old_pass = false;

	if ( $old_pass ) {
		$cuser = wp_get_current_user();
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		$wp_hasher = new PasswordHash( 8, true );

		if ( $wp_hasher->CheckPassword( $old_pass, $cuser->data->user_pass ) ) {
			$check_old_pass = true;
		}
	}

	try {
		if ( ! $check_old_pass ) {
			throw new Exception( __( 'Old password incorrect!', 'learnpress' ) );
		} else {
			// check new pass
			$new_pass  = filter_input( INPUT_POST, 'pass1' );
			$new_pass2 = filter_input( INPUT_POST, 'pass2' );

			if ( ! $new_pass || ! $new_pass2 || ( $new_pass != $new_pass2 ) ) {
				throw new Exception( __( 'Confirmation password incorrect!', 'learnpress' ) );
			} else {
				$update_data = array(
					'user_pass' => $new_pass,
					'ID'        => get_current_user_id()
				);
				$return      = wp_update_user( $update_data );

				if ( is_wp_error( $return ) ) {
					return $wp_error ? $return : false;
				}

				return $return;
			}
		}
	}
	catch ( Exception $ex ) {
		return $wp_error ? new WP_Error( 'UPDATE_PROFILE_ERROR', $ex->getMessage() ) : false;
	}
}

//add_action( 'learn_press_update_user_profile_change-password', 'learn_press_update_user_profile_change_password' );

function learn_press_get_avatar_thumb_size() {
	$avatar_size_settings = LP()->settings->get( 'profile_picture_thumbnail_size' );
	$avatar_size          = array();
	if ( ! empty( $avatar_size_settings['width'] ) ) {
		$avatar_size['width'] = absint( $avatar_size_settings['width'] );
	} elseif ( ! empty( $avatar_size_settings[0] ) ) {
		$avatar_size['width'] = absint( $avatar_size_settings[0] );
	} else {
		$avatar_size['width'] = 150;
	}
	if ( ! empty( $avatar_size_settings['height'] ) ) {
		$avatar_size['height'] = absint( $avatar_size_settings['height'] );
	} elseif ( ! empty( $avatar_size_settings[1] ) ) {
		$avatar_size['height'] = absint( $avatar_size_settings[1] );
	} else {
		$avatar_size['height'] = 150;
	}

	return $avatar_size;
}

/**
 * @param $user_id
 * @param $course_ids
 *
 * @return mixed
 */
function learn_press_get_user_courses_info( $user_id, $course_ids ) {
	global $wpdb;
	$user_course_info = LP_Cache::get_course_info( false, array() );

	if ( $user_id ) {
		settype( $course_ids, 'array' );
		$format = array( $user_id );
		$format = array_merge( $format, $course_ids, array( 'lp_course' ) );
		$in     = array_fill( 0, sizeof( $course_ids ), '%d' );
		$query  = $wpdb->prepare( "
			SELECT uc.*
			FROM {$wpdb->prefix}learnpress_user_items uc
			INNER JOIN {$wpdb->posts} o ON o.ID = uc.item_id
			WHERE uc.user_id = %d AND uc.status IS NOT NULL
			AND uc.item_id IN(" . join( ',', $in ) . ") AND uc.item_type = %s
			ORDER BY user_item_id DESC
		", $format );
		if ( empty( $user_course_info[ $user_id ] ) ) {
			$user_course_info[ $user_id ] = array();
		}

		if ( $result = $wpdb->get_results( $query ) ) {
			foreach ( $result as $row ) {
				$course_id = $row->item_id;
				if ( ! empty( $user_course_info[ $user_id ][ $course_id ]['history_id'] ) ) {
					continue;
				}
				//$row                                    = $result;
				$info                                       = array(
					'history_id' => 0,
					'start'      => null,
					'end'        => null,
					'status'     => null
				);
				$course                                     = learn_press_get_course( $course_id );
				$info['history_id']                         = $row->user_item_id;
				$info['start']                              = $row->start_time;
				$info['end']                                = $row->end_time;
				$info['status']                             = $row->status;
				$info['results']                            = $course->evaluate_course_results( $user_id );
				$info['items']                              = $course->get_items_params( $user_id );
				$user_course_info[ $user_id ][ $course_id ] = $info;
			}
		}
		// Set default data if a course is not existing in database
		foreach ( $course_ids as $cid ) {
			if ( isset( $user_course_info[ $user_id ], $user_course_info[ $user_id ][ $cid ] ) ) {
				continue;
			}
			$user_course_info[ $user_id ][ $cid ] = array(
				'history_id' => 0,
				'start'      => null,
				'end'        => null,
				'status'     => null
			);
		}
	} else {
		// Set default data if a course is not existing in database
		$user_course_info[ $user_id ] = array();
		foreach ( $course_ids as $cid ) {
			$user_course_info[ $user_id ][ $cid ] = array(
				'history_id' => 0,
				'start'      => null,
				'end'        => null,
				'status'     => null
			);
		}
	}
	LP_Cache::set_course_info( $user_course_info );

	return $user_course_info[ $user_id ];
}

/**
 * Set a fake cookie to
 */
function learn_press_set_user_cookie_for_guest() {
	if ( ! is_admin() && ! headers_sent() ) {
		$guest_key = 'wordpress_lp_guest';
		if ( is_user_logged_in() ) {
			if ( ! empty( $_COOKIE[ $guest_key ] ) ) {
				//setcookie( $guest_key, md5( time() ), - 10000 );
				learn_press_remove_cookie( $guest_key );
			}
		} else {
			if ( empty( $_COOKIE[ $guest_key ] ) ) {
				///setcookie( $guest_key, md5( time() ), time() + 3600 );
				learn_press_setcookie( $guest_key, md5( time() ), time() + 3600 );
			}
		}
	}
}

add_action( 'wp', 'learn_press_set_user_cookie_for_guest' );

function learn_press_get_user_avatar( $user_id = 0, $size = '' ) {
	$user = learn_press_get_user( $user_id );

	return $user->get_profile_picture( '', $size );
}

/**
 * Get profile instance for an user to view.
 *
 * @param int $for_user
 *
 * @return LP_Profile|WP_Error
 */
function learn_press_get_profile( $for_user = 0 ) {
	return LP_Profile::instance( $for_user );
}

function learn_press_profile_list_display_names( $args = '' ) {

	$args = wp_parse_args( $args, array(
		'id'      => 'display_name',
		'name'    => 'display_name',
		'user_id' => get_current_user_id(),
		'echo'    => true
	) );

	$output         = sprintf( '<select name="%s" id="%s">', $args['name'], $args['id'] );
	$public_display = learn_press_get_display_name_publicly( $args['user_id'] );

	$user = learn_press_get_user( $args['user_id'] );

	foreach ( $public_display as $id => $item ) {
		$output .= sprintf( '<option value="%s"%s>%s</option>', $item, selected( $user->get_data( 'display_name' ), $item, false ), $item );
	}
	$output .= '</select>';

	if ( $args['echo'] ) {
		echo $output;
	}

	return $output;
}

///////////////////
/**
 * Remove items from learnpress_user_items.
 *
 * @param int  $user_id
 * @param int  $item_id
 * @param int  $course_id
 * @param bool $include_course - Optional. If TRUE then remove course and it's items
 */
function learn_press_remove_user_items( $user_id, $item_id, $course_id, $include_course = false ) {
	global $wpdb;

	settype( $item_id, 'array' );

	$format = array_fill( 0, sizeof( $item_id ), '%d' );
	$where  = '';

	$args = array( $user_id );
	$args = array_merge( $args, $item_id );

	if ( $course_id ) {
		$args[] = $course_id;
		$where  = "AND ref_id = %d";
	}

	if ( $include_course ) {
		$where  .= " OR ( item_id = %d AND item_type = %s )";
		$args[] = $course_id;
		$args[] = LP_COURSE_CPT;
	}

	$query = $wpdb->prepare( "
        DELETE
        FROM {$wpdb->learnpress_user_items}
        WHERE user_id = %d 
        AND ( item_id IN(" . join( ',', $format ) . ")
        $where )
    ", $args );
}

/**
 * Get user profile link
 *
 * @param int  $user_id
 * @param null $tab
 *
 * @return mixed|string
 */
function learn_press_user_profile_link( $user_id = 0, $tab = null ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$user    = false;
	$deleted = in_array( $user_id, LP_User_Factory::$_deleted_users );
	if ( ! $deleted ) {
		if ( is_numeric( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );
		} else {
			$user = get_user_by( 'login', urldecode( $user_id ) );
		}
	} else {
		return '';
	}
	if ( ! $deleted && ! $user ) {
		LP_User_Factory::$_deleted_users[] = $user_id;
	}

	$user = learn_press_get_user( $user_id );

	if ( ! $user ) {
		return '';
	}

	global $wp_query;
	$args = array(
		'user' => $user->get_username()
	);

	if ( isset( $args['user'] ) ) {
		if ( '' === $tab ) {
			$tab = learn_press_get_current_profile_tab();
		}
		if ( $tab ) {
			$args['tab'] = $tab;
		}

		/**
		 * If no tab is selected in profile and is current user
		 * then no need the username in profile link.
		 */
		if ( ( $user_id == get_current_user_id() ) && ! isset( $args['tab'] ) ) {
			unset( $args['user'] );
		}
	}
	$args         = array_map( '_learn_press_urlencode', $args );
	$profile_link = trailingslashit( learn_press_get_page_link( 'profile' ) );
	if ( $profile_link ) {
		if ( get_option( 'permalink_structure' ) /*&& learn_press_get_page_id( 'profile' )*/ ) {
			$url = trailingslashit( $profile_link . join( "/", array_values( $args ) ) );
		} else {
			$url = add_query_arg( $args, $profile_link );
		}
	} else {
		$url = get_author_posts_url( $user_id );
	}


	return apply_filters( 'learn_press_user_profile_link', $url, $user_id, $tab );
}

/**********************************************/
/*       Functions are used for hooks         */
/**********************************************/

function learn_press_hk_before_start_quiz( $true, $quiz_id, $course_id, $user_id ) {
	if ( 'yes' !== get_post_meta( $quiz_id, '_lp_archive_history', true ) ) {
		learn_press_remove_user_items( $user_id, $quiz_id, $course_id );
	}

	return $true;
}

add_filter( 'learn-press/before-start-quiz', 'learn_press_hk_before_start_quiz', 10, 4 );

function learn_press_default_user_item_status( $item_id ) {
	$status = '';
	switch ( get_post_type( $item_id ) ) {
		case LP_LESSON_CPT:
			$status = 'started';
			break;
		case LP_QUIZ_CPT:
			$status = 'viewed';
			break;
		case LP_COURSE_CPT:
			$status = 'enrolled';
	}

	return apply_filters( 'learn-press/default-user-item-status', $status, $item_id );
}