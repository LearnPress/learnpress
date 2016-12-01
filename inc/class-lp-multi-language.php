<?php

// Prevent loading directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'LP_Multi_Language' ) ) {
	/**
	 * Class LP_Multi_Language
	 *
	 * @author  ThimPress
	 * @package LearnPress/Clases
	 * @version 1.0
	 */
	class LP_Multi_Language {
		public static function init() {
			self::load_textdomain();

			$plugin = 'learnpress/learnpress.php';
			add_filter( "plugin_action_links_$plugin", array( __CLASS__, 'plugin_links' ) );

		}

		/**
		 * Load plugin translation
		 *
		 * @return void
		 */
		public static function load_textdomain() {
			$plugin_folder = basename( LP_PLUGIN_PATH );
			$text_domain   = 'learnpress';
			$locale        = apply_filters( 'plugin_locale', get_locale(), $text_domain );

			if ( is_admin() ) {
				load_textdomain( $text_domain, WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-admin-{$locale}.mo" );
				load_textdomain( $text_domain, WP_LANG_DIR . "/plugins/{$plugin_folder}-admin-{$locale}.mo" );
			}
			load_textdomain( $text_domain, WP_LANG_DIR . "/{$plugin_folder}/{$plugin_folder}-{$locale}.mo" );
			load_plugin_textdomain( $text_domain, false, plugin_basename( LP_PLUGIN_PATH ) . "/languages" );
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
			$links[] = '<a href="https://github.com/LearnPress/LearnPress/wiki">' . __( 'Documentation', 'learnpress' ) . '</a>';
			$links[] = '<a href="' . get_admin_url() . '/admin.php?page=learn-press-addons' . '">' . __( 'Add-ons', 'learnpress' ) . '</a>';

			return $links;
		}
	}
}

if ( !function_exists( 'learn_press_load_plugin_text_domain' ) ) {
	/**
	 * Load plugin text domain
	 *
	 * @param        string
	 * @param        mixed
	 */
	function learn_press_load_plugin_text_domain( $path, $text_domain = true ) {
		$plugin_folder = basename( $path );
		if ( true === $text_domain ) {
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
		load_plugin_textdomain( $text_domain, false, plugin_basename( $path ) . "/languages" );

	}
}
LP_Multi_Language::init();
