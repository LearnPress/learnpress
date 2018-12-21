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

	$query = $wpdb->prepare( "
        SELECT user_item_id
        FROM {$wpdb->prefix}learnpress_user_items
        WHERE user_id = %d
        " . ( $course_id ? " AND item_id = %d" : "" ) . "
    ", $query_args );

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
		if ( $item = learn_press_cache_get( 'course-' . $user_id . '-' . $item_id, 'lp-user-courses' ) ) {
			return $item['user_item_id'];
		}
	} else {

		// Otherwise, get item of the course
		if ( $items = learn_press_cache_get( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, 'lp-user-course-items' ) ) {
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
	static $current_user = false;

	if ( $id = get_current_user_id() ) {
		if ( ! $current_user || $force_new ) {
			$current_user = learn_press_get_user( $id, $force_new );
		}

		return $current_user;
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
		LP_Debug::logTime( __FUNCTION__ );

		if ( $user_id != LP()->session->guest_user_id ) {
			// Check if user is existing
			if ( $current && ! get_user_by( 'id', $user_id ) ) {
				$user_id = get_current_user_id();
			}
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
		LP_Debug::logTime( __FUNCTION__ );

		return LP_Global::$users[ $user_id ];
	}
}

/**
 * Add more 2 user roles teacher and student
 *
 */
function learn_press_add_user_roles() {

	$settings = LP()->settings;

	/* translators: user role */
	_x( 'LP Instructor', 'User role' );

	add_role(
		LP_TEACHER_ROLE,
		'LP Instructor',
		array()
	);


	$course_cap = LP_COURSE_CPT . 's';
	$lesson_cap = LP_LESSON_CPT . 's';
	$order_cap  = LP_ORDER_CPT . 's';

	// teacher
	if ( $teacher = get_role( LP_TEACHER_ROLE ) ) {
		$teacher->add_cap( 'delete_published_' . $course_cap );
		$teacher->add_cap( 'edit_published_' . $course_cap );
		$teacher->add_cap( 'edit_' . $course_cap );
		$teacher->add_cap( 'delete_' . $course_cap );
		$teacher->add_cap( 'unfiltered_html' );

		$settings->get( 'required_review' );

		if ( $settings->get( 'required_review' ) == 'yes' ) {
			$teacher->remove_cap( 'publish_' . $course_cap );
		} else {
			$teacher->add_cap( 'publish_' . $course_cap );
		}

		$teacher->add_cap( 'delete_published_' . $lesson_cap );
		$teacher->add_cap( 'edit_published_' . $lesson_cap );
		$teacher->add_cap( 'edit_' . $lesson_cap );
		$teacher->add_cap( 'delete_' . $lesson_cap );
		$teacher->add_cap( 'publish_' . $lesson_cap );
		$teacher->add_cap( 'upload_files' );
		$teacher->add_cap( 'read' );
		$teacher->add_cap( 'edit_posts' );
	}

	// administrator
	if ( $admin = get_role( 'administrator' ) ) {
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
}

add_action( 'init', 'learn_press_add_user_roles' );

/**
 * @param null  $user_id
 * @param array $args
 *
 * @return mixed
 */
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
	if ( ( $profile = learn_press_get_page_id( 'profile' ) ) && learn_press_get_post_type( $profile ) == 'page' && get_post_status( $profile ) != 'trash' && ( LP()->settings->get( 'admin_bar_link' ) == 'yes' ) ) {
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
 * @param array $fields                         - Fields and values to be updated.
 *                                              Format: array(
 *                                              field_name_1 => value 1,
 *                                              field_name_2 => value 2,
 *                                              ....
 *                                              field_name_n => value n
 *                                              )
 * @param mixed $where                          - Optional. Fields with values for conditional update with the same format of $fields.
 * @param bool  $update_cache                   - Optional. Should be update to cache or not (since 3.0.0).
 * @param bool  $update_extra_fields_as_meta    - Optional. Update extra fields as item meta (since 3.1.0).
 *
 * @return mixed
 */
function learn_press_update_user_item_field( $fields, $where = false, $update_cache = true, $update_extra_fields_as_meta = false ) {
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
		$item_type = learn_press_get_post_type( $fields['item_id'] );
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
		$where['user_id'] = ! empty( $fields['user_id'] ) ? $fields['user_id'] : learn_press_get_current_user_id();
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
	if ( $updated_item && $update_extra_fields_as_meta ) {
		$extra_fields = array_diff_key( $fields, $table_fields );
		if ( $extra_fields ) {
			foreach ( $extra_fields as $meta_key => $meta_value ) {
				if ( $meta_value == 'user_item_id' ) {
					continue;
				}

				if ( $meta_value === false ) {
					learn_press_delete_user_item_meta( $updated_item->user_item_id, $meta_key );
				} else {

					if ( empty( $meta_value ) ) {
						$meta_value = '';
					}
					learn_press_update_user_item_meta( $updated_item->user_item_id, $meta_key, $meta_value );
				}
			}
		}
	}

	// Refresh cache
	if ( $update_cache && $updated_item ) {

		// Get course id
		if ( LP_COURSE_CPT === learn_press_get_post_type( $updated_item->item_id ) ) {
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
	if ( false === ( $temp_users = LP_Object_Cache::get( 'learn-press/temp-users' ) ) ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->users} u 
			INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
			LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
		", '_lp_temp_user', 'yes', '_lp_expiration' );

		$temp_users = $wpdb->get_col( $query );

		LP_Object_Cache::set( 'learn-press/temp-users', $temp_users );
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

//add_action( 'init', 'learn_press_update_user_profile' );

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
				$newname = md5( $user->user_login . microtime( true ) );
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

	$update_data = array(
		'ID'           => $user_id,
		'first_name'   => filter_input( INPUT_POST, 'first_name', FILTER_SANITIZE_STRING ),
		'last_name'    => filter_input( INPUT_POST, 'last_name', FILTER_SANITIZE_STRING ),
		'display_name' => filter_input( INPUT_POST, 'display_name', FILTER_SANITIZE_STRING ),
		'nickname'     => filter_input( INPUT_POST, 'nickname', FILTER_SANITIZE_STRING ),
		'description'  => filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING )
	);

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
	switch ( learn_press_get_post_type( $item_id ) ) {
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

/**
 * Get current state of distraction mode
 *
 * @since 3.1.0
 *
 * @return mixed
 */
function learn_press_get_user_distraction() {
	if ( is_user_logged_in() ) {
		return get_user_option( 'distraction_mode', get_current_user_id() );
	} else {
		return LP()->session->distraction_mode;
	}
}

function learn_press_get_user_role( $user_id ) {
	if ( $user = learn_press_get_user( $user_id ) ) {
		return $user->get_role();
	}

	return false;
}