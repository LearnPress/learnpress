<?php

/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Assets {
	/**
	 * Styles
	 *
	 * @var array
	 */
	protected static $styles = array();

	/**
	 * Scripts
	 *
	 * @var array
	 */
	protected static $scripts = array();

	/**
	 * Localize scripts
	 *
	 * @var array
	 */
	protected static $wp_localize_scripts = array();

	/**
	 * Params
	 *
	 * @var array
	 */
	protected static $wp_params = array();

	/**
	 * @var array
	 */
	protected static $wp_script_codes = array();

	/**
	 * Localized flag
	 *
	 * @var bool
	 */
	protected static $localized = false;

	/**
	 * @var int
	 */
	protected static $id = 0;

	/**
	 * @var array
	 */
	protected static $_enqueue_scripts = array();

	/**
	 * @var array
	 */
	protected static $_enqueue_styles = array();

	/**
	 * @var LP_Assets|null
	 */
	protected static $_instance = null;

	/**
	 * @var array
	 */
	public static $registered = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$_instance = $this;
		self::init();
	}

	/**
	 * Init Asset
	 */
	public static function init() {
		if ( !is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( self::$_instance, 'load_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( self::$_instance, 'wp_assets' ) );
			add_action( 'wp_print_scripts', array( self::$_instance, 'localize_printed_scripts' ), 5 );
			add_action( 'wp_print_footer_scripts', array( self::$_instance, 'localize_printed_scripts' ), 5 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ) );

		} else {
			add_action( 'admin_enqueue_scripts', array( self::$_instance, 'load_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( self::$_instance, 'wp_assets' ) );
			add_action( 'admin_print_scripts', array( self::$_instance, 'localize_printed_scripts' ), 5 );
			add_action( 'admin_print_footer_scripts', array( self::$_instance, 'localize_printed_scripts' ), 5 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ) );
		}
		add_action( 'wp_default_scripts', array( __CLASS__, 'default_scripts' ) );
		add_action( 'wp_default_styles', array( __CLASS__, 'default_styles' ) );
		add_filter( 'script_loader_src', array( $class = self::$_instance, 'script_params' ), 999, 2 );
	}

	/**
	 * Load default scripts
	 *
	 * @param $scripts
	 */
	public static function default_scripts( &$scripts ) {
		if ( !defined( 'LEARNPRESS_VERSION' ) ) {
			define( 'LEARNPRESS_VERSION', '1.1' );
		}

		$develop_src = false !== strpos( LEARNPRESS_VERSION, '-src' );

		if ( !defined( 'SCRIPT_DEBUG' ) ) {
			define( 'SCRIPT_DEBUG', $develop_src );
		}
		if ( !$guessurl = site_url() ) {
			$guessed_url = true;
			$guessurl    = wp_guess_url();
		}
		$default_path = '/wp-content/plugins/learnpress/assets/';

		$scripts->base_url        = $guessurl;
		$scripts->content_url     = defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : '';
		$scripts->default_version = get_bloginfo( 'version' );
		$scripts->default_dirs    = array( $default_path, '/wp-admin/js/', '/wp-includes/js/' );

		$suffix     = SCRIPT_DEBUG ? '' : '';
		$dev_suffix = $develop_src ? '' : '.min';
		$deps       = array( 'jquery', 'backbone', 'utils' );

		// global
		$scripts->add( 'learn-press-global', $default_path . 'js/global' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-jalerts', $default_path . 'js/jquery.alert' . $suffix . '.js', $deps, false, 1 );

		// frontend
		$scripts->add( 'learn-press-js', $default_path . 'js/frontend/learnpress' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-single-course', $default_path . 'js/frontend/single-course' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-course-quiz', $default_path . 'js/frontend/quiz' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-enroll', $default_path . 'js/frontend/enroll' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-add-to-cart', $default_path . 'js/frontend/add-to-cart' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-timer', $default_path . 'js/jquery.timer' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-checkout', $default_path . 'js/frontend/checkout' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-course-lesson', $default_path . 'js/frontend/course-lesson' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-become-teacher', $default_path . 'js/frontend/become-teacher' . $suffix . '.js', $deps, false, 1 );

		// admin
		$scripts->add( 'learn-press-admin', $default_path . 'js/admin/admin' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-admin-settings', $default_path . 'js/admin/settings' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-mb-question', $default_path . 'js/admin/meta-box-question' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-mb-course', $default_path . 'js/admin/meta-box-course' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-mb-quiz', $default_path . 'js/admin/meta-box-quiz' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-mb-order', $default_path . 'js/admin/meta-box-order' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-modal-search-items', $default_path . 'js/admin/modal-search-items' . $suffix . '.js', $deps, false, 1 );
		$scripts->add( 'learn-press-order', $default_path . 'js/admin/meta-box-order' . $suffix . '.js', $deps, false, 1 );

		// upgrade
		$scripts->add( 'learn-press-upgrade', '/wp-content/plugins/learnpress/inc/updates/09/script' . $suffix . '.js', $deps, false, 1 );
	}

	/**
	 * Load default styles
	 *
	 * @param WP_Styles $styles
	 */
	public static function default_styles( &$styles ) {
		if ( !defined( 'LEARNPRESS_VERSION' ) ) {
			define( 'LEARNPRESS_VERSION', '1.1' );
		}

		$develop_src = false !== strpos( LEARNPRESS_VERSION, '-src' );

		if ( !defined( 'SCRIPT_DEBUG' ) ) {
			define( 'SCRIPT_DEBUG', $develop_src );
		}
		if ( !$guessurl = site_url() ) {
			$guessed_url = true;
			$guessurl    = wp_guess_url();
		}
		$default_path = '/wp-content/plugins/learnpress/assets/';

		$styles->base_url        = $guessurl;
		$styles->content_url     = defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : '';
		$styles->default_version = get_bloginfo( 'version' );
		$styles->default_dirs    = array( $default_path, '/wp-admin/css/', '/wp-includes/css/' );

		$suffix     = SCRIPT_DEBUG ? '' : '';
		$dev_suffix = $develop_src ? '' : '.min';
		$styles->add( 'learn-press-global', $default_path . 'css/global' . $suffix . '.css' );
		$styles->add( 'learn-press-admin', $default_path . 'css/admin/admin' . $suffix . '.css' );
		$styles->add( 'learn-press-mb-course', $default_path . 'css/admin/meta-box-course' . $suffix . '.css' );
		$styles->add( 'learn-press-mb-question', $default_path . 'css/admin/meta-box-question' . $suffix . '.css' );
		$styles->add( 'learn-press-mb-order', $default_path . 'css/admin/meta-box-order' . $suffix . '.css' );

		//LP_Assets::enqueue_style( 'select2', RWMB_CSS_URL . 'select2/select2.css' );
		//LP_Assets::enqueue_script( 'learn-press-select2', RWMB_JS_URL . 'select2/select2.min.js' );
		//LP_Assets::enqueue_script( 'learn-press-modal-search-items' );
		//LP_Assets::enqueue_script( 'learn-press-meta-box-course', learn_press_plugin_url( 'assets/js/admin/meta-box-course.js' ), array( 'jquery' ) );
		//$styles->add( 'learn-press-meta-box-question', $default_path . 'css/admin/meta-box-question' . $suffix . '.css' );
	}

	/**
	 * register script
	 *
	 * @param string  $handle
	 * @param string  $src
	 * @param array   $deps
	 * @param string  $version
	 * @param boolean $in_footer
	 */
	public static function add_script( $handle, $src, $deps = array( 'jquery' ), $version = LEARNPRESS_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $src, $deps, $version, $in_footer );
	}

	/**
	 * register style
	 *
	 * @param string $handle
	 * @param string $src
	 * @param array  $deps
	 * @param string $version
	 * @param string $media
	 */
	public static function add_style( $handle, $src, $deps = array(), $version = LEARNPRESS_VERSION, $media = 'all' ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * enqueue script
	 *
	 * @param string  $handle
	 * @param string  $src
	 * @param array   $deps
	 * @param string  $version
	 * @param boolean $in_footer
	 */
	public static function enqueue_script( $handle, $src = '', $deps = array( 'jquery' ), $version = LEARNPRESS_VERSION, $in_footer = true ) {
		global $wp_scripts;

		if ( is_array( $handle ) ) {
			foreach ( $handle as $_handle ) {
				self::enqueue_script( $_handle, $src, $deps, $version, $in_footer );
			}
		} else {
			if ( !in_array( $handle, self::$scripts ) && $src ) {
				self::add_script( $handle, $src, $deps, $version, $in_footer );
			}
			self::$_enqueue_scripts[$handle] = func_get_args();
			if ( $deps ) {
				foreach ( $deps as $dep ) {
					self::add_script_concat( $dep );
				}
			}
			self::add_script_concat( $handle );
		}
	}

	public static function add_script_concat( $handle ) {
		global $wp_scripts;
		if ( empty( $wp_scripts->lp_script_concat ) ) {
			$wp_scripts->lp_script_concat = array();
		}
		if ( strpos( $handle, 'learn-press-' ) !== false ) {
			$concat = str_replace( 'learn-press-', '', $handle );
			if ( !in_array( $concat, $wp_scripts->lp_script_concat ) ) {
				$wp_scripts->lp_script_concat[] = $concat;
			}
		}
	}

	public static function add_style_concat( $handle ) {
		global $wp_styles;
		if ( empty( $wp_styles->lp_style_concat ) ) {
			$wp_styles->lp_style_concat = array();
		}
		if ( strpos( $handle, 'learn-press-' ) !== false ) {
			$concat = str_replace( 'learn-press-', '', $handle );
			if ( !in_array( $concat, $wp_styles->lp_style_concat ) ) {
				$wp_styles->lp_style_concat[] = $concat;
			}
		}
	}

	/**
	 * enqueue style
	 *
	 * @param string $handle
	 * @param string $src
	 * @param array  $deps
	 * @param string $version
	 * @param string $media
	 */
	public static function enqueue_style( $handle, $src = '', $deps = array(), $version = LEARNPRESS_VERSION, $media = 'all' ) {
		if ( is_array( $handle ) ) {
			foreach ( $handle as $_handle ) {
				self::enqueue_style( $_handle, $src, $deps, $version, $media );
			}
		} else {
			if ( !in_array( $handle, self::$styles ) && $src ) {
				self::add_style( $handle, $src, $deps, $version, $media );
			}
			self::$_enqueue_styles[$handle] = func_get_args();
			if ( $deps ) {
				foreach ( $deps as $dep ) {
					self::add_style_concat( $dep );
				}
			}
			self::add_style_concat( $handle );
		}
		return;
		if ( !in_array( $handle, self::$styles ) && $src ) {
			self::add_style( $handle, $src, $deps, $version, $media );
		}
		//wp_enqueue_style( $handle );
		self::$_enqueue_styles[$handle] = func_get_args();
	}

	/**
	 * add translate text
	 *
	 * @param       string
	 * @param array $localize
	 * @param       string
	 */
	public static function add_localize( $key, $localize = null, $handle = 'learn-press-js' ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				self::add_localize( $k, $v, $handle );
			}
		} elseif ( is_string( $key ) && strlen( $key ) ) {
			if ( !$handle ) $handle = 'learn-press-js';
			if ( empty( self::$wp_localize_scripts[$handle] ) ) self::$wp_localize_scripts[$handle] = array();
			self::$wp_localize_scripts[$handle][$key] = $localize;
		}
	}

	/**
	 * Add js param
	 *
	 * @param string
	 * @param array
	 * @param string
	 */
	public static function add_param( $key, $param = null, $handle = 'learn-press-js' ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $k => $v ) {
				self::add_param( $k, $v, $handle );
			}
		} elseif ( is_string( $key ) && strlen( $key ) ) {
			if ( !$handle ) $handle = 'learn-press-js';
			if ( empty( self::$wp_params[$handle] ) ) self::$wp_params[$handle] = array();
			self::$wp_params[$handle][$key] = $param;
		}
	}

	/**
	 * Localize script
	 *
	 * @param  mixed $handle
	 */
	private static function localize_script( $handle ) {
		$data = !empty( self::$wp_localize_scripts[$handle] ) ? self::$wp_localize_scripts[$handle] : false;
		if ( wp_script_is( $handle ) && $data ) {
			$name = str_replace( '-', '_', $handle ) . '_localize';
			unset( self::$wp_localize_scripts[$handle] );
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Print script params
	 *
	 * @param  mixed $handle
	 */
	private static function script_paramsx( $handle ) {
		$data = !empty( self::$wp_params[$handle] ) ? self::$wp_params[$handle] : false;
		if ( wp_script_is( $handle ) && $data ) {
			$name = str_replace( '-', '_', $handle ) . '_params';
			unset( self::$wp_params[$handle] );
			//$data = learn_press_sanitize_json( $data );
			//wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	public static function add_script_tag( $code, $handle = '' ) {
		if ( empty( self::$wp_script_codes[$handle] ) ) {
			self::$wp_script_codes[$handle] = '';
		}
		self::$wp_script_codes[$handle] .= preg_replace( '!</?script(.*)>!', '', $code );
	}

	public static function wp_assets() {
		do_action( 'learn_press_print_assets', is_admin() );
	}

	public static function script_params( $src, $handle ) {
		$data = !empty( self::$wp_params[$handle] ) ? self::$wp_params[$handle] : false;
		if ( wp_script_is( $handle ) && $data ) {
			$name = str_replace( '-', '_', $handle ) . '_params';
			echo "<script type='text/javascript'>\n"; // CDATA and type='text/javascript' is not needed for HTML 5
			echo "/* <![CDATA[ */\n";
			echo "var {$name}=" . wp_json_encode( $data ) . "\n";
			echo "/* ]]> */\n";
			echo "</script>\n";
			unset( self::$wp_params[$handle] );
		}
		return $src;
	}

	public static function localize_printed_scripts() {
		if ( self::$scripts ) foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
			//self::script_params( $handle );
			if ( !empty( self::$wp_script_codes[$handle] ) ) {
				learn_press_get_template( 'global/scripts.php', array( 'code' => self::$wp_script_codes[$handle] ) );
				unset( self::$wp_script_codes[$handle] );
			}
		}

	}

	public static function _enqueue_scripts() {
		if ( strpos( current_action(), 'enqueue_scripts' ) === false ) {
			return;
		}
		if ( !empty( self::$_enqueue_scripts ) ) {
			foreach ( self::$_enqueue_scripts as $handle => $args ) {
				call_user_func_array( 'wp_enqueue_script', array( $handle ) );
			}
		}
		if ( !empty( self::$_enqueue_styles ) ) {
			foreach ( self::$_enqueue_styles as $handle => $args ) {
				call_user_func_array( 'wp_enqueue_style', array( $handle ) );
			}
		}
	}

	/**
	 * Load assets for frontend
	 */
	public function load_scripts() {
		if ( is_admin() ) {
			$screen    = get_current_screen();
			$screen_id = $screen->id;
			$page_id   = !empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';

			if ( in_array( $screen_id, learn_press_get_screens() ) || in_array( $page_id, learn_press_get_admin_pages() ) ) {
				self::enqueue_style( 'learn-press-global' );
				self::enqueue_style( 'learn-press-admin' );
				self::enqueue_style( 'learn-press-icons' );
				self::enqueue_script( 'learn-press-global' );
				self::enqueue_script( 'learn-press-admin' );
			}

			foreach ( array( 'lp_course', 'lp_order', 'lp_quiz', 'lp_lesson', 'lp_question' ) as $post_type ) {
				if ( learn_press_is_post_type_screen( $post_type ) ) {
					$type = str_replace( 'lp_', '', $post_type );
					self::enqueue_style( "learn-press-mb-{$type}" );
					self::enqueue_script( "learn-press-mb-{$type}" );
				}
			}

			if ( learn_press_is_post_type_screen( array( 'lp_quiz' ) ) ) {
				self::enqueue_style( 'learn-press-mb-question' );
				self::enqueue_script( 'learn-press-mb-question' );
			}

			if ( learn_press_is_post_type_screen( array( 'lp_course', 'lp_quiz', 'lp_order' ) ) ) {
				self::enqueue_style( 'learn-press-modal-search-items' );
				self::enqueue_script( 'learn-press-modal-search-items' );
			}
			return;
		}

		// global
		self::enqueue_style( 'learn-press-icon' );
		self::enqueue_style( 'learn-press-jalerts' );
		self::enqueue_style( 'learn-press-admin' );
		// frontend
		if ( LP()->settings->get( 'load_css' ) == 'yes' || LP()->settings->get( 'load_css' ) == '' ) {
			self::enqueue_style( 'learn-press', learn_press_plugin_url( 'assets/css/learnpress.css' ) );
		}

		self::enqueue_script( 'learn-press-jalerts' );
		self::enqueue_script( 'learn-press-global' );
		self::enqueue_script( 'learn-press-js' );
		self::enqueue_script( 'learn-press-course-lesson' );
		self::enqueue_script( 'learn-press-single-course' );
		if ( LP()->settings->get( 'ajax_add_to_cart' ) == 'yes' ) {
			self::enqueue_script( 'learn-press-add-to-cart' );
		}
		self::enqueue_script( 'learn-press-timer' );
		self::enqueue_script( 'learn-press-checkout' );
		self::enqueue_script( 'learn-press-become-teacher' );
		self::enqueue_script( 'learn-press-course-quiz' );
	}
}

return new LP_Assets();
