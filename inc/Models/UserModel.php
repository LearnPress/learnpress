<?php

namespace LearnPress\Models;

/**
 * Class UserModel
 *
 * @version 1.0.2
 * @since 4.2.6.9
 */

use Exception;
use LearnPress\Filters\FilterBase;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Cache;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Database;
use LP_Debug;
use LP_Profile;
use LP_User;
use LP_User_DB;
use LP_User_Filter;

use LP_User_Items_DB;
use LP_User_Items_Filter;
use LP_WP_Filesystem;
use stdClass;
use Throwable;
use WP_Error;

class UserModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $ID = 0;
	/**
	 * @var string author id, foreign key
	 */
	public $user_login = 0;
	/**
	 * @var LP_User author model
	 */
	public $user_nicename;
	/**
	 * @var string post date
	 */
	public $user_email = null;
	/**
	 * @var string post date gmt
	 */
	public $user_url = null;
	/**
	 * @var string post content
	 */
	public $user_register = '';
	/**
	 * Item type (course, lesson, quiz ...)
	 *
	 * @var string Item type
	 */
	public $display_name = '';
	/**
	 * @var stdClass all meta data
	 */
	public $meta_data = null;
	/**
	 * @var string image url
	 */
	public $image_url = '';

	// Meta keys
	const META_KEY_IMAGE       = '_lp_profile_picture';
	const META_KEY_COVER_IMAGE = '_lp_profile_cover_image';

	// Roles
	const ROLE_INSTRUCTOR    = LP_TEACHER_ROLE;
	const ROLE_ADMINISTRATOR = 'administrator';

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}

		if ( is_null( $this->meta_data ) ) {
			$this->meta_data = new stdClass();
		}
	}

	/**
	 * Map array, object data to UserItemModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return UserModel
	 */
	public function map_to_object( $data ): UserModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get course by ID
	 *
	 * @param int $user_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $user_id, bool $check_cache = false ) {
		$filter_user     = new LP_User_Filter();
		$filter_user->ID = $user_id;
		$key_cache       = "userModel/find/id/{$user_id}";
		$lp_course_cache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$user_model = $lp_course_cache->get_cache( $key_cache );
			if ( $user_model instanceof UserModel ) {
				return $user_model;
			}
		}

		// Query database no cache.
		$user_model = self::get_user_model_from_db( $filter_user );

		// Set cache
		if ( $user_model instanceof UserModel ) {
			$lp_course_cache->set_cache( $key_cache, $user_model );
		}

		return $user_model;
	}

	/**
	 * Get course from database.
	 * If not exists, return false.
	 * If exists, return CoursePostModel.
	 *
	 * @param LP_User_Filter $filter
	 *
	 * @return UserModel|false|static
	 * @since 4.2.6.9
	 * @version 1.0.1
	 */
	public static function get_user_model_from_db( LP_User_Filter $filter ) {
		$lp_user_db = LP_User_DB::instance();
		$user_model = false;

		try {
			$filter->only_fields = [ 'ID', 'user_nicename', 'user_login', 'user_email', 'display_name' ];
			$lp_user_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_db->get_users( $filter );
			$user_rs          = $lp_user_db->wpdb->get_row( $query_single_row );
			if ( $user_rs instanceof stdClass ) {
				$user_model = new UserModel( $user_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_model;
	}

	/**
	 * Get all meta_data, all keys of a user it
	 *
	 * @return stdClass|null
	 * @throws Exception
	 */
	public function get_all_metadata() {
	}

	/**
	 * Get meta value by key.
	 *
	 * @param string $key
	 * @param mixed $default_value
	 *
	 * @return false|mixed
	 */
	public function get_meta_value_by_key( string $key, $default_value = false ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$value = get_user_meta( $this->ID, $key, true );
		if ( empty( $value ) ) {
			$value = $default_value;
		}

		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Set meta value by key.
	 *
	 * @param string $key
	 * @param $value
	 *
	 * @return void
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function set_meta_value_by_key( string $key, $value ) {
		$this->meta_data->{$key} = $value;
		update_user_meta( $this->ID, $key, $value );
	}

	/**
	 * Get upload profile src.
	 *
	 * @return string
	 */
	public function get_image_url(): string {
		if ( ! empty( $this->image_url ) ) {
			return $this->image_url;
		}

		$profile_picture = $this->get_meta_value_by_key( self::META_KEY_IMAGE, '' );
		if ( ! empty( $profile_picture ) ) {
			// Check if hase slug / at the beginning of the path, if not add it.
			$slash           = substr( $profile_picture, 0, 1 ) === '/' ? '' : '/';
			$profile_picture = $slash . $profile_picture;
			// End check.
			$upload    = learn_press_user_profile_picture_upload_dir();
			$file_path = $upload['basedir'] . $profile_picture;

			if ( file_exists( $file_path ) ) {
				$this->image_url = $upload['baseurl'] . $profile_picture;
			} else { // For remote url.
				$this->image_url = $profile_picture;
			}
		}

		return $this->image_url;
	}

	/**
	 * Get upload cover image src.
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function get_cover_image_url(): string {
		$cover_image = $this->get_meta_value_by_key( self::META_KEY_COVER_IMAGE, '' );
		if ( ! empty( $cover_image ) ) {
			// Check if hase slug / at the beginning of the path, if not add it.
			$slash       = substr( $cover_image, 0, 1 ) === '/' ? '' : '/';
			$cover_image = $slash . $cover_image;
			// End check.
			$upload    = learn_press_user_profile_picture_upload_dir();
			$file_path = $upload['basedir'] . $cover_image;

			if ( file_exists( $file_path ) ) {
				return $upload['baseurl'] . $cover_image;
			} else { // For remote url.
				return $cover_image;
			}
		}

		return '';
	}

	/**
	 * Set cover image url.
	 *
	 * @param string $url
	 *
	 * @return void
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function set_cover_image_url( string $url ) {
		$this->set_meta_value_by_key( self::META_KEY_COVER_IMAGE, $url );
	}

	/**
	 * Delete cover image.
	 *
	 * @return void
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function delete_cover_image() {
		$upload_dir = learn_press_user_profile_picture_upload_dir();

		// Delete old image if exists
		$image_path = $this->get_meta_value_by_key( UserModel::META_KEY_COVER_IMAGE, '' );
		if ( $image_path ) {
			$path = $upload_dir['basedir'] . '/' . $image_path;

			if ( file_exists( $path ) ) {
				LP_WP_Filesystem::instance()->unlink( $path );
			}
		}

		// Save empty string to database.
		$this->set_cover_image_url( '' );
	}

	/**
	 * Get display name
	 *
	 * Hook from function get_the_author_meta of WP
	 *
	 * @return string
	 * @uses get_the_author_meta
	 * @version 1.0.1
	 * @since 4.2.7
	 */
	public function get_display_name(): string {
		return apply_filters(
			'get_the_author_display_name',
			$this->display_name,
			$this->get_id(),
			$this->get_id()
		);
	}

	/**
	 * Get url instructor.
	 *
	 * @move from LP_User
	 * @return string
	 * @version 1.0.1
	 * @since 4.2.3
	 */
	public function get_url_instructor(): string {
		$single_instructor_link = '';

		try {
			$user_name                 = $this->user_nicename ?? '';
			$single_instructor_page_id = learn_press_get_page_id( 'single_instructor' );
			if ( ! $single_instructor_page_id ) {
				return $single_instructor_link;
			}

			$single_instructor_link = trailingslashit(
				trailingslashit( get_page_link( $single_instructor_page_id ) ) . $user_name
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $single_instructor_link;
	}

	/**
	 * Get profile avatar url
	 * 1. Get upload avatar src
	 * 2. If not exists, get form Gravatar
	 * 3. If not exists, get default image
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.1
	 */
	public function get_avatar_url(): string {
		$avatar_url = $this->get_upload_avatar_src();
		if ( empty( $avatar_url ) ) {
			// Get form Gravatar.
			$args       = learn_press_get_avatar_thumb_size();
			$args       = apply_filters( 'learn-press/gravatar/args', $args );
			$avatar_url = get_avatar_url( $this->get_id(), $args );
			// If not exists, get default avatar.
			if ( empty( $avatar_url ) ) {
				$avatar_url = LP_PLUGIN_URL . 'assets/images/avatar-default.png';
			}
		}

		return $avatar_url;
	}

	/**
	 * Get upload avatar src.
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 * @move from get_upload_profile_src method on LP_Profile class
	 */
	public function get_upload_avatar_src(): string {
		$uploaded_avatar_src = '';
		$profile_picture     = $this->get_meta_value_by_key( self::META_KEY_IMAGE, '' );

		if ( $profile_picture ) {
			// Check if hase slug / at the beginning of the path, if not, add it.
			$slash           = substr( $profile_picture, 0, 1 ) === '/' ? '' : '/';
			$profile_picture = $slash . $profile_picture;
			// End check.
			$upload    = learn_press_user_profile_picture_upload_dir();
			$file_path = $upload['basedir'] . $profile_picture;

			if ( file_exists( $file_path ) ) {
				$uploaded_avatar_src = $upload['baseurl'] . $profile_picture;
			}
		}

		return apply_filters( 'learn-press/user/upload-avatar-src', $uploaded_avatar_src, $this );
	}

	/**
	 * Get links socials of use on Profile page
	 * Icon is svg
	 *
	 * @move from LP_Abstract_User
	 * @return array
	 * @since 4.2.3
	 * @version 1.0.2
	 */
	public function get_profile_social(): array {
		$socials    = array();
		$extra_info = learn_press_get_user_extra_profile_info( $this->get_id() );

		if ( $extra_info ) {
			foreach ( $extra_info as $k => $v ) {
				if ( empty( $v ) ) {
					continue;
				}

				switch ( $k ) {
					case 'facebook':
						$i = '<i class="lp-user-ico lp-icon-facebook"></i>';
						break;
					case 'twitter':
						$i = '<i class="lp-user-ico lp-icon-twitter"></i>';
						break;
					case 'linkedin':
						$i = '<i class="lp-user-ico lp-icon-linkedin"></i>';
						break;
					case 'youtube':
						$i = '<i class="lp-user-ico lp-icon-youtube-play"></i>';
						break;
					default:
						$i = sprintf(
							'<i class="lp-user-ico lp-icon-%s"></i>',
							esc_attr( $k )
						);
				}

				$icon          = apply_filters(
					'learn-press/user-profile-social-icon',
					$i,
					$k,
					$this->get_id(),
					$this
				);
				$socials[ $k ] = sprintf(
					'<a href="%s">%s</a>',
					esc_url_raw( $v ),
					$icon
				);
			}
		}

		return apply_filters( 'learn-press/user-profile-socials', $socials, $this->get_id(), $this );
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return UserModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save(): UserModel {
		// Clear caches.
		$this->clean_caches();

		return $this;
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Clear cache.
		$key_cache       = "userModel/find/id/{$this->get_id()}";
		$lp_course_cache = new LP_Cache();
		$lp_course_cache->clear( $key_cache );
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return (int) $this->ID;
	}

	/**
	 * Get description of user.
	 *
	 * @return string
	 * @since 4.2.6.9
	 * @version 1.0.1
	 */
	public function get_description(): string {
		return get_the_author_meta( 'description', $this->get_id() );
	}

	/**
	 * Get email of user.
	 *
	 * @return string
	 * @since 4.2.7.4
	 * @version 1.0.0
	 */
	public function get_email(): string {
		return $this->user_email ?? '';
	}

	/**
	 * Get username of user.
	 *
	 * @return string
	 * @since 4.2.7.4
	 * @version 1.0.0
	 */
	public function get_username(): string {
		return $this->user_login ?? '';
	}

	/**
	 * Get statistic info of instructor user
	 *
	 * @param array $params
	 *
	 * @return array
	 * @since 4.1.6
	 * @version 1.0.6
	 */
	public function get_instructor_statistic( array $params = [] ): array {
		$statistic = array(
			'total_course'        => 0,
			'published_course'    => 0,
			'pending_course'      => 0,
			'total_student'       => 0,
			'student_completed'   => 0,
			'student_in_progress' => 0,
		);

		try {
			$key_cache_first = "instructor/{$this->get_id()}/statistic";
			$statistic_cache = LP_Cache::cache_load_first( 'get', $key_cache_first );
			if ( $statistic_cache !== false ) {
				return $statistic_cache;
			}

			$user_id          = $this->get_id();
			$lp_user_items_db = LP_User_Items_DB::getInstance();
			$lp_course_db     = LP_Course_DB::getInstance();

			// Count total user completed course of author
			$filter_course                      = new LP_Course_Filter();
			$filter_course->only_fields         = array( 'ID' );
			$filter_course->post_author         = $user_id;
			$filter_course->post_status         = [ 'publish', 'private' ];
			$filter_course->return_string_query = true;
			$query_courses_str                  = LP_Course_DB::getInstance()->get_courses( $filter_course );

			$filter_count_users            = new LP_User_Items_Filter();
			$filter_count_users->item_type = LP_COURSE_CPT;
			$filter_count_users->where[]   = "AND item_id IN ({$query_courses_str})";
			$count_student_has_status      = $lp_user_items_db->count_status_by_items( $filter_count_users );
			// Count total user in progress course of author

			// Get total users attend course of author
			$filter_count_users                   = $lp_user_items_db->count_user_attend_courses_of_author( $user_id );
			$count_users_attend_courses_of_author = $lp_user_items_db->get_user_courses( $filter_count_users );

			// Get total courses publish of author
			$filter_count_courses            = $lp_course_db->count_courses_of_author( $user_id, [ 'publish' ] );
			$total_courses_publish_of_author = $lp_course_db->get_courses( $filter_count_courses );

			// Get total courses of author
			$filter_count_courses    = $lp_course_db->count_courses_of_author( $user_id );
			$total_courses_of_author = $lp_course_db->get_courses( $filter_count_courses );

			// Get total courses pending of author
			$filter_count_courses            = $lp_course_db->count_courses_of_author( $user_id, [ 'pending' ] );
			$total_courses_pending_of_author = $lp_course_db->get_courses( $filter_count_courses );

			$statistic['total_course']        = $total_courses_of_author;
			$statistic['published_course']    = $total_courses_publish_of_author;
			$statistic['pending_course']      = $total_courses_pending_of_author;
			$statistic['total_student']       = $count_users_attend_courses_of_author;
			$statistic['student_completed']   = $count_student_has_status->{LP_COURSE_FINISHED} ?? 0;
			$statistic['student_in_progress'] = $count_student_has_status->{LP_COURSE_GRADUATION_IN_PROGRESS} ?? 0;

			$statistic = apply_filters( 'lp/profile/instructor/statistic', $statistic, $this );

			// Set cache first.
			LP_Cache::cache_load_first( 'set', $key_cache_first, $statistic );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return $statistic;
	}

	/**
	 * Get statistic info of student user
	 *
	 * @return array
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function get_student_statistic(): array {
		$statistic = array(
			'enrolled_courses'   => 0,
			'in_progress_course' => 0,
			'finished_courses'   => 0,
			'passed_courses'     => 0,
			'failed_courses'     => 0,
		);

		try {
			$user_id          = $this->get_id();
			$lp_user_items_db = LP_User_Items_DB::getInstance();

			// Count status
			$filter                 = new LP_User_Items_Filter();
			$filter->user_id        = $user_id;
			$count_status           = $lp_user_items_db->count_status_by_items( $filter );
			$total_courses_enrolled = intval( $count_status->{LP_COURSE_PURCHASED} ?? 0 )
				+ intval( $count_status->{LP_COURSE_ENROLLED} ?? 0 )
				+ intval( $count_status->{LP_COURSE_FINISHED} ?? 0 );

			$statistic['enrolled_courses']   = $total_courses_enrolled;
			$statistic['in_progress_course'] = $count_status->{LP_COURSE_GRADUATION_IN_PROGRESS} ?? 0;
			$statistic['finished_courses']   = $count_status->{LP_COURSE_FINISHED} ?? 0;
			$statistic['passed_courses']     = $count_status->{LP_COURSE_GRADUATION_PASSED} ?? 0;
			$statistic['failed_courses']     = $count_status->{LP_COURSE_GRADUATION_FAILED} ?? 0;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return apply_filters( 'lp/profile/student/statistic', $statistic, $this );
	}

	/**
	 * Check user is instructor or not.
	 *
	 * @return bool
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function is_instructor(): bool {
		return user_can( $this->get_id(), self::ROLE_INSTRUCTOR )
			|| user_can( $this->get_id(), self::ROLE_ADMINISTRATOR );
	}

	/**
	 * Get quizzes attend of user.
	 *
	 * @param LP_User_Items_Filter|UserItemModel $filter
	 * @param int $total_rows
	 *
	 * @return array|int|string|null
	 * @throws Exception
	 * @since 4.2.8.2
	 * @version 1.0.0
	 */
	public function get_quizzes_attend( $filter, int &$total_rows = 0 ) {
		$lp_db_user_items  = LP_User_Items_DB::getInstance();
		$filter->order_by  = 'user_item_id';
		$filter->order     = 'DESC';
		$filter->user_id   = $this->get_id();
		$filter->item_type = LP_QUIZ_CPT;
		$filter->ref_type  = LP_COURSE_CPT;

		return $lp_db_user_items->get_user_items( $filter, $total_rows );
	}
}
