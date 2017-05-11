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
	protected static $wp_param_names = array();

	/**
	 * @var array
	 */
	protected static $wp_script_codes = array();

	/**
	 * Localized flag
	 *
	 * @var bool
	 */
	protected static $localized = array( '__all' => '' );

	/**
	 * @var array
	 */
	protected static $param_printed = array( '__all' );

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
	 * @var array
	 */
	protected static $js_vars = array();

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
		$priory = 900;
		if ( !is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( self::$_instance, 'load_scripts' ), $priory );
			add_action( 'wp_enqueue_scripts', array( self::$_instance, 'wp_assets' ), $priory );
			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), $priory + 10 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ), $priory + 10 );

		} else {
			add_action( 'admin_enqueue_scripts', array( self::$_instance, 'load_scripts' ), $priory );
			add_action( 'admin_enqueue_scripts', array( self::$_instance, 'wp_assets' ), $priory );
			add_action( 'admin_print_footer_scripts', array( self::$_instance, 'localize_printed_scripts' ), $priory + 10 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ), $priory + 10 );
		}
		add_filter( 'script_loader_src', array( __CLASS__, 'script_localized' ), $priory + 5, 2 );

		/**
		 * Check if action add_default_scripts has called then we need
		 * to call method add_default_scripts directly to make scripts
		 * work properly
		 *
		 * Fixed in ver 2.0.8
		 */
		if ( did_action( 'add_default_scripts' ) ) {
			global $wp_scripts;
			if ( $wp_scripts ) {
				self::add_default_scripts( $wp_scripts );
			}
		} else {
			add_action( 'wp_default_scripts', array( __CLASS__, 'add_default_scripts' ) );
		}

		/**
		 * Check if action wp_default_styles has called then we need
		 * to call method add_default_styles directly to make styles
		 * work properly
		 *
		 * Fixed in ver 2.0.8
		 */
		if ( did_action( 'wp_default_styles' ) ) {
			global $wp_styles;
			if ( $wp_styles ) {
				self::add_default_styles( $wp_styles );
			}
		} else {
			add_action( 'wp_default_styles', array( __CLASS__, 'add_default_styles' ) );
		}
		if ( !defined( 'LP_DEBUG' ) || ( false == LP_DEBUG ) ) {
			add_filter( 'script_loader_tag', array( self::$_instance, 'unload_script_tag' ), $priory, 3 );
			add_filter( 'style_loader_tag', array( self::$_instance, 'unload_script_tag' ), $priory, 3 );
			add_action( 'wp_print_footer_scripts', array( self::$_instance, 'include_script_file' ), $priory );
			add_action( 'admin_print_footer_scripts', array( self::$_instance, 'include_script_file' ), $priory );
			add_action( 'wp_print_scripts', array( self::$_instance, 'include_stylesheet_file' ), $priory );
			add_action( 'admin_print_scripts', array( self::$_instance, 'include_stylesheet_file' ), $priory );
		}
	}

	public static function script_localized( $source, $handle ) {
		//if ( !empty( self::$wp_localize_scripts[$handle] ) ) {
		self::$localized[$handle] = $source;
		//}
		if ( !empty( self::$wp_params[$handle] ) ) {
			self::$param_printed[] = $handle;
		}
		return $source;
	}

	/**
	 * Load default scripts
	 *
	 * @param $scripts
	 */
	public static function default_scripts( &$scripts ) {
		if ( !defined( 'LEARNPRESS_VERSION' ) ) {
			define( 'LEARNPRESS_VERSION', '2.1.1' );
		}

		$develop_src = false !== strpos( LEARNPRESS_VERSION, '-src' );

		if ( !defined( 'SCRIPT_DEBUG' ) ) {
			define( 'SCRIPT_DEBUG', $develop_src );
		}
		if ( !$guessurl = site_url() ) {
			$guessed_url = true;
			$guessurl    = wp_guess_url();
		}
		$default_dirs             = array( '/wp-admin/js/', '/wp-includes/js/' );
		$scripts->base_url        = $guessurl;
		$scripts->content_url     = defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : '';
		$scripts->default_version = get_bloginfo( 'version' );
		$scripts->default_dirs    = $default_dirs;
		self::add_default_scripts( $scripts );
	}

	public static function add_default_scripts( &$scripts ) {

		$default_path = plugins_url( 'learnpress/assets/' );
		$suffix       = '';
		$deps         = array( 'jquery', 'backbone', 'utils' );
		$ver          = LEARNPRESS_VERSION;

		$scripts->add( 'angularjs', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.6.4/angular.min.js' ,null, $ver, 1 );

		// global
		$scripts->add( 'learn-press-global', $default_path . 'js/global' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-jalerts', $default_path . 'js/jquery.alert' . $suffix . '.js', $deps, $ver, 1 );

		// frontend
		$scripts->add( 'learn-press-js', $default_path . 'js/frontend/learnpress' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-single-course', $default_path . 'js/frontend/single-course' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-course-quiz', $default_path . 'js/frontend/quiz' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-course-lesson', $default_path . 'js/frontend/lesson' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-enroll', $default_path . 'js/frontend/enroll' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-timer', $default_path . 'js/jquery.timer' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-checkout', $default_path . 'js/frontend/checkout' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-become-teacher', $default_path . 'js/frontend/become-teacher' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-profile', $default_path . 'js/frontend/profile' . $suffix . '.js', array( 'jquery', 'backbone', 'utils', 'plupload', 'jquery-ui-slider', 'jquery-ui-draggable' ), $ver, 1 );

		// admin
		$scripts->add( 'learn-press-admin', $default_path . 'js/admin/admin' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-admin-settings', $default_path . 'js/admin/settings' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-question', $default_path . 'js/admin/meta-box-question' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-course', $default_path . 'js/admin/meta-box-course' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-quiz', $default_path . 'js/admin/meta-box-quiz' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-mb-order', $default_path . 'js/admin/meta-box-order' . $suffix . '.js', $deps, $ver, 1 );
		$scripts->add( 'learn-press-modal-search-items', $default_path . 'js/admin/modal-search-items' . $suffix . '.js', array( 'learn-press-global' ), $ver, 1 );
		$scripts->add( 'learn-press-order', $default_path . 'js/admin/meta-box-order' . $suffix . '.js', $deps, $ver, 1 );
		//$scripts->add( 'learn-press-statistics-select2', '/' . LP_WP_CONTENT . '/plugins/learnpress/inc/libraries/meta-box/js/select2/select2.min.js', $deps, false, 1 );

		$scripts->add( 'learn-press-select2', '/' . LP_WP_CONTENT . '/plugins/learnpress/inc/libraries/meta-box/js/select2/select2.min.js', $deps, $ver, 1 );

		// upgrade
		$scripts->add( 'learn-press-upgrade', '/' . LP_WP_CONTENT . '/plugins/learnpress/inc/updates/09/script' . $suffix . '.js', $deps, $ver, 1 );

		do_action_ref_array( 'learn_press_add_default_scripts', array( $scripts, $default_path, $suffix, $deps ) );
	}

	/**
	 * Load default styles
	 *
	 * @param WP_Styles $styles
	 */
	public static function default_styles( &$styles ) {
		if ( !defined( 'LEARNPRESS_VERSION' ) ) {
			define( 'LEARNPRESS_VERSION', '2.1.1' );
		}

		$develop_src = false !== strpos( LEARNPRESS_VERSION, '-src' );

		if ( !defined( 'SCRIPT_DEBUG' ) ) {
			define( 'SCRIPT_DEBUG', $develop_src );
		}
		if ( !$guessurl = site_url() ) {
			$guessed_url = true;
			$guessurl    = wp_guess_url();
		}
		$default_dirs            = array( '/wp-admin/css/', '/wp-includes/css/' );
		$styles->base_url        = $guessurl;
		$styles->content_url     = defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : '';
		$styles->default_version = get_bloginfo( 'version' );
		$styles->default_dirs[]  = $default_dirs;

		self::add_default_styles( $styles );
	}

	/**
	 * @param WP_Styles $styles
	 */
	public static function add_default_styles( &$styles ) {
		$default_path = plugins_url( 'learnpress/assets/' );
		$suffix       = '';
		$deps         = array( 'dashicons' );
		$ver          = LEARNPRESS_VERSION;
		// global
		$styles->add( 'learn-press-global', $default_path . 'css/global' . $suffix . '.css', $deps, $ver );

		// admin
		$styles->add( 'learn-press-admin', $default_path . 'css/admin/admin' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-jquery.ui.datepicker', $default_path . 'css/admin/jquery.ui.datepicker' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-jquery.ui.theme', $default_path . 'css/admin/jquery.ui.theme' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-jquery.ui.core', $default_path . 'css/admin/jquery.ui.core' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-jquery.ui.slider', $default_path . 'css/admin/jquery.ui.slider' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-mb-course', $default_path . 'css/admin/meta-box-course' . $suffix . '.css', null, $ver );
		//$styles->add( 'learn-press-mb-question', $default_path . 'css/admin/meta-box-question' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-mb-order', $default_path . 'css/admin/meta-box-order' . $suffix . '.css', null, $ver );
		$styles->add( 'learn-press-jalerts', $default_path . 'css/jalert' . $suffix . '.css', null, $ver );
		//$styles->add( 'learn-press-statistics-select2', '/' . LP_WP_CONTENT . '/plugins/learnpress/inc/libraries/meta-box/css/select2/select2.css' );
		$styles->add( 'learn-press-select2', '/' . LP_WP_CONTENT . '/plugins/learnpress/inc/libraries/meta-box/css/select2/select2.css' );


		// frontend
		$styles->add( 'learn-press-style', $default_path . 'css/learnpress.css', $deps, $ver );
		do_action_ref_array( 'learn_press_add_default_styles', array( $styles, $default_path, $suffix ) );

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

	/**
	 * @param $handle
	 */
	public static function add_script_concat( $handle ) {
		global $wp_scripts;
		if ( !$wp_scripts ) {
			return;
		}
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

	/**
	 * @param $handle
	 */
	public static function add_style_concat( $handle ) {
		global $wp_styles;
		if ( !$wp_styles ) {
			return;
		}
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
	}

	/**
	 * add translate text
	 *
	 * @param       string
	 * @param array $localize
	 * @param       string
	 */
	public static function add_localize( $key, $localize = null, $handle = '' ) {
		if ( !$handle ) {
			$handle = is_admin() ? 'learn-press-admin' : 'learn-press-js';
		}
		if ( empty( self::$wp_localize_scripts[$handle] ) ) {
			self::$wp_localize_scripts[$handle] = array();
		}
		if ( is_array( $key ) ) {
			self::$wp_localize_scripts[$handle] = array_merge( self::$wp_localize_scripts[$handle], $key );
		} elseif ( is_string( $key ) && strlen( $key ) ) {
			self::$wp_localize_scripts[$handle][$key] = $localize;
		}
	}

	/**
	 * Add js param
	 *
	 * @param string
	 * @param array
	 * @param string
	 * @param $name
	 */
	public static function add_param( $key, $param = null, $handle = 'learn-press-js', $name = null ) {
		if ( !$handle ) {
			$handle = 'learn-press-js';
		}
		if ( is_array( $handle ) ) {
			foreach ( $handle as $h ) {
				self::add_param( $key, $param, $h, $name );
			}
			return;
		}
		if ( empty( $name ) ) {
			$name = $handle;
		}
		if ( empty( self::$wp_params[$handle] ) ) {
			self::$wp_params[$handle] = array( $name => array() );
		}
		if ( empty( self::$wp_params[$handle][$name] ) ) {
			self::$wp_params[$handle][$name] = array();
		}

		if ( is_array( $key ) ) {
			self::$wp_params[$handle][$name] = array_merge( self::$wp_params[$handle][$name], $key );
		} elseif ( is_string( $key ) && strlen( $key ) ) {
			self::$wp_params[$handle][$name][$key] = $param;
		}
	}

	public static function add_var( $name, $value, $handle = 'learn-press-global' ) {
		if ( is_array( $handle ) ) {
			foreach ( $handle as $h ) {
				self::add_var( $name, $value, $h );
			}
			return;
		}
		if ( empty( self::$js_vars[$handle] ) ) {
			self::$js_vars[$handle] = array();
		}
		self::$js_vars[$handle][$name] = $value;
	}

	/**
	 * @param        $code
	 * @param string $handle
	 */
	public static function add_script_tag( $code, $handle = '' ) {
		if ( empty( self::$wp_script_codes[$handle] ) ) {
			self::$wp_script_codes[$handle] = '';
		}
		self::$wp_script_codes[$handle] .= preg_replace( '!</?script(.*)>!', '', $code );
	}

	/**
	 * wp_assets
	 */
	public static function wp_assets() {
		do_action( 'learn_press_print_assets', is_admin() );
	}

	/**
	 * localize_printed_scripts
	 */
	public static function localize_printed_scripts() {
		$has_localized = !empty( self::$localized );
		$has_params    = !empty( self::$param_printed );
		$has_vars      = !empty( self::$js_vars );
		if ( $has_localized || $has_params || $has_vars ) {
			echo "<script type='text/javascript'>\n"; // CDATA and type='text/javascript' is not needed for HTML 5
			echo "/* <![CDATA[ */\n";
			if ( $has_localized ) {
				if ( self::$wp_localize_scripts ) {
					echo "\n/* LearnPress Localized */\n";
					foreach ( self::$localized as $handle => $src ) {
						if ( !empty( self::$wp_localize_scripts[$handle] ) ) {
							$name = str_replace( '-', '_', $handle ) . '_localize';
							echo "var {$name} = " . json_encode( self::$wp_localize_scripts[$handle] ) . ";\n";
						}
					}
				}
			}

			if ( $has_params ) {
				$groups = array();

				foreach ( self::$param_printed as $handle ) {
					if ( !empty( self::$wp_params[$handle] ) ) {
						foreach ( self::$wp_params[$handle] as $name => $value ) {
							$name  = str_replace( '-', '_', $name );
							$value = (array) ( $value );
							if ( empty( $groups[$name] ) ) {
								$groups[$name] = $value;
							} else {
								$groups[$name] = array_merge( $groups[$name], $value );
							}
						}
					}
				}
				//print_r($groups);
				if ( $groups ) {
					echo "\n/* LearnPress Params */\n";
					foreach ( $groups as $name => $code ) {
						echo "var {$name} = " . wp_json_encode( $code ) . ";\n";
					}
				}
			}


			if ( $has_localized ) {
				if ( self::$js_vars ) {
					echo "\n/* Custom vars */\n";
					$abort = array();
					foreach ( self::$localized as $handle => $src ) {
						if ( !empty( self::$js_vars[$handle] ) ) {
							foreach ( self::$js_vars[$handle] as $name => $var ) {
								if ( in_array( $name, $abort ) ) {
									continue;
								}
								$abort[] = $name;
								echo "var {$name} = " . maybe_serialize( $var ) . ";\n";
							}
						}
					}
				}
				if ( self::$wp_script_codes ) {
					echo "\n/* LearnPress Custom Scripts */\n ( typeof jQuery != 'undefined' ) && jQuery(function($){\n";
					foreach ( self::$localized as $handle => $src ) {
						if ( !empty( self::$wp_script_codes[$handle] ) ) {
							echo( self::$wp_script_codes[$handle] );
						}
					}
					echo "\n});\n";
				}
			}

			echo "/* ]]> */\n";
			echo "</script>\n";
		}
	}

	public static function remove_script( $handle ) {
		if ( isset( self::$_enqueue_scripts[$handle] ) ) {
			unset( self::$_enqueue_scripts[$handle] );
		}
		wp_deregister_script( $handle );
	}

	public static function remove_style( $handle ) {
		if ( isset( self::$_enqueue_styles[$handle] ) ) {
			unset( self::$_enqueue_styles[$handle] );
		}
		wp_deregister_style( $handle );

	}

	/**
	 * _enqueue_scripts
	 */
	public static function _enqueue_scripts() {
		do_action( 'learn_press_enqueue_scripts', __CLASS__ );
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
	 * Do not load script
	 */
	public function unload_script_tag( $tag, $handle, $src ) {
		if ( strpos( $handle, 'learn-press-' ) !== false && strpos( $src, '/learnpress/' ) !== false ) {
			return false;
		}
		return $tag;
	}

	/**
	 * include_script_file
	 */
	public function include_script_file() {
		global $wp_scripts, $compress_scripts;

		$zip = $compress_scripts ? 1 : 0;
		if ( $zip && defined( 'ENFORCE_GZIP' ) && ENFORCE_GZIP )
			$zip = 'gzip';

		if ( !empty( $wp_scripts->lp_script_concat ) && $concat = join( ',', $wp_scripts->lp_script_concat ) ) {
			if ( !empty( $wp_scripts->print_code ) ) {
				echo "\n<script type='text/javascript'>\n";
				echo "/* <![CDATA[ */\n"; // not needed in HTML 5
				echo $wp_scripts->print_code;
				echo "/* ]]> */\n";
				echo "</script>\n";
			}

			$concat = str_split( $concat, 128 );
			$concat = 'load%5B%5D=' . implode( '&load%5B%5D=', $concat );

			$src = get_site_url() . "/" . LP_WP_CONTENT . "/plugins/learnpress/assets/load-scripts.php?" . $concat . "&c={$zip}&ver=" . $wp_scripts->default_version;
			echo "<script type='text/javascript' src='" . esc_attr( $src ) . "'></script>\n";
		}

		if ( !empty( $wp_scripts->print_html ) )
			echo $wp_scripts->print_html;
	}

	/**
	 * include_stylesheet_file
	 */
	public function include_stylesheet_file() {

		if ( did_action( 'learn_press_included_style_file' ) ) {
			return;
		}

		global $compress_css;

		$wp_styles = wp_styles();

		$zip = $compress_css ? 1 : 0;
		if ( $zip && defined( 'ENFORCE_GZIP' ) && ENFORCE_GZIP )
			$zip = 'gzip';

		if ( !empty( $wp_styles->lp_style_concat ) && $concat = join( ',', $wp_styles->lp_style_concat ) ) {
			$dir = $wp_styles->text_direction;
			$ver = $wp_styles->default_version;

			$concat = str_split( $concat, 128 );
			$concat = 'load%5B%5D=' . implode( '&load%5B%5D=', $concat );

			$href = get_site_url() . "/" . LP_WP_CONTENT . "/plugins/learnpress/assets/load-styles.php?" . $concat . "&c={$zip}&ver=" . $ver;
			echo "<link rel='stylesheet' href='" . esc_attr( $href ) . "' type='text/css' media='all' />\n";

			if ( !empty( $wp_styles->print_code ) ) {
				echo "<style type='text/css'>\n";
				echo $wp_styles->print_code;
				echo "\n</style>\n";
			}
		}

		if ( !empty( $wp_styles->print_html ) )
			echo $wp_styles->print_html;
		do_action( 'learn_press_included_style_file' );
	}

	/**
	 * Load assets
	 */
	public function load_scripts() {
		if ( is_admin() ) {
			global $pagenow;
			$screen    = get_current_screen();
			$screen_id = $screen->id;
			$page_id   = !empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
			self::enqueue_style( 'learn-press-admin' );
			if ( in_array( $screen_id, learn_press_get_screens() ) || in_array( $page_id, learn_press_get_admin_pages() ) ) {
				self::enqueue_style( 'learn-press-global' );
				self::enqueue_style( 'learn-press-jquery.ui.datepicker' );
				self::enqueue_style( 'learn-press-jquery.ui.theme' );
				self::enqueue_style( 'learn-press-jquery.ui.core' );
				self::enqueue_style( 'learn-press-jquery.ui.slider' );
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

			if ( $screen_id === 'learnpress_page_learn-press-settings' || $screen_id === 'dashboard' ) {
				LP_Assets::enqueue_style( 'learn-press-admin' );
				LP_Assets::enqueue_style( 'learn-press-select2' );

				LP_Assets::enqueue_script( 'learn-press-select2' );
				LP_Assets::enqueue_script( 'learn-press-admin-settings', LP()->plugin_url( 'assets/js/admin/settings.js' ) );
			}

			if ( $pagenow === 'edit.php' && $screen_id === 'edit-lp_course' ) {
				LP_Assets::enqueue_script( 'learn-press-duplicate-course', LP()->plugin_url( 'assets/js/admin/duplicate-course.js' ) );
			}

			if ( $pagenow === 'post.php' && $screen_id === 'lp_quiz' ) {
				self::enqueue_style( 'learn-press-jalerts' );
				self::enqueue_script( 'learn-press-jalerts' );
			}

			if ( 'learnpress_page_learn-press-statistics' === $screen_id ) {
				self::enqueue_script( 'learn-press-statistics-select2' );
				self::enqueue_style( 'learn-press-statistics-select2' );
			}
			do_action( 'learn_press_admin_load_scripts' );
			return;
		}
		$user = learn_press_get_current_user();

		// global
		self::enqueue_style( 'learn-press-icon' );
		self::enqueue_style( 'learn-press-jalerts' );

		// frontend
		if ( LP()->settings->get( 'load_css' ) == 'yes' || LP()->settings->get( 'load_css' ) == '' ) {
			self::enqueue_style( 'learn-press-style' );
		}
		self::enqueue_script( 'learn-press-jalerts' );
		self::enqueue_script( 'learn-press-global' );
		self::enqueue_script( 'learn-press-js' );
		if ( learn_press_is_course() ) {
			self::enqueue_script( 'learn-press-single-course' );
			self::enqueue_script( 'learn-press-course-quiz' );
			self::enqueue_script( 'learn-press-course-lesson' );
			if ( !$user->has_course_status( null, array( 'enrolled', 'finished' ) ) ) {
				self::enqueue_script( 'learn-press-enroll' );
			}
		}
		if ( learn_press_is_checkout() ) {
			self::enqueue_script( 'learn-press-checkout' );
		}
		self::enqueue_script( 'learn-press-become-teacher' );

		if ( learn_press_is_profile() ) {
			// Localize the script with new data
			$translation_array = array(
				'confim_pass_not_match' => __( 'Password and confirmation password do not match', 'learnpress' ),
				'msg_field_is_required' => __( 'This field is required', 'learnpress' )
			);
			wp_localize_script( 'learn-press-profile', 'lp_profile_translation', $translation_array );


			self::add_param( 'avatar_size', learn_press_get_avatar_thumb_size(), 'learn-press-profile', 'LP_Settings' );

			self::enqueue_script( 'learn-press-profile' );
		}
		do_action( 'learn_press_load_scripts' );
	}

	public static function url( $file = '' ) {
		return LP_PLUGIN_URL . "assets/{$file}";
	}
}

// Call class
return new LP_Assets();