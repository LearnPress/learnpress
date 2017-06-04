<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Submenu_Settings
 */
class LP_Submenu_Settings extends LP_Abstract_Submenu {

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

		add_action( 'learn-press/admin/page-content-settings', array(
			$this,
			'page_contents'
		) );

		add_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content', array(
			$this,
			'section_content'
		) );
		parent::__construct();
	}

	protected function init_tab() {
		if ( $active_tab = $this->get_active_tab() ) {
			switch ( $active_tab ) {
				case 'payments':
					$this->sections = '';
					break;
				case 'emails':
					$sections       = array(
						'new_course' => __( 'New course' )
					);
					$this->sections = apply_filters( 'learn-press/admin/page-settings/emails/sections', $sections );
					break;
				default:
					do_action( 'learn-press/admin/page-settings/init', $active_tab, $this );
			}
		}
	}

	/**
	 * Display menu content
	 */
	public function page_content() {
		parent::page_content();

		/*$section_data = ! empty( $sections[ $section ] ) ? $sections[ $section ] : false;
		if ( $section_data instanceof LP_Abstract_Settings ) {
			$section_data->admin_options();
		} else if ( is_array( $section_data ) ) {

		} else {
			do_action( 'learn-press/admin/setting-payments/admin-options-' . $section );
		}*/
	}

	public function page_contents() {
		$active_tab = $this->get_active_tab();
		$this->tabs[ $active_tab ]->admin_page( $this->get_active_section(), $this->get_sections() );

		?>
        <p>
            <button class="button button-primary">Save settings</button>
            <a class="button"
               href="/foobla/learnpress/wporg/wp-admin/admin.php?page=learn-press-settings&amp;tab=payments&amp;reset=yes&amp;_wpnonce=e0a7fed10d"
               id="learn-press-reset-settings" data-text="Do you want to restore all settings to default?">Reset</a>
        </p>
		<?php

	}

	public function page_content_generalxx() {

	}

	public function page_content_coursesxx() {
		echo 'Courses';
	}

	public function page_content_paymentsxx() {

		$this->tabs['payments']->admin_page( $this->get_active_section(), $this->get_sections() );

		return;
		$active_section = $this->get_active_section();
		$sectionClass   = '';
		if ( ! empty( $this->sections[ $active_section ] ) ) {
			$sectionClass = $this->sections[ $active_section ];
			if ( is_string( $sectionClass ) && class_exists( $sectionClass ) ) {
				$sectionClass = new $sectionClass();
			}
		}
		$callback = array( $sectionClass, 'admin_page' );
		if ( is_callable( $callback ) ) {
			call_user_func_array( $callback, array() );
		} else {
			$this->display_section();
		}
	}

	public function section_content_paypaldd() {
	}

	public function page_content_emails() {
		learn_press_debug( $this->get_sections() );

	}

	public function section_content( $section ) {
		echo $section;
	}
}

return new LP_Submenu_Settings();