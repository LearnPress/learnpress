<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Multi_Language' ) ) {
	/**
	 * Class LP_Multi_Language
	 *
	 * @author  ThimPress
	 * @package LearnPress/Clases
	 * @version 1.0
	 */
	class LP_Multi_Language {
		public static function init() {
			self::load_plugin_text_domain( LP_PLUGIN_FILE );
			$plugin = 'learnpress/learnpress.php';
			add_filter( "plugin_action_links_$plugin", array( __CLASS__, 'plugin_links' ) );

		}

		/**
		 * Add links to Documentation and Extensions in plugin's list of action links
		 *
		 * @since 4.3.11
		 *
		 * @param array $links Array of action links
		 *
		 * @return array
		 */
		public static function plugin_links( $links ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=learn-press-settings' ) . '">' . __( 'Settings', 'learnpress' ) . '</a>';
			$links[] = '<a href="https://learnpress.io/docs/">' . __( 'Documentation', 'learnpress' ) . '</a>';
			$links[] = '<a href="' . get_admin_url() . '/admin.php?page=learn-press-addons' . '">' . __( 'Add-ons', 'learnpress' ) . '</a>';

			return $links;
		}

		/**
		 * Helper function to load text-domain for LP addons.
		 *
		 * @param string $path
		 * @param string $text_domain
		 * @param string $language_folder
		 */
		public static function load_plugin_text_domain( $path, $text_domain = '', $language_folder = 'languages' ) {
			// Get absolute plugin folder instead of plugin file
			if ( false !== strpos( $path, '.php' ) ) {
				$path = dirname( $path );
			}

			// Plugin folder, such as: learnpress-offline-payment
			$plugin_folder = basename( $path );

			// If name of text-domain is not set
			if ( ! $text_domain ) {
				$text_domain = $plugin_folder;
			}

			$locale = apply_filters( 'plugin_locale', get_locale(), $text_domain );

			if ( is_admin() ) {
				load_textdomain( $text_domain, WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-admin-{$locale}.mo" );
				load_textdomain( $text_domain, WP_LANG_DIR . "/plugins/{$plugin_folder}-admin-{$locale}.mo" );
			}

			load_textdomain( $text_domain, WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-{$locale}.mo" );

			$mo = WP_CONTENT_DIR . "/plugins/{$plugin_folder}/languages/{$plugin_folder}-{$locale}.mo";
			load_textdomain( $text_domain, $mo );
			load_plugin_textdomain( $text_domain, false, plugin_basename( $path ) . '/' . $language_folder );

		}
	}
}

LP_Multi_Language::init();

if ( ! function_exists( 'learn_press_load_plugin_text_domain' ) ) {
	/**
	 * Load plugin text domain
	 *
	 * @param string $path            - Path to plugin
	 * @param string $text_domain     - Name of text-domain
	 * @param string $language_folder - Folder inside the plugin that contains language files
	 */
	function learn_press_load_plugin_text_domain( $path, $text_domain = '', $language_folder = '' ) {

		LP_Multi_Language::load_plugin_text_domain( $path, $text_domain, $language_folder );

	}
}
