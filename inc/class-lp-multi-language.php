<?php

// Prevent loading directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Multi_Language' ) ) {
	class LP_Multi_Language {
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
			$dir    = trailingslashit( LP_PLUGIN_PATH . '/lang' );
			$mofile = "{$dir}{$locale}.mo";

			// In themes/plugins/mu-plugins directory			
			load_textdomain( 'learn_press', $mofile );
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
			$links[] = '<a href="https://github.com/LearnPress/LearnPress/wiki">' . __( 'Documentation', 'meta-box' ) . '</a>';
			$links[] = '<a href="' . get_admin_url() . '/admin.php?page=learn_press_add_ons' . '">' . __( 'Extensions', 'meta-box' ) . '</a>';

			return $links;
		}
	}
}
LP_Multi_Language::on_load();
