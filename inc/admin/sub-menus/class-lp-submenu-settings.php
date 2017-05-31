<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Submenu_Settings
 */
class LP_Submenu_Settings extends LP_Abstract_Submenu {

	/**
	 * @var array
	 */
	protected $subtabs = array();

	/**
	 * LP_Submenu_Settings constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-settings';
		$this->menu_title = __( 'Settings', 'learnpress' );
		$this->page_title = __( 'LearnPress Settings', 'learnpress' );
		$this->priority   = 30;

		// Heading tabs
		$this->tabs = learn_press_settings_tabs_array();
		$this->init_tab();
	}

	protected function init_tab() {
		if ( $active_tab = $this->get_active_tab() ) {
			switch ( $active_tab ) {
				case 'payments':
					$this->subtabs['general'] = __( 'General', 'learnpress' );
					if ( $gateways = LP_Gateways::instance()->get_gateways() ) {
						$this->subtabs = array_merge( $this->subtabs, $gateways );
					}
					break;
				case 'emails':
					break;
				default:
					do_action( 'learn-press/admin/settings-tab-init', $active_tab, $this );
			}
		}
	}

	/**
	 * Display menu content
	 */
	public function page_content() {
		parent::page_content();
	}

	public function page_content_general() {
		echo 'General';
	}

	public function page_content_courses() {
		echo 'Courses';
	}

	public function page_content_payments() {
		learn_press_debug($this->subtabs);
	}

	public function xxxx() {
		echo 'Custom hook';
	}
}

return new LP_Submenu_Settings();