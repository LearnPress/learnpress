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
	$user_item_ids = LP_Cache::get_user_item_id( false, array() );
	if ( empty( $user_item_ids[ $user_id . '-' . $item_id ] ) ) {
		global $wpdb;
		$query                                      = $wpdb->prepare( "SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items WHERE user_id = %d AND item_id = %d ORDER BY user_item_id DESC LIMIT 0,1", $user_id, $item_id );
		$user_item_ids[ $user_id . '-' . $item_id ] = $wpdb->get_var( $query );
	}
	LP_Cache::set_user_item_id( $user_item_ids );

	return $user_item_ids[ $user_id . '-' . $item_id ];
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
	return LP_User_Factory::get_user( $user_id ? $user_id : get_current_user_id() );
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
	return LP_User_Factory::get_user( $user_id, $force );
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
		if ( $tabs = learn_press_user_profile_tabs() ) {
			$keys      = array_keys( $tabs );
			$first_tab = reset( $keys );
		} else {
			$first_tab = '';
		}
		$text                             = LP()->settings->get( 'admin_bar_link_text' );
		$user_id                          = learn_press_get_current_user_id();
		$course_profile                   = array();
		$course_profile['id']             = 'course_profile';
		$course_profile['parent']         = 'user-actions';
		$course_profile['title']          = $text ? $text : get_the_title( $profile );
		$course_profile['href']           = learn_press_user_profile_link( $user_id, $first_tab );
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
	if ( $user->user_login != $current_user->user_login && $section == LP()->settings->get( 'profile_endpoints.profile-orders', 'profile-orders' ) ) {
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

function learn_press_get_profile_user() {
	global $wp_query;
	if ( isset( $wp_query->query['user'] ) ) {
		$user = get_user_by( 'login', urldecode( $wp_query->query['user'] ) );
	} else {
		$user = get_user_by( 'id', get_current_user_id() );
	}

	return $user;
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
	if ( ! isset( $_POST['become_teacher'] ) ) {
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
	$table_fields = array(
		'user_id'    => '%d',
		'item_id'    => '%d',
		'ref_id'     => '%d',
		'start_time' => '%s',
		'end_time'   => '%s',
		'item_type'  => '%s',
		'status'     => '%s',
		'ref_type'   => '%s',
		'parent_id'  => '%d'
	);

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
	$table_fields = array(
		'user_id'    => '%d',
		'item_id'    => '%d',
		'ref_id'     => '%d',
		'start_time' => '%s',
		'end_time'   => '%s',
		'item_type'  => '%s',
		'status'     => '%s',
		'ref_type'   => '%s',
		'parent_id'  => '%d'
	);

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

				$_message = $uploaded ? __( 'Image is uploaded success', 'learnpress' ) : __( 'Error on upload image', 'learnpress' );
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
					$_message               = __( 'Error on crop user picture profile ', 'learnpress' );
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
										update_user_option( $user->id, '_lp_profile_picture_thumbnail_url', $lp_profile_url . $avatar_thumbnail_filename, true );
									}
								}
							}
						}
					}
					#
					# Create Thumbnai for Profile Picture
					# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					update_user_option( $user->id, '_lp_profile_picture_type', 'picture', true );
					update_user_option( $user->id, '_lp_profile_picture', $avatar_filename, true );
					update_user_option( $user->id, '_lp_profile_picture_url', $lp_profile_url . $avatar_filename, true );

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
		update_user_option( $user->id, '_lp_profile_picture_type', $profile_picture_type, true );
		$res = wp_update_user( $update_data );
		if ( $res ) {
			$_message               = __( 'Your change is saved', 'learnpress' );
			$message                = sprintf( $message_template, 'success', $_message );
			$return['return']       = true;
			$return['message']      = $message;
			$return['redirect_url'] = '';
			learn_press_send_json( $return );
			exit();
		} else {
			$_message               = __( 'Error on update your profile info', 'learnpress' );
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
	 * @param        $avatar
	 * @param string $id_or_email
	 * @param array  $size
	 *
	 * @return string|void
	 */
	function learn_press_pre_get_avatar_callback( $avatar, $id_or_email = '', $size ) {
		if ( ( isset( $size['gravatar'] ) && $size['gravatar'] ) || ( $size['default'] && $size['force_default'] ) ) {
			return $avatar;
		}
		$user_id = 0;
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
		if ( $profile_picture_src = $user->get_upload_profile_src() ) {// $user_profile_picture_url . $profile_picture;
			$lp           = LP();
			$lp_setting   = $lp->settings;
			$setting_size = $lp_setting->get( 'profile_picture_thumbnail_size' );
			$img_size     = '';
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
	$redirect_to = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
	$admin_url   = admin_url();
	$pos         = strpos( $redirect_to, $admin_url );
	if ( $pos === false ) {
		$page_id  = LP()->settings->get( 'logout_redirect_page_id' );
		$page_url = get_page_link( $page_id );
		if ( $page_id && $page_url ) {
			wp_redirect( $page_url );
			exit();
		}
	}
}

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

add_action( 'wp_logout', '_learn_press_redirect_logout_redirect' );

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
function learn_press_get_display_name_publicly( $user_info ) {
	$public_display                     = array();
	$public_display['display_nickname'] = $user_info->nickname;
	$public_display['display_username'] = $user_info->user_login;

	if ( ! empty( $user_info->first_name ) ) {
		$public_display['display_firstname'] = $user_info->first_name;
	}

	if ( ! empty( $user_info->last_name ) ) {
		$public_display['display_lastname'] = $user_info->last_name;
	}

	if ( ! empty( $user_info->first_name ) && ! empty( $user_info->last_name ) ) {
		$public_display['display_firstlast'] = $user_info->first_name . ' ' . $user_info->last_name;
		$public_display['display_lastfirst'] = $user_info->last_name . ' ' . $user_info->first_name;
	}

	if ( ! in_array( $user_info->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
	{
		$public_display = array( 'display_displayname' => $user_info->display_name ) + $public_display;
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
	learn_press_add_message( __( 'Your avatar updated', 'learnpress' ) );
	wp_redirect( learn_press_get_current_url() );
	exit;
}

add_action( 'learn_press_update_user_profile_avatar', 'learn_press_update_user_profile_avatar' );

/**
 * Update user basic information
 */
function learn_press_update_user_profile_basic_information() {
	$user_id     = learn_press_get_current_user_id();
	$update_data = array(
		'ID'           => $user_id,
		'first_name'   => filter_input( INPUT_POST, 'first_name', FILTER_SANITIZE_STRING ),
		'last_name'    => filter_input( INPUT_POST, 'last_name', FILTER_SANITIZE_STRING ),
		'display_name' => filter_input( INPUT_POST, 'display_name', FILTER_SANITIZE_STRING ),
		'nickname'     => filter_input( INPUT_POST, 'nickname', FILTER_SANITIZE_STRING ),
		'description'  => filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING ),
	);
	$res         = wp_update_user( $update_data );
	if ( $res ) {
		$message = __( 'Your change is saved', 'learnpress' );
	} else {
		$message = __( 'Error on update your profile info', 'learnpress' );
	}
	$current_url = learn_press_get_current_url();
	learn_press_add_message( $message );
	wp_redirect( $current_url );
	exit();
}

add_action( 'learn_press_update_user_profile_basic-information', 'learn_press_update_user_profile_basic_information' );

/**
 * Change password
 */
function learn_press_update_user_profile_change_password() {
	# check and update pass word
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
		$message = __( 'Old password incorrect!', 'learnpress' );
	} else {
		// check new pass
		$new_pass  = filter_input( INPUT_POST, 'pass1' );
		$new_pass2 = filter_input( INPUT_POST, 'pass2' );
		if ( $new_pass != $new_pass2 ) {
			$message = __( 'Confirmation password incorrect!', 'learnpress' );
		} else {
			$update_data = array(
				'user_pass' => $new_pass,
				'ID'        => learn_press_get_current_user_id()
			);
			if ( wp_update_user( $update_data ) ) {
				$message = __( 'Your password updated', 'learnpress' );
			} else {
				$message = __( 'Change your password failed', 'learnpress' );
			}
		}
	}
	learn_press_add_message( $message );
	wp_redirect( learn_press_get_current_url() );
	exit;
}

add_action( 'learn_press_update_user_profile_change-password', 'learn_press_update_user_profile_change_password' );

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

function learn_press_set_user_cookie_for_guest() {
	if ( learn_press_is_course() && ! is_admin() ) {
		$guest_key = 'wordpress_logged_in_' . md5( 'guest' );
		if ( is_user_logged_in() ) {
			if ( ! empty( $_COOKIE[ $guest_key ] ) ) {
				setcookie( 'wordpress_logged_in_' . md5( 'guest' ), md5( time() ), - 10000 );
			}
		} else {
			if ( empty( $_COOKIE[ $guest_key ] ) ) {
				setcookie( 'wordpress_logged_in_' . md5( 'guest' ), md5( time() ), time() + 3600 );
			}
		}
	}
}

add_action( 'wp', 'learn_press_set_user_cookie_for_guest' );