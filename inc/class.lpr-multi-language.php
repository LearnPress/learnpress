<?php

// Prevent loading directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LPR_Multi_Language' ) ) {
	class LPR_Multi_Language {
		public static function on_load() {
			self::load_textdomain();

			$plugin = 'thim-learn-press/learnpress.php';
			add_filter( "plugin_action_links_$plugin", array( __CLASS__, 'plugin_links' ) );
		}

		/**
		 * Load plugin translation
		 *
		 * @return void
		 */
		public static function load_textdomain() {
			// l18n translation files
			$locale = get_locale();
			$dir    = trailingslashit( LPR_PLUGIN_PATH . 'lang' );
			$mofile = "{$dir}{$locale}.mo";

			// In themes/plugins/mu-plugins directory
			load_textdomain( 'learn_press', $mofile );
		}
	}
}
