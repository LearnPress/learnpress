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
	public function __construct() {
		if ( strtolower( current_filter() ) == 'activate_learnpress/learnpress.php' ) {
			return;
		}
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
			if ( !$current_section )
				$current_section = reset( $array_keys );
			if ( !empty( $sections[$current_section] ) ) {
				$this->section = $sections[$current_section];
			} else {
				$this->section = array( 'id' => null, 'title' => '' );
			}
		} else {
			$this->section = array( 'id' => null, 'title' => '' );
		}

		if ( $sections = $this->get_sections() )
			foreach ( $sections as $id => $text ) {
				$callback = apply_filters( 'learn_press_section_callback_' . $this->id . '_' . $id, array( $this, 'output_section_' . $id ) );
				if ( is_callable( $callback ) ) {
					add_action( 'learn_press_section_' . $this->id . '_' . $id, $callback );
				}
			}
		self::$current_tab = $current_tab;
		// hooks
		add_action( 'learn_press_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'learn_press_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'learn_press_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Output tab's sections if defined
	 */
	public function output_sections() {
		$current_section = $this->section['id'];
		$sections        = $this->get_sections();

		if ( $sections && sizeof( $sections ) > 1 ) {
			$array_keys = array_keys( $sections );
			echo '<ul class="subsubsub">';
			foreach ( $sections as $name => $section ) {
				?>
				<li>
					<a href="<?php echo '?page=learn-press-settings&tab=' . $this->id . '&section=' . sanitize_title( $name ); ?>" class="<?php echo $current_section == $name ? 'current' : ''; ?>">
						<?php echo $section['title']; ?>
					</a>
					<?php //echo( end( $array_keys ) == $name ? '' : '|' ); ?>
				</li>
				<?php
			}
			echo '</ul>';
			//echo '<div class="clear"></div>';
		}else{
		}
	}

	/**
	 * Output settings tab content
	 */
	public function output() {
		do_action( 'learn_press_section_' . $this->id . '_' . $this->section['id'] );
	}

	/**
	 * Save settings for current tab
	 */
	public function save() {
		foreach ( $_POST as $k => $v ) {
			if ( ( strpos( $k, 'learn_press_' ) === false ) || ( !apply_filters( 'learn_press_abort_update_option', true, $k ) ) )
				continue;
			update_option( $k, apply_filters( 'learn_press_update_option_value', ( $v ), $k ) );
		}
	}

	/**
	 * Get tab's sections if defined
	 *
	 * @return bool
	 */
	public function get_sections() {
		return false;
	}

	/**
	 * Get name for field
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function get_field_name( $name ) {
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
	public function get_field_id( $name ) {
		return preg_replace( array( '!\[|(\]\[)!', '!\]!' ), array( '_', '' ), $this->get_field_name( $name ) );
	}

	public function get_settings() {
		return array();
	}

	/**
	 * admin settings page
	 */
	public function output_settings() {

		$settings = new LP_Settings_Base();
		if ( $fields = $this->get_settings() )
			foreach ( $fields as $field ) {
				$settings->output_field( $field );
			}
	}

	public function output_field( $options ) {
		if ( !isset( $options['type'] ) ) {
			return;
		}
		if ( !isset( $options['id'] ) ) {
			$options['id'] = '';
		}
		if ( !isset( $options['title'] ) ) {
			$options['title'] = isset( $options['name'] ) ? $options['name'] : '';
		}
		if ( !isset( $options['class'] ) ) {
			$options['class'] = '';
		}
		if ( !isset( $options['css'] ) ) {
			$options['css'] = '';
		}
		if ( !isset( $options['default'] ) ) {
			$options['default'] = '';
		}
		if ( !isset( $options['desc'] ) ) {
			$options['desc'] = '';
		}
		if ( !isset( $options['desc_tip'] ) ) {
			$options['desc_tip'] = false;
		}
		if ( !isset( $options['placeholder'] ) ) {
			$options['placeholder'] = '';
		}

		$custom_attributes = array();

		if ( !empty( $options['custom_attributes'] ) && is_array( $options['custom_attributes'] ) ) {
			foreach ( $options['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( !empty( $options['desc'] ) ) {
			$description = sprintf( '<p class="description">%s</p>', $options['desc'] );
		} else {
			$description = '';
		}
		$file = $options['type'];
		if ( in_array( $file, array( 'text', 'email', 'color', 'password', 'number' ) ) ) {
			$file = 'text';
		}
		require learn_press_get_admin_view( 'settings/fields/' . $file . '.php' );
	}

	public function get_option( $option_name, $default = null ) {
		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[$option_name] );

			if ( isset( $option_values[$key] ) ) {
				$option_value = $option_values[$key];
			} else {
				$option_value = null;
			}

			// Single value
		} else {
			$option_value = LP()->settings->get( preg_replace( '!^learn_press_!', '', $option_name ), null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes', $option_value );
		} elseif ( !is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return $option_value === null ? $default : $option_value;
	}

}

return new LP_Settings_Base();
