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

	$query = $wpdb->prepare(
		"
        SELECT user_item_id
        FROM {$wpdb->prefix}learnpress_user_items
        WHERE user_id = %d
        " . ( $course_id ? ' AND item_id = %d' : '' ) . '
    ',
		$query_args
	);

	// delete all courses user has enrolled
	$query = $wpdb->prepare(
		"
        DELETE FROM {$wpdb->prefix}learnpress_user_items
        WHERE user_id = %d
        " . ( $course_id ? ' AND item_id = %d' : '' ) . '
    ',
		$query_args
	);

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
 * @editor tungnx
 * @reason this function only get cache, not handle get user_item_id
 * @deprecated 4.1.6.9
 */
function learn_press_get_user_item_id( $user_id, $item_id, $course_id = 0 /* added 3.0.0 */ ) {
	return false;
}

/**
 * Get current user ID
 *
 * @return int|string LP_User is int, LP_User_Guest is string
 */
function learn_press_get_current_user_id() {
	$user = learn_press_get_current_user();

	if ( ! $user ) {
		return 0;
	}

	return $user->get_id();
}

/**
 * Get the user by $user_id passed. If $user_id is NULL, get current user.
 * If current user is not logged in, return a GUEST user
 *
 * @param bool $create_temp - Optional. Create temp user if user is not logged in.
 *
 * @return LP_User|LP_User_Guest
 * @editor tungnx
 * @modify 4.1.4
 * @version 1.0.1
 */
function learn_press_get_current_user( $create_temp = true ) {
	$user_id = get_current_user_id();

	if ( $user_id ) {
		return learn_press_get_user( $user_id );
	}

	// Return LP_User_Guest
	return learn_press_get_user( 0 );
}

if ( ! function_exists( 'learn_press_get_user' ) ) {
	/**
	 * Get user by ID. Return false if the user does not exist.
	 * If user_id = 0, return a guest user.
	 *
	 * @param int  $user_id
	 * @param bool $current
	 *
	 * @return LP_User|LP_User_Guest|false
	 * @since 3.0.0
	 * @version 4.0.1
	 */
	function learn_press_get_user( $user_id = 0, $current = false, $force_new = false ) {
		$userClass = $user_id && get_user_by( 'ID', $user_id ) ? 'LP_User' : ( $user_id == 0 ? 'LP_User_Guest' : false );
		if ( false === $userClass ) {
			return false;
		}

		return new $userClass( $user_id );
	}
}

/**
 * Add more 2 user roles teacher and student
 */
function learn_press_add_user_roles() {

	$settings = LP_Settings::instance();

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

	$teacher = get_role( LP_TEACHER_ROLE );
	if ( $teacher ) {
		$teacher->add_cap( 'read_private_' . $course_cap );
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

		$teacher->add_cap( 'read_private_' . $lesson_cap );
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
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'read_private_' . $course_cap );
		$admin->add_cap( 'delete_' . $course_cap );
		$admin->add_cap( 'delete_published_' . $course_cap );
		$admin->add_cap( 'edit_' . $course_cap );
		$admin->add_cap( 'edit_published_' . $course_cap );
		$admin->add_cap( 'publish_' . $course_cap );
		$admin->add_cap( 'delete_private_' . $course_cap );
		$admin->add_cap( 'edit_private_' . $course_cap );
		$admin->add_cap( 'delete_others_' . $course_cap );
		$admin->add_cap( 'edit_others_' . $course_cap );

		$admin->add_cap( 'read_private_' . $lesson_cap );
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
 * @deprecated 4.1.7.3
 */
/*function learn_press_get_user_questions( $user_id = null, $args = array() ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return learn_press_get_user( $user_id )->get_questions( $args );
}*/

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

function learn_press_current_user_can_view_profile_section( $section, $user ) {
	$current_user = wp_get_current_user();
	$view         = true;
	if ( $user->get_data( 'user_login' ) != $current_user->user_login && $section == LP_Settings::instance()->get(
		'profile_endpoints.orders',
		'profile-orders'
	) ) {
		$view = false;
	}

	return apply_filters( 'learn_press_current_user_can_view_profile_section', $view, $section, $user );
}

/*function learn_press_profile_tab_courses_content( $current, $tab, $user ) {
	learn_press_get_template(
		'profile/tabs/courses.php',
		array(
			'user'    => $user,
			'current' => $current,
			'tab'     => $tab,
		)
	);
}*/

function learn_press_profile_tab_quizzes_content( $current, $tab, $user ) {
	learn_press_get_template(
		'profile/tabs/quizzes.php',
		array(
			'user'    => $user,
			'current' => $current,
			'tab'     => $tab,
		)
	);
}

function learn_press_profile_tab_orders_content( $current, $tab, $user ) {
	learn_press_get_template(
		'profile/tabs/orders.php',
		array(
			'user'    => $user,
			'current' => $current,
			'tab'     => $tab,
		)
	);
}

/**
 * Get queried user in profile link
 *
 * @return false|WP_User
 * @since 3.0.0
 */
function learn_press_get_profile_user() {
	return LP_Profile::get_queried_user();
}


/**
 * Add instructor registration button to register page and admin bar
 */
function learn_press_user_become_teacher_registration_form() {
	if ( LP_Settings::instance()->get( 'instructor_registration' ) != 'yes' ) {
		return;
	}
	?>
	<p>
		<label for="become_teacher">
			<input type="checkbox" name="become_teacher" id="become_teacher">
			<?php esc_html_e( 'Want to become an instructor?', 'learnpress' ); ?>
		</label>
	</p>
	<?php
}

add_action( 'register_form', 'learn_press_user_become_teacher_registration_form' );

/**
 * Update data into table learnpress_user_items.
 *
 * @param array $fields - Fields and values to be updated.
 *                                              Format: array(
 *                                              field_name_1 => value 1,
 *                                              field_name_2 => value 2,
 *                                              ....
 *                                              field_name_n => value n
 *                                              )
 * @param mixed $where - Optional. Fields with values for conditional update with the same format of $fields.
 * @param bool  $update_cache - Optional. Should be update to cache or not (since 3.0.0).
 * @param bool  $update_extra_fields_as_meta - Optional. Update extra fields as item meta (since 3.1.0).
 *
 * @return mixed
 */
function learn_press_update_user_item_field( array $fields = [], $where = false, $update_cache = true, $update_extra_fields_as_meta = false ) {
	global $wpdb;

	// Table fields format.
	$table_fields = array(
		'user_item_id' => '%d',
		'user_id'      => '%d',
		'item_id'      => '%d',
		'ref_id'       => '%d',
		'start_time'   => '%s',
		'end_time'     => '%s',
		'access_level' => '%d',
		'graduation'   => '%s',
		'item_type'    => '%s',
		'status'       => '%s',
		'ref_type'     => '%s',
		'parent_id'    => '%d',
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

	$data        = array();
	$data_format = array();
	foreach ( $fields as $field => $value ) {
		if ( ! empty( $table_fields[ $field ] ) ) {
			$data[ $field ] = $value;

			// Do not format the date-time field if it's value is NULL
			if ( in_array( $field, [ 'start_time', 'end_time' ] ) && empty( $value ) ) {
				$data[ $field ] = null;
				$data_format[]  = '';
			} else {
				if ( $value instanceof LP_Datetime ) {
					$data[ $field ] = $value->toSql();
				}
				$data_format[] = $table_fields[ $field ];
			}
		}
	}

	if ( ! empty( $fields['user_item_id'] ) ) {
		$where = wp_parse_args(
			$where,
			array( 'user_item_id' => $fields['user_item_id'] )
		);
	}

	// Set where and where format
	$where_format = array();
	if ( is_array( $where ) && ! empty( $where ) ) {
		if ( empty( $where['user_id'] ) ) {
			$where['user_id'] = ! empty( $fields['user_id'] ) ? $fields['user_id'] : learn_press_get_current_user_id();
		}

		foreach ( $where as $field => $value ) {
			if ( isset( $table_fields[ $field ] ) ) {
				$where_format[] = $table_fields[ $field ];
			}
		}
	}

	if ( ! $data ) {
		return false;
	}

	$inserted = false;
	$updated  = false;

	// Update to database.
	if ( $where ) {
		$updated = $wpdb->update(
			$wpdb->learnpress_user_items,
			$data,
			$where,
			$data_format,
			$where_format
		);
	} else {
		// Insert to database.
		if ( $wpdb->insert(
			$wpdb->learnpress_user_items,
			$data,
			$data_format
		) ) {
			$inserted = $wpdb->insert_id;
		}
	}

	if ( $updated && ! empty( $where['user_item_id'] ) ) {
		$inserted = $where['user_item_id'];
	}

	/**
	 * @var object|bool $updated_item
	 */
	$updated_item = false;

	// Get the item we just have updated or inserted.
	if ( $inserted ) {
		$updated_item = learn_press_get_user_item( $inserted );
	} elseif ( $updated ) {
		$updated_item = learn_press_get_user_item( $where );
	}

	// Clear caches.
	if ( $updated_item ) {
		// Clear cache total students enrolled.
		if ( isset( $updated_item->item_type )
			&& LP_COURSE_CPT === $updated_item->item_type
			&& isset( $updated_item->item_id ) ) {
			$lp_course_cache = new LP_Course_Cache( true );
			$lp_course_cache->clean_total_students_enrolled( $updated_item->item_id );
			$lp_course_cache->clean_total_students_enrolled_or_purchased( $updated_item->item_id );
		}
		// Clear cache user item.
		$lp_user_items_cache = new LP_User_Items_Cache( true );
		$lp_user_items_cache->clean_user_item(
			[
				$updated_item->user_id,
				$updated_item->item_id,
				$updated_item->item_type,
			]
		);
	}

	do_action( 'learn-press/updated-user-item-field', $updated_item );

	/**
	 * If there is some fields does not contain in the main table
	 * then consider update them as metadata.
	 * @comment by tungnx - 4.1.7.3
	 */
	/*if ( $updated_item && $update_extra_fields_as_meta ) {
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
	}*/

	//do_action( 'learn-press/updated-user-item-meta', $updated_item );

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
		'parent_id'    => '%d',
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
		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE " . join( ' AND ', $where_str ) . '
			ORDER BY user_item_id DESC
			',
			$where
		);
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
 * @param int    $user_item_id .
 * @param string $meta_key .
 * @param bool   $single .
 *
 * @return mixed
 */
function learn_press_get_user_item_meta( $user_item_id = 0, $meta_key = '', $single = true ) {
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
 * Get temp users.
 *
 * @return array
 * @deprecated 4.1.6.9
 */
/*function learn_press_get_temp_users() {
	return false;
	if ( false === ( $temp_users = LP_Object_Cache::get( 'learn-press/temp-users' ) ) ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
			LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
		",
			'_lp_temp_user',
			'yes',
			'_lp_expiration'
		);

		$temp_users = $wpdb->get_col( $query );

		LP_Object_Cache::set( 'learn-press/temp-users', $temp_users );
	}

	return $temp_users;
}*/

/**
 * Update field created_time after added user item meta
 *
 * @use updated_{meta_type}_meta hook
 *
 * @param $meta_id
 * @param $object_id
 * @param $meta_key
 * @param $_meta_value
 * @deprecated 4.1.7.3
 */
/*function _learn_press_update_created_time_user_item_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	global $wpdb;
	$wpdb->update(
		$wpdb->learnpress_user_itemmeta,
		array( 'create_time' => current_time( 'mysql' ) ),
		array( 'meta_id' => $meta_id ),
		array( '%s' ),
		array( '%d' )
	);
}*/

// add_action( 'added_learnpress_user_item_meta', '_learn_press_update_created_time_user_item_meta', 10, 4 );

/**
 * Update field updated_time after updated user item meta
 *
 * @use updated_{meta_type}_meta hook
 *
 * @param $meta_id
 * @param $object_id
 * @param $meta_key
 * @param $_meta_value
 * @deprecated
 */
/*function _learn_press_update_updated_time_user_item_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	global $wpdb;
	$wpdb->update(
		$wpdb->learnpress_user_itemmeta,
		array( 'update_time' => current_time( 'mysql' ) ),
		array( 'meta_id' => $meta_id ),
		array( '%s' ),
		array( '%d' )
	);
}*/

// add_action( 'updated_learnpress_user_item_meta', '_learn_press_update_updated_time_user_item_meta', 10, 4 );

/**
 * @param     $status
 * @param int    $quiz_id
 * @param int    $user_id
 * @param int    $course_id
 *
 * @return bool|mixed
 * @deprecated 4.1.7.3
 */
/*function learn_press_user_has_quiz_status( $status, $quiz_id = 0, $user_id = 0, $course_id = 0 ) {
	$user = learn_press_get_user( $user_id );

	return $user->has_quiz_status( $status, $quiz_id, $course_id );
}*/

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
	function learn_press_pre_get_avatar_callback( $avatar, $id_or_email = '', $size = array() ) {
		try {
			if ( ( isset( $size['gravatar'] ) && $size['gravatar'] ) || ( $size['default'] && $size['force_default'] ) ) {
				return $avatar;
			}

			$user_id = 0;

			/**
			 * Get the ID of user from $id_or_email
			 */
			if ( ! is_numeric( $id_or_email ) && is_string( $id_or_email ) ) {
				$user = get_user_by( 'email', $id_or_email );
				if ( $user ) {
					$user_id = $user->ID;
				}
			} elseif ( is_numeric( $id_or_email ) ) {
				$user_id = $id_or_email;
			} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) && $id_or_email->user_id ) {
				$user_id = $id_or_email->user_id;
			} elseif ( $id_or_email instanceof WP_Comment ) {
				$user = get_user_by( 'email', $id_or_email->comment_author_email );
				if ( $user ) {
					$user_id = $user->ID;
				}
			}

			if ( ! $user_id ) {
				return $avatar;
			}

			$user = learn_press_get_user( $user_id );
			if ( ! $user ) {
				return $avatar;
			}

			return $user->get_profile_picture( '', $size['height'] ?? 32 );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
			return $avatar;
		}
	}
}
add_filter( 'pre_get_avatar', 'learn_press_pre_get_avatar_callback', 1, 5 );


function learn_press_user_profile_picture_upload_dir( $width_user = true ) {
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

	return $upload_dir;
}

add_action( 'learn_press_before_purchase_course_handler', '_learn_press_before_purchase_course_handler', 10, 2 );
function _learn_press_before_purchase_course_handler( $course_id, $cart ) {
	// Redirect to login page if user is not logged in
	if ( ! is_user_logged_in() ) {
		$return_url = esc_url_raw( add_query_arg( $_POST, get_the_permalink( $course_id ) ) );
		$return_url = apply_filters( 'learn_press_purchase_course_login_redirect_return_url', $return_url );
		$redirect   = apply_filters(
			'learn_press_purchase_course_login_redirect',
			learn_press_get_login_url( $return_url )
		);
		if ( $redirect !== false ) {
			learn_press_add_message( __( 'Please log in to enroll in this course', 'learnpress' ) );

			if ( learn_press_is_ajax() ) {
				learn_press_send_json(
					array(
						'redirect' => $redirect,
						'result'   => 'success',
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
			learn_press_add_message( __( 'You have already finished the course', 'learnpress' ) );
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

function learn_press_profile_tab_edit_content( $current, $tab, $user ) {
	learn_press_get_template(
		'profile/tabs/edit.php',
		array(
			'user'    => $user,
			'current' => $current,
			'tab'     => $tab,
		)
	);
}

function learn_press_get_profile_endpoints() {
	$endpoints = (array) LP_Settings::instance()->get( 'profile_endpoints' );
	$tabs      = LP_Profile::instance()->get_tabs();
	if ( $tabs ) {
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
 * @param int  $id
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
 * @param int  $id
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
 * Update user basic information.
 *
 * @param bool $wp_error - Optional. Return WP_Error object in case updating failed.
 *
 * @return bool|mixed|WP_Error
 */
function learn_press_update_user_profile_basic_information( $wp_error = false ) {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return new WP_Error( 2, 'The user is invalid' );
	}

	$update_data = array(
		'ID'           => $user_id,
		'first_name'   => LP_Request::get_param( 'first_name' ),
		'last_name'    => LP_Request::get_param( 'last_name' ),
		'description'  => LP_Request::get_param( 'description', '', 'html' ),
		'display_name' => LP_Request::get_param( 'account_display_name' ),
		'user_email'   => LP_Request::get_param( 'account_email' ),
	);

	$update_data = apply_filters( 'learn-press/update-profile-basic-information-data', $update_data );
	$update_meta = LP_Helper::sanitize_params_submitted( $_POST['_lp_custom_register'] ?? '' );

	$return = LP_Forms_Handler::update_user_data( $update_data, $update_meta );

	// Update for social.
	$socials    = LP_Request::get_array( 'user_profile_social' );
	$extra_data = get_user_meta( $user_id, '_lp_extra_info', true );

	if ( ! empty( $extra_data ) ) {
		$socials = array_merge( $extra_data, $socials );
	}

	update_user_meta( $user_id, '_lp_extra_info', $socials );

	if ( is_wp_error( $return ) ) {
		return $wp_error ? $return : false;
	}

	return $return;
}

/**
 * Update new password.
 */
function learn_press_update_user_profile_change_password( $wp_error = false ) {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return new WP_Error( 2, 'The user is invalid' );
	}

	$old_pass       = filter_input( INPUT_POST, 'pass0' );
	$check_old_pass = false;

	if ( $old_pass ) {
		$user = wp_get_current_user();
		require_once ABSPATH . 'wp-includes/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );

		if ( $wp_hasher->CheckPassword( $old_pass, $user->data->user_pass ) ) {
			$check_old_pass = true;
		}
	}

	try {
		if ( ! $check_old_pass ) {
			throw new Exception( __( 'The old password is incorrect!', 'learnpress' ) );
		} else {
			$new_pass  = filter_input( INPUT_POST, 'pass1' );
			$new_pass2 = filter_input( INPUT_POST, 'pass2' );

			if ( ! $new_pass || ! $new_pass2 || ( $new_pass != $new_pass2 ) ) {
				throw new Exception( __( 'Incorrect confirmation password!', 'learnpress' ) );
			} else {
				$update_data = array(
					'user_pass' => $new_pass,
					'ID'        => get_current_user_id(),
				);
				$return      = wp_update_user( $update_data );

				if ( is_wp_error( $return ) ) {
					return $wp_error ? $return : false;
				}

				return $return;
			}
		}
	} catch ( Exception $ex ) {
		return $wp_error ? new WP_Error( 'UPDATE_PROFILE_ERROR', $ex->getMessage() ) : false;
	}
}

function learn_press_get_avatar_thumb_size() {
	$option = LP_Settings::get_option(
		'avatar_dimensions',
		array(
			'width'  => 250,
			'height' => 250,
		)
	);

	if ( ! isset( $option['width'] ) || ! isset( $option['height'] ) ) {
		$option = array(
			'width'  => 250,
			'height' => 250,
		);
	}

	return $option;
}

function learn_press_get_course_thumbnail_dimensions() {
	$option = LP_Settings::get_option(
		'course_thumbnail_dimensions',
		array(
			'width'  => 500,
			'height' => 300,
		)
	);

	if ( ! isset( $option['width'] ) || ! isset( $option['height'] ) ) {
		$option = array(
			'width'  => 500,
			'height' => 300,
		);
	}

	return $option;
}

/**
 * Set a fake cookie to
 * @deprecated 4.2.0
 */
function learn_press_set_user_cookie_for_guest() {
	_deprecated_function( __METHOD__, '4.2.0' );
	if ( ! is_admin() && ! headers_sent() ) {
		$guest_key = '_wordpress_lp_guest';

		if ( is_user_logged_in() ) {
			if ( ! empty( $_COOKIE[ $guest_key ] ) ) {
				learn_press_remove_cookie( $guest_key );
			}
		} else {
			if ( empty( $_COOKIE[ $guest_key ] ) ) {
				learn_press_setcookie( $guest_key, md5( time() ), time() + 3600 );
			}
		}
	}
}

//add_action( 'wp', 'learn_press_set_user_cookie_for_guest' );

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
		$where  = 'AND ref_id = %d';
	}

	if ( $include_course ) {
		$where .= ' OR ( item_id = %d AND item_type = %s )';
		$args[] = $course_id;
		$args[] = LP_COURSE_CPT;
	}

	$query = $wpdb->prepare(
		"
        DELETE
        FROM {$wpdb->learnpress_user_items}
        WHERE user_id = %d
        AND ( item_id IN(" . join( ',', $format ) . ")
        $where )
    ",
		$args
	);
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
		'user' => $user->get_username(),
	);

	if ( isset( $args['user'] ) ) {
		if ( '' === $tab ) {
			//$tab = learn_press_get_current_profile_tab();
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

	/*$args         = array_map(
		function ( $string ) {
			return preg_replace( '/\s/', '+', $string );
		},
		$args
	);*/
	$profile_link = trailingslashit( learn_press_get_page_link( 'profile' ) );
	if ( $profile_link ) {
		if ( get_option( 'permalink_structure' ) /*&& learn_press_get_page_id( 'profile' )*/ ) {
			$url = trailingslashit( $profile_link . join( '/', array_values( $args ) ) );
		} else {
			$url = esc_url_raw( add_query_arg( $args, $profile_link ) );
		}
	} else {
		$url = get_author_posts_url( $user_id );
	}

	return apply_filters( 'learn_press_user_profile_link', $url, $user_id, $tab );
}

/**********************************************/
/*       Functions are used for hooks         */
/**********************************************/

/*function learn_press_hk_before_start_quiz( $true, $quiz_id, $course_id, $user_id ) {
	if ( 'yes' !== get_post_meta( $quiz_id, '_lp_archive_history', true ) ) {
		learn_press_remove_user_items( $user_id, $quiz_id, $course_id );
	}

	return $true;
}*/

//add_filter( 'learn-press/before-start-quiz', 'learn_press_hk_before_start_quiz', 10, 4 );

/*function learn_press_default_user_item_status( $item_id ) {
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
}*/

/**
 * Get current state of distraction mode
 *
 * @return mixed
 * @since 3.1.0
 * @deprecated 4.2.0
 */
function learn_press_get_user_distraction() {
	_deprecated_function( __FUNCTION__, '4.2.0' );
	if ( is_user_logged_in() ) {
		return get_user_option( 'distraction_mode', get_current_user_id() );
	} else {
		return LearnPress::instance()->session->distraction_mode;
	}
}

/**
 * @deprecated 4.2.0
 */
function learn_press_get_user_role( $user_id ) {
	_deprecated_function( __FUNCTION__, '4.2.0' );
	if ( $user = learn_press_get_user( $user_id ) ) {
		return $user->get_role();
	}

	return false;
}

/**
 * @param array $args
 * @param bool  $wp_error
 *
 * @return bool|int|LP_User_Item|mixed|WP_Error
 */
function learn_press_create_user_item( $args = array(), $wp_error = false ) {
	global $wpdb;

	$defaults = array(
		'user_id'     => get_current_user_id(),
		'item_id'     => '',
		'start_time'  => time(),
		'end_time'    => '',
		'graduation'  => '',
		'item_type'   => '',
		'status'      => '',
		'ref_id'      => 0,
		'ref_type'    => '',
		'parent_id'   => 0,
		'create_meta' => array(),
	);

	$item_data = wp_parse_args( $args, $defaults );

	// Validate item_id and post type
	if ( empty( $item_data['item_id'] ) ) {
		if ( $wp_error ) {
			return new WP_Error( 'invalid_item_id', __( 'Invalid item id.', 'learnpress' ) );
		}

		return 0;
	}

	$post_type = learn_press_get_post_type( $item_data['item_id'] );
	if ( empty( $item_data['item_type'] ) && $post_type ) {
		$item_data['item_type'] = $post_type;
	}

	// Get id and type of ref if they are null
	if ( ! empty( $item_data['parent_id'] ) && ( empty( $item_data['ref_id'] ) || ( empty( $item_data['ref_type'] ) ) ) ) {
		$parent = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->learnpress_user_items} WHERE %d",
				$item_data['parent_id']
			)
		);

		if ( $parent ) {
			if ( empty( $item_data['ref_id'] ) ) {
				$item_data['ref_id'] = $parent->item_id;
			}

			if ( empty( $item_data['ref_type'] ) ) {
				$item_data['ref_type'] = $parent->item_type;
			}
		}
	}

	// Filter
	$item_data = apply_filters( 'learn-press/create-user-item-data', $item_data );
	if ( ! $item_data ) {
		if ( $wp_error ) {
			return new WP_Error( 'invalid_item_data', __( 'Invalid item data.', 'learnpress' ) );
		}

		return 0;
	}

	do_action( 'learn-press/before-create-user-item', $item_data );

	$create_meta = ! empty( $item_data['create_meta'] ) ? $item_data['create_meta'] : false;

	if ( $create_meta ) {
		unset( $item_data['create_meta'] );
	}

	$user_item = new LP_User_Item( $item_data );
	$result    = $user_item->update();
	if ( ! $result || is_wp_error( $result ) ) {
		if ( $wp_error && is_wp_error( $result ) ) {
			return $result;
		}

		return 0;
	}

	do_action( 'learn-press/created-user-item', $user_item, $item_data );

	$create_meta = apply_filters( 'learn-press/create-user-item-meta', $create_meta, $item_data );
	if ( ! $create_meta ) {
		return $user_item;
	}

	do_action( 'learn-press/before-create-user-item-meta', $create_meta );

	foreach ( $create_meta as $key => $value ) {
		learn_press_update_user_item_meta( $user_item->get_user_item_id(), $key, $value );
	}

	do_action( 'learn-press/created-user-item-meta', $user_item, $create_meta );

	return $user_item;
}

/**
 * @param array $args
 * @param bool  $wp_error - Optional. TRUE will return WP_Error on fail.
 *
 * @return bool|array|LP_User_Item|WP_Error
 * @deprecated 4.2.5
 */
function learn_press_create_user_item_for_quiz( $args = array(), $wp_error = false ) {
	$item_data = wp_parse_args(
		$args,
		array(
			'item_type'  => LP_QUIZ_CPT,
			'status'     => LP_ITEM_STARTED,
			'graduation' => LP_COURSE_GRADUATION_IN_PROGRESS,
			'user_id'    => get_current_user_id(),
		)
	);

	$user_item = learn_press_create_user_item( $item_data, $wp_error );

	if ( $user_item && ! is_wp_error( $user_item ) ) {
		$user_item = new LP_User_Item_Quiz( $user_item->get_data() );
		$user_item->update();
	}

	return $user_item;
}

/**
 * Get list user_item_id for Quiz in table learnpress_user_items
 *
 * @param int $quiz_id
 * @param int $course_id
 * @return array || false
 */
function learn_press_isset_user_item_for_quiz( $quiz_id, $course_id ) {
	global $wpdb;

	$query = $wpdb->prepare( "SELECT user_item_id FROM $wpdb->learnpress_user_items WHERE ref_id=%d AND item_id=%d", $course_id, $quiz_id );
	$col   = $wpdb->get_col( $query );

	if ( ! empty( $col ) ) {
		return $col;
	} else {
		return false;
	}
}

/**
 * Create new user item prepare for user starts a quiz
 * Update error retry course not work - Nhamdv.
 *
 * @param int $quiz_id
 * @param int $user_id
 * @param int $course_id
 * @param bool $wp_error
 *
 * @return array|bool|LP_User_Item|WP_Error
 * @throws Exception
 * @since 4.0.0
 * @version 1.0.1
 * @deprecated 4.2.5
 */
function learn_press_user_start_quiz( $quiz_id, $user_id = 0, $course_id = 0, $wp_error = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	global $wpdb;

	$query = $wpdb->prepare(
		"
	    SELECT user_item_id, item_id id, item_type type
	    FROM {$wpdb->learnpress_user_items}
	    WHERE user_item_id = (SELECT max(user_item_id)
	    FROM {$wpdb->learnpress_user_items}
	    WHERE user_id = %d AND item_id = %d AND status IN ('enrolled', 'in-progress'))
		",
		$user_id,
		$course_id
	);

	$parent = $wpdb->get_row( $query );
	if ( is_null( $parent ) ) {
		throw new Exception( __( 'Course of Quiz not enroll', 'learnpress' ) );
	}

	do_action( 'learn-press/before-user-start-quiz', $quiz_id, $user_id, $course_id );

	/*$user        = learn_press_get_user( $user_id );
	$course_data = $user->get_course_data( $course_id );
	$quiz_data   = $course_data->get_item( $quiz_id );*/

	//$quiz      = LP_Quiz::get_quiz( $quiz_id );
	//$duration  = $quiz->get_duration();
	$user_quiz = learn_press_create_user_item_for_quiz(
		array(
			'item_id'   => $quiz_id,
			//'duration'  => $duration ? $duration->get() : 0,
			'user_id'   => $user_id,
			'parent_id' => $parent ? absint( $parent->user_item_id ) : 0,
			'ref_type'  => $parent ? $parent->type : '',
			'ref_id'    => $parent ? $parent->id : '',
		),
		$wp_error
	);

	if ( $user_quiz && ! is_wp_error( $user_quiz ) ) {
		do_action( 'learn-press/user-started-quiz', $user_quiz, $quiz_id, $user_id, $course_id );
	}

	// Reset first cache
	//$user_quiz->get_status( 'status', true );

	return $user_quiz;
}

/**
 * Function retake quiz.
 *
 * @param [type]  $quiz_id
 * @param integer $user_id
 * @param integer $course_id
 * @param boolean $wp_error
 * @deprecated 4.2.5
 *
 * @return void
 */
function learn_press_user_retake_quiz( $quiz_id, $user_id = 0, $course_id = 0, $wp_error = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $course_id ) {
		return new WP_Error( 'invalid_course_id', esc_html__( 'Invalid Course ID.', 'learnpress' ) );
	}

	global $wpdb;

	$query = $wpdb->prepare(
		"
	    SELECT user_item_id, item_id id, item_type type
	    FROM {$wpdb->learnpress_user_items}
	    WHERE user_item_id = (SELECT max(user_item_id)
	    FROM {$wpdb->learnpress_user_items}
	    WHERE user_id = %d AND item_id = %d AND status IN ('enrolled', 'in-progress'))
		",
		$user_id,
		$course_id
	);

	$parent = $wpdb->get_row( $query );

	if ( ! $parent ) {
		return new WP_Error( 'invalid_user_item', esc_html__( 'Invalid Quiz', 'learnpress' ) );
	}

	$data = learn_press_get_user_item(
		array(
			'item_id'   => $quiz_id,
			'user_id'   => $user_id,
			'parent_id' => $parent ? absint( $parent->user_item_id ) : 0,
			'ref_type'  => $parent ? $parent->type : LP_COURSE_CPT,
			'ref_id'    => $parent ? $parent->id : '',
		)
	);

	$user_item = new LP_User_Item_Quiz( $data );

	$ratake_count = get_post_meta( $quiz_id, '_lp_retake_count', true );

	if ( $ratake_count > 0 ) {
		$user_item->update_retake_count();
	}

	// Create new result in table learnpress_user_item_results.
	LP_User_Items_Result_DB::instance()->insert( $data->user_item_id );

	// Remove user_item_meta.
	learn_press_delete_user_item_meta( $data->user_item_id, '_lp_question_checked' );

	$user_item->set_status( LP_ITEM_STARTED )
				->set_start_time( time() ) // Error Retake when change timezone - Nhamdv
				->set_end_time()
				->set_graduation( LP_COURSE_GRADUATION_IN_PROGRESS )
				->update();

	// Reset first cache
	//$user_item->get_status( 'status', true );

	// Error Retake when change timezone - Nhamdv
	//  learn_press_update_user_item_field(
	//      array(
	//          'start_time' => current_time( 'mysql', true ),
	//      ),
	//      array(
	//          'user_item_id' => $data->user_item_id,
	//      )
	//  );

	return $user_item;
}


/**
 * Prepares list of questions for rest api.
 *
 * @param int[] $question_ids
 * @param array $args
 *
 * @return array
 * @since 3.3.0
 */
function learn_press_rest_prepare_user_questions( array $question_ids = array(), array $args = array() ) : array {
	if ( is_numeric( $args ) ) {

	} else {
		$args = wp_parse_args(
			$args,
			array(
				'instant_hint'        => true,
				'instant_check'       => true,
				'quiz_status'         => '',
				'checked_questions'   => array(),
				'hinted_questions'    => array(),
				'answered'            => array(),
				'show_correct_review' => true,
			)
		);
	}

	$checkedQuestions = $args['checked_questions'];
	$hintedQuestions  = $args['hinted_questions'];
	$instantHint      = $args['instant_hint'];
	$instantCheck     = $args['instant_check'];
	$quizStatus       = $args['quiz_status'];
	$answered         = $args['answered'];
	$status           = $args['status'] ?? '';
	$questions        = array();

	if ( $question_ids ) {
		foreach ( $question_ids as $id ) {
			$question       = learn_press_get_question( $id );
			$hasHint        = false;
			$hasExplanation = false;
			$canCheck       = false;
			$hinted         = false;
			$checked        = false;
			$theHint        = $question->get_hint();
			$theExplanation = '';

			if ( $instantCheck || $status == 'completed' ) {
				$theExplanation = $question->get_explanation();
				$checked        = in_array( $id, $checkedQuestions );
				$hasExplanation = ! ! $theExplanation;
			}

			 $mark = $question->get_mark() ? $question->get_mark() : 1;

			$questionData = array(
				'object' => $question,
				'id'     => absint( $id ),
				'title'  => $question->get_title(),
				'type'   => $question->get_type(),
				'point'  => $mark,
			);

			$content = $question->get_content();
			if ( $content ) {
				$questionData['content'] = $content;
			}

			if ( $theHint ) {
				$questionData['hint'] = $theHint;
			}

			if ( $status == 'completed' || ( $checked && $theExplanation ) ) {
				$questionData['explanation'] = $theExplanation;
			}

			if ( $hasExplanation ) {
				$questionData['has_explanation'] = $hasExplanation;

				if ( $checked ) {
					$questionData['explanation'] = $theExplanation;
				}
			}

			$with_true_or_false = ( $checked || ( $quizStatus == 'completed' && $args['show_correct_review'] ) );

			// if ( $question->is_support( 'answer-options' ) ) {
			$questionData['options'] = learn_press_get_question_options_for_js(
				$question,
				array(
					'include_is_true' => $with_true_or_false,
					'answer'          => $answered[ $id ]['answered'] ?? '',
				)
			);
			// }

			$questions[] = $questionData;
		}

		/**
		 * Remove answered
		 */
		if ( $quizStatus !== 'completed' ) {
			if ( $checkedQuestions && $quizStatus ) {

				$omitIds = array_diff( $question_ids, $checkedQuestions );

				if ( $omitIds ) {
					foreach ( $omitIds as $omitId ) {
						if ( ! empty( $answered[ $omitId ] ) ) {
							unset( $answered[ $omitId ] );
						}
					}
				}
			}
		}
	}

	return apply_filters( 'learn-press/list-questions-data', $questions );
}

/**
 * Update extra profile data upon update user.
 *
 * @param int $user_id
 *
 * @since 4.0.0
 */
function learn_press_update_extra_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if ( isset( $_POST['_lp_extra_info'] ) ) {
		update_user_meta( $user_id, '_lp_extra_info', LP_Helper::sanitize_params_submitted( $_POST['_lp_extra_info'] ) );
	}
}
add_action( 'personal_options_update', 'learn_press_update_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'learn_press_update_extra_user_profile_fields' );

/**
 * Get extra profile info data
 *
 * @param int $user_id
 *
 * @return array
 * @since 4.0.0
 */
function learn_press_get_user_extra_profile_info( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$extra_profile_info = get_the_author_meta( '_lp_extra_info', $user_id );
	$extra_fields       = learn_press_get_user_extra_profile_fields();

	$extra_profile_info = wp_parse_args(
		$extra_profile_info,
		array_fill_keys( array_keys( $extra_fields ), '' )
	);

	return apply_filters( 'learn-press/user-extra-profile-info', $extra_profile_info, $user_id );
}

function learn_press_social_profiles() {
	return apply_filters(
		'learn-press/social-profiles',
		array(
			'facebook',
			'twitter',
			'youtube',
			'linkedin',
		)
	);
}

function lp_add_default_fields( $fields ) {
	$first_name = LP_Settings::get_option( 'enable_register_first_name', 'no' );

	if ( $first_name === 'yes' ) {
		?>
		<li class="form-field">
			<label for="reg_first_name"><?php esc_html_e( 'First name', 'learnpress' ); ?></label>
			<input id="reg_first_name" name="reg_first_name" type="text"
				placeholder="<?php esc_attr_e( 'First name', 'learnpress' ); ?>"
				value="<?php echo esc_attr( wp_unslash( $_POST['reg_first_name'] ?? '' ) ); ?>">
		</li>
		<?php
	}

	$last_name = LP_Settings::instance()->get( 'enable_register_last_name' );

	if ( $last_name === 'yes' ) {
		?>
		<li class="form-field">
			<label for="reg_last_name"><?php esc_html_e( 'Last name', 'learnpress' ); ?></label>
			<input id="reg_last_name" name="reg_last_name" type="text"
				placeholder="<?php esc_attr_e( 'Last name', 'learnpress' ); ?>"
				value="<?php echo esc_attr( wp_unslash( $_POST['reg_last_name'] ?? '' ) ); ?>">
		</li>
		<?php
	}

	$display_name = LP_Settings::instance()->get( 'enable_register_display_name' );

	if ( $display_name === 'yes' ) {
		?>
		<li class="form-field">
			<label for="reg_display_name"><?php esc_html_e( 'Display name', 'learnpress' ); ?></label>
			<input id="reg_display_name" name="reg_display_name" type="text"
				placeholder="<?php esc_attr_e( 'Display name', 'learnpress' ); ?>"
				value="<?php echo esc_attr( wp_unslash( $_POST['reg_display_name'] ?? '' ) ); ?>">
		</li>
		<?php
	}
}

add_filter( 'learn-press/after-form-register-fields', 'lp_add_default_fields' );

function lp_custom_register_fields_display() {
	?>
	<?php $custom_fields = LP_Settings::instance()->get( 'register_profile_fields' ); ?>

	<?php if ( $custom_fields ) : ?>
		<?php foreach ( $custom_fields as $custom_field ) : ?>
			<?php
			$cf_class = '';
			if ( $custom_field['required'] == 'yes' ) {
				$cf_class = ' required';
				?>
				<style>
					.required label {
						font-weight: bold;
					}
					.required label:after {
						content: ' *';
						display:inline;
					}
				</style>
				<?php
			}

			if ( isset( $custom_field['id'] ) ) {
				?>
				<?php $value = $custom_field['id']; ?>

				<li class="form-field<?php echo esc_attr( $cf_class ); ?>">
					<?php
					switch ( $custom_field['type'] ) {
						case 'text':
						case 'number':
						case 'email':
						case 'url':
						case 'tel':
							?>
							<label for="description"><?php echo esc_html( $custom_field['name'] ); ?></label>
							<input name="_lp_custom_register_form[<?php echo esc_attr( $value ); ?>]"
								type="<?php echo esc_attr( $custom_field['type'] ); ?>" class="regular-text"
								value="" />
							<?php
							break;
						case 'textarea':
							?>
							<label for="description"><?php echo esc_html( $custom_field['name'] ); ?></label>
							<textarea name="_lp_custom_register_form[<?php echo esc_attr( $value ); ?>]"></textarea>
							<?php
							break;
						case 'checkbox':
							?>
							<label>
								<input name="_lp_custom_register_form[<?php echo esc_attr( $value ); ?>]"
									type="<?php echo esc_attr( $custom_field['type'] ); ?>" value="1">
								<?php echo esc_html( $custom_field['name'] ); ?>
							</label>
							<?php
							break;
					}
					?>
				</li>
			<?php } ?>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php
}

add_action( 'learn-press/after-form-register-fields', 'lp_custom_register_fields_display' );

/**
 * Custom register fields
 *
 * @param [type] $user_id
 *
 * @return void
 */
function lp_user_custom_register_fields( $user_id, $fields = array() ) {
	if ( ! empty( $fields ) ) {
		update_user_meta( $user_id, '_lp_custom_register', LP_Helper::sanitize_params_submitted( $fields ) );
	} elseif ( isset( $_POST['_lp_custom_register'] ) ) {
		update_user_meta( $user_id, '_lp_custom_register', LP_Helper::sanitize_params_submitted( $_POST['_lp_custom_register'] ) );
	}
}

add_action( 'personal_options_update', 'lp_user_custom_register_fields' );
add_action( 'edit_user_profile_update', 'lp_user_custom_register_fields' );

function lp_get_user_custom_register_fields( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$register_fields = get_the_author_meta( '_lp_custom_register', $user_id );
	$defaults        = lp_get_user_custom_fields();

	$extra_profile_info = wp_parse_args( $register_fields, $defaults );

	return apply_filters( 'lp/user-custom-register-fields', $register_fields, $user_id );
}

function lp_get_user_custom_fields() {
	$custom_fields = LP_Settings::instance()->get( 'register_profile_fields' );

	$output = array();

	if ( $custom_fields ) {
		foreach ( $custom_fields as $field ) {
			$output[ $field['id'] ] = '';
		}
	}

	return $output;
}

/**
 * Check extra user data is a social profile.
 *
 * @param $key
 *
 * @return bool
 * @since 4.0.0
 */
function learn_press_is_social_profile( $key ) {
	$is_socials = learn_press_social_profiles();

	return in_array( $key, $is_socials );
}

function learn_press_social_profile_name( $key ) {
	$name = '';
	switch ( $key ) {
		case 'facebook':
			$name = esc_html__( 'Facebook Profile', 'learnpress' );
			break;
		case 'twitter':
			$name = esc_html__( 'Twitter Profile', 'learnpress' );
			break;
		case 'googleplus':
			$name = esc_html__( 'Google Profile', 'learnpress' );
			break;
		case 'youtube':
			$name = esc_html__( 'Youtube Channel', 'learnpress' );
			break;
		case 'linkedin':
			$name = esc_html__( 'Linkedin Profile', 'learnpress' );
			break;
		default:
			$name = ucfirst( $key );
	}

	return apply_filters( 'learn-press/social-profile-name', $name, $key );
}

/**
 * Get extra profile fields will be registered in backend profile.
 *
 * @return array
 * @since 4.0.0
 */
function learn_press_get_user_extra_profile_fields() {
	$socials = learn_press_social_profiles();
	$fields  = array();

	foreach ( $socials as $social ) {
		$fields[ $social ] = learn_press_social_profile_name( $social );
	}

	return apply_filters( 'learn-press/user-extra-profile-fields', $fields );
}

/**
 * Show courses user enrolled on backend
 *
 * @param $user
 *
 * @return void
 */
function learn_press_user_profile_data( $user ) {
	if ( ! is_admin() ) {
		return;
	}

	learn_press_admin_view( 'backend-user-profile', array( 'user' => $user ) );
	learn_press_admin_view( 'user/courses.php', array( 'user_id' => $user->ID ) );
}
add_action( 'edit_user_profile', 'learn_press_user_profile_data', 1000 );

function learnpress_get_count_by_user( $user_id = '', $post_type = 'lp_course' ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	$args = array(
		'author'         => $user_id,
		'posts_per_page' => - 1,
		'post_type'      => $post_type,
		'post_status'    => 'any',
	);

	$posts = get_posts( $args );

	$output = array(
		'all'     => count( $posts ),
		'publish' => array(),
		'pending' => array(),
	);

	$pending = $public = array();

	if ( ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			switch ( $post->post_status ) {
				case 'pending':
					$pending[] = $post;
					break;
				case 'publish':
					$public[] = $post;
					break;
				default:
					break;
			}
		}
	}

	return array(
		'all'     => count( $posts ),
		'publish' => count( $public ),
		'pending' => count( $pending ),
	);

}
