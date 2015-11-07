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

	static function init(){
		add_action( 'learn_press_print_assets', array( __CLASS__, '_print_assets' ) );
		parent::init();
	}

	/**
	 * Load assets for frontend
	 */
	static function load_scripts(){

		// global
		self::add_script( 'learn-press-global', learn_press_plugin_url( 'assets/js/global.js' ) );
		self::add_script( 'learn-press-block-ui', learn_press_plugin_url( 'assets/js/jquery.block-ui.js' ) );

		// admin
		self::add_style( 'learn-press-admin', learn_press_plugin_url( 'assets/css/learnpress-admin.css') );

		// frontend
		self::add_style( 'learn-press', learn_press_plugin_url( 'assets/css/learnpress.css' ) );
		self::add_script( 'learn-press-js', learn_press_plugin_url( 'assets/js/frontend/learnpress.js' ), array( 'learn-press-global' ) );

		// single course
		self::add_script( 'single-course', learn_press_plugin_url( 'assets/js/frontend/single-course.js' ), array( 'backbone', 'utils' ) );

		// single quiz
		self::add_script( 'learn-press-timer', learn_press_plugin_url( 'assets/js/jquery.timer.js' ) );
		self::add_script( 'single-quiz', learn_press_plugin_url( 'assets/js/frontend/single-quiz.js' ), array( 'backbone', 'utils' ) );

		// checkout page
		self::add_script( 'checkout', learn_press_plugin_url( 'assets/js/frontend/checkout.js' ) );

		//self::add_script( 'lpr-alert-js', LP_JS_URL . 'jquery.alert.js' );

		/*self::enqueue_style( 'lpr-learnpress-css' );
		self::enqueue_style( 'lpr-time-circle-css' );

		self::enqueue_script( 'learn-press-js' );
		self::enqueue_script( 'lpr-alert-js' );
		self::enqueue_script( 'lpr-time-circle-js' );
		self::enqueue_script( 'block-ui' );
*/

	}

	static function _print_assets(){

		self::enqueue_style(
			array(
				'learn-press'
			)
		);

		self::enqueue_script(
			array(
				'learn-press-global'
			)
		);

		// single course
		if( is_course() ){
			self::enqueue_script( 'single-course' );
		}

		// single quiz
		if( is_quiz() ){
			self::enqueue_script( 'learn-press-timer' );
			self::enqueue_script( 'single-quiz' );
		}

		// checkout page
		if( learn_press_is_checkout() ) {
			self::enqueue_script( 'checkout' );
		}
	}
}
LP_Assets::init();
