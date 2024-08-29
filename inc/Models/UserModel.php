<?php

/**
 * Class UserModel
 *
 * @version 1.0.0
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Profile;
use LP_User;
use LP_User_DB;
use LP_User_Filter;

use LP_User_Items_DB;
use LP_User_Items_Filter;
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

	const META_KEY_IMAGE = '_lp_profile_picture';

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
	 * @param int $course_id
	 * @param bool $no_cache
	 *
	 * @return false|static
	 */
	public static function find( int $user_id, bool $check_cache = false ) {
		$filter_user     = new LP_User_Filter();
		$filter_user->ID = $user_id;
		$key_cache       = "user-model/find/id/{$user_id}";
		$lp_course_cache = new \LP_Cache();

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
	 * @param bool $no_cache
	 *
	 * @return UserModel|false|static
	 */
	public static function get_user_model_from_db( LP_User_Filter $filter, bool $check_cache = false ) {
		$lp_user_db = LP_User_DB::instance();
		$user_model = false;

		try {
			$filter->only_fields = [ 'ID', 'user_nicename', 'user_email', 'display_name' ];
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
	 * @param mixed $default
	 *
	 * @return false|mixed
	 */
	public function get_meta_value_by_key( string $key, $default = false ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$value = get_user_meta( $this->ID, $key, true );
		if ( empty( $value ) ) {
			$value = $default;
		}

		$this->meta_data->{$key} = $value;

		return $value;
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
			}
		}

		return $this->image_url;
	}

	/**
	 * Get display name
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->display_name ?? '';
	}

	public function get_profile_link(): string {
		return '';
	}

	/**
	 * Get url instructor.
	 *
	 * @move from LP_User
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function get_url_instructor(): string {
		$single_instructor_link = '';

		try {
			$user_name                 = $this->user_nicename ?? '';
			$single_instructor_page_id = learn_press_get_page_id( 'single_instructor' );
			$single_instructor_link    = trailingslashit(
				trailingslashit( get_page_link( $single_instructor_page_id ) ) . $user_name
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $single_instructor_link;
	}

	/**
	 * Get profile picture
	 *
	 * @param string $type
	 * @param int $size
	 * @param bool $src_only
	 *
	 * @move from LP_Abstract_User
	 * @return string
	 */
	public function get_profile_picture( $type = '', $size = 96, $src_only = false ) {
		return LP_Profile::instance( $this->get_id() )->get_profile_picture( $type, $size );
	}

	/**
	 * Get links socials of use on Profile page
	 * Icon is svg
	 *
	 * @param int $user_id
	 *
	 * @move from LP_Abstract_User
	 * @return array
	 * @since 4.2.3
	 * @version 1.0.0
	 */
	public function get_profile_social( int $user_id = 0 ): array {
		$socials    = array();
		$extra_info = learn_press_get_user_extra_profile_info( $user_id );

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
						$i = sprintf( '<i class="lp-user-ico lp-icon-%s"></i>', $k );
				}

				$icon          = apply_filters(
					'learn-press/user-profile-social-icon',
					$i,
					$k,
					$this->get_id(),
					$this
				);
				$socials[ $k ] = sprintf( '<a href="%s">%s</a>', esc_url_raw( $v ), $icon );
			}
		}

		return apply_filters( 'learn-press/user-profile-socials', $socials, $this->get_id(), $this );
	}

	/**
	 * Check user can enroll course
	 *
	 * @param CourseModel $course
	 *
	 * @return mixed|object|bool
	 */
	public function can_enroll_course( CourseModel $course ) {

	}

	/**
	 * Check user can purchase course
	 *
	 * @param int $course_id
	 *
	 * @return bool|WP_Error
	 * @author nhamdv
	 * @editor tungnx
	 * @since 4.0.8
	 * @version 1.0.5
	 */
	public function can_purchase_course( int $course_id = 0 ) {

	}

	/**
	 * Check user can retake course.
	 *
	 * @param CourseModel $course
	 *
	 * @return int
	 * @since 4.0.0
	 * @author tungnx
	 */
	public function can_retake_course( CourseModel $course ) {

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
	 */
	public function get_description(): string {
		return wpautop( $this->get_meta_value_by_key( 'description', '' ) );
	}

	/**
	 * Get statistic info of instructor user
	 *
	 * @param array $params
	 *
	 * @return array
	 * @since 4.1.6
	 * @version 1.0.1
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
			$user_id          = $this->get_id();
			$lp_user_items_db = LP_User_Items_DB::getInstance();
			$lp_course_db     = LP_Course_DB::getInstance();

			// Count total user completed course of author
			$filter_course                      = new LP_Course_Filter();
			$filter_course->only_fields         = array( 'ID' );
			$filter_course->post_author         = $user_id;
			$filter_course->post_status         = 'publish';
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
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return apply_filters( 'lp/profile/instructor/statistic', $statistic, $this );
	}
}
