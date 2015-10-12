<?php
/**
 * Update LearnPress to 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Upgrade_10
 */
class LP_Upgrade_10{
	/**
	 * All steps for update actions
	 *
	 * @var array
	 */
	protected $_steps = array(
		'welcome', 'repair-database'
	);

	/**
	 * Current step
	 *
	 * @var string
	 */
	protected $_current_step = '';

	/**
	 * Constructor
	 */
	function __construct(){
		$this->_prevent_access_admin();
		add_action( 'admin_menu', array( $this, 'learn_press_update_10_menu' ) );
		add_action( 'admin_init', array( $this, 'learnpress_update_10_page' ) );
	}

	private function _prevent_access_admin(){
		if( $this->check_post_types() || $this->check_admin_menu() ) {
			wp_redirect( admin_url( 'admin.php?page=learnpress_update_10' ) );
			exit;
		}
	}

	/**
	 * Check if user trying to access the old custom post type
	 *
	 * @return bool
	 */
	private function check_post_types(){
		$post_type = ! empty( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : '';
		if( ! $post_type ){
			$post_id = ! empty( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;
			if( $post_id ){
				$post_type = get_post_field( $post_id, 'post_type' );
			}
		}
		$old_post_types = array( 'lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question', 'lpr_order', 'lpr_assignment' );
		return in_array( $post_type, $old_post_types );
	}

	/**
	 * Check if user trying to access LearnPress admin menu
	 *
	 * @return bool
	 */
	private function check_admin_menu(){
		$admin_page = ! empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
		return preg_match( '!^learn_press_!', $admin_page );
	}

	/**
	 * Display update page content
	 */
	function learnpress_update_10_page(){
		if( empty( $_REQUEST['page'] ) || $_REQUEST['page'] != 'learnpress_update_10' ) return;

		wp_enqueue_style( 'lp-update-10', LP()->plugin_url( 'assets/css/lp-update-10.css' ), array( 'dashicons', 'install' ) );
		wp_enqueue_script( 'lp-update-10', LP()->plugin_url( 'assets/js/lp-update-10.js' ), array( 'jquery' ) );

		add_action( 'learn_press_update_step_welcome', array( $this, 'update_welcome' ) );
		add_action( 'learn_press_update_step_repair-database', array( $this, 'update_repair_database' ) );

		$step = ! empty( $_REQUEST['step'] ) ? $_REQUEST['step'] : 'welcome';
		if( ! in_array( $step, $this->_steps ) ){
			$step = reset( $this->_steps ) ;
		}
		$this->_current_step = $step;
		$view = learn_press_get_admin_view( 'updates/update-10-wizard.php' );
		include_once $view;
		exit();
	}

	/**
	 * Add menu to make it work properly
	 */
	function learn_press_update_10_menu() {
		add_dashboard_page( '', '', 'manage_options', 'learnpress_update_10', '' );
	}

	/**
	 * Welcome step page
	 */
	function update_welcome(){
		$view = learn_press_get_admin_view( 'updates/step-welcome.php' );
		include $view;
	}

	/**
	 * Repair Database step page
	 */
	function update_repair_database(){
		$view = learn_press_get_admin_view( 'updates/step-repair-database.php' );
		include $view;
	}

	function next_link(){
		if( $this->_current_step ){
			if( ( $pos = array_search( $this->_current_step, $this->_steps ) ) !== false ){
				if( $pos < sizeof( $this->_steps ) - 1 ){
					$pos++;
					return admin_url( 'admin.php?page=learnpress_update_10&step=' . $this->_steps[ $pos ] );
				}
			}
		}
		return false;
	}

	function prev_link(){
		if( $this->_current_step ){
			if( ( $pos = array_search( $this->_current_step, $this->_steps ) ) !== false ){
				if( $pos > 0 ){
					$pos--;
					return admin_url( 'admin.php?page=learnpress_update_10&step=' . $this->_steps[ $pos ] );
				}
			}
		}
		return false;
	}
}
new LP_Upgrade_10();

if( ! empty( $_REQUEST['learnpress_update_10'] ) ) {

// TODO: convert post types

// TODO: convert course meta

// TODO: convert lesson meta

// TODO: convert quiz meta

// TODO: convert question meta

// TODO: convert order meta

// TODO: convert assignment data

}else {

	//wp_redirect( admin_url( 'admin.php?page=learnpress_update_10' ) );
	//die();
}