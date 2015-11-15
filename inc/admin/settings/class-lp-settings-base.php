<?php

/**
 * Class LP_Settings_Base
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Base {

	/**
	 * Tab's ID
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Tab's text
	 *
	 * @var string
	 */
	public $text = '';

	/**
	 * Tab's sections
	 *
	 * @var array|bool
	 */
	public $section = false;

	/**
	 * @var array|bool
	 */
	public $tab = false;

	/**
	 * Current tab
	 *
	 * @var string
	 */
	static $current_tab = '';

	/**
	 * Constructor
	 */
	function __construct() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		$tabs        = learn_press_settings_tabs_array();
		if ( !$current_tab && $tabs ) {
			$tab_keys    = array_keys( $tabs );
			$current_tab = reset( $tab_keys );
			$this->tab   = array(
				'id'   => $current_tab,
				'text' => $tabs[$current_tab]
			);
		} else {
			$this->tab = array( 'id' => null, 'text' => null );
		}

		$current_section = !empty( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
		$sections        = $this->get_sections();

		/**
		 * Find current section by detect request
		 */
		if ( $sections ) {
			$array_keys = array_keys( $sections );
			if ( !$current_section ) $current_section = reset( $array_keys );
			if ( !empty( $sections[$current_section] ) ) {
				$this->section = array( 'id' => $current_section, 'text' => $sections[$current_section] );
			} else {
				$this->section = array( 'id' => null, 'text' => '' );
			}

		} else {
			$this->section = array( 'id' => null, 'text' => '' );
		}

		if ( $sections = $this->get_sections() ) foreach ( $sections as $id => $text ) {
			$callback = apply_filters( 'learn_press_section_callback_' . $this->id . '_' . $id, array( $this, 'output_section_' . $id ) );
			if ( is_callable( $callback ) ) {
				add_action( 'learn_press_section_' . $this->id . '_' . $id, $callback );
			}
		}

		// hooks
		add_action( 'learn_press_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'learn_press_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'learn_press_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Output tab's sections if defined
	 */
	function output_sections() {
		$current_section = $this->section['id'];
		$sections        = $this->get_sections();

		if ( $sections ) {
			$array_keys = array_keys( $sections );
			echo '<ul class="subsubsub clearfix">';
			foreach ( $sections as $name => $gateway ) {
				?>
				<li>
					<a href="<?php echo '?page=learn_press_settings&tab=' . $this->id . '&section=' . sanitize_title( $name ); ?>" class="<?php echo $current_section == $name ? 'current' : ''; ?>">
						<?php echo $gateway; ?>
					</a>
					<?php echo( end( $array_keys ) == $name ? '' : '|' ); ?>
				</li>
				<?php
			}
			echo '</ul>';
			echo '<div class="clear"></div>';
		}
	}

	/**
	 * Output settings tab content
	 */
	function output() {
		do_action( 'learn_press_section_' . $this->id . '_' . $this->section['id'] );
	}

	/**
	 * Save settings for current tab
	 */
	function save() {
		foreach ( $_POST as $k => $v ) {
			if ( ( strpos( $k, 'learn_press_' ) === false ) || ( !apply_filters( 'learn_press_abort_update_option', true, $k ) ) ) continue;
			update_option( $k, apply_filters( 'learn_press_update_option_value', $v, $k ) );
		}
	}

	/**
	 * Get tab's sections if defined
	 *
	 * @return bool
	 */
	function get_sections() {
		return false;
	}

	/**
	 * Get name for field
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	function get_field_name( $name ) {
		$field_name = apply_filters( 'learn_press_settings_field_name_' . $name, "learn_press_{$name}" );
		return $field_name;
	}

	/**
	 * Get ID for field
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	function get_field_id( $name ) {
		return preg_replace( array( '!\[|(\]\[)!', '!\]!' ), array( '_', '' ), $this->get_field_name( $name ) );
	}
}
new LP_Settings_Base();
function learn_press_load_settings_base(){

}
add_action( 'plugins_loaded', 'learn_press_load_settings_base' );
//
