<?php

/**
 * Class LP_Admin_Assets
 *
 * Manage admin assets
 */
class LP_Admin_Assets extends LP_Abstract_Assets {

	/**
	 * Init Asset
	 */
	public function __construct() {
		parent::__construct();
		$priory = 900;
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ), $priory );
		//add_action( 'admin_print_footer_scripts', array( $this, 'localize_printed_scripts' ), $priory + 10 );
		//add_action( 'admin_enqueue_scripts', array( __CLASS__, '_enqueue_scripts' ), $priory + 10 );
	}

	protected function _get_script_data() {
		return array(
			'learn-press-global' => array(
				'i18n' => 'This is global script for both admin and site'
			)
		);
	}

	/**
	 * Get default scripts in admin.
	 *
	 * @return mixed
	 */
	protected function _get_scripts() {
		return apply_filters(
			'learn-press/admin-default-scripts',
			array(
				'lp-vuejs'               => array(
					'url' => self::url( 'js/vendor/vue.js' ),
					'ver' => '2.4.0'
				),
				'learn-press-global'     => array(
					'url'  => $this->url( 'js/global.js' ),
					'deps' => array( 'jquery', 'underscore', 'utils', 'jquery-ui-sortable' )
				),
				'learn-press-utils'      => array(
					'url'  => $this->url( 'js/admin/utils.js' ),
					'deps' => array( 'jquery' )
				),
				'admin'                  => array(
					'url'  => $this->url( 'js/admin/admin.js' ),
					'deps' => array( 'learn-press-global', 'learn-press-utils' )
				),
				'admin-tabs'             => array(
					'url'  => $this->url( 'js/admin/admin-tabs.js' ),
					'deps' => array( 'jquery' )
				),
				'angularjs'              => $this->url( 'js/vendor/angular.1.6.4.js' ),
				'tipsy'                  => array(
					'url'  => $this->url( 'js/vendor/jquery-tipsy/jquery.tipsy.js' ),
					'deps' => array( 'jquery' )
				),
				'modal-search'           => array(
					'url'  => $this->url( 'js/admin/controllers/modal-search.js' ),
					'deps' => array( 'jquery', 'utils', 'angularjs' )
				),
				'modal-search-questions' => array(
					'url'  => $this->url( 'js/admin/controllers/modal-search-questions.js' ),
					'deps' => array( 'modal-search' )
				),
				'base-controller'        => array(
					'url'  => $this->url( 'js/admin/controllers/base.js' ),
					'deps' => array( 'jquery', 'utils', 'angularjs' )
				),
				'base-app'               => array(
					'url'  => $this->url( 'js/admin/base.js' ),
					'deps' => array( 'jquery', 'utils', 'angularjs' )
				),
				'question-controller'    => array(
					'url'  => $this->url( 'js/admin/controllers/question.js' ),
					'deps' => array( 'base-controller' )
				),
				'quiz-controller'        => array(
					'url'  => $this->url( 'js/admin/controllers/quiz.js' ),
					'deps' => array( 'base-controller', 'modal-search-questions' )
				),
				'course-controller'      => array(
					'url'  => $this->url( 'js/admin/controllers/course.js' ),
					'deps' => array( 'base-controller' )
				),
				'question-app'           => array(
					'url'  => $this->url( 'js/admin/question.js' ),
					'deps' => array( 'question-controller', 'base-app' )
				),
				'quiz-app'               => array(
					'url'  => $this->url( 'js/admin/quiz.js' ),
					'deps' => array( 'question-controller', 'quiz-controller', 'question-app' )
				),
//				'course-app'             => array(
//					'url'  => $this->url( 'js/admin/course.js' ),
//					'deps' => array(
//						'quiz-app'
//					)
//				),
				'course-editor-v2'       => array(
					'url'  => $this->url( 'js/admin/course-editor-v2.js' ),
					'deps' => array(
						'lp-vuejs'
					)
				)
			)
		);
	}

	/**
	 * Get default styles in admin.
	 *
	 * @return mixed
	 */
	protected function _get_styles() {
		return apply_filters(
			'learn-press/admin-default-styles',
			array(
				'font-awesome'      => $this->url( 'css/font-awesome.min.css' ),
				'learn-press-admin' => $this->url( 'css/admin/admin.css' )
			)
		);
	}

	/**
	 * Register and enqueue needed scripts and styles
	 */
	public function load_scripts() {
		// Register
		$this->_register_scripts();

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only scripts needed in specific pages
		 */
		if ( $scripts = $this->_get_scripts() ) {
			foreach ( $scripts as $handle => $data ) {
				wp_enqueue_script( $handle );
			}
		}

		/**
		 * Enqueue scripts
		 *
		 * TODO: check to show only styles needed in specific pages
		 */
		if ( $styles = $this->_get_styles() ) {
			foreach ( $styles as $handle => $data ) {
				wp_enqueue_style( $handle );
			}
		}
	}
}

/**
 * Shortcut function to get instance of LP_Admin_Assets
 *
 * @return LP_Admin_Assets|null
 */
function learn_press_admin_assets() {
	static $assets = null;
	if ( ! $assets ) {
		$assets = new LP_Admin_Assets();
	}

	return $assets;
}

learn_press_admin_assets();