<?php
/**
 * Class LP_Settings_Addons
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared settings tab for LearnPress add-ons.
 */
class LP_Settings_Addons extends LP_Abstract_Settings_Page {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'addons';
		$this->text = esc_html__( 'Addons', 'learnpress' );

		parent::__construct();

		add_filter( 'learn-press/admin/submenu-has-sections', array( $this, 'force_section_nav' ), 10, 3 );
	}

	/**
	 * Always show the section navigation for the Addons tab, even with a single
	 * add-on section, so each add-on reads as a distinct section like Payments.
	 *
	 * @param bool                 $has_sections Current decision (>1 section).
	 * @param array<string, mixed> $sections     Sections for the active tab.
	 * @param string               $active_tab   Active settings tab id.
	 *
	 * @return bool
	 */
	public function force_section_nav( $has_sections, $sections, $active_tab ) {
		if ( $this->id === $active_tab && ! empty( $sections ) ) {
			return true;
		}

		return $has_sections;
	}

	/**
	 * Sections contributed by active add-ons.
	 *
	 * @return array<string, string>
	 */
	public function get_sections() {
		$sections = apply_filters( 'learn-press/settings/addons/sections', array() );

		return is_array( $sections ) ? $sections : array();
	}

	/**
	 * Whether any add-on registered a settings section.
	 *
	 * @return bool
	 */
	public function has_sections(): bool {
		return ! empty( $this->get_sections() );
	}

	/**
	 * Field dispatch via add-on filters.
	 *
	 * @param string|array $section Section key or keys.
	 * @param string       $tab     Tab key.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_settings( $section = '', $tab = '' ) {
		if ( ! $section ) {
			$section = array_keys( $this->get_sections() );
		}

		settype( $section, 'array' );

		$return = array();

		foreach ( $section as $sec ) {
			$fields = apply_filters( "learn-press/settings/addons/fields-{$sec}", array() );

			if ( $fields && is_array( $fields ) ) {
				$return = array_merge( $return, $fields );
			}
		}

		return $return;
	}
}

return new LP_Settings_Addons();
