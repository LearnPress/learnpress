<?php

/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 * @extends LP_Abstract_Assets
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

learn_press_include( 'abstracts/abstract-lp-assets.php' );

class LP_Assets extends LP_Abstract_Assets {
	static protected $_minify = array(
		'assets/js/admin/meta-box-question.js' => 'assets/js/admin/question.min.js',
		'assets/js/global.js'                  => 'assets/js/global.min.js'
	);

	public static function init() {
		parent::$caller = __CLASS__;
		add_action( 'learn_press_print_assets', array( __CLASS__, '_print_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'footer_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'eduma_assets' ), 9999 );
		/*add_filter( 'script_loader_src', array( __CLASS__, 'script_loader_src' ), 10, 2 );
		add_action( 'learn_press_settings_save_general', array( __CLASS__, '_minify_source' ) );*/
		parent::init();
	}

	public static function eduma_assets() {
		if ( learn_press_is_course() ) {
			self::enqueue_script( 'eduma-single-course' );
			self::enqueue_style( 'eduma-single-course' );
		}
	}

	public static function footer_scripts() {
		global $wp_scripts;
	}

	public static function script_loader_src( $src, $handle ) {
		if ( LP_Settings::instance()->get( 'debug' ) == 'yes' ) {
			return $src;
		}
		$minify = self::$_minify;
		foreach ( $minify as $find => $replace ) {
			if ( strpos( $src, $find ) !== false ) {
				$src = str_replace( $find, $replace, $src );
				break;
			}
		}
		return $src;
	}

	public static function _url_to_path( $url, $sub ) {
		$path = str_replace( LP_PLUGIN_URL, preg_replace( '/\\\\/', '/', LP_PLUGIN_PATH ), $url );
		echo preg_replace( '!\?.*!', '', $path );
	}

	public static function _create_file( $src, $des ) {
		if ( $src == $des ) {
			return $src;
		}
		$read_file  = LP_PLUGIN_PATH . '/' . $src;
		$write_file = LP_PLUGIN_PATH . '/' . $des;

		global $wp_filesystem;
		if ( $wp_filesystem ) {
			include_once LP_PLUGIN_PATH . '/inc/libraries/minify/src/Minify.php';
			include_once LP_PLUGIN_PATH . '/inc/libraries/minify/src/JS.php';
			$code     = $wp_filesystem->get_contents( $read_file );
			$minifier = new JS( $code );
			$code     = $minifier->minify();

			$wp_filesystem->put_contents(
				$write_file,
				$code
			);
			if ( file_exists( $write_file ) ) {
				$src = $des;
			}
		}
		return $src;
	}

	public static function _minify_source() {
		if ( LP_Settings::instance()->get( 'debug' ) == 'yes' ) {
			return;
		}
		$minify = self::$_minify;
		foreach ( $minify as $find => $replace ) {
			self::_create_file( $find, $replace );
		}
	}

	/**
	 * Load assets for frontend
	 */
	public static function load_scripts() {
		$deps = array( 'jquery', 'backbone', 'utils', 'course-lesson', 'jalerts' );

		// global
		self::add_style( 'learn-press-icon', learn_press_plugin_url( 'assets/css/icons.css' ) );
		self::add_script( 'learn-press-global', learn_press_plugin_url( 'assets/js/global.js' ), $deps );
		//self::add_script( 'learn-press-block-ui', learn_press_plugin_url( 'assets/js/jquery.block-ui.js' ) );

		// jAlerts
		self::add_style( 'jalerts', learn_press_plugin_url( 'assets/css/icons.css' ) );
		self::add_script( 'jalerts', learn_press_plugin_url( 'assets/js/jquery.alert.js' ) );

		// admin
		self::add_style( 'learn-press-admin', learn_press_plugin_url( 'assets/css/admin/admin.css' ) );

		// frontend
		self::add_style( 'learn-press', learn_press_plugin_url( 'assets/css/learnpress.css' ) );
		self::add_script( 'learn-press-js', learn_press_plugin_url( 'assets/js/frontend/learnpress.js' ), array( 'learn-press-global' ) );

		// lesson
		self::add_script( 'course-lesson', learn_press_plugin_url( 'assets/js/frontend/course-lesson.js' ) );


		$v2 = "";
		// single course
		self::add_script( 'single-course', learn_press_plugin_url( 'assets/js/frontend/single-course' . $v2 . '.js' ), $deps );
		if ( $v2 && wp_get_theme()->get( 'Name' ) == 'eduma' ) {
			self::add_script( 'eduma-single-course', learn_press_plugin_url( 'assets/eduma/custom-script.js' ) );
			self::add_style( 'eduma-single-course', learn_press_plugin_url( 'assets/eduma/custom-style.css' ) );
		}

		if ( LP()->settings->get( 'ajax_add_to_cart' ) == 'yes' ) {
			self::add_script( 'learn-press-add-to-cart', learn_press_plugin_url( 'assets/js/frontend/add-to-cart.js' ) );
		}
		// single quiz
		self::add_script( 'learn-press-timer', learn_press_plugin_url( 'assets/js/jquery.timer.js' ) );
		self::add_script( 'single-quiz', learn_press_plugin_url( 'assets/js/frontend/single-quiz.js' ), $deps );

		// checkout page
		self::add_script( 'checkout', learn_press_plugin_url( 'assets/js/frontend/checkout.js' ) );

		// become teacher
		self::add_script( 'become-teacher', learn_press_plugin_url( 'assets/js/frontend/become-teacher.js' ) );

	}

	public static function _print_assets() {

		if ( is_admin() ) {
			//return;
		}

		do_action( 'learn_press_frontend_before_load_assets' );

		self::enqueue_style(
			array(
				'learn-press-icon',
				'learn-press'
			)
		);

		self::enqueue_script(
			array(
				'learn-press-global'
			)
		);

		// single course
		if ( learn_press_is_course() ) {
			self::enqueue_script( 'single-course' );

			$course = LP()->course;
			if ( $course->load_media == 'yes' ) {
				wp_enqueue_style( 'wp-mediaelement' );
				wp_enqueue_script( 'wp-mediaelement' );
			}
			if ( $course && $course->is_free() && LP()->settings->get( 'no_checkout_free_course' ) == 'yes' ) {
			} else {
				self::enqueue_script( 'learn-press-add-to-cart' );
			}
		}

		// single quiz
		if ( learn_press_is_quiz() ) {
			self::enqueue_script( 'learn-press-timer' );
			self::enqueue_script( 'single-quiz' );
		}

		// checkout page
		if ( learn_press_is_checkout() ) {
			self::enqueue_script( 'checkout' );
		}

		if ( learn_press_is_profile() ) {
			self::enqueue_script( 'learn-press-js' );
		}

		if ( is_learnpress() ) {
			self::enqueue_script( 'learn-press' );
		}
		do_action( 'learn_press_frontend_after_load_assets' );
	}
}

LP_Assets::init();
