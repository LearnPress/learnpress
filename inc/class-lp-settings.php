<?php
/**
 * Class LP_Settings
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Settings {
	/**
	 * @var array
	 */
	protected $_options = array();

	/**
	 * @var string
	 */
	protected $_prefix = '';

	/**
	 * @var bool
	 */
	protected static $_instance = null;

	/**
	 * Constructor.
	 *
	 * @param array|mixed $data
	 * @param string      $prefix
	 */
	protected function __construct( $data = false, $prefix = 'learn_press_' ) {
		try {
			$this->_prefix = $prefix;

			if ( false === $data ) {
				$this->_load_options();
			} else {
				settype( $data, 'array' );
				$this->_options = $data;
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * @param string $group
	 * @param string $prefix
	 *
	 * @return LP_Settings
	 */
	public function get_group( $group, $prefix = '' ) {
		$options = ! empty( $this->_options[ $this->_prefix . $group ] ) ? $this->get( $this->_prefix . $group ) : array();

		return new LP_Settings( $options, $prefix );
	}

	/**
	 * Load all options.
	 *
	 * @throws Exception
	 * @version 1.0.2
	 * @since 3.0.0
	 */
	protected function _load_options() {
		// Check cache exists
		$lp_settings_cache = new LP_Settings_Cache( true );
		$lp_options        = $lp_settings_cache->get_lp_settings();
		if ( false !== $lp_options ) {
			$this->_options = LP_Helper::json_decode( $lp_options, true );
			return;
		}

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT option_name, option_value
			FROM {$wpdb->options}
			WHERE option_name LIKE %s",
			$wpdb->esc_like( $this->_prefix ) . '%'
		);

		$options = $wpdb->get_results( $query );
		if ( ! empty( $options ) ) {
			foreach ( $options as $option ) {
				$this->_options[ $option->option_name ] = LP_Helper::maybe_unserialize( $option->option_value );
			}

			// Set cache
			$lp_settings_cache
				->set_action_thim_cache( Thim_Cache_DB::ACTION_INSERT )
				->set_lp_settings( json_encode( $this->_options ) );
		}
	}

	/**
	 * Set new value for a key
	 *
	 * @param $name
	 * @param $value
	 */
	public function set( $name, $value ) {
		if ( $this->_prefix && strpos( $name, $this->_prefix ) === false ) {
			$name = $this->_prefix . $name;
		}
		$this->_set_option( $this->_options, $name, $value );
	}

	private function _set_option( &$obj, $var, $value ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				$obj->{$current_var} = LP_Helper::maybe_unserialize( $obj->{$current_var} );
				if ( count( $var ) ) {
					$this->_set_option( $obj->{$current_var}, join( '.', $var ), $value );
				} else {
					$obj->{$current_var} = $value;
				}
			} else {
				$obj->{$current_var} = $value;
			}
		} else {
			if ( isset( $obj[ $current_var ] ) ) {
				$obj[ $current_var ] = LP_Helper::maybe_unserialize( $obj[ $current_var ] );
				if ( count( $var ) ) {
					$this->_set_option( $obj[ $current_var ], join( '.', $var ), $value );
				} else {
					$obj[ $current_var ] = $value;
				}
			} else {
				$obj[ $current_var ] = $value;
			}
		}
	}

	/**
	 * Get option recurse separated by DOT
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get( string $var = '', $default = null ) {
		if ( empty( $var ) ) {
			return $this->_options;
		}

		if ( $this->_prefix && strpos( $var, $this->_prefix ) === false ) {
			$var = $this->_prefix . $var;
		}

		$return = $this->_get_option( $this->_options, $var, $default );

		if ( $return == '' || is_null( $return ) ) {
			$return = $default;
		}

		return $return;
	}

	/**
	 * @param      $obj
	 * @param      $var
	 * @param null $default
	 *
	 * @return null
	 */
	public function _get_option( $obj, $var, $default = null ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				$obj->{$current_var} = LP_Helper::maybe_unserialize( $obj->{$current_var} );
				if ( count( $var ) ) {
					return $this->_get_option( $obj->{$current_var}, join( '.', $var ), $default );
				} else {
					return $obj->{$current_var};
				}
			} else {
				return $default;
			}
		} else {
			if ( isset( $obj[ $current_var ] ) ) {
				$obj[ $current_var ] = LP_Helper::maybe_unserialize( $obj[ $current_var ] );
				if ( count( $var ) ) {
					return $this->_get_option( $obj[ $current_var ], join( '.', $var ), $default );
				} else {
					return $obj[ $current_var ];
				}
			} else {
				return $default;
			}
		}
	}

	public function update( $key, $value = '' ) {
		if ( func_num_args() == 1 ) {
			$value = $this->get( $key );
		}
		update_option( $this->_prefix . $key, $value );
		// $this->refresh();
	}

	/**
	 * Update option with default prefix is learn_press_
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @param string $prefix
	 */
	public static function update_option( $name, $value, $prefix = 'learn_press_' ) {
		update_option( "{$prefix}{$name}", $value );
		$lp_settings_cache = new LP_Settings_Cache( true );
		$lp_settings_cache->clean_lp_settings();
	}

	/**
	 * Get option with default prefix is learn_press_
	 *
	 * @param string $name
	 * @param mixed  $default
	 *
	 * @return mixed
	 * @since 3.2.8
	 * @editor tungnx
	 */
	public static function get_option( string $name = '', $default = false ) {
		$key     = "learn_press_{$name}";
		$options = self::instance()->_options;
		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return get_option( $key, $default );
	}

	public function get_int( $key ) {
		$value = $this->get( $key );

		return intval( $value );
	}

	/**
	 * @return bool|LP_Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get settings endpoints for checkout page.
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function get_checkout_endpoints() {
		$endpoints = LP_Object_Cache::get( 'checkout', 'learn-press-endpoints' );

		if ( false === $endpoints ) {
			$defaults = array(
				'lp-order-received' => 'lp-order-received',
			);

			$endpoints = array();
			$settings  = LP_Settings::instance()->get( 'checkout_endpoints' );

			if ( $settings ) {
				foreach ( $settings as $k => $v ) {
					$k               = preg_replace( '!_!', '-', $k );
					$endpoints[ $k ] = $v;
				}
			}

			foreach ( $defaults as $k => $v ) {
				if ( empty( $endpoints[ $k ] ) ) {
					$endpoints[ $k ] = $v;
				}
			}

			LP_Object_Cache::set( 'checkout', $endpoints, 'learn-press-endpoints' );
		}

		return apply_filters( 'learn-press/endpoints/checkout', $endpoints );
	}

	/**
	 * Get settings endpoints for profile page.
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function get_profile_endpoints() {
		$endpoints = LP_Object_Cache::get( 'profile', 'learn-press-endpoints' );

		if ( false === $endpoints ) {
			$defaults  = array();
			$endpoints = array();

			$settings = LP_Settings::instance()->get( 'profile_endpoints' );
			if ( $settings ) {
				foreach ( $settings as $k => $v ) {
					$k               = preg_replace( '!_!', '-', $k );
					$endpoints[ $k ] = $v;
				}
			}

			foreach ( $defaults as $k => $v ) {
				if ( empty( $endpoints[ $k ] ) ) {
					$endpoints[ $k ] = $v;
				}
			}

			LP_Object_Cache::set( 'profile', $endpoints, 'learn-press-endpoints' );
		}

		return apply_filters( 'learn-press/endpoints/profile', $endpoints );
	}

	/**
	 * Check setting enable option "Auto start"
	 *
	 * @return bool
	 */
	public static function is_auto_start_course(): bool {
		return 'yes' === self::get_option( 'auto_enroll', 'yes' );
	}

	/**
	 * Check table thim_cache is created
	 *
	 * @return bool
	 */
	public static function is_created_tb_thim_cache(): bool {
		return get_option( 'thim_cache_tb_created' ) === 'yes';
	}
	/**
	 * Check table learnpress_files is created
	 * @return boolean
	 */
	public static function is_created_tb_material_files(): bool {
		return get_option( 'table_learnpress_files_created' ) == 'yes';
	}
	public static function lp_material_file_types(): array {
		return array(
			'txt'      => 'text/plain',
			'doc,docx' => 'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt'      => 'application/vnd.oasis.opendocument.text',
			'rtf'      => 'application/rtf',
			'pdf'      => 'application/pdf',
			'jpg,jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'gif'      => 'image/gif',
			'bmp'      => 'image/bmp',
			// 'svg'       =>  'image/svg+xml',
			'mp3'      => 'audio/mpeg',
			'wav'      => 'audio/wav',
			'flac'     => 'audio/flac',
			'aac'      => 'audio/aac',
			'wma'      => 'audio/x-ms-wma',
			'mp4'      => 'video/mp4',
			'avi'      => 'video/avi',
			'mkv'      => 'video/x-matroska',
			'mov'      => 'video/quicktime',
			'wmv'      => 'video/x-ms-wmv',
			'xls,xlsx' => 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ods'      => 'application/vnd.oasis.opendocument.spreadsheet',
			'csv'      => 'text/csv',
			'numbers'  => 'application/vnd.apple.numbers',
			'tsv'      => 'text/tab-separated-values',
			'zip'      => 'application/zip,application/octet-stream,application/x-zip-compressed,multipart/x-zip',
		);
	}

	/**
	 * Check theme support load courses ajax
	 *
	 * @return bool
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public static function theme_no_support_load_courses_ajax(): bool {
		$theme_no_load_ajax = apply_filters(
			'lp/page/courses/themes/no_load_ajax',
			[
				'Coaching',
				'Course Builder',
				'eLearningWP',
				'Ivy School',
				'StarKid',
				'Academy LMS',
				'Coaching Child',
				'Course Builder Child',
				'eLearningWP Child',
				'Ivy School Child',
				'StarKid Child',
				'Academy LMS Child',
			]
		);
		$theme_current      = wp_get_theme()->get( 'Name' );

		return in_array( $theme_current, $theme_no_load_ajax );
	}

	/**
	 * Check theme support load courses ajax
	 *
	 * @since 4.2.3.3
	 * @version 1.0.0
	 * @return string
	 */
	public static function get_permalink_single_course(): string {
		$course_slug_default = 'courses';
		try {
			$course_slug = self::get_option( 'course_base', 'courses' );
			if ( empty( $course_slug ) ) {
				$course_slug = $course_slug_default;
			}
			$course_slug = preg_replace( '!^/!', '', $course_slug );
		} catch ( Throwable $e ) {
			$course_slug = $course_slug_default;
		}

		return $course_slug;
	}

	/**
	 * Check theme support load courses ajax
	 *
	 * @since 4.2.3.3
	 * @version 1.0.0
	 * @return array
	 */
	public static function get_course_items_slug(): array {
		/**
		 * Set rule item course.
		 *
		 * Use urldecode to convert an encoded string to normal.
		 * This fixed the issue with custom slug of lesson/quiz in some languages
		 * Eg: урока
		 */
		$lesson_slug = urldecode( sanitize_title_with_dashes( self::get_option( 'lesson_slug', 'lessons' ) ) );
		$quiz_slug   = urldecode( sanitize_title_with_dashes( self::get_option( 'quiz_slug', 'quizzes' ) ) );
		return apply_filters(
			'learn-press/course-item-slugs/for-rewrite-rules',
			array(
				LP_LESSON_CPT => $lesson_slug,
				LP_QUIZ_CPT   => $quiz_slug,
			)
		);
	}
}

LP_Settings::instance();
