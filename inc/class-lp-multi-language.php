<?php

// Prevent loading directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Multi_Language' ) ) {
	class LP_Multi_Language {
		public static function on_load() {
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
			// Get mo file
			$text_domain = 'learn_press';
			$prefix = 'learnpress';
			$locale      = apply_filters( 'plugin_locale', get_locale(), $text_domain );
			$mo_file   = $prefix . '-' . $locale . '.mo';
			// Check mo file global
			$mo_global = WP_LANG_DIR . '/plugins/' . $mo_file;
			// Load translate file
			if ( file_exists( $mo_global ) ) {
				load_textdomain( $text_domain, $mo_global );
			} else {
				load_textdomain( $text_domain, LP_PLUGIN_PATH . '/lang/' . $mo_file );
			}
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
			$links[] = '<a href="https://github.com/LearnPress/LearnPress/wiki">' . __( 'Documentation', 'learn_press' ) . '</a>';
			$links[] = '<a href="' . get_admin_url() . '/admin.php?page=learn_press_add_ons' . '">' . __( 'Add-ons', 'learn_press' ) . '</a>';

			return $links;
		}
	}
}
LP_Multi_Language::on_load();
