<?php

/**
 * Class LP_Settings_Emails
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Emails extends LP_Abstract_Settings_Page {
	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'emails';
		$this->text = __( 'Emails', 'learnpress' );
		parent::__construct();

		add_filter( 'learn-press/admin/submenu-section-title', array( $this, 'custom_section_title' ), 10, 2 );
		add_action( 'learn-press/update-settings/settings-value', array( $this, 'sanitize_value' ), 10, 3 );
		add_action( 'learn-press/update-settings/updated', function () {
			die();
		} );
	}

	public function sanitize_value( $value, $key, $postdata ) {
		if(!empty($value['email_content']))
		echo "[$key]<pre>";print_r($value);echo '</pre>';

		return $value;
	}

	public function custom_section_title( $title, $slug ) {
		$sections = $this->get_sections();
		if ( ! empty( $sections[ $slug ] ) && $sections[ $slug ] instanceof LP_Email ) {
			$title = $title . sprintf( '<span class="learn-press-tooltip" title="%s"></span>', esc_attr( $sections[ $slug ]->title ) );
		}

		return $title;
	}

	/**
	 * Sections
	 *
	 * @return mixed
	 */
	public function get_sections() {

		$emails = LP_Emails::instance()->emails;

		$sections = array(
			'general' => __( 'General options', 'learnpress' )
		);

		if ( $emails ) {
			foreach ( $emails as $email ) {
				$sections[ $email->id ] = $email;
			}
		}

		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	/**
	 * Display admin page for payments settings tab.
	 *
	 * @param string $section
	 * @param string $tab
	 */
	public function admin_page( $section = null, $tab = null ) {
		$sections = array();
		$items    = LP_Admin_Menu::instance()->get_menu_items();
		if ( ! empty( $items['settings'] ) ) {
			$tab      = $items['settings']->get_active_tab();
			$section  = $items['settings']->get_active_section();
			$sections = $items['settings']->get_sections();
		}
		$section_data = ! empty( $sections[ $section ] ) ? $sections[ $section ] : false;

		// If current section is an instance of Settings just call to admin_options.
		if ( $section_data instanceof LP_Email ) {
			$section_data->admin_options();
		} else if ( is_array( $section_data ) ) {
		} else {
			// If I have a function point to current section with prefix 'admin_options_'.
			// Then call to it.
			if ( is_callable( array( $this, 'admin_options_' . $section ) ) ) {
				call_user_func_array( array( $this, 'admin_options_' . $section ), array(
					$section,
					$tab
				) );
			} else {
				// leave of all, do an action.
				do_action( 'learn-press/admin/setting-payments/admin-options-' . $section, $tab );
			}
		}
	}

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

//
return LP_Settings_Emails::instance();
