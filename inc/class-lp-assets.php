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

LP()->_include( 'abstracts/abstract-lp-assets.php' );

class LP_Assets extends LP_Abstract_Assets {

	/**
	 * Load assets for frontend
	 */
	static function load_scripts(){

		self::add_style( 'learn-press-admin', LP()->plugin_url( 'assets/css/learnpress-admin.css') );

		self::add_style( 'lpr-learnpress-css', LP_CSS_URL . 'learnpress.css' );
		self::add_style( 'lpr-time-circle-css', LP_CSS_URL . 'timer.css' );

		self::add_script( 'lpr-alert-js', LP_JS_URL . 'jquery.alert.js' );
		self::add_script( 'lpr-global', LP_JS_URL . 'global.js' );
		self::add_script( 'lpr-time-circle-js', LP_JS_URL . 'jquery.timer.js' );
		self::add_script( 'block-ui', LP_JS_URL . 'jquery.block-ui.js' );
		self::add_script( 'learn-press-js', LP_JS_URL . 'frontend/learnpress.js', array( 'jquery', 'lpr-alert-js', 'lpr-global', 'lpr-time-circle-js' ) );

		learn_press_enqueue_script( "<script>var learn_press_ajaxurl='" . admin_url( 'admin-ajax.php' ) . "';</script>", true );
		learn_press_enqueue_script( "<script>var learn_press_site_url='" . get_site_url() . "';</script>", true );
		learn_press_enqueue_script( "<script>var learn_press_url='" . get_the_permalink() . "';</script>", true );

		global $post;

		if ( !$post || !in_array( $post->post_type, array( LP()->course_post_type, LP()->quiz_post_type, LP()->lesson_post_type, LP()->assignment_post_type ) ) ) {
			//return;
		}

		self::enqueue_style( 'lpr-learnpress-css' );
		self::enqueue_style( 'lpr-time-circle-css' );

		self::enqueue_script( 'learn-press-js' );
		self::enqueue_script( 'lpr-alert-js' );
		self::enqueue_script( 'lpr-time-circle-js' );
		self::enqueue_script( 'block-ui' );

		self::enqueue_script( 'single-course', LP()->plugin_url( 'assets/js/frontend/single-course.js' ) );

		if( learn_press_is_checkout() ) {
			self::enqueue_script( 'checkout', LP()->plugin_url( 'assets/js/frontend/checkout.js' ) );
		}
	}
}
LP_Assets::init();
function learn_press_init_assets(){

}
add_action( 'wp_enqueue_scripts', 'learn_press_init_assets' );
