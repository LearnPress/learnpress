<?php
/**
 * class LP_Background_Installer
 *
 * @since 4.0.7
 * @author tungnx
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Installer' ) ) {
	/**
	 * Class LP_Background_Installer
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Installer {

		/**
		 * LP_Background_Installer constructor.
		 */
		public function __construct() {
		}

		/**
		 * Get all the tables are not created
		 *
		 * @return array
		 */
		protected function get_tables_missing() {
		}

		/**
		 * Get tables must have on this version of LP
		 *
		 * @since 4.0.7
		 * @author tungnx
		 */
		protected function get_tables_require() {

		}
	}
}
